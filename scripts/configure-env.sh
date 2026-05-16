#!/usr/bin/env bash
# Apply Docker / Postgres settings to .env (after cp .env.example .env).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ENV_FILE="${1:-.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE — run: cp .env.example .env"
  exit 1
fi

set_var() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    echo "${key}=${value}" >> "$ENV_FILE"
  fi
}

set_var APP_NAME Drachme
set_var APP_ENV local
set_var APP_DEBUG true
set_var APP_URL http://localhost:8080

# Do not overwrite APP_KEY if already generated

set_var DB_CONNECTION pgsql
set_var DB_HOST db
set_var DB_PORT 5432
set_var DB_DATABASE drachme
set_var DB_USERNAME drachme
set_var DB_PASSWORD drachme

set_var SESSION_DRIVER database
set_var CACHE_STORE database
set_var QUEUE_CONNECTION database

set_var VITE_DEV_SERVER_URL http://localhost:5173

echo "Configured $ENV_FILE for Docker Postgres (service: db)."

if [[ -f artisan ]] && grep -q '^APP_KEY=$' "$ENV_FILE" 2>/dev/null || ! grep -q '^APP_KEY=' "$ENV_FILE"; then
  echo "Run: docker compose exec app php artisan key:generate"
fi
