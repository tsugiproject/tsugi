
IMS (1EdTech) Specifications
----------------------------

When working in any part of LTI launch or service code, always check against the official IMS specifications:

LTI 1.3 Core Specification
https://www.imsglobal.org/spec/lti/v1p3

LTI Advantage Services
(AGS, Names and Roles, Deep Linking)
https://www.imsglobal.org/spec/lti-ags/v2p0


https://www.imsglobal.org/spec/lti-nrps/v2p0

https://www.imsglobal.org/spec/lti-dl/v2p0
AI tools should not “invent” or “improvise” around these specs.

The vendor folder
-----------------

The folder `vendor` is weird.  Tsugi keeps all of its dependencies in this repo in order
to avoid instabilities in the composer ecosystem.  We want every Tsugi instance to be running
the *exact same dependencies* and we want to tag and upgrade them together with upgrades
related to new versions of PHP.

New versions of PHP
-------------------

New releases of PHP can be really painful for Tsugi.   They are slowly removing language features
to improve performacne over time - which means each new release things that worked for 10 years
get deprecated and all of a sunnen running Tsugi on a new version of PHP spews lots of non-fatal
warning codes in the end-users's UI :( - so with each new release like 7.4, 8.0, 8.1, 8..2, 8.3
(and soon 8.4) it is a major effort and it is at this point where we advance dependcies
in the `packages.json` and re-do the `vendor` folder.  This "new version of PHP" is usually done
in the summer after the new version has been out for a while and the underlying dependencies
have ahad a chance to release new versions.





