.PHONY: help install update clean cache assets check test stan stan-fix cs cs-fix rector rector-fix db db-reset migrate make-migration up down logs build build-prod ps shell prod-up prod-down worker worker-stop console m me mc mf

# Variables
DOCKER := docker compose
PHP_CONTAINER := php

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	$(DOCKER) exec $(PHP_CONTAINER) composer install

update: ## Update dependencies
	$(DOCKER) exec $(PHP_CONTAINER) composer update

clean: ## Clean cache and logs
	$(DOCKER) exec $(PHP_CONTAINER) rm -rf var/cache/*
	$(DOCKER) exec $(PHP_CONTAINER) rm -rf var/log/*

cache: ## Clear cache
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console cache:clear

assets: ## Install assets
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console assets:install --symlink

test: ## Run tests
	$(DOCKER) exec $(PHP_CONTAINER) php bin/phpunit

check: ## Run all checks (stan, cs, rector, test)
	@echo "\033[33m▶ Running PHPStan...\033[0m"
	@$(DOCKER) exec -T $(PHP_CONTAINER) php vendor/bin/phpstan analyse
	@echo "\033[33m▶ Running PHP-CS-Fixer...\033[0m"
	@$(DOCKER) exec -T $(PHP_CONTAINER) php vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "\033[33m▶ Running Rector...\033[0m"
	@$(DOCKER) exec -T $(PHP_CONTAINER) php vendor/bin/rector process --dry-run
	@echo "\033[33m▶ Running PHPUnit...\033[0m"
	@$(DOCKER) exec -T $(PHP_CONTAINER) php bin/phpunit
	@echo "\033[32m✓ All checks passed!\033[0m"

stan: ## Run PHPStan
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/phpstan analyse

stan-fix: ## Generate PHPStan baseline
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/phpstan analyse --generate-baseline

cs: ## Check code style (dry-run)
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/php-cs-fixer fix

rector: ## Check code with Rector (dry-run)
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/rector process --dry-run

rector-fix: ## Fix code with Rector
	$(DOCKER) exec $(PHP_CONTAINER) php vendor/bin/rector process

# Database
db: ## Create database and run migrations
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:database:create --if-not-exists
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

migrate: ## Run migrations
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

make-migration: ## Create new migration
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console make:migration

db-reset: ## Reset database (drop, create, migrate)
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:database:create --if-not-exists
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

# Docker
up: ## Start containers (dev)
	$(DOCKER) up -d

down: ## Stop containers
	$(DOCKER) down

logs: ## Show logs
	$(DOCKER) logs -f

build: ## Build images
	$(DOCKER) build --no-cache

build-prod: ## Build production images
	$(DOCKER) -f compose.yaml -f compose.prod.yaml build --no-cache

ps: ## Show running containers
	$(DOCKER) ps

shell: ## Open shell in PHP container
	$(DOCKER) exec -it $(PHP_CONTAINER) /bin/bash

prod-up: ## Start production containers
	$(DOCKER) -f compose.yaml -f compose.prod.yaml up -d

prod-down: ## Stop production containers
	$(DOCKER) -f compose.yaml -f compose.prod.yaml down

# Messenger
worker: ## Consume messages
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console messenger:consume async

worker-stop: ## Stop all workers
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console messenger:stop-workers

# Shortcuts
console: ## Run console command (make console args="cache:clear")
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console $(args)

m: ## make:command/make:controller/etc (make m args="controller")
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console make:$(args)

me: ## make:entity (make me args="User")
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console make:entity $(args)

mc: ## make:controller (make mc args="HomeController")
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console make:controller $(args)

mf: ## make:form (make mf args="UserType")
	$(DOCKER) exec $(PHP_CONTAINER) php bin/console make:form $(args)
