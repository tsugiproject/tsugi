
GIFT Quiz Tool
==============

[![Apereo Incubating badge](https://img.shields.io/badge/apereo-incubating-blue.svg?logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAABmJLR0QA%2FwD%2FAP%2BgvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QUTEi0ybN9p9wAAAiVJREFUKM9lkstLlGEUxn%2Fv%2B31joou0GTFKyswkKrrYdaEQ4cZAy4VQUS2iqH%2BrdUSNYmK0EM3IkjaChnmZKR0dHS0vpN%2FMe97TIqfMDpzN4XkeDg8%2Fw45R1XNAu%2Fe%2BGTgAqLX2KzAQRVGytLR0jN2jqo9FZFRVvfded66KehH5oKr3dpueiMiK915FRBeXcjo9k9K5zLz%2B3Nz8EyAqX51zdwGMqp738NSonlxf36Cn7zX9b4eYX8gSBAE1Bw9wpLaW%2BL5KWluukYjH31tr71vv%2FU0LJ5xzdL3q5dmLJK7gON5wjEQizsTkFMmeXkbHxtHfD14WkbYQaFZVMzk1zfDHERrPnqGz4wZ1tYfJ5%2FPMLOYYW16ltrqKRDyOMcYATXa7PRayixSc4%2FKFRhrqjxKGIWVlZVQkqpg1pYyvR%2BTFF2s5FFprVVXBAAqq%2F7a9uPKd1NomeTX4HXfrvZ8D2F9dTSwWMjwywueJLxQKBdLfZunue0Mqt8qPyMHf0HRorR0ArtbX1Zkrly7yPNnN1EyafZUVZLJZxjNLlHc%2BIlOxly0RyktC770fDIGX3vuOMAxOt19vJQxD%2BgeHmE6liMVKuNPawlZ9DWu2hG8bW1Tuib0LgqCrCMBDEckWAVjKLetMOq2ZhQV1zulGVFAnohv5wrSq3tpNzwMR%2BSQi%2FyEnIl5Ehpxzt4t6s9McRdGpIChpM8Y3ATXbkKdEZDAIgqQxZrKo%2FQUk5F9Xr20TrQAAAABJRU5ErkJggg%3D%3D)](https://www.apereo.org/content/projects-currently-incubation)

This tool currently contains two pieces of related functionality:

* It can author and deliver GIFT-authored quizzes using LTI
* It can convert its quizzes to QTI 1.2 

These two pieces are related because they share a bunch of library code.

Preloading Quizzes
------------------

You can have this quiz tool consult a folder to pre-load quizzes.  Use the following 
configuration option in your `config.php`:

    $CFG->giftquizzes = $CFG->dirroot.'/../php-solutions/quiz';

It should be a folder with a serires of files that end in `*.txt` in the
GIFT format.  You can store these files in a private GitHub repo.  You can 
also add a password to all the files by creating the file `.lock` in the folder
and put in a single line with the plaintext password to unlock the quzzes.

You can request a default quiz from this folder using a GET parameter:

    http://localhost:8888/wa4e/mod/gift/?quiz=00-CSS.txt

The instructor still needs to go in and configure the quiz - but the right quiz 
will be pre-populated in the configuration drop down and pre-loaded if there
is no `.lock` file.

Quiz format convertor from GIFT to QTI 1.2
==========================================

This includes a simple converter into QTI 1.2 for import into lots of systems
like Sakai, Coursera, and Canvas.

GIFT seems to be a micro-format invented by the Moodle community - and a pretty cool 
idea if I do say so myself.  I like it because I can put quizzes in GitHub :)

    https://docs.moodle.org/28/en/GIFT_format

There is a stand alone version of the GIFT converter at:

    https://www.tsugi.org/gift2qti/

Comments (and Pull requests) welcome.

Unit Tests
==========

If you want to run and/or make Unit tests, first install composer from 

https://getcomposer.org/doc/

At some point you can run `composer` from the command line.   Then from 
the gift folder do:

    composer update

This will install `phpunit` into a `vendor` folder - don't worry - this won't 
go back into github - it is ignored.

Then to run the unit tests do:

    vendor/bin/phpunit

It should look like this:

    PHPUnit 5.7.27 by Sebastian Bergmann and contributors.
    .  1 / 1 (100%)
    Time: 21 ms, Memory: 4.00MB

    OK (1 test, 4 assertions)

The unit tests are in folders under the folder `tests` - just add a subfolder and your
unit tests in php files.


-- Chuck
