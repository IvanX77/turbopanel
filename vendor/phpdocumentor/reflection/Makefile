.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: code-style
code-style:
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/phpcs

.PHONY: fix-code-style
fix-code-style:
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/phpcbf

.PHONY: static-code-analysis
static-code-analysis: #vendor ## Runs a static code analysis with phpstan/phpstan and vimeo/psalm
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.2-cli vendor/bin/phpstan --configuration=phpstan.neon
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.2-cli vendor/bin/psalm.phar --show-info=true --threads=4

.PHONY: test
test: test-unit test-functional ## Runs all test suites with phpunit/phpunit
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/phpunit

.PHONY: test-unit
test-unit: ## Runs unit tests with phpunit/phpunit
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/phpunit --testsuite=unit

.PHONY: test-functional
test-functional: ## Runs unit tests with phpunit/phpunit
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/phpunit --testsuite=integration

.PHONY: dependency-analysis
dependency-analysis: vendor ## Runs a dependency analysis with maglnet/composer-require-checker
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli .phive/composer-require-checker check --config-file=/opt/project/composer-require-checker.json

vendor: composer.json composer.lock
	composer validate --no-check-publish
	composer install --no-interaction --no-progress

.PHONY: benchmark
benchmark:
	docker run -it --rm -v${CURDIR}:/opt/project -w /opt/project php:8.1-cli tools/phpbench run

.PHONY: rector
rector: ## Refactor code using rector
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project php:8.1-cli vendor/bin/rector process

.PHONY: pre-commit-test
pre-commit-test: fix-code-style test code-style static-code-analysis

.PHONY: docs
docs: ## Generate documentation
	docker run -it --rm -v${PWD}:/opt/project -w /opt/project phpdoc/phpdoc:3-unstable
