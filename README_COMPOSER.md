
To update only one thing whilst not bumping the transitive dependencies

    composer update tsugi/lib
    composer update symfony/browser-kit

And to ignore platform requirements:

    composer update tsugi/lib --ignore-platform-reqs

