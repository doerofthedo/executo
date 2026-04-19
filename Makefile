SHELL := /bin/bash

# Variables — edit here if container/service names change
COMPOSE        = docker compose -f docker-compose.yml
PHP            = $(COMPOSE) exec backend php
ARTISAN        = $(PHP) artisan
COMPOSER       = $(COMPOSE) exec backend composer
NPM            = $(COMPOSE) exec node npm
HOST_UID       := $(shell id -u)
HOST_GID       := $(shell id -g)

.DEFAULT_GOAL := help

.PHONY: help dev init-dev build rebuild stop down restart logs logs-app assets-dev assets-build assets-clean migrate migrate-fresh migrate-fresh-seed seed rollback cache-clear cache-build queue-work tinker test test-coverage analyse lint security composer-install composer-update npm-install npm-update clean reset

define run-artisan-if-installed
	@if [ -f backend/vendor/autoload.php ]; then \
		$(ARTISAN) $(1); \
	else \
		echo "Skipping '$(1)' because backend dependencies are not installed yet."; \
	fi
endef

##@ Help

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} \
	/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2 } \
	/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

##@ Development

dev: ## Start all containers and watch logs
	@if [[ ! -f backend/vendor/autoload.php ]]; then \
		echo "[lexrate] Missing backend/vendor/. Installing PHP dependencies..."; \
		composer install --working-dir=backend --no-interaction --no-scripts; \
	fi
	@if [[ ! -d frontend/node_modules ]]; then \
		echo "[lexrate] Missing frontend/node_modules/. Installing JS dependencies..."; \
		npm install --prefix frontend; \
	fi
	@echo "[lexrate] Starting dev containers..."
	@$(COMPOSE) up -d
	@echo "[lexrate] Dev stack is up."
	@echo "→ App:     http://executo.local"
	@echo "→ Mailpit: http://executo.local/mailpit"
	@echo "[lexrate] Attaching to container logs. Press Ctrl+C to stop log tailing."
	@$(COMPOSE) logs --tail=100 -f

init-dev: ## Prepare local dependencies, migrate, seed, and run checks
	@if [[ ! -f backend/vendor/autoload.php ]]; then \
		echo "[lexrate] Missing backend/vendor/. Installing PHP dependencies..."; \
		composer install --working-dir=backend --no-interaction --no-scripts; \
	fi
	@if [[ ! -d frontend/node_modules ]]; then \
		echo "[lexrate] Missing frontend/node_modules/. Installing JS dependencies..."; \
		npm install --prefix frontend; \
	fi
	@echo "[lexrate] Building and starting dev containers..."
	@$(COMPOSE) up --build -d
	@if ! grep -q '^APP_KEY=base64:' $(ENV); then \
		echo "[lexrate] Generating Laravel APP_KEY..."; \
		$(COMPOSE) exec -T backend php artisan key:generate --force; \
	fi
	@echo "[lexrate] Running Laravel package discovery..."
	@$(COMPOSE) exec -T backend php artisan package:discover --ansi
	@echo "[lexrate] Running Laravel migrations..."
	@$(COMPOSE) exec -T backend php artisan migrate --force
	@echo "[lexrate] Seeding sample data..."
	@$(COMPOSE) exec -T backend php artisan db:seed --force
	@echo "[lexrate] Running test suite..."
	@$(MAKE) test

build: ## Build Docker images only
	$(COMPOSE) build

rebuild: ## Rebuild Docker images and then start containers
	$(COMPOSE) up --build -d

stop: ## Stop all containers
	$(COMPOSE) stop

down: ## Stop and remove containers (keeps volumes)
	$(COMPOSE) down

restart: ## Restart all containers
	$(COMPOSE) restart

logs: ## Tail logs from all containers
	$(COMPOSE) logs --tail=100 -f

logs-app: ## Tail logs from PHP container only
	$(COMPOSE) logs --tail=100 -f backend

##@ Assets

assets-dev: ## Start Vite dev server (HMR) — alias for npm run dev inside node container
	$(NPM) run dev

assets-build: ## Build and compile frontend assets to public/assets/
	$(NPM) run build

assets-clean: ## Remove compiled assets from public/assets/
	rm -rf public/assets/*
	touch public/assets/.gitkeep

##@ Laravel

migrate: ## Run database migrations
	$(call run-artisan-if-installed,migrate)

migrate-fresh: ## ⚠ Drop all tables and re-run migrations (keeps seeders out)
	@bash -c 'read -p "This will destroy all data. Continue? [y/N] " c; [[ $$c == y ]]'
	$(call run-artisan-if-installed,migrate:fresh)

migrate-fresh-seed: ## ⚠ Drop all tables, re-run migrations, and seed
	@bash -c 'read -p "This will destroy all data. Continue? [y/N] " c; [[ $$c == y ]]'
	$(call run-artisan-if-installed,migrate:fresh --seed)

seed: ## Run database seeders only (no migration)
	$(call run-artisan-if-installed,db:seed)

rollback: ## Roll back the last migration batch
	$(call run-artisan-if-installed,migrate:rollback)

cache-clear: ## Clear all Laravel caches
	$(call run-artisan-if-installed,cache:clear)
	$(call run-artisan-if-installed,config:clear)
	$(call run-artisan-if-installed,route:clear)
	$(call run-artisan-if-installed,view:clear)

cache-build: ## Rebuild all Laravel caches (for production-like testing)
	$(call run-artisan-if-installed,config:cache)
	$(call run-artisan-if-installed,route:cache)
	$(call run-artisan-if-installed,view:cache)

queue-work: ## Start queue worker
	$(call run-artisan-if-installed,queue:work --tries=3)

tinker: ## Open Laravel Tinker REPL
	$(call run-artisan-if-installed,tinker)

##@ Code Quality

test: ## Run full test suite (Pest)
	$(PHP) vendor/bin/pest

test-coverage: ## Run tests with coverage report
	$(PHP) vendor/bin/pest --coverage --min=80

analyse: ## Run PHPStan static analysis (level 8)
	$(PHP) vendor/bin/phpstan analyse

lint: ## Run all quality checks (PHPStan + tests)
	$(MAKE) analyse
	$(MAKE) test

##@ Dependencies

composer-update: ## Update PHP dependencies
	@if ! command -v composer >/dev/null 2>&1; then \
		echo "composer is required on the host for this target."; \
		exit 1; \
	fi
	cd backend && composer update

npm-install: ## Install JS dependencies
	@if ! command -v npm >/dev/null 2>&1; then \
		echo "npm is required on the host for this target."; \
		exit 1; \
	fi
	cd frontend && npm install

npm-update: ## Update JS dependencies
	@if ! command -v npm >/dev/null 2>&1; then \
		echo "npm is required on the host for this target."; \
		exit 1; \
	fi
	cd frontend && npm update

##@ Cleanup

clean: ## Remove compiled assets and Laravel caches
	$(MAKE) assets-clean
	$(MAKE) cache-clear

reset: ## ⚠ Full reset — down, remove volumes, rebuild, migrate fresh, seed
	@bash -c 'read -p "This will destroy ALL data and volumes. Continue? [y/N] " c; [[ $$c == y ]]'
	$(COMPOSE) down -v
	$(COMPOSE) up --build -d
	$(call run-artisan-if-installed,migrate:fresh --seed)
