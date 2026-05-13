#!/usr/bin/env bash
set -eu
set -o pipefail 2>/dev/null || true

# One-click deploy for Debian (debug/prod)
# Usage examples:
#   bash scripts/debian-one-click-deploy.sh --mode debug
#   bash scripts/debian-one-click-deploy.sh --mode prod
#   bash scripts/debian-one-click-deploy.sh --mode debug --proxy http://10.144.1.10:8080

MODE="debug"
PROXY_URL=""
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --mode)
      MODE="${2:-}"
      shift 2
      ;;
    --proxy)
      PROXY_URL="${2:-}"
      shift 2
      ;;
    --project-dir)
      PROJECT_DIR="${2:-}"
      shift 2
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: $0 --mode [debug|prod] [--proxy http://host:port] [--project-dir /path/to/project]"
      exit 1
      ;;
  esac
done

if [[ "${MODE}" != "debug" && "${MODE}" != "prod" ]]; then
  echo "Error: --mode must be debug or prod"
  exit 1
fi

if [[ ! -f "${PROJECT_DIR}/docker-compose.yml" ]]; then
  echo "Error: docker-compose.yml not found in ${PROJECT_DIR}"
  exit 1
fi

run_cmd() {
  echo ">> $*"
  "$@"
}

as_root() {
  if [[ "${EUID}" -eq 0 ]]; then
    "$@"
  else
    sudo "$@"
  fi
}

setup_proxy_env() {
  if [[ -z "${PROXY_URL}" ]]; then
    return
  fi
  export http_proxy="${PROXY_URL}"
  export https_proxy="${PROXY_URL}"
  export HTTP_PROXY="${PROXY_URL}"
  export HTTPS_PROXY="${PROXY_URL}"
  echo "Proxy enabled: ${PROXY_URL}"
}

configure_apt_proxy() {
  if [[ -z "${PROXY_URL}" ]]; then
    return
  fi
  as_root mkdir -p /etc/apt/apt.conf.d
  as_root bash -c "cat > /etc/apt/apt.conf.d/99proxy <<EOF
Acquire::http::Proxy \"${PROXY_URL}/\";
Acquire::https::Proxy \"${PROXY_URL}/\";
EOF"
}

install_docker_if_missing() {
  if command -v docker >/dev/null 2>&1; then
    echo "Docker already installed."
  else
    echo "Docker not found. Installing Docker..."
    as_root apt-get update
    as_root apt-get install -y ca-certificates curl gnupg lsb-release
    as_root install -m 0755 -d /etc/apt/keyrings
    if [[ -n "${PROXY_URL}" ]]; then
      run_cmd curl -x "${PROXY_URL}" -fsSL https://download.docker.com/linux/debian/gpg | as_root gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    else
      run_cmd curl -fsSL https://download.docker.com/linux/debian/gpg | as_root gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    fi
    as_root chmod a+r /etc/apt/keyrings/docker.gpg
    as_root bash -c 'echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list'
    as_root apt-get update
    as_root apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
  fi

  as_root systemctl enable docker
  as_root systemctl start docker
}

prepare_env_file() {
  cd "${PROJECT_DIR}"
  if [[ "${MODE}" == "debug" ]]; then
    if [[ ! -f .env ]]; then
      cp .env.debug.example .env
      echo "Created .env from .env.debug.example"
    else
      echo ".env already exists, keeping current values."
    fi
  else
    if [[ ! -f .env ]]; then
      cp .env.prod.example .env
      echo "Created .env from .env.prod.example"
    else
      echo ".env already exists, keeping current values."
    fi
  fi
}

deploy_stack() {
  cd "${PROJECT_DIR}"
  if [[ "${MODE}" == "debug" ]]; then
    run_cmd docker compose up -d
  else
    run_cmd docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
  fi
}

health_check() {
  cd "${PROJECT_DIR}"
  run_cmd docker compose ps

  local wp_port
  wp_port="$(awk -F= '/^WORDPRESS_PORT=/{print $2}' .env | tr -d '[:space:]')"
  wp_port="${wp_port:-8080}"

  echo "Waiting for WordPress on port ${wp_port}..."
  for i in $(seq 1 30); do
    if command -v curl >/dev/null 2>&1 && curl -fsS "http://127.0.0.1:${wp_port}" >/dev/null 2>&1; then
      echo "WordPress is reachable: http://<server-ip>:${wp_port}"
      return 0
    fi
    sleep 2
  done

  echo "WordPress is not reachable yet. Check logs:"
  echo "  docker compose logs -f wordpress"
  echo "  docker compose logs -f db"
}

main() {
  echo "=== Stage Lighting Debian One-click Deploy ==="
  echo "Mode: ${MODE}"
  echo "Project: ${PROJECT_DIR}"

  setup_proxy_env
  configure_apt_proxy
  install_docker_if_missing
  prepare_env_file
  deploy_stack
  health_check

  echo "Done."
}

main "$@"
