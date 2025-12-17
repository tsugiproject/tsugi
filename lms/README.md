Tsugi LMS Features
------------------

This folder wll be used to reimplement features from Koseu in the Tsugi tree
without using the Lumen Framework at all - these will just be straight up
PHP.  If you look at the Koseu code, for most of them theu delegate all the UI
markup to Tsugi classes anyways.

For example, the code in:

./vendor/koseu/lib/src/Controllers/Badges.php

Will end up in lms/badges/index.php

We will mount this at /badges using a .htaccess as shown in EXAMPLE_HTaccess.md

