.PHONY: test test-coverage test-parser test-regression install clean

# Install dependencies
install:
	composer install

# Run all tests
test:
	vendor/bin/phpunit

# Run tests with coverage
test-coverage:
	vendor/bin/phpunit --coverage-html tests/coverage

# Run parser tests only
test-parser:
	vendor/bin/phpunit --testsuite "Parser Tests"

# Run regression tests
test-regression:
	vendor/bin/phpunit --filter RegressionTest

# Run specific test
test-filter:
	vendor/bin/phpunit --filter $(FILTER)

# Clean generated files
clean:
	rm -rf tests/coverage
	find . -name "*.php~" -delete

# Quick test (no coverage)
quick-test:
	vendor/bin/phpunit --no-coverage

