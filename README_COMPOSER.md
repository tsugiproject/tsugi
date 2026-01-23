
To update only one thing whilst not bumping the transitive dependencies

    composer update tsugi/lib --no-dev
    composer update symfony/browser-kit --no-dev --ignore-platform-reqs
    composer update symfony/mime --ignore-platform-reqs --no-dev

To advance dependencies

    composer update --ignore-platform-reqs --no-dev

Make sure to allow it to work with any version of PHP

When advancing the version of PHP and moving from the php-84-x branch to master, update
composer.json to master-dev hashes and type:

Run this before pushing for sanity check:

    bash qa/pre-commit-vendor-check.sh 

