TSUGI - An Standards-Based Learning Tool Framework
==================================================

The overall goal of the TSUGI framework is to make it as simple
as possible to write
IMS Learning Tools Interoperability™ (LTI)™ tools supporting
LTI 1.x (and soon 2.x) and put them
into production.   The framework hides all the detail of the
IMS standards behind an API. The use of this framework does
not automatically imply any type of IMS certification.  Tools and
products that use this framework must still go through the formal
certification process through IMS (www.imsglobal.org).

My overall goal is to create a learning ecosystem that spans all
the LMS systems including Sakai, Moodle, Blackboard, Desire2Learn,
Canvas, Coursera, EdX, NovoEd, and perhaps even Google Classroom.
It is time to move away from the one-off LTI implementations and
move towards a shared hosting container for learning tools.  With the
emergence of IMS standards for Analytics, Gradebook, Roster,
App Store, and a myriad of other services, we cannot afford to
do indepnent implementations for each of these standards.  TSUGI
hopes to provide one sharable implementation of all of these
standards as they are developed, completed, and approved.

In the long run I expect to develop Java, Ruby, and other variants
of TSUGI but I am initially focusing on the PHP version because
it allows me to be most agile as we explore architecure choices
and engages the widest range of software developers.

In the long run, I hope to make this a formal open source project,
but for now it is just my own "Dr. Chuck Labs" effort.
Even in its current form it is very reliable and very scalable
but I am not eager to have too many adoptions because I expect the code
will see several refactor phases as various communities start to
look critically at the code.

There is also a 
[Java version of Tsugi](https://github.com/csev/tsugi-java-servlet) 
that is in development.

\-- Chuck
Fri Jun 12 21:34:55 EDT 2015

Learning Tools Interoperability™ (LTI™) is a
trademark of IMS Global Learning Consortium, Inc. in
the United States and/or other countries. (www.imsglobal.org)


