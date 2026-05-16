#!/usr/bin/env bash
# Quick environment check for Drachme (WSL + Docker Option B).
set -euo pipefail

LIB="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/drachme-lib.sh"
# shellcheck source=drachme-lib.sh
source "$LIB"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ok() { echo -e "${GREEN}OK${NC}  $1"; }
fail() { echo -e "${RED}FAIL${NC}  $1"; exit 1; }
warn() { echo -e "${YELLOW}WARN${NC}  $1"; }

echo "=== Drachme environment check ==="
echo

if [[ "${HOME:-}" == /home/* ]]; then
  ok "HOME=${HOME}"
else
  warn "HOME=${HOME:-unset} (expected /home/<user> — see docs/SETUP-WINDOWS-WSL.md)"
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ "$ROOT" == /mnt/c/* ]]; then
  warn "Project on Windows mount ($ROOT). Prefer ~/Projects/Drachme in WSL."
else
  ok "Project path: $ROOT"
fi

if command -v git >/dev/null 2>&1; then
  ok "Git: $(git --version)"
else
  fail "Git not found"
fi

if command -v docker >/dev/null 2>&1; then
  if docker info >/dev/null 2>&1; then
    ok "Docker: $(docker --version)"
  else
    fail "Docker installed but daemon not running — start Docker Desktop"
  fi
else
  fail "Docker not found in WSL"
fi

if docker compose version >/dev/null 2>&1; then
  ok "Compose: docker compose ($(docker compose version --short 2>/dev/null || true))"
elif command -v docker-compose >/dev/null 2>&1; then
  ok "Compose: docker-compose ($(docker-compose version --short 2>/dev/null || docker-compose --version))"
else
  fail "Docker Compose not available (plugin or docker-compose binary)"
fi

if grep -q $'\r' "$ROOT/Makefile" 2>/dev/null; then
  warn "Makefile has CRLF — run: make normalize"
fi

for f in "$ROOT"/scripts/*.sh; do
  if [[ -f "$f" ]] && grep -q $'\r' "$f"; then
    warn "CRLF in $(basename "$f") — run: make normalize"
    break
  fi
done

if [[ -f "$ROOT/docker-compose.yml" ]]; then
  ok "docker-compose.yml present"
else
  warn "docker-compose.yml missing"
fi

echo
echo "=== Done ==="
