# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'

INFECTION_PARENT_PHAR=var/tools/infection.phar
INFECTION=../../../$(INFECTION_PARENT_PHAR)
MIN_MSI=68
MIN_COVERED_MSI=97


.PHONY: check
check: 	 ## Runs all the checks
check: cs cs-lint test


.PHONY: cs
cs:	 ## Apply CS fixes
cs: gitignore composer-validate

.PHONY: cs-lint
cs-lint: ## Run CS checks
cs-lint: composer-validate

gitignore:
	LC_ALL=C sort -u .gitignore -o .gitignore

.PHONY: composer-validate
composer-validate: vendor/autoload.php
	composer validate --strict --no-check-publish

.PHONY: test
test:	 ## Executes the tests
test: phpspec infection

.PHONY: phpspec
phpspec: vendor/autoload.php
	XDEBUG_MODE=coverage vendor/bin/phpspec run

.PHONY: infection
infection: $(INFECTION) vendor/autoload.php
	$(INFECTION) \
		--min-msi=$(MIN_MSI) \
		--min-covered-msi=$(MIN_COVERED_MSI) \
		--threads=max \
		--show-mutations \
		--test-framework="phpspec"
	diff --ignore-all-space expected-output.txt var/infection.log

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

$(INFECTION):
	cd ../../../; $(MAKE) --file Makefile $(INFECTION_PARENT_PHAR)
