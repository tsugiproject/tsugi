<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_user",
"drop table if exists {$CFG->dbprefix}lti_key",
"drop table if exists {$CFG->dbprefix}lti_nonce",
"drop table if exists {$CFG->dbprefix}lti_domain",
"drop table if exists {$CFG->dbprefix}profile"
);

// Note that the TEXT xxx_key fields are UNIQUE but not
// marked as UNIQUE because of MySQL key index length limitations.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_key",
"create table {$CFG->dbprefix}lti_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL UNIQUE,
    key_key             TEXT NOT NULL,

    secret              TEXT NULL,
    new_secret          TEXT NULL,
    ack                 TEXT NULL,

    -- This is the owner of this key - it is not a foreign key
    -- on purpose to avoid potential circular foreign keys
    -- This is null for LTI1 and the user_id for LTI2 keys
    -- In LTI2, key_key is chosen by the TC so we must not allow
    -- One TC to take over another's key_key - this must be
    -- checked carefully in a transaction during LTI 2 registration
    user_id             INTEGER NULL,

    consumer_profile    TEXT NULL,
    new_consumer_profile  TEXT NULL,

    tool_profile    TEXT NULL,
    new_tool_profile  TEXT NULL,

    json                TEXT NULL,
    settings            TEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    UNIQUE(key_sha256),
    PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_context",
"create table {$CFG->dbprefix}lti_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         TEXT NOT NULL,

    key_id              INTEGER NOT NULL,

    title               TEXT NULL,

    json                TEXT NULL,
    settings            TEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, context_sha256),
    PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link",
"create table {$CFG->dbprefix}lti_link (
    link_id             INTEGER NOT NULL AUTO_INCREMENT,
    link_sha256         CHAR(64) NOT NULL,
    link_key            TEXT NOT NULL,

    context_id          INTEGER NOT NULL,

    title               TEXT NULL,

    json                TEXT NULL,
    settings            TEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_link_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_sha256, context_id),
    PRIMARY KEY (link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_user",
"create table {$CFG->dbprefix}lti_user (
    user_id             INTEGER NOT NULL AUTO_INCREMENT,
    user_sha256         CHAR(64) NOT NULL,
    user_key            TEXT NOT NULL,

    key_id              INTEGER NOT NULL,
    profile_id          INTEGER NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,

    json                TEXT NULL,
    login_at            DATETIME NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_user_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, user_sha256),
    PRIMARY KEY (user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_membership",
"create table {$CFG->dbprefix}lti_membership (
    membership_id       INTEGER NOT NULL AUTO_INCREMENT,

    context_id          INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,

    role                SMALLINT NULL,
    role_override       SMALLINT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(context_id, user_id),
    PRIMARY KEY (membership_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_service",
"create table {$CFG->dbprefix}lti_service (
    service_id          INTEGER NOT NULL AUTO_INCREMENT,
    service_sha256      CHAR(64) NOT NULL,
    service_key         TEXT NOT NULL,

    key_id              INTEGER NOT NULL,

    format              VARCHAR(1024) NULL,

    json                TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_service_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, service_sha256),
    PRIMARY KEY (service_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// service_id/sourcedid are for LTI 1.x
// result_url is for LTI 2.x
// Sometimes we might get both
array( "{$CFG->dbprefix}lti_result",
"create table {$CFG->dbprefix}lti_result (
    result_id          INTEGER NOT NULL AUTO_INCREMENT,
    link_id            INTEGER NOT NULL,
    user_id            INTEGER NOT NULL,

    result_url         TEXT NULL,

    sourcedid          TEXT NULL,
    service_id         INTEGER NULL,

    grade              FLOAT NULL,
    note               TEXT NULL,
    server_grade       FLOAT NULL,

    json               TEXT NULL,
    entity_version     INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT 0,
    retrieved_at       DATETIME NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_3`
        FOREIGN KEY (`service_id`)
        REFERENCES `{$CFG->dbprefix}lti_service` (`service_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Note service_id is not part of the key on purpose
    -- It is data that can change and can be null in LTI 2.0
    UNIQUE(link_id, user_id),
    PRIMARY KEY (result_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Nonce is not connected using foreign key for performance
// and because it is effectively just a temporary cache
array( "{$CFG->dbprefix}lti_nonce",
"create table {$CFG->dbprefix}lti_nonce (
    nonce          CHAR(128) NOT NULL,
    key_id         INTEGER NOT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `{$CFG->dbprefix}nonce_indx_1` USING HASH (`nonce`),
    UNIQUE(key_id, nonce)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_domain",
"create table {$CFG->dbprefix}lti_domain (
    key_id      INTEGER NOT NULL,
    context_id  INTEGER NULL,
    domain      VARCHAR(128),
    port        INTEGER NULL,
    consumer_key  TEXT,
    secret      TEXT,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_2`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, context_id, domain, port)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Profile is denormalized and not tightly connected to allow
// for disconnecting and reconnecting various user_id values
array( "{$CFG->dbprefix}profile",
"create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    profile_key         TEXT NOT NULL,

    key_id              INTEGER NOT NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,

    json                TEXT NULL,
    login_at            DATETIME NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT 0,

    UNIQUE(profile_id, profile_sha256),
    PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

// Called after a table has been created...
$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;

    if ( $table == "{$CFG->dbprefix}lti_key") {
        $sql= "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key, secret) values
            ( '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', '12345', 'secret')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);

        // Key is null for the google key - no direct launches or logins allowed
        $sql = "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key) values
            ( 'd4c9d9027326271a89ce51fcaf328ed673f17be33469ff979e8ab8dd501e664f', 'google.com')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);
    }

    if ( $table == "{$CFG->dbprefix}lti_nonce") {
        $sql = "CREATE EVENT IF NOT EXISTS {$CFG->dbprefix}lti_nonce_auto
            ON SCHEDULE EVERY 1 HOUR DO
            DELETE FROM {$CFG->dbprefix}lti_nonce WHERE created_at < (UNIX_TIMESTAMP() - 3600)";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

};

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Version 2014041200 improvements
    if ( $oldversion < 2014041200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_membership ADD role_override SMALLINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 2014041300 improvements
    if ( $oldversion < 2014041300 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD subscribe SMALLINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}profile ADD subscribe SMALLINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 2014042100 improvements
    if ( $oldversion < 2014042100 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD server_grade FLOAT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD retrieved_at DATETIME NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 2014050500 improvements
    if ( $oldversion < 2014050500 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD user_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 2014072600 improvements
    if ( $oldversion < 2014072600 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD settings TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD settings TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD settings TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    if ( $oldversion < 201408050800 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD new_secret VARCHAR(4016) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD consumer_profile TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD new_consumer_profile TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add fields to line up with SPV's tables as much as possible
    if ( $oldversion < 201408230900 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD new_tool_profile TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD tool_profile TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 201408240800 improvements
    if ( $oldversion < 201408240800 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_nonce ADD key_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 201409241700 improvements
    if ( $oldversion < 201409241700 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD settings_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD settings_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD settings_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD result_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 201409242100 improvements
    if ( $oldversion < 201409242100 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result MODIFY sourcedid TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result DROP sourcedid_sha256";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201411222200 improvements
    if ( $oldversion < 201411222200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD ack TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201505222100 improvements
    if ( $oldversion < 201505222100 ) {
        $tables = array('lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_nonce', 'lti_membership', 'lti_service', 
            'lti_result', 'profile');
        foreach ( $tables as $table ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD entity_version INTEGER NOT NULL DEFAULT 0";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
        }
    }

    // When you increase this number in any database.php file,
    // make sure to update the global value in setup.php
    return 201505222100;

}; // Don't forget the semicolon on anonymous functions :)

