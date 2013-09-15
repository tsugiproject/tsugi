webauto
=======

This is my automatic grading program for web sites

For now this is simply prototype code that I am putting together - 
it is not ready to use at all.  This is under construction.   If 
you want to see an autograder actually working, you can look at my 
Pyrthon autograder:

* https://lti-tools.dr-chuck.com/pythonauto/
* https://lti-tools.dr-chuck.com/pythonauto/lms.php
* https://github.com/csev/pythonauto

This will be built using two basic technologies: (1) IMS Learning Tools
Interoperability for the LMS integration and grade flow and (2) Goutte
to do the actual unit tests to evaluate the sites and compute the grades.
Here are some relevant URLs:

IMS Learning Tools Interoperability:

* http://www.imsglobal.org/
* http://www.imsglobal.org/lti/
* http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html
* http://developers.imsglobal.org/
* https://vimeo.com/34168694

Goutte:

* https://github.com/fabpot/Goutte

As part of this code development, I am re-working the IMS LTI code 
to be (a) scalable, multi-tenant-aware, and more elegant and (b) 
start laying the ground work for supporting IMS LTI 2.0
when it comes out.

