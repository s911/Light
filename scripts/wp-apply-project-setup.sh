#!/usr/bin/env bash
set -eu
set -o pipefail 2>/dev/null || true

# Apply Stage Lighting WordPress setup on running stack.
# Usage:
#   bash scripts/wp-apply-project-setup.sh
#   bash scripts/wp-apply-project-setup.sh --prod

MODE="debug"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ "${1:-}" == "--prod" ]]; then
  MODE="prod"
fi

cd "${PROJECT_DIR}"

if [[ "${MODE}" == "prod" ]]; then
  COMPOSE_ARGS=(-f docker-compose.yml -f docker-compose.prod.yml)
else
  COMPOSE_ARGS=()
fi

echo ">> Ensuring containers are up..."
docker compose "${COMPOSE_ARGS[@]}" up -d

echo ">> Waiting for WordPress readiness..."
sleep 5

WP_CMD=(docker compose "${COMPOSE_ARGS[@]}" exec -T wpcli wp --path=/var/www/html --allow-root)

if ! "${WP_CMD[@]}" core is-installed >/dev/null 2>&1; then
  echo "WordPress is not installed yet. Finish installer in browser first, then run this script again."
  exit 1
fi

echo ">> Activating required theme/plugins..."
"${WP_CMD[@]}" theme activate stage-lighting || true
"${WP_CMD[@]}" plugin activate stage-lighting-b2b || true
"${WP_CMD[@]}" plugin activate stage-lighting-setup || true

echo ">> Running one-click initializer..."
"${WP_CMD[@]}" eval "if (function_exists('stage_setup_run_initializer')) { stage_setup_run_initializer(); echo 'Initializer done'; } else { echo 'Initializer not found'; }"

echo ">> Flushing rewrite rules..."
"${WP_CMD[@]}" rewrite flush --hard || true

echo "Done. Refresh frontend and verify Stage Lighting theme is active."
