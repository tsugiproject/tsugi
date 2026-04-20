# GIFT Parser Testing Guide

## Quick Start

```bash
# Install dependencies (if not already done)
composer install

# Run all tests
make test
# or
vendor/bin/phpunit
# or
./tests/run_tests.sh

# Run with coverage
make test-coverage
# or
./tests/run_tests.sh --coverage

# Run specific test suite
make test-parser
# or
vendor/bin/phpunit --testsuite "Parser Tests"

# Run specific test
vendor/bin/phpunit --filter testHexColorInAnswers
```

## Test Structure

```
tests/
├── Parser/
│   ├── ParserTest.php              # Main comprehensive tests
│   ├── ParserDataDrivenTest.php    # Data-driven tests
│   ├── ParserDataProvider.php      # Test data provider
│   ├── RegressionTest.php          # Bug regression tests
│   └── IntegrationTest.php         # Integration tests
├── Feedback/
│   └── FeedbackTest.php            # Feedback-specific tests
├── ConfigureQuiz/
│   └── ConfigureParseTest.php      # Configuration parsing tests
├── data/
│   └── valid/                      # Sample GIFT files
│       ├── example_true_false.gift
│       ├── example_multiple_choice.gift
│       ├── example_numerical.gift
│       └── example_matching.gift
├── bootstrap.php                   # Test bootstrap
└── run_tests.sh                    # Test runner script
```

## Test Coverage

### ✅ Question Types
- True/False questions
- Multiple Choice questions
- Multiple Answer questions
- Short Answer questions
- Numerical questions
- Matching questions
- Essay questions

### ✅ Features
- Feedback (single and double)
- Percentage weights
- Hex colors in answers
- HTML questions
- Escaped characters
- Multi-line questions/answers
- Comments

### ✅ Edge Cases
- Empty titles
- Special characters
- Escaped braces
- Multi-line formatting
- Error handling

## Adding Tests

### Method 1: Add to ParserTest.php

```php
public function testMyFeature() {
    $gift = "::Q1:: My question {T}";
    $questions = $this->assertParseSuccess($gift, 1);
    $this->assertEquals('true_false_question', $questions[0]->type);
}
```

### Method 2: Add to Data Provider

Edit `ParserDataProvider.php`:

```php
public static function getValidTestCases() {
    return array(
        // ... existing cases ...
        array('My test', "::Q1:: Question {T}", 'true_false_question', 2),
    );
}
```

### Method 3: Add Test Data File

Create `tests/data/valid/my_test.gift`:

```
::Q1:: My question {T}
```

Then load in test:

```php
$gift = file_get_contents(__DIR__ . '/../../data/valid/my_test.gift');
```

## Test Helpers

### assertParseSuccess($gift, $expectedCount)
Asserts parsing succeeds with no errors.

```php
$questions = $this->assertParseSuccess($gift, 1);
```

### assertParseErrors($gift, $pattern)
Asserts parsing produces expected errors.

```php
$result = $this->assertParseErrors($gift, "No correct answers");
```

### parseGift($gift)
Parse GIFT and return questions/errors array.

```php
$result = $this->parseGift($gift);
$questions = $result['questions'];
$errors = $result['errors'];
```

## Common Test Patterns

### Test Question Type
```php
$questions = $this->assertParseSuccess($gift, 1);
$this->assertEquals('multiple_choice_question', $questions[0]->type);
```

### Test Answer Count
```php
$this->assertCount(4, $questions[0]->parsed_answer);
```

### Test Feedback
```php
foreach ($questions[0]->parsed_answer as $ans) {
    if ($ans[1] === 'Correct') {
        $this->assertEquals('Good job!', $ans[2]);
    }
}
```

### Test Correct Answers
```php
$this->assertEquals(2, $questions[0]->correct_answers);
```

## Continuous Integration

The test suite is designed to work with CI/CD:

```yaml
# Example .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/composer@v1
      - run: composer install
      - run: vendor/bin/phpunit
```

## Performance

Tests are designed to run quickly:
- Unit tests: < 1 second
- Full suite: < 5 seconds
- With coverage: < 10 seconds

## Troubleshooting

### Tests not found
```bash
# Make sure composer dependencies are installed
composer install
```

### Class not found errors
```bash
# Check bootstrap.php is being loaded
vendor/bin/phpunit --bootstrap tests/bootstrap.php
```

### Path issues
```bash
# Run from project root
cd /path/to/gift
vendor/bin/phpunit
```

## Best Practices

1. ✅ Test one thing per test method
2. ✅ Use descriptive test names (`testFeatureName`)
3. ✅ Test both success and failure cases
4. ✅ Keep tests independent
5. ✅ Use data providers for similar tests
6. ✅ Add regression tests for bugs
7. ✅ Document complex test scenarios

## Contributing

When adding new features:
1. Write tests first (TDD)
2. Ensure all tests pass
3. Add to appropriate test suite
4. Update this guide if needed

