#!/bin/bash
# Test runner script for GIFT parser tests

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "GIFT Parser Test Suite"
echo "======================"
echo ""

# Check if vendor/bin/phpunit exists
if [ ! -f "vendor/bin/phpunit" ]; then
    echo -e "${YELLOW}Warning: vendor/bin/phpunit not found.${NC}"
    echo "Run 'composer install' or 'composer update' first."
    exit 1
fi

# Run tests
if [ "$1" == "--coverage" ]; then
    echo "Running tests with coverage..."
    vendor/bin/phpunit --coverage-html tests/coverage
elif [ "$1" == "--filter" ] && [ -n "$2" ]; then
    echo "Running filtered tests: $2"
    vendor/bin/phpunit --filter "$2"
elif [ "$1" == "--suite" ] && [ -n "$2" ]; then
    echo "Running test suite: $2"
    vendor/bin/phpunit --testsuite "$2"
else
    echo "Running all tests..."
    vendor/bin/phpunit "$@"
fi

EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo ""
    echo -e "${RED}✗ Some tests failed${NC}"
fi

exit $EXIT_CODE

