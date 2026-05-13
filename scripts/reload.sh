#!/usr/bin/env bash
set -eu
set -o pipefail 2>/dev/null || true

# Quick reload script for Stage Lighting stack.
# Usage:
#   bash scripts/reload.sh
#   bash scripts/reload.sh --prod
#   bash scripts/reload.sh --prod --rebuild
#   bash scripts/reload.sh --logs

MODE="debug"
REBUILD="false"
SHOW_LOGS="false"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --prod)
      MODE="prod"
      shift
      ;;
    --debug)
      MODE="debug"
      shift
      ;;
    --rebuild)
      REBUILD="true"
      shift
      ;;
    --logs)
      SHOW_LOGS="true"
      shift
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: $0 [--prod|--debug] [--rebuild] [--logs]"
      exit 1
      ;;
  esac
done

cd "${PROJECT_DIR}"

ensure_env_file() {
  if [[ -f ".env" ]]; then
    return
  fi

  if [[ "${MODE}" == "prod" ]]; then
    if [[ -f ".env.prod.example" ]]; then
      cp .env.prod.example .env
      echo ">> .env not found, created from .env.prod.example"
    else
      echo "Error: .env missing and .env.prod.example not found."
      exit 1
    fi
  else
    if [[ -f ".env.debug.example" ]]; then
      cp .env.debug.example .env
      echo ">> .env not found, created from .env.debug.example"
    else
      echo "Error: .env missing and .env.debug.example not found."
      exit 1
    fi
  fi

  echo ">> Please review .env credentials before production use."
}

if [[ "${MODE}" == "prod" ]]; then
  COMPOSE_ARGS=(-f docker-compose.yml -f docker-compose.prod.yml)
else
  COMPOSE_ARGS=()
fi

echo "=== Stage Lighting Reload ==="
echo "Mode: ${MODE}"
echo "Rebuild: ${REBUILD}"
ensure_env_file

if [[ "${REBUILD}" == "true" ]]; then
  echo ">> Rebuilding and recreating services..."
  docker compose "${COMPOSE_ARGS[@]}" down
  docker compose "${COMPOSE_ARGS[@]}" up -d --build
else
  echo ">> Restarting WordPress and wpcli..."
  docker compose "${COMPOSE_ARGS[@]}" restart wordpress wpcli || docker compose "${COMPOSE_ARGS[@]}" up -d
fi

echo ">> Applying WordPress project setup..."
if [[ "${MODE}" == "prod" ]]; then
  bash scripts/wp-apply-project-setup.sh --prod
else
  bash scripts/wp-apply-project-setup.sh
fi

echo ">> Service status"
docker compose "${COMPOSE_ARGS[@]}" ps

if [[ "${SHOW_LOGS}" == "true" ]]; then
  echo ">> Last 80 lines: wordpress"
  docker compose "${COMPOSE_ARGS[@]}" logs --tail=80 wordpress || true
  echo ">> Last 80 lines: db"
  docker compose "${COMPOSE_ARGS[@]}" logs --tail=80 db || true
fi

echo "Reload complete."
