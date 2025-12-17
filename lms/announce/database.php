<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}announcement",
"create table {$CFG->dbprefix}announcement (
    announcement_id      INTEGER NOT NULL AUTO_INCREMENT,
    context_id           INTEGER NOT NULL,
    title                VARCHAR(512) NOT NULL,
    text                 TEXT NOT NULL,
    url                  VARCHAR(2048) NULL,
    user_id              INTEGER NOT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}announcement_pk` PRIMARY KEY (announcement_id),

    CONSTRAINT `{$CFG->dbprefix}announcement_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}announcement_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}announcement_indx_1` ( context_id ),
    INDEX `{$CFG->dbprefix}announcement_indx_2` ( created_at )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}announcement_dismissal",
"create table {$CFG->dbprefix}announcement_dismissal (
    dismissal_id         INTEGER NOT NULL AUTO_INCREMENT,
    announcement_id      INTEGER NOT NULL,
    user_id              INTEGER NOT NULL,
    dismissed_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}announcement_dismissal_pk` PRIMARY KEY (dismissal_id),

    CONSTRAINT `{$CFG->dbprefix}announcement_dismissal_ibfk_1`
        FOREIGN KEY (`announcement_id`)
        REFERENCES `{$CFG->dbprefix}announcement` (`announcement_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}announcement_dismissal_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `{$CFG->dbprefix}announcement_dismissal_unique` (`announcement_id`, `user_id`),

    INDEX `{$CFG->dbprefix}announcement_dismissal_indx_1` ( announcement_id ),
    INDEX `{$CFG->dbprefix}announcement_dismissal_indx_2` ( user_id )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}announcement_dismissal",
"drop table if exists {$CFG->dbprefix}announcement"
);

// No upgrades yet
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;
    return 202501010000;
};
