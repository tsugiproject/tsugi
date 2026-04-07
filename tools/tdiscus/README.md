TDISCUS
-------

This is a Tsugi Threaded Discussion Tool.  It is intended to be a
flexible backend with at least one UI but ready to support many
different UI's.

Installation
------------

You can install this as a module using the administration interface
on your tsugi server, or just check it out into your `mod` folder.

Of course once you check it out, run the database upgrade from the admin
UI or from the command line:

    cd tsugi/admin
    php upgrade.php

At this point it is a launchable tool and you can play with it in in the
test environment like

https://www.wa4e.com/tsugi/store/

This test environment is great as it allows you to see both the instructor and
several student views with test data all setup.

Use in lessons.json
-------------------

You can add discussion entries into your `lessons.json`.  You can add
course-wide discussions at the top-level in the JSON:

    "discussions" : [
        {
        "title" : "Welcome to Python for Everybody",
        "launch" : "mod/tdiscus/",
        "resource_link_id": "discussion_welcome"
        }
    ],
    "modules": [
        ...

The format of a discussions entry is the same as an `lti` entry.

You can add one or more discussions to a `module` entry at the same level
as an `lti` entry and using the same format.  Of course make sure to keep
`resource_link_id` values unique across the whole file.

    "discussions" : [
        {
        "title" : "Hello World module",
        "launch" : "mod/tdiscus/",
        "resource_link_id": "discuss_01_hello"
        }
    ],
    "lti" : [
        ...

Enabling tdiscus in config.php
------------------------------

You need to enable discussions in your `tsugi/config.php` by adding the following line:

    $CFG->tdiscus = true;

This configuration is needed to keep Tsugi from having to look through the `mod` folder to see
if `tdisucs` is installed.

Course-wide link to all discussions
-----------------------------------

If you are running a site with `lessons.json` you are using Koseu to make the
lessons UI appear under the `/lessons` URL.   There is a course wide disucssions
tool automatically available at the `/discussions` URL.  For example:

https://www.py4e.com/discussions

This tool works whether or not the session is logged in.  If the user is not
logged in the sessions are shown but cannot be entered.

History
-------

What's in a name?  The https://www.tdiscus.com/ domain was available
when the project was started back in 2017.  

This project was in `tsugicontrib` from 2017 through 2020 with a data
model and minimal experimental JSON + Handlebars UI prototype.  Over
the holiday break 2020, the project went from prototype to MVP in
six days.  This is nice because in many ways a threaded discussion
tool was the last big gap in Tsugi covering all the core use cases
for an LMS.

TODO
----

This is a very early product.  Its MVP UI is very much modelled after
the Coursera threaded disussion tool because its features have a nod
towards the scalable / MOOCs use cases.

The goals of the data model are pretty lofty - the idea is to support
a wide range of UI's and pedagogies.  If you look at the Settings in the
tool you will see a lot of checkboxes - there will be a lot more by the time
it is done.

Features yet to be added:

* The ability to get email updates when a comment is added to a thread.

* Image uploading into thread text.  I added a feature to Tsugi's blob storage to
improve the ability to delete unreferences images from the Tsugi single-instance blob store.
I want to make sure this works really well before I let thread creators upload pictures.

* Rich text editor for comments.  Right now for simplicity the current version uses
server-based HTML cleaining using the http://htmlpurifier.org/ library, and does
cleaning as data is presented.  To avoid performance problems we don't enable the
RTE for comments.  But this could be done in-bound without too much effort.  There
are already `cleaned` fields in the `tdiscus_thread` and `tdiscus_comment` tables
to allow for a flexible evolution on this front.

* Allow for version tracking and student editing of the thread text.  This
is a way to meet some Piazza-unique use cases which will likely be useful
in general for various pedagogies.  The verion tracking will be done in a general
way and at some point version trcking will be added to the CKEditor tool.

In the long term there is a dream of a Piazza-style UI and Slack-style UI
using this back-end - but that will require some UI / UI design talent that
I do not posess.

References
----------

http://htmlpurifier.org/

https://stackoverflow.com/questions/192220/what-is-the-most-efficient-elegant-way-to-parse-a-flat-table-into-a-tree/192462#192462

https://www.slideshare.net/billkarwin/models-for-hierarchical-data

https://stackoverflow.com/questions/8252323/mysql-closure-table-hierarchical-database-how-to-pull-information-out-in-the-c



