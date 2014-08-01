Coding Standards for TSUGI
==========================

This document describes the coding standards for the PHP
version of TSUGI.  Here are the sources for inspiration
for the style we use in TSUGI:

* As we are hoping to be an widely-used framework and some of
the tools and capabilities will be using other frameworks like
Symfony, we follow the recommendations in the
PHP Framework Interoperability Group available
at http://www.php-fig.org/

* Where PHP-FIG does not apply we take our inspiration from
Moodle www.moodle.org - we are not trying to be 100% compatible with
Moodle but to use the design patterns as inspiration for our design.
Some areas where we follow Moodle closely are in directory and file
naming conventions. We also follow the Moodle data structure
patterns for courses and users where possible.   We follow many of the
Moodle approaches for HTML generation as well as use library code
to generate tables and simple CRUD screens in a common way where
possible.

* We depart from Moodle in several important areas where it seems to me
that Moodle has a legacy compatibility requirement holding its
architecture back or making it unnecessarily complex in certain areas.

    * We only support MySQL / MariaDB for our relational database
    and we only use PDO
    * We use library code to encapsulate certain common tasks like
    loading extended user info - but we do not abstract database access
    since we have no need for portability outside of MySQL
    * We assume JQuery and Bootstrap for UI companents and support
    no other UI themeing approaches
    * We do not abstract HTML form generation and form processing. If
    we were to build library code to support forms, it would not look
    like Moodle.

* In terms of database design, we have a requirement of fully-connected
data models using MySQL foreign key declarations as follows:

    CONSTRAINT \`{$CFG->dbprefix}peer_assn_ibfk_1\`
    FOREIGN KEY (\`link_id\`)
    REFERENCES \`{$CFG->dbprefix}lti_link\` (\`link_id\`)
    ON DELETE CASCADE ON UPDATE CASCADE,

Generally every table must link to one of the core lti tables with an
explicit foreign key.  The typical tables might be `lti_key`, `lti_context`,
`lti_user`, or `lti_link`.  The preferred approach is to use the `CASCADE`
option as this allows easy deletion or disabling of a user, course, or even
tenant with a simple statement.

In terms of capitalization, I struggled with the right approach
and you can see in the early commit history that I changed my mind
several times.
Classic PHP global functions and Moodle tend to use *Snake Case* for
methods, while PHP-FIG and newer PHP capabilities like PDO tend to
use *Camel Case* for methods.  Given that I see TSUGI implemented in
other languages like Java I figured to simply use Camel Case
across the board.  Also for
purposes of capitalization, acronyms like URL, JSON, TCP are all
treated as "words" - so the proper capitalization in Camel Case for
these is "getUrl", "getJson", and "getTcp".

These are early days for the project and we are still small enough that
a refactor can easily be done.  And in particular with only one developer
in the project so far - I am sure that mistakes have been made and I am
looking forward to other people looking at these decisions, commenting on
these decisions and then pushing TSUGI in different directions as the
project goes forward towards more broad adoption.

While the coding style has not yet ben reviewed by someone senior
in the Moodle community - I am looking forward to that review and expect
TSUGI to be changed as a result of that review.  I want this to be a
collective project as it goes forward and moves from an experiment by
one person to a learning ecosystem.

Chuck
Sun Jun  8 11:44:56 EDT 2014



