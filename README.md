# tsugi-static
This holds the static files used by the Tsugi framework

If this has been pulled into a subtree in a project like tsugi-php - to
pull subtree updates in, use a command like this:

    git subtree pull --prefix=static https://github.com/csev/tsugi-static master
    git push origin master

All of the forks of tsugi-php will get new static code with a
simple "git pull" since they only see that something in the "static"
folder changed.

