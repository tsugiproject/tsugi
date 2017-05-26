# Koseu (코스)

Koseu solves a number of important use cases:

* A Learning Object Repository with seamless integration into previous-generation LMSs
* An independent open source, highly scalable, extremely flexible MOOC hosting platform
* A Next Generation Open Source Learning Management System

Eventually, this will be the best LMS, LOR, and MOOC hosting platform in the world.  But for now this is just a README file
so I can get started writing the code.

If you want to see Koseu in action visit these web sites:

* Python for Everybody www.py4e.com
* Web Applications for Everybody www.wa4e.com
* The Tsugi project - www.tsugi.org

Actually most of the koseu code that already exists lives in https://github.com/tsugiproject/tsugi - the Koseu
code will be refactored out of the Tsugi Management Console, rewritten to be more OO, and moved into this repository.

# Technology

Koseu is written as a Silex (http://silex.sensiolabs.org/) application so it
has pretty URLs, routing, nice OO patterns and all that hipster stuff.

# Installing Koseu (Pre-Alpha)

The basic idea of Koseu is that it is "embedded" into a static web site.  So the web site has things like images, 
PHP files, HTML, PowerPoint, tools etc that are just served like normal Web 1.0 files.  When you add Koseu to a
web site, it makes URLs like https://www.py4e.com/tsugi/lessons start to appear.  First you install Koseu using the
following `composer` command:

    composer require --dev koseu/lib

Then you need to route non-file, non-folder urls to `koseu.php` using a `.htaccess` similar to the following:

    FallbackResource koseu.php

Then you need to add the `koseu.php` to your web site:

     <?php
     define('COOKIE_SESSION', true);
     require_once "tsugi/config.php";

     // Pull in the Koseu LMS (/lessons, /map, /badges ...)
     $launch = \Tsugi\Core\LTIX::session_start();
     $app = new \Koseu\Core\Application($launch);
     $app->run();

That is (or soon will be) it.  Then you just add Koseu URLs in your web content to Koseu tools.  For now,
you still need to install and setup the Tsugi Management Console (https://github.com/tsugiproject/tsugi).
But over time the Tsugi management console will also move into `composer` so as to deliver in the promise of
"installing an LMS" in 10 lines of code.

# What is in a name?

코스 is the Korean word for "Course" and www.koseu.com and www.koseu.org were still available :)

