<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lms_tools",
"drop table if exists {$CFG->dbprefix}lms_tools_status"
);

/*
            "clone_url": "https:\/\/github.com\/tsugiproject\/tsugi.git",
            "html_url": "https:\/\/github.com\/tsugiproject\/tsugi.git",
            "name": "Tsugi Admin",
            "description": "Tsugi Adminstration, Management, and Development Console.",
            "writeable": true,
            "update_note": "Fetching origin\n",
            "status_note": "On branch master\nYour branch is up-to-date with 'origin\/master'.\nnothing to commit (use -u to show untracked files)\n",
            "updates": false,
            "tsugitools": false,
            "index": 1,
            "path": "\/Applications\/MAMP\/htdocs\/tsugi",
            "guid": "817a628fa3231ccba37d5a061620708a"
*/

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lms_tools",
"create table {$CFG->dbprefix}lms_tools (
    tool_id             INTEGER NOT NULL AUTO_INCREMENT,
    toolpath            VARCHAR(128) NOT NULL UNIQUE,
    name                TEXT NOT NULL,
    description         TEXT NOT NULL,
    clone_url           TEXT NOT NULL,
    gitversion          VARCHAR(1024) NULL,

    rank                INTEGER NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    json                MEDIUMTEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    PRIMARY KEY (tool_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lms_tools_status",
"create table {$CFG->dbprefix}lms_tools_status (
    tool_id             INTEGER NOT NULL,
    ipaddr              VARCHAR(64),
    status_note         TEXT NULL,

    json                MEDIUMTEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lms_tools_status_ibfk_1`
        FOREIGN KEY (`tool_id`)
        REFERENCES `{$CFG->dbprefix}lms_tools` (`tool_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(tool_id, ipaddr)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    return 201804291011;

}; // Don't forget the semicolon on anonymous functions :)

