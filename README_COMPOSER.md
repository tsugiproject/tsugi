
To update only one thing whilst not bumping the transitive dependencies

    composer update tsugi/lib --no-dev
    composer update symfony/browser-kit --no-dev --ignore-platform-reqs
    composer update symfony/mime --ignore-platform-reqs --no-dev

To add a dependency - don't edit composer.json - do this

    composer require symfony/process:^7.2 --no-interaction --no-update --ignore-platform-reqs

To advance dependencies

    composer update --ignore-platform-reqs -W --no-dev

IMPORTANT: vendor/ is committed but require-dev packages (phpunit, phpstan,
myclabs/deep-copy, panther, etc.) are in .gitignore. Always end with:

    composer install --no-dev --ignore-platform-reqs

so vendor/composer/autoload_*.php does not reference packages that are not in git.
If production fatals on myclabs/deep-copy or phpunit autoload paths, re-run that
command and commit the regenerated vendor/composer/ files.

Make sure to allow it to work with any version of PHP

When advancing the version of PHP and moving from the php-84-x branch to master, update
composer.json to master-dev hashes and type:

Run this before pushing for sanity check:

    bash qa/pre-commit-vendor-check.sh 

