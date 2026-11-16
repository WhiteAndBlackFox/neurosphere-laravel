.DEFAULT_GOAL := help

COMPOSE := docker compose
EXEC := $(COMPOSE) exec -T app

.PHONY: help up down restart sh migrate seed fresh test lint lint-syntax lint-pint lint-larastan fix ci

help: ## Показать список команд
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-18s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Собрать, установить зависимости и поднять приложение
	@test -f .env || cp .env.example .env
	$(COMPOSE) build
	$(COMPOSE) run --rm --no-deps app composer install --no-interaction --prefer-dist
	@if ! grep -Eq '^APP_KEY=.+$$' .env; then $(COMPOSE) run --rm --no-deps app php artisan key:generate --force; fi
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
	$(EXEC) php artisan test

lint: lint-syntax lint-pint lint-larastan ## Проверить синтаксис, стиль и типы

lint-syntax: ## Проверить синтаксис PHP
	$(EXEC) sh -c 'find app bootstrap config routes tests database public -name "*.php" ! -path "bootstrap/cache/*" -print0 | xargs -0 -n1 php -l && php -l artisan'

lint-pint: ## Проверить стиль кода
	$(EXEC) ./vendor/bin/pint --test

lint-larastan: ## Проверить типы через Larastan
	$(EXEC) ./vendor/bin/phpstan analyse --memory-limit=512M --no-progress

fix: ## Исправить стиль кода
	$(EXEC) ./vendor/bin/pint

ci: lint test ## Запустить все проверки и тесты без автоисправлений
