TSUGI - A Framework for Building PHP-Based Learning Tools
=========================================================

**Note:** As of February 9, 2016 - I am refactoring much of the library code in this repository into 
tsugi-php - to get to the point where a tool can be written outside this directory tree using
composer.  I have made a branch 0.1.0 that is before the non-upwrds compatible changes in case 
folks want to stop following master as I move it forward. There will be a few non-upwards 
compatible changes in how you access library code along the way.
Please contact me if you are using this in production so I can inform you when changes are being done.

Welcome to the www.tsugi.org project. 
Its goal is to build a scalable multi-tenant "tool" hosting environment based on the 
emerging IMS standards.  

* Video Presentation: [Tsugi Overview](https://www.youtube.com/watch?v=iDcoWH9PO6I&index=2&list=PLlRFEj9H3Oj5WZUjVjTJVBN18ozYSWMhw)

Here are some documentation pages for this project:

* [Installing Tsugi](docs/INSTALL.md)
* [Developing Tsugi Applications](docs/DEVELOP.md)
* [About The Project](docs/ABOUT.md)

If you want to see this code actually working, you can play online:

* https://lti-tools.dr-chuck.com/tsugi/

You can log in to this site and request an account to use with your IMS
LTI compatible LMS.  Once you have a key/secret to use the system, here
is some LTI 1.0 documentation:

* [Configuring LTI 1.0 Launches](docs/LAUNCHING.md)

You may also be interested in the Java version of this library:

* [Java Tsugi web site](http://csev.github.io/tsugi-java/)
* [Java Tsugi API Docs](http://csev.github.io/tsugi-java/apidocs/index.html)

/Chuck

