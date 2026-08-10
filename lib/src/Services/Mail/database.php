<?php

if ( ! isset($CFG) ) exit;

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}mail_ses_events",
"drop table if exists {$CFG->dbprefix}mail_suppress",
"drop table if exists {$CFG->dbprefix}mail_sent",
"drop table if exists {$CFG->dbprefix}mail_bulk");

$DATABASE_INSTALL = array(

array( "{$CFG->dbprefix}mail_bulk",
"create table {$CFG->dbprefix}mail_bulk (
    bulk_id             INTEGER NOT NULL AUTO_INCREMENT,

    user_id             INTEGER NOT NULL,
    context_id          INTEGER NOT NULL,

    subject             VARCHAR(256) NULL,
    body                TEXT NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (bulk_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}mail_sent",
"create table {$CFG->dbprefix}mail_sent (
    sent_id             INTEGER NOT NULL AUTO_INCREMENT,

    context_id          INTEGER NULL,
    link_id             INTEGER NULL,
    bulk_id             INTEGER NULL,

    user_to             INTEGER NULL,
    user_from           INTEGER NULL,

    subject             VARCHAR(256) NULL,
    body                TEXT NULL,
    message_id          VARCHAR(255) NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_2`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_3`
        FOREIGN KEY (`user_to`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_4`
        FOREIGN KEY (`user_from`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_5`
        FOREIGN KEY (`bulk_id`)
        REFERENCES `{$CFG->dbprefix}mail_bulk` (`bulk_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}mail_sent_bulk` (bulk_id),
    INDEX `{$CFG->dbprefix}mail_sent_message` (message_id),
    PRIMARY KEY (sent_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}mail_suppress",
"create table {$CFG->dbprefix}mail_suppress (
    suppress_id         INTEGER NOT NULL AUTO_INCREMENT,

    email               VARCHAR(255) NOT NULL,
    reason              VARCHAR(32) NOT NULL,
    detail              VARCHAR(255) NULL,
    message_id          VARCHAR(255) NULL,

    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    UNIQUE KEY `{$CFG->dbprefix}mail_suppress_email` (email),
    PRIMARY KEY (suppress_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}mail_ses_events",
"create table {$CFG->dbprefix}mail_ses_events (
    event_id            INTEGER NOT NULL AUTO_INCREMENT,

    sns_message_id      VARCHAR(255) NULL,
    ses_message_id      VARCHAR(255) NULL,
    event_type          VARCHAR(32) NOT NULL,
    event_subtype       VARCHAR(64) NULL,
    email               VARCHAR(255) NULL,
    mail_type           VARCHAR(32) NULL,
    action              VARCHAR(32) NOT NULL,
    detail              VARCHAR(255) NULL,
    payload_json        MEDIUMTEXT NULL,

    created_at          DATETIME NOT NULL,

    INDEX `{$CFG->dbprefix}mail_ses_events_created` (created_at),
    INDEX `{$CFG->dbprefix}mail_ses_events_email` (email),
    INDEX `{$CFG->dbprefix}mail_ses_events_type` (event_type),
    INDEX `{$CFG->dbprefix}mail_ses_events_sns` (sns_message_id),
    PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $fields = $PDOX->metadata("{$CFG->dbprefix}mail_suppress");
    if ( $fields === false ) {
        $sql = "create table {$CFG->dbprefix}mail_suppress (
    suppress_id         INTEGER NOT NULL AUTO_INCREMENT,

    email               VARCHAR(255) NOT NULL,
    reason              VARCHAR(32) NOT NULL,
    detail              VARCHAR(255) NULL,
    message_id          VARCHAR(255) NULL,

    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    UNIQUE KEY `{$CFG->dbprefix}mail_suppress_email` (email),
    PRIMARY KEY (suppress_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            echo("Unable to create mail_suppress: ".$q->errorImplode."<br/>\n");
            error_log("Unable to create mail_suppress: ".$q->errorImplode);
        }
    }

    $fields = $PDOX->metadata("{$CFG->dbprefix}mail_ses_events");
    if ( $fields === false ) {
        $sql = "create table {$CFG->dbprefix}mail_ses_events (
    event_id            INTEGER NOT NULL AUTO_INCREMENT,

    sns_message_id      VARCHAR(255) NULL,
    ses_message_id      VARCHAR(255) NULL,
    event_type          VARCHAR(32) NOT NULL,
    event_subtype       VARCHAR(64) NULL,
    email               VARCHAR(255) NULL,
    mail_type           VARCHAR(32) NULL,
    action              VARCHAR(32) NOT NULL,
    detail              VARCHAR(255) NULL,
    payload_json        MEDIUMTEXT NULL,

    created_at          DATETIME NOT NULL,

    INDEX `{$CFG->dbprefix}mail_ses_events_created` (created_at),
    INDEX `{$CFG->dbprefix}mail_ses_events_email` (email),
    INDEX `{$CFG->dbprefix}mail_ses_events_type` (event_type),
    INDEX `{$CFG->dbprefix}mail_ses_events_sns` (sns_message_id),
    PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            echo("Unable to create mail_ses_events: ".$q->errorImplode."<br/>\n");
            error_log("Unable to create mail_ses_events: ".$q->errorImplode);
        }
    }

    if ( $PDOX->columnExists('bulk_id', "{$CFG->dbprefix}mail_sent") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent ADD bulk_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);

        $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
            CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_5`
            FOREIGN KEY (`bulk_id`)
            REFERENCES `{$CFG->dbprefix}mail_bulk` (`bulk_id`)
            ON DELETE SET NULL ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            echo("Non-fatal: mail_sent.bulk_id FK: ".$q->errorImplode."<br/>\n");
            error_log("Non-fatal: mail_sent.bulk_id FK: ".$q->errorImplode);
        }

        $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent ADD INDEX `{$CFG->dbprefix}mail_sent_bulk` (bulk_id)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    // Allow admin/test mail with no course context.
    $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent MODIFY context_id INTEGER NULL";
    echo("Upgrading: ".$sql."<br/>\n");
    error_log("Upgrading: ".$sql);
    $q = $PDOX->queryReturnError($sql);
    if ( ! $q->success ) {
        echo("Non-fatal: mail_sent.context_id NULL: ".$q->errorImplode."<br/>\n");
        error_log("Non-fatal: mail_sent.context_id NULL: ".$q->errorImplode);
    }

    if ( $PDOX->columnExists('message_id', "{$CFG->dbprefix}mail_sent") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent ADD message_id VARCHAR(255) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);

        $sql = "ALTER TABLE {$CFG->dbprefix}mail_sent ADD INDEX `{$CFG->dbprefix}mail_sent_message` (message_id)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    return 202608092030;
}; // Don't forget the semicolon on anonymous functions :)
