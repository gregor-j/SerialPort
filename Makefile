SHELL := /bin/sh
.DEFAULT_GOAL := help

COMPOSER_IMAGE     := composer:2
COMPOSER_CACHE_DIR := $(HOME)/.cache/composer
DOCKER_USER        := --user "$$(id -u)":"$$(id -g)"
DOCKER_MOUNT       := --volume "$$(pwd)":/app --workdir /app

ifdef DEPENDENCIES_LOWEST
DEPENDENCY_FLAGS := --prefer-lowest
endif

ifdef CA_CERT_FILE
CA_MOUNT     := --volume "$(CA_CERT_FILE)":/usr/local/share/ca-certificates/custom-ca.crt:ro
CA_TRUST_CMD := export CURL_CA_BUNDLE=/usr/local/share/ca-certificates/custom-ca.crt SSL_CERT_FILE=/usr/local/share/ca-certificates/custom-ca.crt &&
else
CA_MOUNT     :=
CA_TRUST_CMD :=
endif

.PHONY: help clean check-php-version install test lint analyze beautify sniff audit validate

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

clean: ## Remove vendor/, composer.lock and PHPUnit cache
	rm -rf vendor composer.lock .phpunit.cache .phpunit.result.cache

check-php-version: ## Ensure PHP_VERSION is set
	@if [ -z "$(PHP_VERSION)" ]; then \
		echo "Error: PHP_VERSION must be set, e.g. 'make $(MAKECMDGOALS) PHP_VERSION=8.1'." >&2; \
		exit 1; \
	fi

install: check-php-version ## Resolve dependencies for PHP_VERSION (set DEPENDENCIES_LOWEST=1 for lowest versions)
	@mkdir -p "$(COMPOSER_CACHE_DIR)"
	@set +e; \
	docker run --rm --init $(DOCKER_USER) \
		--env HTTP_PROXY --env HTTPS_PROXY --env NO_PROXY \
		--env COMPOSER_CACHE_DIR=/composer-cache \
		--volume "$(COMPOSER_CACHE_DIR)":/composer-cache \
		$(DOCKER_MOUNT) \
		$(CA_MOUNT) \
		$(COMPOSER_IMAGE) sh -c '\
			$(CA_TRUST_CMD) \
			composer config platform.php "$(PHP_VERSION)"; \
			status=$$?; \
			if [ $$status -eq 0 ]; then \
				composer update $(DEPENDENCY_FLAGS) --prefer-dist --no-interaction --no-progress --ignore-platform-req=ext-sockets --ignore-platform-req=ext-pcntl; \
				status=$$?; \
			fi; \
			composer config --unset platform.php >/dev/null 2>&1 || true; \
			composer config --unset platform >/dev/null 2>&1 || true; \
			php -r '\''$$data = json_decode(file_get_contents("composer.json"), true); if (isset($$data["config"]) && $$data["config"] === []) { unset($$data["config"]); file_put_contents("composer.json", json_encode($$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL); }'\''; \
			rm -f composer.lock; \
			exit $$status \
		'; \
	status=$$?; \
	rm -f composer.lock; \
	exit $$status

test: check-php-version ## Run PHPUnit for PHP_VERSION
	docker run --rm --init $(DOCKER_MOUNT) \
		"php:$(PHP_VERSION)-cli" sh -c "docker-php-ext-install pcntl && php vendor/bin/phpunit --testdox"

lint: check-php-version ## Syntax-check PHP files in src/ and tests/ for PHP_VERSION
	docker run --rm --init $(DOCKER_USER) $(DOCKER_MOUNT) \
		"php:$(PHP_VERSION)-cli" sh -c "find src tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l"

analyze: check-php-version ## Run PHPStan for PHP_VERSION
	docker run --rm --init $(DOCKER_USER) $(DOCKER_MOUNT) \
		"php:$(PHP_VERSION)-cli" php vendor/bin/phpstan analyse --memory-limit=-1

beautify: check-php-version ## Run PHPCBF for PHP_VERSION
	docker run --rm --init $(DOCKER_USER) $(DOCKER_MOUNT) \
		"php:$(PHP_VERSION)-cli" php vendor/bin/phpcbf

sniff: check-php-version ## Run PHPCS for PHP_VERSION
	docker run --rm --init $(DOCKER_USER) $(DOCKER_MOUNT) \
		"php:$(PHP_VERSION)-cli" php vendor/bin/phpcs

audit: ## Audit dependencies for known vulnerabilities
	docker run --rm --init $(DOCKER_USER) \
		--env HTTP_PROXY --env HTTPS_PROXY --env NO_PROXY \
		$(DOCKER_MOUNT) $(CA_MOUNT) \
		$(COMPOSER_IMAGE) sh -c '$(CA_TRUST_CMD) composer audit'

validate: ## Validate composer.json
	docker run --rm --init $(DOCKER_USER) $(DOCKER_MOUNT) \
		$(COMPOSER_IMAGE) composer validate --strict
