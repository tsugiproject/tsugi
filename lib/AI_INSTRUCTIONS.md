# AI Instructions for tsugi-php

This file contains instructions for AI coding assistants working on this project.

## Testing
After making code changes, always run the full test suite using one of these shortcuts:

**Preferred (composer script):**
```bash
composer test
```

**Alternative (direct command):**
```bash
./vendor/bin/phpunit tests --bootstrap vendor/autoload.php
```

Verify all tests pass before considering the task complete. Some skipped tests are acceptable, but all non-skipped tests must pass.

## Code Style
- Follow existing PHP code style in the project
- Use PSR-12 coding standards where applicable
- Maintain consistency with existing code patterns

## Documentation
- Add appropriate comments for complex logic
- Update inline documentation when modifying functions
- Keep test files well-documented with clear test descriptions

