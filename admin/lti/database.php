<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_link_activity",
"drop table if exists {$CFG->dbprefix}lti_link_user_activity",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_user",
"drop table if exists {$CFG->dbprefix}lti_issuer",
"drop table if exists {$CFG->dbprefix}lti_keyset",
"drop table if exists {$CFG->dbprefix}lti_key",
"drop table if exists {$CFG->dbprefix}lti_nonce",
"drop table if exists {$CFG->dbprefix}lti_message",
"drop table if exists {$CFG->dbprefix}lti_domain",
"drop table if exists {$CFG->dbprefix}lti_external",
"drop table if exists {$CFG->dbprefix}cal_event",
"drop table if exists {$CFG->dbprefix}cal_key",
"drop table if exists {$CFG->dbprefix}cal_context",
"drop table if exists {$CFG->dbprefix}tsugi_string",
"drop table if exists {$CFG->dbprefix}sessions",
"drop table if exists {$CFG->dbprefix}profile"
);

// Note that the TEXT xxx_key fields are UNIQUE but not
// marked as UNIQUE because of MySQL key index length limitations.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_issuer",
"create table {$CFG->dbprefix}lti_issuer (
    issuer_id           INTEGER NOT NULL AUTO_INCREMENT,
    issuer_title        TEXT NULL,
    issuer_sha256       CHAR(64) NULL,  -- Will become obsolete
    issuer_guid         CHAR(36) NOT NULL,  -- Our local GUID

    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    -- This is the owner of this issuer - it is not a foreign key
    -- We might use this if we end up with self-service issuers
    user_id             INTEGER NULL,

    issuer_key          TEXT NOT NULL,  -- iss from the JWT
    issuer_client       TEXT NOT NULL,  -- aud from the JWT
    lti13_oidc_auth     TEXT NULL,
    lti13_keyset_url    TEXT NULL,
    lti13_token_url     TEXT NULL,
    lti13_token_audience  TEXT NULL,

    -- Cached values
    lti13_keyset        TEXT NULL,
    lti13_platform_pubkey TEXT NULL,
    lti13_kid           TEXT NULL,

    json                MEDIUMTEXT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_pk` PRIMARY KEY (issuer_id),
    CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_guid` UNIQUE (issuer_guid)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_keyset",
"create table {$CFG->dbprefix}lti_keyset (
    keyset_id           INTEGER NOT NULL AUTO_INCREMENT,
    keyset_title        TEXT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    pubkey              TEXT NULL,
    privkey             TEXT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    CONSTRAINT `{$CFG->dbprefix}lti_keyset_const_pk` PRIMARY KEY (keyset_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Removed in issuer refactor
//    CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_1` UNIQUE(issuer_sha256),

// https://stackoverflow.com/questions/28418360/jwt-json-web-token-audience-aud-versus-client-id-whats-the-difference

// Key is in effect "tenant" (like a billing endpoint)
// We need to be able to look this up by either oauth_consumer_key or
// (issuer, client_id, deployment_id)

// TODO: Decide if the key_key must always be unique.
array( "{$CFG->dbprefix}lti_key",
"create table {$CFG->dbprefix}lti_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_title           TEXT NULL,
    key_sha256          CHAR(64) NULL,
    key_key             TEXT NULL,   -- oauth_consumer_key

    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    secret              TEXT NULL,
    new_secret          TEXT NULL,

    -- This is the owner of this key - it is not a foreign key
    -- on purpose to avoid potential circular foreign keys
    user_id             INTEGER NULL,

    -- When LTI 1.3 Security arrangements are auto-provisioned
    -- and the issuer matches a pre-created issuer, we link to it.
    -- Or if the key is being manually configured, we link to
    -- a pre-created issuer.  If this is set, all the lms_
    -- values below are in effect ignored.
    issuer_id           INTEGER NULL,

    -- Issuer / client_id / deployment_id defines a client (i.e. who -- pays the bill)

    deploy_sha256       CHAR(64) NULL,
    deploy_key          TEXT NULL,     -- deployment_id renamed

    -- But if the issuer is not pre-existing during dynamic configuration,
    -- we leave issuer_id null -- and store the security arrangement data
    -- here in the key.  The user never touches these LTI 1.3 fields in the
    -- management UI These columns are explicitly *not* named the same as the
    -- fields in the lti_issuers table so as to allow LEFT JOIN and COALESCE
    -- to be easily used and to make sure we are doing the right things
    -- to the right tables.

    -- Issuer is not unique - especially in single instance cloud LMS systems
    -- Issuer / client_id uniquely identifies a security arrangement
    -- But because Tsugi forces oidc_login and oidc_launch to a URL that
    -- includes key_id, we can just look up the proper row in this table by PK

    lms_issuer           TEXT NULL,  -- iss from the JWT
    lms_issuer_sha256    CHAR(64) NULL,
    lms_client           TEXT NULL,  -- aud from the JWT / client_id in OAuth

    lms_oidc_auth       TEXT NULL,
    lms_keyset_url      TEXT NULL,
    lms_token_url       TEXT NULL,
    lms_token_audience  TEXT NULL,

    -- Our cache of the LMS data
    lms_cache_keyset    TEXT NULL,
    lms_cache_pubkey    TEXT NULL,
    lms_cache_kid       TEXT NULL,

    xapi_url            TEXT NULL,
    xapi_user           TEXT NULL,
    xapi_password       TEXT NULL,

    caliper_url         TEXT NULL,
    caliper_key         TEXT NULL,

    json                MEDIUMTEXT NULL,
    user_json           MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}lti_key_ibfk_1`
        FOREIGN KEY (`issuer_id`)
        REFERENCES `{$CFG->dbprefix}lti_issuer` (`issuer_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_key_const_2` UNIQUE(issuer_id, deploy_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_key_const_pk` PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

/* If MySQL had constraints - these would be nice in lti_key - for now
   we will just need to be careful in code.

    CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    CHECK (
        (key_sha256 IS NOT NULL OR deploy_sha256 IS NOT NULL)
    )

    CONSTRAINT `{$CFG->dbprefix}lti_key_deploy_linked`
    CHECK (
        (deploy_key IS NOT NULL AND issuer_id IS NOT NULL)
     OR (deploy_key NOT NULL AND issuer_id NOT NULL)
    )
 */

array( "{$CFG->dbprefix}lti_user",
"create table {$CFG->dbprefix}lti_user (
    user_id             INTEGER NOT NULL AUTO_INCREMENT,
    user_sha256         CHAR(64) NULL,
    user_key            TEXT NULL,
    subject_sha256      CHAR(64) NULL,
    subject_key         TEXT NULL,
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
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_user_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_user_const_1` UNIQUE(key_id, user_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_user_const_2` UNIQUE(key_id, subject_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_user_const_pk` PRIMARY KEY (user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

/* If MySQL had CHECK constraints - these would be nice in lti_user - for now
   we will just need to be careful in code.

    CONSTRAINT `{$CFG->dbprefix}lti_user_both_not_null`
    CHECK (
        (user_sha256 IS NOT NULL OR subject_sha256 IS NOT NULL)
    )
 */

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
    user_json           MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,
    ext_memberships_id  TEXT NULL,
    ext_memberships_url TEXT NULL,
    memberships_url     TEXT NULL,
    lineitems_url       TEXT NULL,
    lti13_lineitems     TEXT NULL,
    lti13_membership_url  TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_const_1` UNIQUE(key_id, context_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_context_const_pk` PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link",
"create table {$CFG->dbprefix}lti_link (
    link_id             INTEGER NOT NULL AUTO_INCREMENT,
    link_sha256         CHAR(64) NOT NULL,
    link_key            TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    context_id          INTEGER NOT NULL,

    path                TEXT NULL,
    lti13_lineitem      TEXT NULL,

    title               TEXT NULL,
    score_maximum       DOUBLE NULL,

    json                MEDIUMTEXT NULL,
    settings            MEDIUMTEXT NULL,
    settings_url        TEXT NULL,

    placementsecret     VARCHAR(64) NULL,
    oldplacementsecret  VARCHAR(64) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_link_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_const_1` UNIQUE(link_sha256, context_id),
    CONSTRAINT `{$CFG->dbprefix}lti_link_const_pk` PRIMARY KEY (link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_link_activity",
"create table {$CFG->dbprefix}lti_link_activity (
    link_id             INTEGER NOT NULL,
    event               INTEGER NOT NULL,

    link_count          INTEGER UNSIGNED NOT NULL DEFAULT 0,
    activity            VARBINARY(1024) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

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
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_membership_const_1` UNIQUE(context_id, user_id),
    CONSTRAINT `{$CFG->dbprefix}lti_membership_const_pk` PRIMARY KEY (membership_id)
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
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_link_user_activity_const_pk` PRIMARY KEY (link_id, user_id, event)
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
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_service_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_service_const_1` UNIQUE(key_id, service_sha256),
    CONSTRAINT `{$CFG->dbprefix}lti_service_const_pk` PRIMARY KEY (service_id)
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
    grading_progress   TINYINT(1) NOT NULL DEFAULT 0,
    activity_progress  TINYINT(1) NOT NULL DEFAULT 0,

    json               MEDIUMTEXT NULL,
    entity_version     INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,
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
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_1` UNIQUE(link_id, user_id),
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_pk`  PRIMARY KEY (result_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Nonce is not connected using foreign key for performance
// and because it is effectively just a temporary cache
array( "{$CFG->dbprefix}lti_nonce",
"create table {$CFG->dbprefix}lti_nonce (
    nonce          CHAR(128) NOT NULL,
    key_id         INTEGER NOT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}lti_nonce_const_1` UNIQUE(key_id, nonce)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// This is for messaging if web sockets is not present
// These records should never last more than 5 minutes
// No foreign key on link_id - orphan records will expire
array( "{$CFG->dbprefix}lti_message",
"create table {$CFG->dbprefix}lti_message (
    link_id             INTEGER NOT NULL,
    room_id             INTEGER NOT NULL DEFAULT 0,

    message             TEXT NULL,

    micro_time          DOUBLE NOT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}lti_message_const_pk` PRIMARY KEY (link_id,room_id, micro_time)
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
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_2`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_const_pk` PRIMARY KEY (domain_id),
    CONSTRAINT `{$CFG->dbprefix}lti_domain_const_1` UNIQUE(key_id, context_id, domain, port)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}lti_external",
"create table {$CFG->dbprefix}lti_external (
    external_id  INTEGER NOT NULL AUTO_INCREMENT,
    endpoint        VARCHAR(128),
    name        TEXT,
    url         VARCHAR(128),
    description TEXT,
    fa_icon     VARCHAR(128),
    pubkey      TEXT,
    privkey     TEXT,
    deleted     TINYINT(1) NOT NULL DEFAULT 0,
    json        TEXT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_external_const_pk` PRIMARY KEY (external_id),
    CONSTRAINT `{$CFG->dbprefix}lti_external_const_1` UNIQUE(endpoint)
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

    CONSTRAINT `{$CFG->dbprefix}lti_string_const_pk` PRIMARY KEY (string_id),
    CONSTRAINT `{$CFG->dbprefix}lti_string_const_1` UNIQUE(domain, string_sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Sessions is used if we are storing session data
// in the database if we are storing sessions elsewhere
// this will remain empty
array( "{$CFG->dbprefix}sessions",
"CREATE TABLE {$CFG->dbprefix}sessions (
        sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
        sess_data BLOB NOT NULL,
        sess_time INTEGER UNSIGNED NOT NULL,
        sess_lifetime MEDIUMINT NOT NULL,
        created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at          TIMESTAMP NULL
) COLLATE utf8_bin, ENGINE = InnoDB;"),

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
    google_translate    TINYINT(1) NOT NULL DEFAULT 0,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}profile_const_pk` PRIMARY KEY (profile_id)
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
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}cal_event_const_pk` PRIMARY KEY (event_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}cal_key",
"create table {$CFG->dbprefix}cal_key (
    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL,
    key_key             TEXT NOT NULL,

    activity            VARBINARY(8192) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    CONSTRAINT `{$CFG->dbprefix}cal_key_const_1` UNIQUE(key_sha256),
    CONSTRAINT `{$CFG->dbprefix}cal_key_const_pk` PRIMARY KEY (key_id)
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

    CONSTRAINT `{$CFG->dbprefix}cal_context_const_1` UNIQUE(key_id, context_sha256),
    CONSTRAINT `{$CFG->dbprefix}cal_context_const_pk` PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

// Called after a table has been created...
$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;


    if ( $table == "{$CFG->dbprefix}lti_key") {
        $shaval = lti_sha256('12345');
        $sql= "insert into {$CFG->dbprefix}lti_key (key_sha256, key_key, secret) values
            ( '$shaval', '12345', 'secret')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);

        // Secret is big ugly string for the google key - in case we launch internally in Koseu
        $secret = bin2hex(openssl_random_pseudo_bytes(16));
        $shaval = lti_sha256('google.com');
        $sql = "insert into {$CFG->dbprefix}lti_key (key_sha256, secret, key_key) values
            ( '$shaval', '$secret', 'google.com')";
        error_log("Post-create: ".$sql);
        echo("Post-create: ".$sql."<br/>\n");
        $q = $PDOX->queryDie($sql);
    }

    if ( $table == "{$CFG->dbprefix}lti_nonce") {
        $sql = "CREATE INDEX `{$CFG->dbprefix}nonce_indx_1` ON {$CFG->dbprefix}lti_nonce ( nonce ) USING HASH";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        // PGSQL has no CRON feature - we depend on the probabilistic cleanup
        if ( $PDOX->isMySQL() ) {
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
    }

};

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Removed the 2014 - 2017 migrations - 2019-06-15

    // This is a place to make sure added fields are present
    // if you add a field to a table, put it in here and it will be auto-added
    $add_some_fields = array(
        array('lti_issuer', 'issuer_title', 'TEXT NULL'),
        array('lti_key', 'key_title', 'TEXT NULL'),
        array('profile', 'google_translate', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('lti_link', 'lti13_lineitem', 'TEXT NULL'),
        array('lti_context', 'lti13_lineitems', 'TEXT NULL'),
        array('lti_context', 'user_json', 'MEDIUMTEXT NULL'),
        array('lti_context', 'lti13_membership_url', 'TEXT NULL'),
        array('lti_key', 'deploy_key', 'TEXT NULL'),
        array('lti_key', 'issuer_id', 'INTEGER NULL'),
        array('lti_key', 'user_json', 'MEDIUMTEXT NULL'),
        array('lti_issuer', 'lti13_token_audience', 'TEXT NULL'),
        array('lti_key', 'xapi_url', 'TEXT NULL'),
        array('lti_key', 'xapi_user', 'TEXT NULL'),
        array('lti_key', 'xapi_password', 'TEXT NULL'),

        // 2021-08-26 - Add key-local security arrangements
        array('lti_key', 'lms_issuer', 'TEXT NULL'),
        array('lti_key', 'lms_issuer_sha256', 'CHAR(64) NULL'),
        array('lti_key', 'lms_client', 'TEXT NULL'),
        array('lti_key', 'lms_oidc_auth', 'TEXT NULL'),
        array('lti_key', 'lms_keyset_url', 'TEXT NULL'),
        array('lti_key', 'lms_token_url', 'TEXT NULL'),
        array('lti_key', 'lms_token_audience', 'TEXT NULL'),

        // Tenant/key cache of the LMS signing data
        array('lti_key', 'lms_cache_keyset', 'TEXT NULL'),
        array('lti_key', 'lms_cache_pubkey', 'TEXT NULL'),
        array('lti_key', 'lms_cache_kid', 'TEXT NULL'),

        array('lti_keyset', 'keyset_title', 'TEXT NULL'),

        array('lti_result', 'grading_progress', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('lti_result', 'activity_progress', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('lti_link', 'score_maximum', 'DOUBLE NULL'),
    );

    foreach ( $add_some_fields as $add_field ) {
        if (count($add_field) != 3 ) {
            echo("Badly formatted add_field");
            var_dump($add_field);
            continue;
        }
        $table = $add_field[0];
        $column = $add_field[1];
        $type = $add_field[2];
        if ( $PDOX->columnExists($column, "{$CFG->dbprefix}".$table ) ) continue;
        $sql= "ALTER TABLE {$CFG->dbprefix}$table ADD $column $type";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Generally wait a while to drop the fields - there is no rush
    $drop_some_fields = array(

        array('lti_result', 'lti13_lineitem'),

        // 20190610 - Remove lti13 fields from lti_key
        // (Yes this is somewhat ironic :) )
        array('lti_key', 'lti13_oidc_auth'),
        array('lti_key', 'lti13_keyset_url'),
        array('lti_key', 'lti13_keyset'),
        array('lti_key', 'lti13_platform_pubkey'),
        array('lti_key', 'lti13_kid'),
        array('lti_key', 'lti13_pubkey'),
        array('lti_key', 'lti13_privkey'),
        array('lti_key', 'lti13_token_url'),

        // Remove from lti2
        array('lti_key', 'consumer_profile'),
        array('lti_key', 'new_consumer_profile'),
        array('lti_key', 'tool_profile'),
        array('lti_key', 'new_tool_profile'),
        array('lti_key', 'ack'),

        // Short-lived key rotation idea - replaced by lti_keyset
        array('lti_issuer', 'lti13_pubkey_old',),
        array('lti_issuer', 'lti13_pubkey_old_at'),
        array('lti_issuer', 'lti13_pubkey_next'),
        array('lti_issuer', 'lti13_pubkey_next_at'),
        array('lti_issuer', 'lti13_privkey_next'),

        // TODO: Twists and turns - remove these after the branch has run for a bit
        array('lti_key', 'lms_issuer_key'),
        array('lti_key', 'our_pubkey'),
        array('lti_key', 'our_privkey'),
        array('lti_key', 'our_pubkey_old'),
        array('lti_key', 'our_pubkey_old_at'),
        array('lti_key', 'our_pubkey_next'),
        array('lti_key', 'our_pubkey_next_at'),
        array('lti_key', 'our_privkey_next'),
        array('lti_key', 'our_token_url'),
        array('lti_key', 'our_token_audience'),
        array('lti_key', 'platform_issuer_key'),
        array('lti_key', 'platform_issuer_client'),
        array('lti_key', 'platform_oidc_auth'),
        array('lti_key', 'platform_keyset_url'),
        array('lti_key', 'platform_keyset'),
        array('lti_key', 'platform_kid'),
        array('lti_key', 'platform_pubkey'),
        array('lti_key', 'lms_deployment_id'),
        array('lti_key', 'lms_deployment'),
        array('lti_key', 'lms_issuer_client_id'),
        array('lti_key', 'lms_issuer_client'),
        array('lti_key', 'lms_keyset'),
        array('lti_key', 'lms_pubkey'),
        array('lti_key', 'lms_kid'),

        // TODO: Remove these later - well after 2021-09 / global key signing
        // array('lti_issuer', 'lti13_pubkey',),
        // array('lti_issuer', 'lti13_privkey',),

    );

    foreach ( $drop_some_fields as $drop_field ) {
        if (count($drop_field) != 2 ) {
            echo("Badly formatted drop_field");
            var_dump($drop_field);
            continue;
        }
        $table = $drop_field[0];
        $column = $drop_field[1];
        if ( ! $PDOX->columnExists($column, "{$CFG->dbprefix}".$table ) ) continue;
        $sql= "ALTER TABLE {$CFG->dbprefix}$table DROP $column";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Make sure that lti_key has the correct unique indexes
    $needed_indexes = array(
        'lti_key_const_1' => 'ADD CONSTRAINT `lti_key_const_1` UNIQUE(key_sha256, deploy_sha256)',
        'lti_key_const_2' => 'ADD CONSTRAINT `lti_key_const_2` UNIQUE(issuer_id, deploy_sha256)',
    );

    $indexes = $PDOX->indexes($CFG->dbprefix."lti_key");

    // DROP INDEX index_name ON tbl_name
    foreach($indexes as $index) {
        if ( strcasecmp($index, "PRIMARY") == 0 ) continue;
        if ( strcasecmp($index, "ibfk") == 0 ) continue;
        $command = isset($needed_indexes[$index]) ? $needed_indexes[$index] : null;
        if ( is_string($command) ) continue;
        $sql = "DROP INDEX ".$index." ON ".$CFG->dbprefix."lti_key";
        echo($sql."<br/>\n");
        error_log($sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal dropping index: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

    // ALTER TABLE `table` ADD INDEX `product_id_index` (`product_id`)
    foreach($needed_indexes as $index => $command) {
        if ( in_array($index, $indexes) ) continue;
        $sql = "ALTER TABLE ".$CFG->dbprefix."lti_key". " " . $command;
        echo($sql."<br/>\n");
        error_log($sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal adding index: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

    // Old-school migrations
    if ( $oldversion < 201801271430 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}mail_bulk
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_bulk_ibfk_2`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_bulk ADD
                   CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_2`
                   FOREIGN KEY (`user_id`)
                   REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                   ON DELETE NO ACTION ON UPDATE NO ACTION";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_2`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_2`
                FOREIGN KEY (`link_id`)
                REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_3`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_3`
                FOREIGN KEY (`user_to`)
                REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent
                  DROP FOREIGN KEY `{$CFG->dbprefix}mail_sent_ibfk_4`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }

        $sql= "ALTER TABLE {$CFG->dbprefix}mail_sent ADD
                CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_4`
                FOREIGN KEY (`user_from`)
                REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating event: ".$q->errorImplode;
            error_log($message);
            echo($message);
        }
    }

    // Add the deleted_at column to columns if they are not there.
    // Double check created_at and updated_at
    $tables = array( 'lti_key', 'lti_context', 'lti_link', 'lti_user',
        'lti_membership', 'lti_service', 'lti_result', 'lti_domain',
         'profile');
    foreach($tables as $table) {
        if ( ! $PDOX->columnExists('deleted_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD deleted_at TIMESTAMP NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
        if ( ! $PDOX->columnExists('updated_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD updated_at TIMESTAMP NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
        if ( ! $PDOX->columnExists('created_at', "{$CFG->dbprefix}".$table) ) {
            $sql= "ALTER TABLE {$CFG->dbprefix}{$table} ADD created_at NOT NULL DEFAULT CURRENT_TIMESTAMP";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryDie($sql);
        }
    }

    // New for the LTI Advantage issuer refactor
    if ( ! $PDOX->columnExists('deploy_sha256', "{$CFG->dbprefix}lti_key") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD deploy_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key DROP KEY `{$CFG->dbprefix}lti_key_ibfk_1`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key ADD
                CONSTRAINT `{$CFG->dbprefix}lti_key_ibfk_1`
                FOREIGN KEY (`issuer_id`)
                REFERENCES `{$CFG->dbprefix}lti_issuer` (`issuer_id`)
                ON DELETE SET NULL ON UPDATE CASCADE";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201905111039 improvements - Prepare for issuer refactor
    if ( $oldversion < 201905111039 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY key_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY key_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Note still have to edit the entry to get the sha256 properly set
    if ( $PDOX->columnExists('issuer_issuer', "{$CFG->dbprefix}lti_issuer") &&
         ! $PDOX->columnExists('issuer_key', "{$CFG->dbprefix}lti_issuer") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer CHANGE issuer_issuer issuer_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('issuer_client_id', "{$CFG->dbprefix}lti_issuer") &&
         ! $PDOX->columnExists('issuer_client', "{$CFG->dbprefix}lti_issuer") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer CHANGE issuer_client_id issuer_client TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('user_subject', "{$CFG->dbprefix}lti_user") &&
         ! $PDOX->columnExists('subject_key', "{$CFG->dbprefix}lti_user") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user CHANGE user_subject subject_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( ! $PDOX->columnExists('subject_sha256', "{$CFG->dbprefix}lti_user") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD subject_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user ADD
            CONSTRAINT `{$CFG->dbprefix}lti_user_const_2` UNIQUE(key_id, subject_sha256)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

    }

    // Version 201905270930 improvements
    if ( $oldversion < 201905270930 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY user_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY user_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_user MODIFY subject_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Version 201907070902 improvements - Bad CREATE statement
    if ( $oldversion < 201907070902 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_key MODIFY key_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Add the issuer_guid field
    if ( ! $PDOX->columnExists('issuer_guid', "{$CFG->dbprefix}lti_issuer") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer ADD issuer_guid CHAR(36) NULL DEFAULT '42'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "UPDATE {$CFG->dbprefix}lti_issuer SET issuer_guid=(SELECT UUID()) WHERE issuer_guid='42'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer ADD
                   CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_guid`
                   UNIQUE (`issuer_guid`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        // CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_1` UNIQUE(issuer_sha256),
        $sql= "ALTER TABLE {$CFG->dbprefix}lti_issuer DROP KEY `{$CFG->dbprefix}lti_issuer_const_1`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

    }

    // Auto populate and/or rotate the lti_keyset data
    echo("Checking lti_keyset<br/>\n");
    $success = \Tsugi\Core\Keyset::maintain();
    if ( is_string($success) ) {
        error_log("Unable to generate public/private pair: ".$retval);
        echo("Unable to generate public/private pair: ".$retval."<br/>\n");
    }

    // It seems like some automatically created LTI1.1 keys between
    // 2017-10-25 and 2019-07-04 ended up with the wrong key_sha256 for the
    // key_key value - because of the way LTIX.php works it is as if these keys
    // don't exist
    /* Removed 2020-Sep-30 - I am sure the keys are cleaned up by now.
    $sql = "UPDATE {$CFG->dbprefix}lti_key SET key_sha256=sha2(key_key, 256)
        WHERE key_key IS NOT NULL AND key_sha256 != sha2(key_key, 256);";
    echo("Upgrading: ".$sql."<br/>\n");
    error_log("Upgrading: ".$sql);
    $q = $PDOX->queryReturnError($sql);
     */

    // When you increase this number in any database.php file,
    // make sure to update the global value in setup.php
    return 202112011310;

}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
}

