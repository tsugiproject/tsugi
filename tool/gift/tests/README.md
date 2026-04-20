# GIFT Parser Test Suite

This directory contains comprehensive unit tests for the GIFT parser.

## Test Structure

- **Parser/ParserTest.php** - Main test class with comprehensive test cases for all question types
- **Parser/ParserDataDrivenTest.php** - Data-driven tests using data providers
- **Parser/ParserDataProvider.php** - Test data provider for easy test case management
- **Parser/RegressionTest.php** - Regression tests for specific bugs
- **Feedback/FeedbackTest.php** - Tests for feedback functionality
- **ConfigureQuiz/ConfigureParseTest.php** - Tests for configuration parsing

## Running Tests

### Run all tests:
```bash
vendor/bin/phpunit
```

### Run specific test suite:
```bash
vendor/bin/phpunit --testsuite "Parser Tests"
```

### Run specific test class:
```bash
vendor/bin/phpunit tests/Parser/ParserTest.php
```

### Run specific test method:
```bash
vendor/bin/phpunit --filter testHexColorInAnswers
```

### With coverage:
```bash
vendor/bin/phpunit --coverage-html tests/coverage
```

## Adding New Tests

### 1. Add to existing test class
Add new test methods to `ParserTest.php`:

```php
public function testMyNewFeature() {
    $gift = "::Q1:: My question {T}";
    $questions = $this->assertParseSuccess($gift, 1);
    // Your assertions
}
```

### 2. Add to data provider
Add test cases to `ParserDataProvider::getValidTestCases()`:

```php
array('My test case', "::Q1:: Question {T}", 'true_false_question', 2),
```

### 3. Add regression test
Add to `RegressionTest.php` for bug fixes:

```php
public function testBugFixXYZ() {
    // Test the specific bug scenario
}
```

## Test Data Files

You can also create test data files in `tests/data/` directory:

```
tests/data/
  valid/
    true_false.gift
    multiple_choice.gift
    ...
  invalid/
    malformed.gift
    ...
```

Then load them in tests:

```php
$gift = file_get_contents(__DIR__ . '/data/valid/multiple_choice.gift');
```

## Test Helpers

The `ParserTest` base class provides helper methods:

- `parseGift($gift)` - Parse GIFT and return questions/errors
- `assertParseSuccess($gift, $expectedCount)` - Assert parsing succeeds
- `assertParseErrors($gift, $pattern)` - Assert parsing produces errors

## Best Practices

1. **One assertion per concept** - Test one thing at a time
2. **Descriptive test names** - Use `testFeatureName` or `testWhatItDoes`
3. **Use data providers** - For testing multiple similar cases
4. **Test edge cases** - Empty strings, special characters, etc.
5. **Test error cases** - Invalid input should produce expected errors
6. **Keep tests fast** - Avoid slow operations in tests
7. **Isolate tests** - Each test should be independent

## Coverage Goals

- ✅ All question types (True/False, MC, MA, SA, Numerical, Matching, Essay)
- ✅ All feedback formats
- ✅ Edge cases (escaped chars, special chars, etc.)
- ✅ Error handling
- ✅ Regression tests for bugs

