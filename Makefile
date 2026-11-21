# FleetCore developer shortcuts. On Windows without make, use ./dev.sh with the same target names.
COMPOSE ?= docker compose
.DEFAULT_GOAL := help

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

up: ## Build and start the whole stack
	$(COMPOSE) up -d --build
down: ## Stop the stack
	$(COMPOSE) down
logs: ## Follow logs
	$(COMPOSE) logs -f --tail=100
ps: ## Show containers
	$(COMPOSE) ps
sh-api: ## Shell into the API container
	$(COMPOSE) exec api bash
sh-web: ## Shell into the web container
	$(COMPOSE) exec web sh
composer: ## Run composer in the API container: make composer cmd="require foo/bar"
	$(COMPOSE) exec api composer $(cmd)
console: ## Run bin/console: make console cmd="cache:clear"
	$(COMPOSE) exec api php bin/console $(cmd)
pnpm: ## Run pnpm in the web container: make pnpm cmd="add zod"
	$(COMPOSE) exec web pnpm $(cmd)
migrate: ## Run Doctrine migrations
	$(COMPOSE) exec api php bin/console doctrine:migrations:migrate --no-interaction
fixtures: ## Reset the database and load fixtures
	$(COMPOSE) exec api php bin/console doctrine:database:drop --force --if-exists
	$(COMPOSE) exec api php bin/console doctrine:database:create
	$(COMPOSE) exec api php bin/console doctrine:migrations:migrate --no-interaction
	$(COMPOSE) exec api php bin/console doctrine:fixtures:load --no-interaction
test: test-api test-web ## Run all tests
test-api: ## Run PHPUnit
	$(COMPOSE) exec api php bin/phpunit
test-web: ## Run Vitest
	$(COMPOSE) exec web pnpm test
lint: ## Static analysis and code style (api + web)
	$(COMPOSE) exec api vendor/bin/phpstan analyse --memory-limit=512M
	$(COMPOSE) exec api vendor/bin/php-cs-fixer fix --dry-run --diff
	$(COMPOSE) exec web pnpm lint
	$(COMPOSE) exec web pnpm typecheck
fix: ## Auto-fix code style
	$(COMPOSE) exec api vendor/bin/php-cs-fixer fix
	$(COMPOSE) exec web pnpm lint --fix
worker: ## Start the Messenger worker (profile)
	$(COMPOSE) --profile worker up -d worker
reset: ## Destroy containers and volumes (data loss)
	$(COMPOSE) down -v --remove-orphans

.PHONY: help up down logs ps sh-api sh-web composer console pnpm migrate fixtures test test-api test-web lint fix worker reset
