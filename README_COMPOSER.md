
To update only one thing whilst not bumping the transitive dependencies

    composer update tsugi/lib
    composer update symfony/browser-kit

And to ignore platform requirements:

    composer update tsugi/lib --ignore-platform-reqs

To advance dependencies

    composer update --ignore-platform-reqs

Make sure to allow it to work with any version of PHP

When advancing the version of PHP and moving from the php-84-x branch to master, update
composer.json to master-dev hashes and type:

composer update tsugi/lib koseu/lib --ignore-platform-reqs

This way to does not trigger a fresh dependency advance across the board.

