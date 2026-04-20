SHELL := /bin/bash

# Variables -- edit here if container/service names change
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

define run-artisan-if-view-configured
	@if [ ! -f backend/vendor/autoload.php ]; then \
		echo "Skipping '$(1)' because backend dependencies are not installed yet."; \
	elif [ ! -f backend/config/view.php ]; then \
		echo "Skipping '$(1)' because backend/config/view.php is not present in this scaffold."; \
	else \
		$(ARTISAN) $(1); \
	fi
endef

define ensure-host-composer
	@if [[ ! -f backend/vendor/autoload.php || ! -f backend/vendor/mockery/mockery/library/Mockery.php || ! -x backend/vendor/bin/pest ]]; then \
		echo "[executo] Backend dependencies are incomplete. Installing PHP dependencies..."; \
		composer install --working-dir=backend --no-interaction --no-scripts; \
	fi
endef

define ensure-host-npm
	@if [[ ! -d frontend/node_modules ]]; then \
		echo "[executo] Missing frontend/node_modules/. Installing JS dependencies..."; \
		npm install --prefix frontend; \
	fi
endef

##@ Help

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} \
	/^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2 } \
	/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

##@ Development

dev: ## Bootstrap everything: dependencies, assets, containers, migrations, seed, tests, logs
	$(call ensure-host-composer)
	$(call ensure-host-npm)
	@echo "[executo] Building frontend assets..."
	@$(MAKE) assets-build
	@echo "[executo] Building and starting dev containers..."
	@$(COMPOSE) up --build -d
	@echo "[executo] Running Laravel package discovery..."
	@$(COMPOSE) exec -T backend php artisan package:discover --ansi
	@echo "[executo] Running database migrations..."
	@$(COMPOSE) exec -T backend php artisan migrate --force
	@echo "[executo] Running database seeders..."
	@$(COMPOSE) exec -T backend php artisan db:seed --force
	@echo "[executo] Running test suite..."
	@$(MAKE) test
	@echo "[executo] Restoring application database state after tests..."
	@$(COMPOSE) exec -T backend php artisan migrate --force
	@$(COMPOSE) exec -T backend php artisan db:seed --force
	@echo "[executo] Dev stack is up."
	@echo "→ App:     http://executo.local"
	@echo "→ Mailpit: http://executo.local/mailpit"
	@echo "[executo] Attaching to container logs. Press Ctrl+C to stop log tailing."
	@$(COMPOSE) logs --tail=100 -f

init-dev: ## Prepare local dependencies, migrate, seed, and run checks
	@$(MAKE) dev

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
	@if ! command -v npm >/dev/null 2>&1; then \
		echo "npm is required on the host for this target."; \
		exit 1; \
	fi
	cd frontend && npm run build

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
	$(call run-artisan-if-view-configured,view:clear)

cache-build: ## Rebuild all Laravel caches (for production-like testing)
	$(call run-artisan-if-installed,config:cache)
	$(call run-artisan-if-installed,route:cache)
	$(call run-artisan-if-view-configured,view:cache)

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
	@if ! command -v php >/dev/null 2>&1; then \
		echo "php is required on the host for this target."; \
		exit 1; \
	fi
	@mkdir -p backend/storage/logs backend/bootstrap/cache
	@if [ ! -w backend/storage ] || [ ! -w backend/storage/logs ] || [ ! -w backend/bootstrap/cache ]; then \
		echo "[executo] Backend writable paths are not writable on the host."; \
		echo "[executo] Run once: sudo chown -R $$(id -u):$$(id -g) backend/storage backend/bootstrap/cache"; \
		exit 1; \
	fi
	cd backend && composer update --no-scripts
	@echo "[executo] Running Laravel package discovery on the host..."
	cd backend && APP_ENV=local APP_DEBUG=true CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync DB_CONNECTION=sqlite DB_DATABASE=':memory:' REDIS_HOST=127.0.0.1 php artisan package:discover --ansi

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

clean: ## ⚠ Stop and remove containers, volumes, orphans, and networks
	$(COMPOSE) down --volumes --remove-orphans

reset: ## ⚠ Full reset — down, remove volumes, rebuild, migrate fresh, seed
	@bash -c 'read -p "This will destroy ALL data and volumes. Continue? [y/N] " c; [[ $$c == y ]]'
	$(COMPOSE) down -v
	$(COMPOSE) up --build -d
	$(call run-artisan-if-installed,migrate:fresh --seed)
