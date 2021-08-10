Database Create / Upgrade Scritps
---------------------------------

This folder contains the scripts that create and upgrade the Tsugi database.
This file is part of the setup and maintenance of a Tsugi server.

The Tsugi project started in 2014 and in 2019, in order to simplify the
`database.php` file the early database migrations from 2014-2017 were
removed from the `database.php` file and kept in 
the `database-pre-2018-conversion.php` file.  One problem was that the early migrations
were adding some LTI 1.3 fields that the later migrations were deleting.

If your Tsugi database was created before 2017 and you have been running upgrades at least once per year,
the removal of the 2014-2017 migrations did not affect you. All the necessary pre-2018
conversions were run well before they were removed from `database.php` is 2019.

But if you installed a server before 2018, and never upgraded the server and never ran the database
upgrade script and then sometime after June 2019 you want to upgrade that system the conversions
will be missing.  The process to run these old conversions is simple:

    cd admin/lti
    # Optional
    php database.php

    php database-pre-2018.php
    php database.php

This works even if you run the `database.php` and some conversions are already done.  All you need
is to run the older conversions and then re-run the modern conversions.

You should only need to do this once per database.
