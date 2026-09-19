<?php

if ( ! isset($CFG) ) exit;

// Singleton row (site_id = 1): installation-wide config. body is the public
// landing page HTML; empty/NULL means index.php shows its built-in copy.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}site",
"create table {$CFG->dbprefix}site (
    site_id              INTEGER NOT NULL,
    body                 MEDIUMTEXT NULL,
    json                 TEXT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}site_pk` PRIMARY KEY (site_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}site"
);

$DATABASE_UPGRADE = function($oldversion) {
    // No ALTERs yet. Keep the version assigned on table create ($CFG->dbversion).
    return $oldversion;
};
