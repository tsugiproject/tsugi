<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_user",
"drop table if exists {$CFG->dbprefix}lti_key",
"drop table if exists {$CFG->dbprefix}profile");

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_key",
"create table {$CFG->dbprefix}lti_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL UNIQUE,
    key_key             VARCHAR(4096) NOT NULL,

    secret              VARCHAR(4096) NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    UNIQUE(key_sha256),
    PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_context",
"create table {$CFG->dbprefix}lti_context (
    context_id          INTEGER NOT NULL AUTO_INCREMENT,
    context_sha256      CHAR(64) NOT NULL,
    context_key         VARCHAR(4096) NOT NULL,

    key_id              INTEGER NOT NULL, 

    title               VARCHAR(2048) NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

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
    link_key            VARCHAR(4096) NOT NULL,

    context_id          INTEGER NOT NULL, 

    title               VARCHAR(2048) NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

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
    user_key            VARCHAR(4096) NOT NULL,

    key_id              INTEGER NOT NULL,
    profile_id          INTEGER NOT NULL,

    displayname         VARCHAR(2048) NULL,
    email               VARCHAR(2048) NULL,
    locale              CHAR(63) NULL,

    json                TEXT NULL,
    login_at            DATETIME NOT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

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

    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

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
    service_key         VARCHAR(4096) NOT NULL,

    key_id              INTEGER NOT NULL, 

    format              VARCHAR(1024) NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_service_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, service_sha256),
    PRIMARY KEY (service_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_result",
"create table {$CFG->dbprefix}lti_result (
    result_id          INTEGER NOT NULL AUTO_INCREMENT,
    link_id            INTEGER NOT NULL, 
    user_id            INTEGER NOT NULL,

    sourcedid          VARCHAR(2048) NOT NULL,
    sourcedid_sha256   CHAR(64) NOT NULL,

    service_id         INTEGER NULL,

    grade              FLOAT NULL,
    note               VARCHAR(2048) NULL,

    json               TEXT NULL,
    created_at         DATETIME NOT NULL,
    updated_at         DATETIME NOT NULL,

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
    UNIQUE(link_id, user_id, sourcedid_sha256),
    PRIMARY KEY (result_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Profile is denormalized and not tightly connected to allow
// for reconnecting
array( "{$CFG->dbprefix}profile",
"create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    profile_key         VARCHAR(4096) NOT NULL,

    key_id              INTEGER NOT NULL,

    displayname         VARCHAR(2048) NULL,
    email               VARCHAR(2048) NULL,
    locale              CHAR(63) NULL,

    json                TEXT NULL,
    login_at            DATETIME NOT NULL,
    created_at          DATETIME NOT NULL,
    updated_at          DATETIME NOT NULL,

    UNIQUE(profile_id, profile_sha256),
    PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($pdo, $oldversion) {
    global $CFG;

    // Version 2 improvements
    if ( $oldversion < 2 ) {
        $sql= "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key, secret) values 
            ( '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', '12345', 'secret')";
        error_log("Upgrading: ".$sql);
        echo("Upgrading: ".$sql."<br/>\n");
        $q = pdoQueryDie($pdo, $sql);

        // Key is null for the google key - no direct launches or logins allowed
        $sql = "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key) values 
            ( 'd4c9d9027326271a89ce51fcaf328ed673f17be33469ff979e8ab8dd501e664f', 'google.com')";
        error_log("Upgrading: ".$sql);
        echo("Upgrading: ".$sql."<br/>\n");
        $q = pdoQueryDie($pdo, $sql);
    }

    // Version 2014041200 improvements
    if ( $oldversion < 2014041200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_membership ADD role_override SMALLINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = pdoQueryDie($pdo, $sql);
    }

    return 2014041200;
}; // Don't forget the semicolon on anonymous functions :)

