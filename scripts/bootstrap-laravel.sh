#!/usr/bin/env bash
# Bootstrap Laravel 13 + official React starter kit into Drachme repo.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ -f artisan ]]; then
  echo "Laravel already bootstrapped (artisan exists)."
  exit 0
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

echo "=== Drachme bootstrap ==="
echo "Temp dir: $TMP_DIR"

echo "Downloading laravel/react-starter-kit (Laravel 13 + React + shadcn)..."
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -e COMPOSER_HOME="/tmp/composer" \
  -v "$TMP_DIR:/app" \
  -w /app \
  composer:2 \
  composer create-project laravel/react-starter-kit:dev-main . --no-interaction --prefer-dist

if [[ ! -f "$TMP_DIR/artisan" ]]; then
  echo "ERROR: artisan missing in temp build — bootstrap failed."
  exit 1
fi

echo "Merging into $ROOT (keeping Drachme docs, docker, .env)..."
RSYNC_EXCLUDES=(
  --exclude docs/
  --exclude scripts/
  --exclude docker/
  --exclude docker-compose.yml
  --exclude Makefile
  --exclude README.md
  --exclude AGENTS.md
  --exclude .cursor/
  --exclude .vscode/
  --exclude .git/
  --exclude .env
)

rsync -a "${RSYNC_EXCLUDES[@]}" "$TMP_DIR/" "$ROOT/"

if [[ -f "$TMP_DIR/.env.example" && ! -f "$ROOT/.env.example" ]]; then
  cp "$TMP_DIR/.env.example" "$ROOT/.env.example"
fi

if [[ ! -f "$ROOT/artisan" ]]; then
  echo "ERROR: merge failed — artisan still missing."
  exit 1
fi

echo ""
echo "Bootstrap OK."
echo "Next:"
echo "  make build && make up"
echo "  make setup"
