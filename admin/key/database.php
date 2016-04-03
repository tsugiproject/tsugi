<?php

if ( ! isset($CFG) ) {
    die("This file is not supposed to be accessed directly.  It is activated using
        the 'Admin' feature from the main page of the application.");
}

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}key_request"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}key_request",
"create table {$CFG->dbprefix}key_request (
    request_id          INTEGER NOT NULL AUTO_INCREMENT,

    user_id             INTEGER NOT NULL,

    title               VARCHAR(512) NOT NULL,
    notes               TEXT NULL,
    admin               TEXT NULL,

    state               SMALLINT NULL,

    lti                 TINYINT NULL,

    json                TEXT NULL,

    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}key_request_fk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (request_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")

);

// Called after a table has been created...
$DATABASE_POST_CREATE = false;

// Called to check if upgrades are needed
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG;

    return 2014042200;  // First version where we existed
}; // Don't forget the semicolon on anonymous functions :)

