#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

HOST="${DEV_HOST:-127.0.0.1}"
PORT="${DEV_PORT:-8080}"

if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: php is not in PATH. Install PHP 8.3+." >&2
  exit 1
fi

if [[ ! -f .env ]]; then
  echo "WARNING: .env not found. Copy .env.example to .env and configure it."
fi

if [[ ! -f web/dist/production/login.html ]]; then
  echo "WARNING: web/dist/production/login.html not found."
  echo "         Build the frontend first:  cd web && npm install && npm run build"
  echo
fi

echo "EpiElla dev server"
echo "  URL:   http://${HOST}:${PORT}/login.html"
echo "  Stop:  Ctrl+C"
echo

exec php -S "${HOST}:${PORT}" -t api/public api/public/router.php
