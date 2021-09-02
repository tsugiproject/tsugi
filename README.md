# TSUGI - A Framework for Building Interoperable Learning Tools

[![Apereo Incubating badge](https://img.shields.io/badge/apereo-incubating-blue.svg?logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAABmJLR0QA%2FwD%2FAP%2BgvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QUTEi0ybN9p9wAAAiVJREFUKM9lkstLlGEUxn%2Fv%2B31joou0GTFKyswkKrrYdaEQ4cZAy4VQUS2iqH%2BrdUSNYmK0EM3IkjaChnmZKR0dHS0vpN%2FMe97TIqfMDpzN4XkeDg8%2Fw45R1XNAu%2Fe%2BGTgAqLX2KzAQRVGytLR0jN2jqo9FZFRVvfded66KehH5oKr3dpueiMiK915FRBeXcjo9k9K5zLz%2B3Nz8EyAqX51zdwGMqp738NSonlxf36Cn7zX9b4eYX8gSBAE1Bw9wpLaW%2BL5KWluukYjH31tr71vv%2FU0LJ5xzdL3q5dmLJK7gON5wjEQizsTkFMmeXkbHxtHfD14WkbYQaFZVMzk1zfDHERrPnqGz4wZ1tYfJ5%2FPMLOYYW16ltrqKRDyOMcYATXa7PRayixSc4%2FKFRhrqjxKGIWVlZVQkqpg1pYyvR%2BTFF2s5FFprVVXBAAqq%2F7a9uPKd1NomeTX4HXfrvZ8D2F9dTSwWMjwywueJLxQKBdLfZunue0Mqt8qPyMHf0HRorR0ArtbX1Zkrly7yPNnN1EyafZUVZLJZxjNLlHc%2BIlOxly0RyktC770fDIGX3vuOMAxOt19vJQxD%2BgeHmE6liMVKuNPawlZ9DWu2hG8bW1Tuib0LgqCrCMBDEckWAVjKLetMOq2ZhQV1zulGVFAnohv5wrSq3tpNzwMR%2BSQi%2FyEnIl5Ehpxzt4t6s9McRdGpIChpM8Y3ATXbkKdEZDAIgqQxZrKo%2FQUk5F9Xr20TrQAAAABJRU5ErkJggg%3D%3D)](https://www.apereo.org/content/projects-currently-incubation)

Tsugi is a multi-tenant scalable LTI library and tool hosting environment.
It is intended to make it more tractable to implement the Application Store
that we will need for the [
Next Generation Digital Learning Environment](http://www.ngdle.org).

This repository is the **Tsugi Administration, Management, and Developer
Console**.  This code also implements an IMS ContentItem App store.

While earlier versions of this repository included a set of modules, examples,
and even exercises, as we move towards a 1.0 release of Tsugi, these elements
are now moved to separate repositories (see below).

## Pre-Requisites

* [Install GIT](https://www.tsugi.org/md/GITHUB.md) so that it works at the command prompt.

* Install a PHP/MySQL Environment like XAMPP / MAMP following the
instructions at:

http://www.wa4e.com/install.php

## Tsugi Versions

Tsugi is intended as a continuously upgrading cloud deployment.  Most of the Dr. Chuck
servers have a cron job that does a `git pull` and runs `upgrade.php` *every 30 minutes*.
You can see this infrastructure at:

https://github.com/tsugiproject/tsugi-build/tree/master/common

As a result, there are no traditional "releases" of Tsugi - the common use case is to
be pretty close to the tip of the main branch.

But sometimes, folks want to "hold back" from upgrading for a while.  Perhaps they have an old
version of PHP and can't run the latest.  It is risky to hold back too long.  But to help those
running Tsugi that want to hold back, a series of versions / tags are maintained as "safe
plateaus".  These tags are often snapped right before a signifacant upgrade or data model change
and announced on the dev list.

These versions originally were the classic geek-style '0.7.0' releases but as of
December 2020, we are switching to a year.month.patch approach to Tsugi versioning, adapting from
the Linux model.

## Installation

* Check the code out from GitHub and put it in a directory where
your web server can read it

        git clone https://github.com/tsugiproject/tsugi.git

### Method 1: Docker install

* If you have Docker installed (OSX/Linux currently) you should just be able to run `docker-compose build` and `docker-compose up` and Tsugi will start up and initialize.
* config-dist.php will be copied, you need to edit a few things in this like `CFG->adminpw`just edit these in place and they'll be updated.
* Go to http://localhost:8888/tsugi and you should be all set.

### Method 2: Manual install 
* Create a database and get authentication info for the database (MySQL 8.0
will need different commands):

        CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
        CREATE USER 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
        GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost';
        CREATE USER 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';
        GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1';

        Or

        CREATE DATABASE ltiuser DEFAULT CHARACTER SET utf8;
        GRANT ALL ON tsugi.* TO ltiuser@'localhost';
        GRANT ALL ON tsugi.* TO ltiuser@'127.0.0.1';
        SET PASSWORD FOR 'ltiuser'@'localhost' = PASSWORD('ltipassword');
        SET PASSWORD FOR 'ltiuser'@'127.0.0.1' = PASSWORD('ltipassword');

* Copy the file config-dist.php to config.php and edit the file
to put in the appropriate values.  Make sure to change all the secrets.
If you are just getting started turn on DEVELOPER mode so you can launch
the tools easily.  Each of the fields is documented in the config-dist.php
file - here is some additional documentation on the configuration values:

    http://do1.dr-chuck.com/tsugi/phpdoc/classes/Tsugi.Config.ConfigInfo.html

* Go to the main page, and click on "Admin" to make all the database
tables - you will need the Admin password you just put into config.php
If all goes well, lots of tables should be created.  You can run upgrade.php
more than once - it will automatically detect that it has been run.

## Production instances

There is an entire repository that contains varous build / deploy documentation
showing how to install on EC2, Docker, Digital Ocean and Ubuntu.

https://github.com/tsugiproject/tsugi-build

These scripts make sure you have all the necessary pre-requisites installed and
configured.

## Adding Some Tools

If you are just exploring Tsugi, or doing a developer bootcamp, you can add some tools
from some of the other repositories:

* If you set the `$CFG->install_path` and go into the Admin interface, you can
use "Manage Installed Modules" to install tools from [Tsugi Tools](https://github.com/tsugitools)

* [Tsugi Module Sample Code](https://github.com/tsugiproject/tsugi-php-samples) - These
are relatively short bits of code that you can look at as you write your
own Tsugi Module.

* [Tsugi Developer Exercises](https://github.com/tsugiproject/tsugi-php-exercises) - This
is a set of exercises of increasing difficulty suitable for a class or
workshop.  Working solutions are provided online.  Source code for working solutions
is only available to instructors that contact Dr. Chuck.

* [Sample Tsugi Module](https://github.com/tsugiproject/tsugi-php-module) - Copy
this if you want to start a fresh Tsugi Module from scratch.  If you are building
a new tool from scratch, you should build it as a "Tsugi Module" following all
of the Tsugi style guidance, using the Tsugi browser environment, and making
full use of the Tsugi framework. This repository contains a basic
"Tsugi Module" you can use as a starting point.

* [Sample Tsugi-Enabled Application](https://github.com/tsugiproject/tsugi-php-standalone) - You
can also use Tsugi as a library and add it to a few places in an existing application.
This repository contains sample code showing how to use Tsugi as a library in an existing
application.

Each of these repositories contain instructions on how to install, configure, and hook
each of these applications into your Tsugi instance.  Once you install a new module or
modules, you will need to re-run the Admin / Database Upgrade process to create
the new tables required by the new applications.

We have a short document on how to check out
[all of the above tools](docs/CHECKOUT_ALL.md)
and set up the configuration for them.

## Developer Documentation

You can view some of the developer documentation for the PHP version of Tsugi at:

* [Developer Documentation](docs/README.md)

* [PHP API Documentation](http://do1.dr-chuck.com/tsugi/phpdoc/)

## Other Repositories

The Tsugi Administration Console and Tsugi Modules / Applications depend on two other
repositories:

* [Tsugi PHP Library](https://github.com/tsugiproject/tsugi-php) - This is the code for the
Tsugi run-time used by the Tsugi administration console and Tsugi PHP Modules
and Applications.

* [Tsugi Static Content](https://github.com/tsugiproject/tsugi-static) - This repository contains
JavaScript, images, and CSS files shared across the various Tsugi implementations
(PHP, Java, and NodeJS).  The static content is available at
https://static.tsugi.org/ - if you like you can check out your own copy
of this repo locally or for your production environment and point your Tsugi `config.php`
at your own copy of the library.

## Other Languages

There were some emergent efforts to port the core Tsugi code to Java and Node.  Partial implementations
were built with the hopes that those interested in these languages would pick the code up, use it,
and invest in those implementations.  This has not happenned so those projects are deprecated
until some resources show up.

Going forward, there is an effort to increasingly move away from PHP and towards Python in a series of careful
steps that won't break existing tools or servers.

The first step is to build a way to develop and host Python-based tools and then being to build a 
parallel version of the Tsugi core code and administration in Python.

You can play with an early version of a [Tsugi Python Tool](https://www.tsugi.org/django_sakai.txt).

While the PHP Implementation of Tsugi is the most well developed, there are additional
Tsugi implementations being developed:

* [Tsugi Java](https://github.com/tsugiproject/tsugi-java-servlet) This is a reasonably complete
implementation of the Tsugi run-time in Java.  It shares low level IMS libraries with
Sakai and is ready for production use.

* [Tsugi NodeJS](https://github.com/tsugiproject/tsugi-node-sample) - This is early
pre-emergent code and not under active development.

## Tsugi Developer List

Please join the
[Tsugi Developer List](https://groups.google.com/a/apereo.org/forum/#!forum/tsugi-dev)
so you can stay up to date with the progress regarding Tsugi.


