
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

    http://gift2qti.dr-chuck.com/

This is an early version and only supports multipla choice, true/false, and essay
question types.  Other types wont' be hard - but I ran out of time.

Comments (and Pull requests) welcome.

-- Chuck
Wed Jan  7 19:10:57 EST 2015
