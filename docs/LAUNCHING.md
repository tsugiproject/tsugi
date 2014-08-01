Using these tools from an LTI Consumer
======================================

Depending on the administrator of the Tsugi server, you may
be able to get an LTI key and secret for the use of the tools
on the server.  Tsugi has a capability to let you log in
and request keys that can be enabled by the sytem owner
using the following options in config.php:

    $CFG->ownername = 'Charles Severance';
    $CFG->owneremail = 'csev@umich.edu';
    $CFG->providekeys = true;

While we are working on full support for LTI 2.0, for now
all launches are for LTI 1.0.  Here are some of the tools
that you can launch (substitute the first part of the
URL as appropriate.

You can plug these into your LMS or test using any LTI
Consumer harness such as the one I use at:

https://online.dr-chuck.com/sakai-api-test/lms.php

Here is a list of the tools installed in a server by default.

Class Map
---------

This is a simple map where students can place themselves and
decide how much data they want to reveal. Its launch url is of
the form:

    https://lti-tools.dr-chuck.com/tsugi/mod/map/index.php

Attendance Tool
---------------

This is a simple tool where the teacher sets a code and the students
need to enter the code.  The teacher can see who entered the code,
when it was entered, and what IP was used to enter the code.
This is really just a starting point.

    https://lti-tools.dr-chuck.com/tsugi/attend/map/index.php

Rock Paper Scissors
-------------------

This is a simple multi-player game of RPS.  My main goal of this
is to demonstrate transactions in sample code.  But it is a bit
of fun and has a little leaderboard.

    https://lti-tools.dr-chuck.com/tsugi/attend/map/index.php

I also used this as part of my sample Android mobile application.
but that is another story.

Social Peer Grader
------------------

This is my very simple social peer grader that I use for low-stakes assessment
in my on-campus and Coursera classes.   It is generally a few images and/or a bit
of text with a simple grading rubric.  Here is the URL for the peer grading:

    https://lti-tools.dr-chuck.com/tsugi/mod/peer-grade/index.php

Make sure to log in first as the instructor and configure the peer-grader and
save the configuration.  Otherwise students will see the "not yet configured"
message.  The default configuration is a blob of JSON that is OK to use use as
is for testing.   It asks for two images and some text.   It requires two other
peer-evals for two points and your peers give you from 0-6 points.  It is hard
to see it in action unless you make a few student acounts.

Optional custom parameters include:

    done=_close

When this is present, the tool will display a "Done" button that closes
the window.  Use this if you put this in a popup window by itself.

Python Autograder
-----------------

This is the autograder associated with my Coursera
Programming for Everybody and Python for Informatics text book.
This tool launches with the following URL:

    https://lti-tools.dr-chuck.com/tsugi/mod/pythonauto/auto.php
    https://lti-tools.dr-chuck.com/tsugi/mod/pythonauto/auto.php?exercise=hello

With no exercise it is an interative Python coding environment
where you can test out code.  You can also specify an exercise which will
be automatically graded and the grades will be sent back to the LMS
via LTI when you properly complete the exercise.

Valid exercises include: hello, fopen, loop, 2.2, 2.3, 3.1, 3.3, 4.6,
5.2, 6.5, 7.1, 7.2, 8.4, 9.4, and 10.2.

Optional custom parameters include:

    done=_close

When this is present, the tool will display a "Done" button that closes
the window.  Use this if you put this in a popup window by itself.

    due=2014-01-27
    penalty_time=34560
    penalty_cost=0.2
    timezone=Pacific/Honolulu

These three options set a due date, timezone, penalty period (in seconds) and
cost per period.  These are optional.   Other than the due date, values above
are the defaults if there is a due date specified.  The due= value is in any
format that PHP strtotime() parses - the best trick is to use the ISO 8601 date
as shown above.

Gradebook Tool
--------------

After you have a bunch of graded items in the external server you
might want to let the student see the current start of the grades
hosted on the LTI server.  Also you might want your teaching assistants
to be able to go through the student grade books and see what is going
on.  This only sees the grades on the LTI server in a class across
all the links in the class.

It also double checks to see if the LMS sense of the grade is the
same as the LTI server and fixes errors in the LMS grade automatially.
In effect it quietly repairs lost grades in the LMS when the student
checks.

    https://lti-tools.dr-chuck.com/tsugi/grades/map/index.php

Summary
=======

Note that if you play with the developer interface, some of the
sample bits are broken on purpose.   They are their to be fixed as
training exercises in a Tsugi boot camp or class.

As you can see, this would greatly benefit from LTI 2.0 - because
you would never look at a page like this with this nasty plumbing
details.  But that takes time - but I should be working on it soon.

As this is emergent software, comments, suggestions, and pull
requests are always welcome.

\-- Chuck
Fri Aug  1 12:44:49 EDT 2014

Learning Tools Interoperability™ (LTI™) is a
trademark of IMS Global Learning Consortium, Inc. in
the United States and/or other countries. (www.imsglobal.org)


