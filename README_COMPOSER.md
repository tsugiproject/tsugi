
To update only one thing whilst not bumping the transitive dependencies

    composer update tsugi/lib
    composer update symfony/browser-kit

And to ignore platform requirements:

    composer update tsugi/lib --ignore-platform-reqs

To advance dependencies

    composer update --ignore-platform-reqs

Make sure to allow it to work with any version of PHP


