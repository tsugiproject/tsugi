Database Create / Upgrade Scripts
---------------------------------

This folder contains the scripts that create and upgrade the Tsugi database.
This file is part of the setup and maintenance of a Tsugi server.

Upgrades are run from **Admin → Database Upgrade**, which loads
`admin/lti/database.php` (and other `database.php` files under admin and
`lib/src/Services`).

Historical note: early 2014–2017 migrations were once kept in a separate
`database-pre-2018.php` helper for sites that skipped upgrades for years.
That file has been removed. Modern installs and routinely upgraded sites
only need `database.php` via the admin upgrade console.
