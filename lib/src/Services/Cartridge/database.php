<?php

if ( ! isset($CFG) ) exit;

// Common Cartridge import: one run, append-only log lines, and durable
// per-course objects used to detect duplicate / copy / new on the next import.
// Duplicate is origin + fingerprint, not identifier alone. cc_object is not
// owned by one cc_import row (import_id is last-run, ON DELETE SET NULL).

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}cc_import",
"create table {$CFG->dbprefix}cc_import (
    import_id              INTEGER NOT NULL AUTO_INCREMENT,
    context_id             INTEGER NOT NULL,
    user_id                INTEGER NULL,
    filename               VARCHAR(512) NULL,
    title                  TEXT NULL,
    manifest_identifier    VARCHAR(255) NULL,
    cc_version             VARCHAR(16) NULL,
    zip_sha256             CHAR(64) NULL,
    status                 VARCHAR(16) NOT NULL DEFAULT 'running',
    created_count          INTEGER NOT NULL DEFAULT 0,
    duplicate_count        INTEGER NOT NULL DEFAULT 0,
    copy_count             INTEGER NOT NULL DEFAULT 0,
    error_count            INTEGER NOT NULL DEFAULT 0,
    json                   MEDIUMTEXT NULL,
    started_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at            TIMESTAMP NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}cc_import_pk` PRIMARY KEY (import_id),

    CONSTRAINT `{$CFG->dbprefix}cc_import_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}cc_import_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}cc_import_indx_1` ( context_id ),
    INDEX `{$CFG->dbprefix}cc_import_indx_2` ( context_id, zip_sha256 )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}cc_object",
"create table {$CFG->dbprefix}cc_object (
    object_id              INTEGER NOT NULL AUTO_INCREMENT,
    context_id             INTEGER NOT NULL,
    import_id              INTEGER NULL,
    resource_identifier    VARCHAR(255) NOT NULL,
    item_identifier        VARCHAR(255) NULL,
    resource_type          VARCHAR(128) NOT NULL,
    identifiers            TEXT NULL,
    local_kind             VARCHAR(32) NULL,
    local_id               INTEGER NULL,
    local_key              VARCHAR(512) NULL,
    content_hash           CHAR(64) NOT NULL,
    diverged               TINYINT(1) NOT NULL DEFAULT 0,
    json                   TEXT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}cc_object_pk` PRIMARY KEY (object_id),

    CONSTRAINT `{$CFG->dbprefix}cc_object_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}cc_object_ibfk_2`
        FOREIGN KEY (`import_id`)
        REFERENCES `{$CFG->dbprefix}cc_import` (`import_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}cc_object_indx_1` ( context_id, resource_identifier, resource_type ),
    INDEX `{$CFG->dbprefix}cc_object_indx_2` ( context_id, diverged ),
    INDEX `{$CFG->dbprefix}cc_object_indx_3` ( import_id )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}cc_import_log",
"create table {$CFG->dbprefix}cc_import_log (
    log_id                 INTEGER NOT NULL AUTO_INCREMENT,
    import_id              INTEGER NOT NULL,
    resource_identifier    VARCHAR(255) NULL,
    item_identifier        VARCHAR(255) NULL,
    resource_type          VARCHAR(128) NULL,
    title                  VARCHAR(512) NULL,
    action                 VARCHAR(32) NOT NULL,
    local_kind             VARCHAR(32) NULL,
    local_id               INTEGER NULL,
    object_id              INTEGER NULL,
    message                TEXT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}cc_import_log_pk` PRIMARY KEY (log_id),

    CONSTRAINT `{$CFG->dbprefix}cc_import_log_ibfk_1`
        FOREIGN KEY (`import_id`)
        REFERENCES `{$CFG->dbprefix}cc_import` (`import_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}cc_import_log_ibfk_2`
        FOREIGN KEY (`object_id`)
        REFERENCES `{$CFG->dbprefix}cc_object` (`object_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}cc_import_log_indx_1` ( import_id ),
    INDEX `{$CFG->dbprefix}cc_import_log_indx_2` ( action )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}cc_import_log",
"drop table if exists {$CFG->dbprefix}cc_object",
"drop table if exists {$CFG->dbprefix}cc_import"
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;
    // Web-link URLs as local_key can exceed the original 128.
    $PDOX->queryReturnError(
        "ALTER TABLE {$CFG->dbprefix}cc_object MODIFY local_key VARCHAR(512) NULL",
        false,
        false
    );
    return $oldversion;
};
