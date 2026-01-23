## Making Changes to the Tsugi PHP Library

The Tsugi PHP library is developed **only** in the main `tsugi` monorepo.
The standalone `tsugi-php` repository is a **read-only mirror** used for
Packagist distribution.

### Canonical Rule

> **All changes to the Tsugi PHP library must be committed and pushed to  
> `tsugi` `master` before they are mirrored to `tsugi-php`.**

Changes flow one way:

```
tsugi (master, lib/) → tsugi-php (master) → Packagist
```

Direct commits or pull requests to `tsugi-php` are not accepted and may be
overwritten.

---

## Step-by-Step Workflow

### 1. Work in the main Tsugi repository

Clone and work in the canonical repository:

```bash
git clone https://github.com/tsugiproject/tsugi.git
cd tsugi
```

Make all code changes **only** under the `lib/` directory.

---

### 2. Commit and push to `tsugi` master

Before mirroring, changes **must** be committed and pushed to the canonical
branch:

```bash
git status
git add lib/
git commit -m "Describe the change to the Tsugi PHP library"
git push origin master
```

Only committed changes are eligible to be mirrored.

---

### 3. Mirror `lib/` to the `tsugi-php` repository

From the **tsugi repository root**, run the mirror script:

```bash
qa/mirror-tsugi-php.sh
```

This script:

1. Extracts the `lib/` directory history
2. Reconstructs it as a standalone repository
3. Force-updates `tsugi-php` `master` using `--force-with-lease`

The result is an exact mirror of `tsugi/lib`.

---

### 4. Verify (optional)

After mirroring, you may verify that the change appears in the standalone repo:

```bash
git ls-remote https://github.com/tsugiproject/tsugi-php.git
```

or by browsing the repository on GitHub.

---

## Important Notes

* The history in `tsugi-php` is a **projection** of the monorepo history and may
  differ from earlier standalone commits.
* Any commits made directly to `tsugi-php` may be replaced during the next
  mirror operation.
* All development discussion, issues, and pull requests belong in the main
  `tsugi` repository.

---

---

## Running Unit Tests

The Tsugi PHP library includes a comprehensive test suite with 70+ test files covering Core, Util, UI, Crypt, and other components.

### From the `/tsugi/lib` directory (monorepo)

When working in the main `tsugi` repository:

```bash
cd tsugi/lib
composer install  # Install dependencies if needed
composer test     # Run all tests
```

Or run PHPUnit directly:

```bash
cd tsugi/lib
./vendor/bin/phpunit --configuration phpunit.xml.dist
```

### From the `tsugi-php` mirror repository root

When working in the standalone `tsugi-php` repository:

```bash
cd tsugi-php
composer install  # Install dependencies if needed
composer test     # Run all tests
```

Or run PHPUnit directly:

```bash
cd tsugi-php
./vendor/bin/phpunit --configuration phpunit.xml.dist
```

### Running specific tests

To run a specific test file or directory:

```bash
# From lib/ or tsugi-php root
./vendor/bin/phpunit tests/Core/LaunchTest.php
./vendor/bin/phpunit tests/Util/
```

---

## Summary

**If you remember only one thing:**

> **Commit and push to `tsugi` `master` first.  
> `tsugi-php` is generated from that state.**
