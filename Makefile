# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'

INFECTION=var/tools/infection.phar
MIN_MSI=100
MIN_COVERED_MSI=100

PHPSPEC=var/tools/phpspec.phar


.PHONY: check
check: 	 ## Runs all the checks
check: cs cs-lint test


.PHONY: cs
cs:	 ## Apply CS fixes
cs: gitignore composer-validate rector php-cs-fixer

.PHONY: cs-lint
cs-lint: ## Run CS checks
cs-lint: composer-validate php-cs-fixer-lint rector-lint

gitignore:
	LC_ALL=C sort -u .gitignore -o .gitignore

.PHONY: composer-validate
composer-validate: vendor/autoload.php
	composer validate --strict

.PHONY: php-cs-fixer
php-cs-fixer: vendor/autoload.php
	vendor/bin/php-cs-fixer fix --verbose --diff

.PHONY: php-cs-fixer-lint
php-cs-fixer-lint: vendor/autoload.php
	vendor/bin/php-cs-fixer fix --verbose --diff --dry-run
	composer validate --strict

.PHONY: rector
rector: vendor/autoload.php
	vendor/bin/rector

.PHONY: rector-lint
rector-lint: vendor/autoload.php
	vendor/bin/rector --dry-run

.PHONY: phpstan
phpstan: vendor/autoload.php
	vendor/bin/phpstan analyse

.PHONY: phpstan-update-baseline
phpstan-update-baseline: vendor/autoload.php
	@rm -f phpstan-baseline.neon
	vendor/bin/phpstan --generate-baseline

.PHONY: test
test:	 ## Executes the tests
test: test-unit infection e2e

.PHONY: test-unit
test-unit: vendor/autoload.php
	vendor/bin/phpunit

.PHONY: e2e
e2e: $(INFECTION) $(PHPSPEC)
	./tests/e2e_tests

.PHONY: infection
infection: $(INFECTION) vendor/autoload.php
	$(INFECTION) \
		--min-msi=$(MIN_MSI) \
		--min-covered-msi=$(MIN_COVERED_MSI) \
		--threads=max \
		--show-mutations

# Do install if there's no 'vendor'
vendor/autoload.php:
	composer install
	touch -c $@
	test -d vendor/infection/infection/src/StreamWrapper/ && rm -fr vendor/infection/infection/src/StreamWrapper/ && $(COMPOSER) dump-autoload || true

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	composer update
	touch -c $@

$(INFECTION): .tools/infection-version
	mkdir -p $(shell dirname $(INFECTION))
	wget --quiet "https://github.com/infection/infection/releases/download/$(shell cat .tools/infection-version)/infection.phar" --output-document=$(INFECTION)
	chmod a+x $@
	$(INFECTION) --version
	touch -c $@

$(PHPSPEC): .tools/phpspec-version
	mkdir -p $(shell dirname $(PHPSPEC))
	wget --quiet "https://github.com/phpspec/phpspec/releases/download/$(shell cat .tools/phpspec-version)/phpspec.phar" --output-document=$(PHPSPEC)
	chmod a+x $@
	BOX_REQUIREMENT_CHECKER=0 $(PHPSPEC) --version
	touch -c $@
