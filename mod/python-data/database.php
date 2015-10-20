<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}pydata_geo"
);

// The SQL to create the tables if they don't exist
// This table is really a cache so we don't crush
// Google's rate limit - so there is no link_id
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}pydata_geo",
"create table {$CFG->dbprefix}pydata_geo (
    geo_sha256          CHAR(64) NOT NULL UNIQUE,
    geo_key             TEXT NOT NULL,
    json_content        TEXT,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

