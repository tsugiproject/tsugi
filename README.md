TSUGI - A Framework for Building Interoperable Learning Tools
=============================================================
[![Apereo Incubating badge](https://img.shields.io/badge/apereo-incubating-blue.svg?logo=data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAAABmJLR0QA%2FwD%2FAP%2BgvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QUTEi0ybN9p9wAAAiVJREFUKM9lkstLlGEUxn%2Fv%2B31joou0GTFKyswkKrrYdaEQ4cZAy4VQUS2iqH%2BrdUSNYmK0EM3IkjaChnmZKR0dHS0vpN%2FMe97TIqfMDpzN4XkeDg8%2Fw45R1XNAu%2Fe%2BGTgAqLX2KzAQRVGytLR0jN2jqo9FZFRVvfded66KehH5oKr3dpueiMiK915FRBeXcjo9k9K5zLz%2B3Nz8EyAqX51zdwGMqp738NSonlxf36Cn7zX9b4eYX8gSBAE1Bw9wpLaW%2BL5KWluukYjH31tr71vv%2FU0LJ5xzdL3q5dmLJK7gON5wjEQizsTkFMmeXkbHxtHfD14WkbYQaFZVMzk1zfDHERrPnqGz4wZ1tYfJ5%2FPMLOYYW16ltrqKRDyOMcYATXa7PRayixSc4%2FKFRhrqjxKGIWVlZVQkqpg1pYyvR%2BTFF2s5FFprVVXBAAqq%2F7a9uPKd1NomeTX4HXfrvZ8D2F9dTSwWMjwywueJLxQKBdLfZunue0Mqt8qPyMHf0HRorR0ArtbX1Zkrly7yPNnN1EyafZUVZLJZxjNLlHc%2BIlOxly0RyktC770fDIGX3vuOMAxOt19vJQxD%2BgeHmE6liMVKuNPawlZ9DWu2hG8bW1Tuib0LgqCrCMBDEckWAVjKLetMOq2ZhQV1zulGVFAnohv5wrSq3tpNzwMR%2BSQi%2FyEnIl5Ehpxzt4t6s9McRdGpIChpM8Y3ATXbkKdEZDAIgqQxZrKo%2FQUk5F9Xr20TrQAAAABJRU5ErkJggg%3D%3D)](https://www.apereo.org/content/projects-currently-incubation)

Tsugi is a multi-tenant scalable LTI library and tool hosting environment.
It is intended to make it more tractable to implement the Application Store
that we will need for the [
Next Generation Digital Learning Environment](http://www.ngdle.org).

This repository is the **Tsugi Administration, Management, and Developer
Console**.  This code also implements an LTI 2.0 Provider,
CASA App Store, and IMS ContentItem App store.  Any Tsugi tool (including
those written in Java and NodeJS) needs to have this software installed
and configured as pre-requisite.  Perhaps someday we will build Java and/or
NodeJS versions of the console - but for now we just use the PHP console
for applications written in any language.

While earlier versions of this repository included a set of modules, examples,
and even exercises, as we move towards a 1.0 release of Tsugi, these elements
are now moved to separate repositories (see below).

Pre-Requisites
--------------

* [Install GIT] (docs/GITHUB.md) so that it works at the command prompt.

* Install a PHP/MySQL Environment like XAMPP / MAMP following the
instructions at:

    http://www.wa4e.com/install.php

Installation
------------

* Check the code out from GitHub and put it in a directory where
your web server can read it

        git clone https://github.com/tsugiproject/tsugi.git

* Create a database and get authentication info for the database

        CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
        GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
        GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';

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

Adding Some Tools
-----------------

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
is only available to inctructors that contact Dr. Chuck.

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

Developer Documentation
-----------------------

You can view some of the developer documentation for the PHP version of Tsugi at:

* [Developer Documentation](docs/README.md)

* [PHP API Documentation](http://do1.dr-chuck.com/tsugi/phpdoc/)

Other Repositories
------------------

The Tsugi Administration Console and Tsugi Modules / Applications depend on two other
repositories:

* [Tsugi PHP Library](https://github.com/tsugiproject/tsugi-php) - This is the code for the 
Tsugi run-time used by the Tsugi administration console and Tsugi PHP Modules 
and Applications.

* [Tsugi Static Content](https://github.com/tsugiproject/tsugi-static) - This repository contains
JavaScript, images, and CSS files shared across the various Tsugi implementations
(PHP, Java, and NodeJS).  The static content is available at 
http://www.dr-chuck.net/tsugi-static/ - if you like you can check out your own copy
of this repo locally or for your production environment and point your Tsugi `config.php`
at your own copy of the library.

Other Languages
---------------

While the PHP Implementation of Tsugi is the most well developed, there are additional 
Tsugi implementations being developed:

* [Tsugi Java](https://github.com/tsugiproject/tsugi-java-servlet) This is a reasonably complete
implementation of the Tsugi run-time in Java.  It shares low level IMS libraries with 
Sakai and is ready for production use.

* [Tsugi NodeJS](https://github.com/tsugiproject/tsugi-node-sample) - This is early 
pre-emergent code.

Tsugi Developer List
--------------------

Please join the 
[Tsugi Developer List](https://groups.google.com/a/apereo.org/forum/#!forum/tsugi-dev)
so you can stay up to date with the progress regarding Tsugi.


