<?php

if ( ! isset($CFG) ) exit;

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
    published            TINYINT NOT NULL DEFAULT 1,
    publish_at           DATETIME NULL DEFAULT NULL,
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

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Add published and publish_at columns for draft/scheduled publish feature
    if ( ! $PDOX->columnExists('published', "{$CFG->dbprefix}announcement") ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}announcement ADD COLUMN published TINYINT NOT NULL DEFAULT 1";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryDie($sql);
    }
    if ( ! $PDOX->columnExists('publish_at', "{$CFG->dbprefix}announcement") ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}announcement ADD COLUMN publish_at DATETIME NULL DEFAULT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryDie($sql);
    }

    return 202503190000;
};
