.PHONY: qa cs csf phpstan tests coverage

all:
	@$(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$' | xargs

vendor: composer.json composer.lock
	composer install

qa: phpstan cs

cs: vendor
	vendor/bin/codesniffer src tests

csf: vendor
	vendor/bin/codefixer src tests

phpstan: vendor
	vendor/bin/phpstan analyse -l max -c phpstan.neon src

tests: vendor
	vendor/bin/phpunit tests --cache-result-file=tests/tmp/phpunit.cache --colors=always

coverage: vendor
	vendor/bin/phpdbg -qrr vendor/bin/phpunit tests --cache-result-file=tests/tmp/phpunit.cache --colors=always -c tests/coverage.xml
