<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_link_activity",
"drop table if exists {$CFG->dbprefix}lti_link_user_activity",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_user",
"drop table if exists {$CFG->dbprefix}lti_key",
"drop table if exists {$CFG->dbprefix}lti_nonce",
"drop table if exists {$CFG->dbprefix}lti_domain",
"drop table if exists {$CFG->dbprefix}lti_event",
"drop table if exists {$CFG->dbprefix}cal_event",
"drop table if exists {$CFG->dbprefix}cal_key",
"drop table if exists {$CFG->dbprefix}cal_context",
"drop table if exists {$CFG->dbprefix}tsugi_string",
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
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

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

    consumer_profile    MEDIUMTEXT NULL,
    new_consumer_profile  MEDIUMTEXT NULL,

    tool_profile    MEDIUMTEXT NULL,
    new_tool_profile  MEDIUMTEXT NULL,

    caliper_url         TEXT NULL,
    caliper_key         TEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    UNIQUE(key_sha256),
    PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_user",
"create table {$CFG->dbprefix}lti_user (
    user_id             INTEGER NOT NULL AUTO_INCREMENT,
    user_sha256         CHAR(64) NOT NULL,
    user_key            TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,
    profile_id          INTEGER NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    locale              CHAR(63) NULL,
    image               TEXT NULL,
    subscribe           SMALLINT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    -- Google classroom token for this user
    gc_token            TEXT NULL,

    ipaddr              VARCHAR(64),
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_user_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, user_sha256),
    PRIMARY KEY (user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_context",
"create table {$CFG->dbprefix}lti_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    secret              VARCHAR(128) NULL,
    gc_secret           VARCHAR(128) NULL,

    key_id              INTEGER NOT NULL,

    -- If this course was created by a user within a key
    -- For example Google Glassroom - or an ad-hoc group
    user_id             INTEGER NULL,

    path                TEXT NULL,

    title               TEXT NULL,

    lessons             MEDIUMTEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    ext_memberships_id  TEXT NULL,
    ext_memberships_url TEXT NULL,
    memberships_url     TEXT NULL,
    lineitems_url       TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, context_sha256),
    PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link",
"create table {$CFG->dbprefix}lti_link (
    link_id             INTEGER NOT NULL AUTO_INCREMENT,
    link_sha256         CHAR(64) NOT NULL,
    link_key            TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    context_id          INTEGER NOT NULL,

    path                TEXT NULL,

    title               TEXT NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,

    placementsecret     VARCHAR(64) NULL,
    oldplacementsecret  VARCHAR(64) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_link_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_sha256, context_id),
    PRIMARY KEY (link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link_activity",
"create table {$CFG->dbprefix}lti_link_activity (
    link_id             INTEGER NOT NULL,
    event               INTEGER NOT NULL,

    link_count          INTEGER UNSIGNED NOT NULL DEFAULT 0,
    activity            VARBINARY(1024) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_link_activity_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (link_id,event)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),


array( "{$CFG->dbprefix}lti_membership",
"create table {$CFG->dbprefix}lti_membership (
    membership_id       INTEGER NOT NULL AUTO_INCREMENT,

    context_id          INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,

    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    role                SMALLINT NULL,
    role_override       SMALLINT NULL,

    json                MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

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

// TODO: Remove this once everyone converts to cal_event
// "FIFO" buffer of events no explicit foreign key
// relationships as these are short-lived records
array( "{$CFG->dbprefix}lti_event",
"create table {$CFG->dbprefix}lti_event (
    event_id        INTEGER NOT NULL AUTO_INCREMENT,
    event           INTEGER NOT NULL,

    state           SMALLINT NULL,

    link_id         INTEGER NULL,
    key_id          INTEGER NULL,
    context_id      INTEGER NULL,
    user_id         INTEGER NULL,

    nonce           BINARY(16) NULL,
    launch          MEDIUMTEXT NULL,
    json            MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link_user_activity",
"create table {$CFG->dbprefix}lti_link_user_activity (
    link_id             INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    event               INTEGER NOT NULL,

    link_user_count     INTEGER UNSIGNED NOT NULL DEFAULT 0,
    activity            VARBINARY(1024) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (link_id, user_id, event)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_service",
"create table {$CFG->dbprefix}lti_service (
    service_id          INTEGER NOT NULL AUTO_INCREMENT,
    service_sha256      CHAR(64) NOT NULL,
    service_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    format              VARCHAR(1024) NULL,

    json                MEDIUMTEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

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
    deleted            TINYINT(1) NOT NULL DEFAULT 0,

    result_url         TEXT NULL,

    sourcedid          TEXT NULL,
    service_id         INTEGER NULL,
    gc_submit_id       TEXT NULL,

    ipaddr             VARCHAR(64),

    grade              FLOAT NULL,
    note               MEDIUMTEXT NULL,
    server_grade       FLOAT NULL,

    json               MEDIUMTEXT NULL,
    entity_version     INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',
    retrieved_at       TIMESTAMP NULL,

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
    domain_id   INTEGER NOT NULL AUTO_INCREMENT,
    key_id      INTEGER NOT NULL,
    context_id  INTEGER NULL,
    deleted     TINYINT(1) NOT NULL DEFAULT 0,
    domain      VARCHAR(128),
    port        INTEGER NULL,
    consumer_key  TEXT,
    secret      TEXT,
    json        TEXT NULL,
    created_at  TIMESTAMP NOT NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_2`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    PRIMARY KEY (domain_id),
    UNIQUE(key_id, context_id, domain, port)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// String table - Not normalized at all - very costly
// Enable with $CFG->checktranslation = true
array( "{$CFG->dbprefix}tsugi_string",
"create table {$CFG->dbprefix}tsugi_string (
    string_id       INTEGER NOT NULL AUTO_INCREMENT,
    domain          VARCHAR(128) NOT NULL,
    string_text     TEXT,
    updated_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    string_sha256   CHAR(64) NOT NULL,

    PRIMARY KEY (string_id),
    UNIQUE(domain, string_sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Profile is denormalized and not tightly connected to allow
// for disconnecting and reconnecting various user_id values
array( "{$CFG->dbprefix}profile",
"create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    profile_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    image               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    UNIQUE(profile_id, profile_sha256),
    PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Caliper tables - event oriented - no foreign keys to the lti_tables

// "FIFO" buffer of events no explicit foreign key
// relationships as these are short-lived records
array( "{$CFG->dbprefix}cal_event",
"create table {$CFG->dbprefix}cal_event (
    event_id        INTEGER NOT NULL AUTO_INCREMENT,
    event           INTEGER NOT NULL,

    state           SMALLINT NULL,

    link_id         INTEGER NULL,
    key_id          INTEGER NULL,
    context_id      INTEGER NULL,
    user_id         INTEGER NULL,

    nonce           BINARY(16) NULL,
    launch          MEDIUMTEXT NULL,
    json            MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}cal_key",
"create table {$CFG->dbprefix}cal_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL UNIQUE,
    key_key             TEXT NOT NULL,

    activity            VARBINARY(8192) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    UNIQUE(key_sha256),
    PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}cal_context",
"create table {$CFG->dbprefix}cal_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         TEXT NOT NULL,

    key_id              INTEGER NOT NULL,

    activity            VARBINARY(8192) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    UNIQUE(key_id, context_sha256),
    PRIMARY KEY (context_id)
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

        // Secret is big ugly string for the google key - in case we launch internally in Koseu
        $secret = bin2hex(openssl_random_pseudo_bytes(16));
        $sql = "insert into {$CFG->dbprefix}lti_key (key_sha256, secret, key_key) values
            ( 'd4c9d9027326271a89ce51fcaf328ed673f17be33469ff979e8ab8dd501e664f', '$secret', 'google.com')";
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

    // Version 201701011135 improvements
    if ( $oldversion < 201701011135 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_domain ADD json TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_membership ADD json TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201701092329 improvements
    if ( $oldversion < 201701092329 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD ipaddr VARCHAR(64)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201701100823 improvements
    if ( $oldversion < 201701100823 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD ipaddr VARCHAR(64)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201701121623 improvements
    if ( $oldversion < 201701121623 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD path TEXT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201702161640 improvements
    if ( $oldversion < 201702161640 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD login_count INTEGER";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Checking for incorrect duplicate profile entries created
    // by pre Jan-2017 login.php mistakenly assuming that the
    // ID returned by Google was "permanent" - so now in
    // profile, we use email as primary key.

    // Stop running after October 2017
    if ( $oldversion < 201710010000) {
        $start = time();
        echo("Running google login duplicate check...<br/>\n");
        $checkSQL = "SELECT profile_id, email, created_at FROM {$CFG->dbprefix}profile WHERE email IN (SELECT T.E FROM (select profile_id AS I, email AS E,COUNT(profile_sha256) as C FROM {$CFG->dbprefix}profile GROUP BY profile_id, email ORDER BY C DESC) AS T WHERE T.C > 1) ORDER BY email DESC, created_at DESC;";
        $stmt = $PDOX->queryReturnError($checkSQL);
        if ( ! $stmt->success ) {
            echo("Fail checking duplicate profile entries:<br/>\n");
            echo($checkSQL);
            echo("Error: ".$stmt->errorImplode."<br/>\n");
        } else {
            $count = 0;
            while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
                if ( $count == 0 ) {
                    echo("These are profiles with duplicates:<br/>\n");
                }
                if ( $count < 10 ) {
	            echo($row['profile_id'].', '.htmlentities($row['email']).', '.$row['created_at']."<br/>\n");
                }
                $count ++;
            }
            if ( $count > 0 ) {
                if ( $count > 10 ) {
                    echo(" .... <br/>\n");
                }
                echo("Total records affected: $count <br/>\n");
                echo('To clear the duplicate records, use <a href="patch_profile.php">patch_profile.php</a><br/>'."\n");
            }
        }
        echo("Google login duplicate complete seconds=".(time()-$start)."<br/>\n");
    }

    // Version 201703171520 improvements
    if ( $oldversion < 201703171520 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD path TEXT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201703171550 improvements - Issue #8
    if ( $oldversion < 201703171550 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_domain ADD domain_id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Lots of MEDIUMTEXT fields
    if ( $oldversion < 201703171713 ) {
        $todo = array(
            "lti_key" => array( "consumer_profile", "new_consumer_profile", "tool_profile",
            "new_tool_profile", "json", "settings"),
            "lti_context" => array( "json", "settings"),
            "lti_link" => array( "json", "settings"),
            "lti_user" => array( "json"),
            "lti_membership" => array( "json"),
            "lti_service" => array( "json"),
            "lti_result" => array( "note", "json"),
            "profile" => array( "json")
        );
        foreach ( $todo as $table => $fields ) {
            foreach($fields as $field ) {
                $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY $field MEDIUMTEXT NULL";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
        	$q = $PDOX->queryReturnError($sql);
            }
        }
    }

    // Version 201705032130 - Add secret for google key if it is not there
    if ( $oldversion < 201705032130 ) {
        $secret = bin2hex(openssl_random_pseudo_bytes(16));
        $sql= "UPDATE {$CFG->dbprefix}lti_key SET secret='$secret' WHERE key_key = 'google.com' AND secret IS NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201705101135 - Add image and lessons fields
    if ( $oldversion < 201705101135 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD image TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}profile ADD image TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD lessons MEDIUMTEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add active columns
    if ( $oldversion < 201705211831 ) {
        $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
             'profile');
        foreach($tables as $table) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD active TINYINT";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    if ( $oldversion < 201705211839 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD ext_memberships_id TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD ext_memberships_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    if ( $oldversion < 201705270934 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD lineitems_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD memberships_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Change active columns to "deleted"
    if ( $oldversion < 201706030959 ) {
        $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
             'profile');
        foreach($tables as $table) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD deleted BIT";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} DROP active";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    // Change deleted to TINYINT(1)
    // Concern that BIT might freak out various ORMs / Run-Times.
    // https://www.xaprb.com/blog/2006/04/11/bit-values-in-mysql/
    if ( $oldversion < 201706092328 ) {
        $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
             'profile');
        foreach($tables as $table) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY deleted TINYINT(1)";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    // Dang - need to avoid NULL in the deleted columns
    // Thanks StackOverflow :)
    // https://stackoverflow.com/questions/44474250/which-is-better-in-mysql-an-ifnull-or-or-logic
    if ( $oldversion < 201706101015 ) {
        $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
             'profile');
        foreach($tables as $table) {
            $sql= "UPDATE {$CFG->dbprefix}{$table} SET deleted=0 WHERE deleted IS NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    // Make bit values not-null DEFAULT 0
    if ( $oldversion < 201706111750 ) {
        $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
            'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
             'profile');
        foreach($tables as $table) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY deleted TINYINT(1) NOT NULL DEFAULT 0";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    // Add the context secret column (for incoming grades)
    if ( $oldversion < 201708101745 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context MODIFY updated_at TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD secret CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "UPDATE {$CFG->dbprefix}lti_context SET secret=SUBSTR(CONCAT(MD5(RAND()),MD5(RAND())),1,64)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Clean up the mess - not very likely - because it was only for two hours
    // TODO: Delete this in a few weeks
    if ( $oldversion == 201708132146 || $oldversion == 201708132246) {

        $sql = 'DROP TABLE lti_link_activity';
        echo("Dropping to re-create: ".$sql."<br/>\n");
        error_log("Dropping to re-create: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Error: ".$q->errorImplode;
            error_log($message);
            echo($message."\n");
        }

        $sql = 'DROP TABLE lti_link_user_activity';
        echo("Dropping to re-create: ".$sql."<br/>\n");
        error_log("Dropping to re-create: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Error: ".$q->errorImplode;
            error_log($message);
            echo($message."\n");
        }

        echo("Please Re-Run Database Upgrade to recreate these tables\n");

    }

    // Add the caliper columns
    if ( $oldversion < 201708161530 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD caliper_url TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD caliper_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Add the login_at columns
    if ( $oldversion < 201709201530 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY updated_at TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context MODIFY updated_at TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD login_at DATETIME NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD login_at DATETIME NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Make sure there are no DATETIME's left
    if ( $oldversion < 201709221243 ) {
        $tables = array(
            'lti_key', 'lti_context', 'lti_user', 'profile', 'lti_link',
            'lti_membership', 'lti_event', 'lti_link_user_activity',
            'lti_service', 'lti_result', 'lti_domain');
        foreach($tables as $table) {
            $sql= "UPDATE {$CFG->dbprefix}{$table} SET updated_at='1970-01-02 00:00:00' WHERE updated_at < '1970-01-02 00:00:00'";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);

            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY updated_at TIMESTAMP NULL DEFAULT '1970-01-02 00:00:00'";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);

            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
	$tables = array( 'lti_key', 'lti_context', 'lti_user', 'profile');
        foreach($tables as $table) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} MODIFY login_at TIMESTAMP NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    if ( $oldversion < 201709241318 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD placementsecret VARCHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD oldplacementsecret VARCHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Oops - put them in the wrong place.
    if ( $oldversion < 201709251127 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD placementsecret VARCHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_link ADD oldplacementsecret VARCHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result DROP COLUMN placementsecret";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result DROP COLUMN oldplacementsecret";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Version 201710041600 improvements
    if ( $oldversion < 201710041600 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY login_count BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD login_count BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD login_count BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201710072300 improvements
    if ( $oldversion < 201710072300 ) {
        $sql= "UPDATE {$CFG->dbprefix}lti_user SET login_count=0 WHERE login_count IS NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "UPDATE {$CFG->dbprefix}lti_key SET login_count=0 WHERE login_count IS NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "UPDATE {$CFG->dbprefix}lti_user SET login_count=0 WHERE login_count IS NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD login_time BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD login_time BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD login_time BIGINT DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $oldversion < 201711252200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD gc_token TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD user_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql = "ALTER TABLE {$CFG->dbprefix}lti_context ADD
            CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_2`
                FOREIGN KEY (`user_id`)
                REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Google classroom incoming secret
    if ( $oldversion < 201711261315 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context ADD gc_secret VARCHAR(128) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_context MODIFY secret VARCHAR(128) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Google classroom submit_id
    if ( $oldversion < 201712022342 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_result ADD gc_submit_id TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // TODO: transfer lti_event contents to cal_event and drop the table

    // When you increase this number in any database.php file,
    // make sure to update the global value in setup.php
    return 201712022342;

}; // Don't forget the semicolon on anonymous functions :)

