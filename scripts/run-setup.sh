#!/usr/bin/env bash
# Post-bootstrap: .env, permissions, deps, frontend build, migrations.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# shellcheck source=drachme-lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/drachme-lib.sh"

if [[ ! -f artisan ]]; then
  echo "ERROR: artisan not found. Run: make bootstrap"
  exit 1
fi

if ! drachme_compose ps --status running -q app 2>/dev/null | grep -q .; then
  echo "ERROR: app container is not running. Run: make build && make up"
  exit 1
fi

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

chmod +x scripts/*.sh 2>/dev/null || true
./scripts/configure-env.sh .env

echo "=== Permissions (nginx + php-fpm must traverse project root) ==="
chmod 755 "$ROOT"
chmod 755 "$ROOT/bootstrap" "$ROOT/config" "$ROOT/routes" "$ROOT/database" 2>/dev/null || true
chmod -R a+rX "$ROOT/public" 2>/dev/null || true
drachme_compose exec -T -u root app chmod 755 /var/www/html
drachme_compose exec -T -u root app chmod 755 \
  /var/www/html/bootstrap \
  /var/www/html/config \
  /var/www/html/routes \
  /var/www/html/database 2>/dev/null || true
drachme_compose exec -T -u root app chmod -R a+rX /var/www/html/public
drachme_compose exec -T -u root app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
drachme_compose exec -T -u root app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "=== App key ==="
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  drachme_compose exec -T app php artisan key:generate --force
fi

echo "=== Composer ==="
drachme_compose exec -T app git config --global --add safe.directory /var/www/html 2>/dev/null || true
drachme_compose exec -T app composer install --no-interaction

echo "=== Wayfinder + frontend build ==="
drachme_compose exec -T app php artisan wayfinder:generate --with-form
drachme_compose exec -T app npm install
drachme_compose exec -T app npm run build

echo "=== Database ==="
drachme_compose exec -T app php artisan migrate --force

echo "=== Cache ==="
drachme_compose exec -T app php artisan optimize:clear

drachme_compose up -d --force-recreate web app

echo ""
echo "Setup complete."
echo "  App:  http://localhost:8080"
echo "  Dev:  make dev   (Vite HMR on :5173)"
echo "  QA:   make quality"
