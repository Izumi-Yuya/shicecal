#!/bin/bash

# Script to run tests with timeout to prevent hanging
# Usage: ./run-test.sh [test-file] [timeout-seconds]

TEST_FILE=${1:-"tests/Feature/SimpleNavigationTest.php"}
TIMEOUT=${2:-10}

echo "Running test: $TEST_FILE with timeout: ${TIMEOUT}s"

gtimeout ${TIMEOUT}s php artisan test $TEST_FILE --stop-on-failure

EXIT_CODE=$?

if [ $EXIT_CODE -eq 124 ]; then
    echo "Test timed out after ${TIMEOUT} seconds"
elif [ $EXIT_CODE -eq 0 ]; then
    echo "Tests passed successfully"
else
    echo "Tests failed with exit code: $EXIT_CODE"
fi

exit $EXIT_CODE