MAKEFLAGS += --no-print-directory

##
## Environment variables
##

ifneq (,$(wildcard ./.env))
	DOTENV_PATH=/tmp/$(shell echo $$(pwd) | base64)
	include $(shell cat .env | grep -v --perl-regexp '^('$$(env | sed 's/=.*//'g | tr '\n' '|')')\=' | sed 's/=/?=/g' > $(DOTENV_PATH); echo '$(DOTENV_PATH)')
endif

APP_ENV ?= dev
DOCKER_PORT_HTTP ?= 8080
DOCKER_BUILDKIT ?= 1

DOCKER_IMAGE_VERSION ?= latest

ifeq ($(APP_ENV), prod)
	COMPOSER_ARGS += --no-dev
endif

# Binaries
DOCKER_COMPOSE = docker-compose
COMPOSER = $(DOCKER_COMPOSE) run --rm --no-deps app composer $(COMPOSER_ARGS)
PHP = $(DOCKER_COMPOSE) run --rm --no-deps app
YARN = $(DOCKER_COMPOSE) run --rm --no-deps app yarn

# Export all variables so they are accessible in the shells created by make
export

##
## Entrypoints
##

.PHONY: up
up:
	$(MAKE) build
	$(DOCKER_COMPOSE) up -d --remove-orphan
	@echo "\e[30m\e[42m\n"
	@echo " App is up and running at http://localhost:$(DOCKER_PORT_HTTP)"
	@echo "\e[49m\e[39m\n"

.PHONY: build
build:
	$(MAKE) .env
	$(DOCKER_COMPOSE) build
	$(MAKE) dependencies
	$(MAKE) cache
	$(PHP) bin/console assets:install public
	$(YARN) build

.PHONY: down
down:
	$(DOCKER_COMPOSE) down --remove-orphan

.PHONY: destroy
destroy:
	$(DOCKER_COMPOSE) down --remove-orphan --volumes --rmi local

##
## Docker image for production
##

.PHONY: docker-image
docker-image: PHP_PCOV_ENABLED=0
docker-image: PHP_XDEBUG_MODE=off
docker-image:
	docker build . \
		--build-arg APP_ENV=prod \
		--build-arg USER=www-data \
		--target production \
		-t $(DOCKER_IMAGE_NAME):$(DOCKER_IMAGE_VERSION)

##
## Dependencies
##

.PHONY: dependencies
dependencies:
	$(COMPOSER) install \
		--no-interaction \
		--no-ansi \
		--prefer-dist \
		--optimize-autoloader
	$(YARN) install --frozen-lockfile

##
## Misc
##

.env:
	cp -n .env.dist .env

.PHONY: cache
cache:
	$(PHP) rm -rf var/cache
	$(PHP) bin/console cache:warmup

.PHONY: cs-fix
cs-fix:
	$(PHP) vendor/bin/php-cs-fixer fix
	$(YARN) stylelint "assets/styles/**/*.scss" --fix

##
## Tests
##

.PHONY: tests
tests: APP_ENV=test
tests: PHP_PCOV_ENABLED=0
tests: PHP_XDEBUG_MODE=off
tests:
	$(MAKE) cache
	$(MAKE) tests-static
	$(MAKE) tests-unit
	$(MAKE) tests-integration

.PHONY: tests-static
tests-static:
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff
	$(PHP) vendor/bin/phpstan analyse --no-progress --level 8 src
	$(PHP) vendor/bin/phpstan analyse --no-progress --level 6 tests
	$(PHP) vendor/bin/psalm --no-progress
	$(PHP) bin/console lint:container
	$(PHP) bin/console lint:yaml config/
	$(PHP) bin/console lint:twig templates/
	$(YARN) stylelint "assets/styles/**/*.scss"

.PHONY: tests-unit
tests-unit: APP_ENV=test
tests-unit: PHP_PCOV_ENABLED=1
tests-unit: PHP_XDEBUG_MODE=off
tests-unit:
	$(PHP) vendor/bin/phpunit --testsuite "Unit" \
		--log-junit var/tests/unit.xml \
		--coverage-html coverage/unit/ \
		--coverage-clover coverage/unit/coverage.xml

.PHONY: tests-integration
tests-integration: APP_ENV=test
tests-integration: PHP_PCOV_ENABLED=1
tests-integration: PHP_XDEBUG_MODE=off
tests-integration:
	$(PHP) vendor/bin/phpunit --testsuite "Integration" \
		--log-junit var/tests/integration.xml \
		--coverage-html coverage/integration/ \
		--coverage-clover coverage/integration/coverage.xml
