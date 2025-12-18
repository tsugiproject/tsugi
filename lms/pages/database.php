<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}pages",
"create table {$CFG->dbprefix}pages (
    page_id                INTEGER NOT NULL AUTO_INCREMENT,
    context_id             INTEGER NOT NULL,
    title                  VARCHAR(512) NOT NULL,
    logical_key            VARCHAR(99) NOT NULL,
    body                   TEXT NOT NULL,
    published              TINYINT(1) NOT NULL DEFAULT 0,
    is_main                TINYINT(1) NOT NULL DEFAULT 0,
    user_id                INTEGER NOT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}pages_pk` PRIMARY KEY (page_id),

    CONSTRAINT `{$CFG->dbprefix}pages_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}pages_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `{$CFG->dbprefix}pages_unique` (`context_id`, `logical_key`),

    INDEX `{$CFG->dbprefix}pages_indx_1` ( context_id ),
    INDEX `{$CFG->dbprefix}pages_indx_2` ( logical_key ),
    INDEX `{$CFG->dbprefix}pages_indx_3` ( published ),
    INDEX `{$CFG->dbprefix}pages_indx_4` ( is_main )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}pages"
);

// No upgrades yet
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;
    return 202501010000;
};
