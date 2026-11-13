# Composer and vendor workflow

Tsugi commits `vendor/` to git so production deploys do not run Composer at
install time. See also `AGENTS.md` for pinning strategy and Symfony notes.

## Update one package

Without bumping all transitive dependencies:

```bash
composer update tsugi/lib --no-dev
composer update symfony/browser-kit --no-dev --ignore-platform-reqs
composer update symfony/mime --ignore-platform-reqs --no-dev
```

## Add a dependency

Do not edit `composer.json` by hand — use:

```bash
composer require symfony/process:^7.2 --no-interaction --no-update --ignore-platform-reqs
```

## Advance dependencies

```bash
composer update --ignore-platform-reqs -W --no-dev
```

## After any update or require

`vendor/` is committed but `require-dev` packages are gitignored. Production
autoload is enforced automatically:

```bash
composer run finalize-vendor
```

`post-update-cmd` runs this for you after `composer update` / `composer require`;
run it explicitly when unsure.

Install the git pre-commit guard:

```bash
bash qa/install-git-hooks.sh
```

## Google API services

Tsugi uses only the generated Classroom API. Google's `pre-autoload-dump`
cleanup task keeps `Classroom` and removes unused services from the committed
`vendor/google/apiclient-services` tree during Composer install, update, or
autoload generation. Keep the Google client and authentication dependencies.

To add another Google API, add its exact service name to
`extra.google/apiclient-services` in `composer.json`, then run
`composer reinstall google/apiclient-services --no-interaction` to restore the
package before cleanup runs. Regenerate the lockfile metadata without upgrading
packages (`composer update --lock --no-dev`), run `composer run finalize-vendor`,
and commit the manifest, lockfile, and vendor changes together.

## Before pushing

Sanity-check committed autoload:

```bash
bash qa/pre-commit-vendor-check.sh
```

## PHP version branches

When advancing PHP and moving from the `php-84-x` branch to `master`, update
`composer.json` to `master-dev` hashes before running `composer update`.

Make sure dependency changes work across supported PHP versions.
