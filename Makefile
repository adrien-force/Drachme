# Drachme — run from WSL: ~/Projects/Drachme
.PHONY: help normalize check build up down dev logs shell composer npm bootstrap env install setup migrate key test analyse typecheck lint quality build-assets strict-types

# docker compose (plugin) or docker-compose (standalone)
COMPOSE := $(shell if docker compose version >/dev/null 2>&1; then echo "docker compose"; elif command -v docker-compose >/dev/null 2>&1; then echo "docker-compose"; else echo ""; fi)
COMPOSE_DEV := $(COMPOSE) --profile dev

ifeq ($(COMPOSE),)
$(error Docker Compose not found. Install the Compose plugin or docker-compose.)
endif

help:
	@echo "Compose: $(COMPOSE)"
	@echo "Targets: bootstrap, build, up, setup, dev, down, build-assets, quality, check"
	@echo "  admin       - Create admin user (drachme:create-admin)"
	@echo "  seed-demo   - Add demo user with sample data (drachme:seed-demo)"

normalize:
	@sed -i 's/\r$$//' Makefile scripts/*.sh 2>/dev/null || true
	@chmod +x scripts/*.sh 2>/dev/null || true

strict-types:
	@$(COMPOSE) exec -T app php scripts/add-strict-types.php

check: normalize
	@./scripts/check-env.sh

bootstrap: normalize
	@./scripts/bootstrap-laravel.sh

build: normalize
	$(COMPOSE) build

up: normalize
	$(COMPOSE) up -d

dev: normalize
	@$(COMPOSE) exec -T app php artisan wayfinder:generate --with-form
	$(COMPOSE_DEV) up -d

down: normalize
	-$(COMPOSE_DEV) down 2>/dev/null || true
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec app bash

composer:
	$(COMPOSE) exec app composer $(filter-out $@,$(MAKECMDGOALS))

npm:
	$(COMPOSE) exec app npm $(filter-out $@,$(MAKECMDGOALS))

env: normalize
	@./scripts/configure-env.sh .env

install: normalize
	$(COMPOSE) exec -T app git config --global --add safe.directory /var/www/html 2>/dev/null || true
	$(COMPOSE) exec app composer install
	$(COMPOSE) exec app npm install

key: normalize
	$(COMPOSE) exec app php artisan key:generate --force

setup: normalize
	@./scripts/run-setup.sh

migrate: normalize
	$(COMPOSE) exec app php artisan migrate

test: normalize
	$(COMPOSE) exec -T app php artisan config:clear --ansi
	$(COMPOSE) exec app php artisan test

analyse: normalize
	$(COMPOSE) exec app composer analyse

typecheck: normalize
	$(COMPOSE) exec app npm run types:check

lint: normalize
	$(COMPOSE) exec app npm run lint

build-assets: normalize
	$(COMPOSE) exec -T app php artisan wayfinder:generate --with-form
	$(COMPOSE) exec -T app npm run build

admin: normalize
	$(COMPOSE) exec app php artisan drachme:create-admin

seed-demo: normalize
	$(COMPOSE) exec app php artisan drachme:seed-demo

seed-demo-fresh: normalize
	$(COMPOSE) exec app php artisan drachme:seed-demo --fresh

db-backup: normalize
	$(COMPOSE) exec app php artisan db:backup

encrypt-financial-data: normalize
	$(COMPOSE) exec app php artisan drachme:encrypt-financial-data

quality: normalize
	$(COMPOSE) exec -T app git config --global --add safe.directory /var/www/html
	$(COMPOSE) exec app composer analyse
	$(COMPOSE) exec app npm run types:check
	$(COMPOSE) exec -T app php artisan config:clear --ansi
	$(COMPOSE) exec app php artisan test

%:
	@:
