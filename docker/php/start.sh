#!/bin/bash
set -euo pipefail

ENV_FILE="/var/www/html/backend/.env"
BACKEND="/var/www/html/backend"

cd "$BACKEND"

# ── Sanity checks ────────────────────────────────────────────────────────────

if [ ! -f artisan ]; then
  echo "ERROR: Laravel not found — artisan missing in $BACKEND" >&2
  echo "       Is the backend volume mounted correctly?" >&2
  exit 1
fi

if [ ! -f vendor/autoload.php ]; then
  echo "ERROR: Composer dependencies missing." >&2
  echo "       Run: make composer-install" >&2
  exit 1
fi

# ── .env setup ───────────────────────────────────────────────────────────────

if [ ! -f "$ENV_FILE" ]; then
  if [ -f .env.example ]; then
    cp .env.example "$ENV_FILE"
    echo "INFO: Created .env from .env.example"
  else
    echo "ERROR: No .env and no .env.example found." >&2
    exit 1
  fi
fi

# ── App key ──────────────────────────────────────────────────────────────────

if ! grep -q '^APP_KEY=base64:' "$ENV_FILE"; then
  echo "INFO: Generating APP_KEY..."
  php artisan key:generate --force
fi

# ── Permissions ──────────────────────────────────────────────────────────────

mkdir -p storage/logs bootstrap/cache
chmod -R 0777 storage bootstrap/cache

echo "INFO: Starting Apache..."
exec apache2-foreground
