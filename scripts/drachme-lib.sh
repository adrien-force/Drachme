#!/usr/bin/env bash
# Shared helpers for Drachme shell scripts (source, do not execute).
set -euo pipefail

drachme_compose() {
    if [[ -n "${DRACHME_COMPOSE_CMD:-}" ]]; then
        "${DRACHME_COMPOSE_CMD[@]}" "$@"
        return
    fi

    if docker compose version >/dev/null 2>&1; then
        DRACHME_COMPOSE_CMD=(docker compose)
    elif command -v docker-compose >/dev/null 2>&1 && docker-compose version >/dev/null 2>&1; then
        DRACHME_COMPOSE_CMD=(docker-compose)
    else
        echo "ERROR: Docker Compose not found (need 'docker compose' or 'docker-compose')." >&2
        exit 1
    fi

    "${DRACHME_COMPOSE_CMD[@]}" "$@"
}

drachme_compose_dev() {
    if docker compose version >/dev/null 2>&1; then
        docker compose --profile dev "$@"
    else
        docker-compose --profile dev "$@"
    fi
}
