#!/usr/bin/env bash
# Wayfinder must run in the PHP (app) container before any Vite build/dev.
set -euo pipefail
docker compose exec -T app php artisan wayfinder:generate --with-form "$@"
