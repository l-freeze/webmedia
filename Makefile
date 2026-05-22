COMPOSE=docker compose

.PHONY: up down build install migrate seed test shell logs

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

install:
	$(COMPOSE) run --rm app composer install

migrate:
	$(COMPOSE) run --rm app composer migrate

seed:
	$(COMPOSE) run --rm app composer seed

test:
	$(COMPOSE) run --rm app composer test

shell:
	$(COMPOSE) run --rm app sh

logs:
	$(COMPOSE) logs -f app
