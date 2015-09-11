
GIFT Quiz Tool
==============

This tool currently contains two pieces of related functionality:

* It can give GIFT-authored quizzes using LTI (index.php)
* It can convert to QTI 1.2 (convert.php)

These two pieces are related because they share a bunch of library code.

Quiz format convertor from GIFT to QTI 1.2
==========================================

This is a simple converter that lets you paste in GIFT formatted quiz questions like:


    // multiple choice with specified feedback for right and wrong answers
    ::Q2:: What's between orange and green in the spectrum? 
    { =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

And get them downloaded as QTI 1.2 for import into lots of systems like Sakai and 
Canvas.

GIFT seems to be a micro-format invented by the Moodle community - and a pretty cool 
idea if I do say so myself.  I like it because I can put quizzes in GitHub :)

    https://docs.moodle.org/28/en/GIFT_format

You can play with my demo server at:

    http://lti-tools.dr-chuck.com/tsugi/mod/gift/convert.php

This is an early version and only supports multipla choice, true/false, and essay
question types.  Other types wont' be hard - but I ran out of time.

Comments (and Pull requests) welcome.

-- Chuck
Fri Sep 11 10:45:45 EDT 2015
