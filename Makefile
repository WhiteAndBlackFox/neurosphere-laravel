.DEFAULT_GOAL := help

PROJECT_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
COMPOSE_PROJECT_NAME ?= $(notdir $(PROJECT_ROOT))
COMPOSE_BIN := $(shell if docker compose version >/dev/null 2>&1; then printf 'docker compose'; elif docker-compose version >/dev/null 2>&1; then printf 'docker-compose'; fi)
COMPOSE := $(COMPOSE_BIN) --project-name "$(COMPOSE_PROJECT_NAME)" --env-file "$(PROJECT_ROOT)/.env" -f "$(PROJECT_ROOT)/docker/docker-compose.yml"
EXEC := $(COMPOSE) exec -T app

# Make standalone Compose delegate builds to Docker so Dockerfile.dockerignore applies.
export DOCKER_BUILDKIT := 1
export COMPOSE_DOCKER_CLI_BUILD := 1

.PHONY: check-compose help up down restart sh migrate seed fresh test lint lint-syntax lint-pint lint-larastan fix ci

up down sh migrate seed fresh test lint-syntax lint-pint lint-larastan fix: | check-compose

check-compose:
	@if [ -z "$(COMPOSE_BIN)" ]; then printf '%s\n' 'Docker Compose не найден: установите docker compose или docker-compose.' >&2; exit 1; fi

help: ## Показать список команд
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Собрать, установить зависимости и поднять приложение
	@test -f "$(PROJECT_ROOT)/.env" || cp "$(PROJECT_ROOT)/.env.example" "$(PROJECT_ROOT)/.env"
	$(COMPOSE) build
	$(COMPOSE) run --rm --no-deps app composer install --no-interaction --prefer-dist
	@if ! grep -Eq '^APP_KEY=.+$$' "$(PROJECT_ROOT)/.env"; then $(COMPOSE) run --rm --no-deps app php artisan key:generate --force; fi
	$(COMPOSE) up -d mysql redis app nginx
	$(EXEC) php artisan migrate --force
	$(COMPOSE) up -d queue-worker

down: ## Остановить контейнеры
	$(COMPOSE) down

restart: ## Перезапустить контейнеры
	$(MAKE) down
	$(MAKE) up

sh: ## Зайти в контейнер app
	$(COMPOSE) exec app sh

migrate: ## Прогнать миграции
	$(EXEC) php artisan migrate --force

seed: ## Заполнить БД демо-данными
	$(EXEC) php artisan db:seed --force

fresh: ## Пересоздать БД и заполнить демо-данными
	$(EXEC) php artisan migrate:fresh --seed --force

test: ## Прогнать тесты
	$(EXEC) ./vendor/bin/phpunit --configuration=tools/phpunit.xml

lint: lint-syntax lint-pint lint-larastan ## Проверить синтаксис, стиль и типы

lint-syntax: ## Проверить синтаксис PHP
	$(EXEC) sh -c 'find app bootstrap config routes tests database public -name "*.php" ! -path "bootstrap/cache/*" -print0 | xargs -0 -n1 php -l && php -l artisan'

lint-pint: ## Проверить стиль кода
	$(EXEC) ./vendor/bin/pint --config=tools/pint.json --test

lint-larastan: ## Проверить типы через Larastan
	$(EXEC) ./vendor/bin/phpstan analyse --configuration=tools/phpstan.neon --memory-limit=512M --no-progress

fix: ## Исправить стиль кода
	$(EXEC) ./vendor/bin/pint --config=tools/pint.json

ci: lint test ## Запустить все проверки и тесты без автоисправлений
