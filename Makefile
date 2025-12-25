.PHONY: ci test prerequisites

# Use any most recent PHP version
PHP=$(shell which php)

# Default parallelism
JOBS=$(shell nproc)

# PHPStan
PHPSTAN=vendor/bin/phpstan
PHPSTAN_ARGS=analyse src tests/phpunit -c .phpstan.neon

# Composer
COMPOSER=$(PHP) $(shell which composer)

COVERAGE_DIR=var/coverage

# Infection
INFECTION=var/tools/infection.phar
MIN_MSI=68
MIN_COVERED_MSI=97

all: test

.PHONY: cs
cs: gitignore composer-validate php-cs-fixer

.PHONY: cs-lint
cs-lint: composer-validate php-cs-fixer-lint

gitignore:
	LC_ALL=C sort -u .gitignore -o .gitignore

.PHONY: composer-validate
composer-validate: vendor
	composer validate --strict

.PHONY: php-cs-fixer
php-cs-fixer: vendor
	vendor/bin/php-cs-fixer fix --verbose --diff

.PHONY: php-cs-fixer-lint
php-cs-fixer-lint: vendor
	vendor/bin/php-cs-fixer fix --verbose --diff --dry-run

.PHONY: phpstan
phpstan: vendor
	vendor/bin/phpstan analyse

.PHONY: phpstan-update-baseline
phpstan-update-baseline: vendor
	@rm -f phpstan-baseline.neon
	vendor/bin/phpstan --generate-baseline

.PHONY: test-unit
test-unit:
	vendor/bin/phpunit

.PHONY: test-unit-xml-coverage
test-unit-xml-coverage:
	@rm -rf $(COVERAGE_DIR) || true
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-xml=$(COVERAGE_DIR)/xml --log-junit=$(COVERAGE_DIR)/junit.xml

.PHONY: infection
infection: $(INFECTION)
	$(INFECTION) \
		--min-msi=$(MIN_MSI) \
		--min-covered-msi=$(MIN_COVERED_MSI) \
		--threads=max \
		--show-mutations

##############################################################
# Development Workflow                                       #
##############################################################

test: phpunit analyze composer-validate

.PHONY: composer-validate
composer-validate: test-prerequisites
	$(COMPOSER) validate --strict

test-prerequisites: prerequisites composer.lock

phpunit: cs-fix
	$(PHPUNIT) $(PHPUNIT_ARGS) --verbose
	cp build/logs/junit.xml build/logs/phpunit.junit.xml
	$(PHP) $(INFECTION) $(INFECTION_ARGS)

analyze: cs-fix
	$(PHPSTAN) $(PHPSTAN_ARGS)
	$(PSALM) $(PSALM_ARGS)

cs-fix: test-prerequisites
	$(PHP_CS_FIXER) fix $(PHP_CS_FIXER_ARGS)
	LC_ALL=C sort -u .gitignore -o .gitignore

##############################################################
# Prerequisites Setup                                        #
##############################################################

# We need both vendor/autoload.php and composer.lock being up to date
.PHONY: prerequisites
prerequisites: build/cache vendor/autoload.php composer.lock infection.json.dist .phpstan.neon

# Do install if there's no 'vendor'
vendor/autoload.php:
	$(COMPOSER) install --prefer-dist
	test -d vendor/infection/infection/src/StreamWrapper/ && rm -fr vendor/infection/infection/src/StreamWrapper/ && $(COMPOSER) dump-autoload || true

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	$(COMPOSER) update && touch composer.lock

build/cache:
	mkdir -p build/cache

$(INFECTION): .tools/infection-version
	mkdir -p $(shell dirname $(INFECTION))
	wget --quiet "https://github.com/infection/infection/releases/download/$(shell cat .tools/infection-version)/infection.phar" --output-document=$(INFECTION)
	chmod a+x $@
	$(INFECTION) --version
	touch -c $@

$(PHP_CS_FIXER): Makefile
	wget -q $(PHP_CS_FIXER_URL) --output-document=$(PHP_CS_FIXER)
	chmod a+x $(PHP_CS_FIXER)
	touch $@
