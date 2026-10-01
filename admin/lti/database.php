<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

if ( ! isset($CFG) ) exit;

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_tool_registration_log",
"drop table if exists {$CFG->dbprefix}lti_tool_message",
"drop table if exists {$CFG->dbprefix}lti_tool_deployment",
"drop table if exists {$CFG->dbprefix}lti_tool_registration",
"drop table if exists {$CFG->dbprefix}lti_result",
"drop table if exists {$CFG->dbprefix}lti_service",
"drop table if exists {$CFG->dbprefix}lti_membership",
"drop table if exists {$CFG->dbprefix}lti_link",
"drop table if exists {$CFG->dbprefix}lti_link_activity",
"drop table if exists {$CFG->dbprefix}lti_link_user_activity",
"drop table if exists {$CFG->dbprefix}manifest",
"drop table if exists {$CFG->dbprefix}context_images",
"drop table if exists {$CFG->dbprefix}lti_context",
"drop table if exists {$CFG->dbprefix}lti_org",
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
"drop table if exists {$CFG->dbprefix}sessions"
);

// Note that the TEXT xxx_key fields are UNIQUE but not
// marked as UNIQUE because of MySQL key index length limitations.

$DATABASE_INSTALL = array(
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

    -- Issuer / client_id / deployment_id defines a client (i.e. who pays the bill)

    deploy_sha256       CHAR(64) NULL,
    deploy_key          TEXT NULL,     -- deployment_id renamed

    -- Per-key LTI 1.3 platform configuration (dynamic registration or manual entry).
    -- oidc_login uses key_id in the URL, so launch lookup is by primary key plus
    -- lms_client and wildcard iss handling in LTIX::loadAllData().

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

    unlock_code         MEDIUMTEXT NULL,

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

    -- deploy_sha256 participates in uniqueness; NULL deploy_sha256 (wildcard deploy_key)
    -- allows multiple rows per key_sha256 in typical MySQL UNIQUE-with-NULL semantics.
    CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
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

    -- Site-level capability to mint courses (not an LTI membership role)
    create_courses      TINYINT(1) NOT NULL DEFAULT 0,

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

// Organization hierarchy inside one tenant key. The tenant is lti_key.
// A null parent_org_id is a top-level org. The tenant itself is not an org row.
array( "{$CFG->dbprefix}lti_org",
"create table {$CFG->dbprefix}lti_org (
    org_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_id              INTEGER NOT NULL,
    parent_org_id       INTEGER NULL,

    title               VARCHAR(512) NOT NULL,
    -- Label only. Parent links define the hierarchy, not this value.
    org_type            VARCHAR(64) NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_org_const_pk` PRIMARY KEY (org_id),

    -- Referenced by child orgs, registrations, and deployments so those rows
    -- stay on the same tenant key as this org.
    CONSTRAINT `{$CFG->dbprefix}lti_org_const_1` UNIQUE (org_id, key_id),

    INDEX `{$CFG->dbprefix}lti_org_indx_1` (key_id),
    INDEX `{$CFG->dbprefix}lti_org_indx_2` (parent_org_id, key_id),

    CONSTRAINT `{$CFG->dbprefix}lti_org_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Same key_id as the parent, so a child cannot attach to another tenant.
    -- ON DELETE RESTRICT: OrgService::deleteOrg reparents children first.
    -- CASCADE here would delete the subtree. Cycles are rejected in moveOrg.
    -- MySQL cannot CHECK org_id because that column is AUTO_INCREMENT.
    CONSTRAINT `{$CFG->dbprefix}lti_org_ibfk_2`
        FOREIGN KEY (`parent_org_id`, `key_id`)
        REFERENCES `{$CFG->dbprefix}lti_org` (`org_id`, `key_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE

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

    -- Optional org inside this tenant key. NULL leaves an existing course unplaced.
    org_id              INTEGER NULL,

    -- If this course was created by a user within a key
    -- For example Google Glassroom - or an ad-hoc group
    user_id             INTEGER NULL,

    path                TEXT NULL,

    title               TEXT NULL,
    short_title         VARCHAR(64) NULL,

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
    lti13_context_groups_url  TEXT NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    login_at            TIMESTAMP NULL,
    login_count         BIGINT DEFAULT 0,
    login_time          BIGINT DEFAULT 0,

    viewDueDates        TINYINT(1) NOT NULL DEFAULT 1,

    -- Active course manifest version (NULL = file-based $CFG->lessons)
    manifest_id         INTEGER NULL,

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

    -- A composite (org_id, key_id) foreign key with ON DELETE SET NULL would
    -- also null key_id. OrgService::placeContext() keeps the course and the org on the same key.
    CONSTRAINT `{$CFG->dbprefix}lti_context_ibfk_3`
        FOREIGN KEY (`org_id`)
        REFERENCES `{$CFG->dbprefix}lti_org` (`org_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_const_1` UNIQUE(key_id, context_sha256),
    -- Deployment rows reference (context_id, key_id) to stay on this tenant key.
    CONSTRAINT `{$CFG->dbprefix}lti_context_const_2` UNIQUE(context_id, key_id),
    CONSTRAINT `{$CFG->dbprefix}lti_context_const_pk` PRIMARY KEY (context_id),

    INDEX `{$CFG->dbprefix}lti_context_indx_1` (org_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}context_images",
"create table {$CFG->dbprefix}context_images (
    context_id          INTEGER NOT NULL,

    hero                MEDIUMBLOB NULL,
    hero_mime           VARCHAR(64) NULL,
    hero_bytes          INTEGER NULL,
    hero_updated_at     TIMESTAMP NULL,

    icon                MEDIUMBLOB NULL,
    icon_mime           VARCHAR(64) NULL,
    icon_bytes          INTEGER NULL,
    icon_updated_at     TIMESTAMP NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}context_images_const_pk` PRIMARY KEY (context_id),

    CONSTRAINT `{$CFG->dbprefix}context_images_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}manifest",
"create table {$CFG->dbprefix}manifest (
    manifest_id         INTEGER NOT NULL AUTO_INCREMENT,
    context_id          INTEGER NOT NULL,
    version             INTEGER NOT NULL,
    title               TEXT NULL,
    -- Skinny Setup field (theme key). New course-setup features are sibling
    -- columns, not keys inside the lessons JSON (`manifest` MEDIUMTEXT).
    theme               VARCHAR(64) NULL,
    -- Teacher-edited course top nav (JSON). NULL = CourseNav default.
    navigation          MEDIUMTEXT NULL,
    manifest            MEDIUMTEXT NOT NULL,
    comment             TEXT NULL,
    user_id             INTEGER NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}manifest_const_pk` PRIMARY KEY (manifest_id),
    CONSTRAINT `{$CFG->dbprefix}manifest_const_1` UNIQUE (context_id, version),

    CONSTRAINT `{$CFG->dbprefix}manifest_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}manifest_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}manifest_indx_1` (context_id)
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
    -- Legacy due window still read by Assignments, Lessons, and Calendar.
    -- New workflow dates are the columns below. Do not overload these two.
    start_datetime      TIMESTAMP NULL,
    end_datetime        TIMESTAMP NULL,

    -- Soft publish. Existing links default to published. Unpublish deletes nothing.
    published           TINYINT(1) NOT NULL DEFAULT 1,
    -- AGS gradesReleased. NULL means the tool has not said.
    grades_released     TINYINT(1) NULL,

    -- Deep Linking available window. Lessons reads these. It does not store its own.
    available_start_datetime TIMESTAMP NULL,
    available_end_datetime   TIMESTAMP NULL,
    -- Deep Linking submission window, and the AGS line item startDateTime/endDateTime.
    submission_start_datetime TIMESTAMP NULL,
    submission_end_datetime   TIMESTAMP NULL,
    -- Tsugi due. Not an AGS field, and not submission end.
    due_datetime        TIMESTAMP NULL,

    -- Opaque AGS resourceId and tag for the tool that owns this column.
    -- That tool may be external, or internal (quiz, discussions). Not the quiz primary key.
    ags_resource_id     VARCHAR(256) NULL,
    ags_tag             VARCHAR(256) NULL,

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

    viewDueDates        TINYINT(1) NOT NULL DEFAULT 1,

    json                MEDIUMTEXT NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    visited_at          TIMESTAMP NULL,
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
    comment            MEDIUMTEXT NULL,
    attempts           INTEGER NULL,
    server_grade       FLOAT NULL,
    grading_progress   VARCHAR(30) NOT NULL DEFAULT 'NotReady',
    activity_progress  VARCHAR(30) NOT NULL DEFAULT 'Initialized',
    result_maximum     DOUBLE NULL,
    -- AGS scoreGiven. The link score_maximum is the column denominator.
    -- grade remains the derived 0-1 fraction.
    score_given        DOUBLE NULL,
    lti13_result_id    TEXT NULL,
    scoring_user_id    INTEGER NULL,
    score_timestamp   TIMESTAMP NULL,
    -- AGS submission.startedAt. submitted_at is submission.submittedAt.
    started_at         TIMESTAMP NULL,
    submitted_at       TIMESTAMP NULL,

    json               MEDIUMTEXT NULL,
    entity_version     INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NULL,
    deleted_at         TIMESTAMP NULL,
    attempted_at       TIMESTAMP NULL,
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

    CONSTRAINT `{$CFG->dbprefix}lti_result_ibfk_4`
        FOREIGN KEY (`scoring_user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    -- Note service_id is not part of the key on purpose
    -- It is data that can change and can be null in LTI 2.0
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_1` UNIQUE(link_id, user_id),
    CONSTRAINT `{$CFG->dbprefix}lti_result_const_pk`  PRIMARY KEY (result_id),
    
    -- Indexes for performance optimization (grades queries)
    KEY `{$CFG->dbprefix}lti_result_indx_link_deleted_grade` (link_id, deleted, grade),
    KEY `{$CFG->dbprefix}lti_result_indx_link_user_grade` (link_id, user_id, grade, deleted)
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
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Outbound LTI tool registration owned by one tenant key.
// org_id NULL means it may be deployed anywhere in that key.
// A non-null org_id is the top of the subtree it may be deployed into.
//
// IMS Dynamic Registration messages are rows in lti_tool_message, not columns
// on this registration. Placements, roles, and custom parameters stay inside
// message_json. Sakai stores placements as columns; this table does not.
// registration_json is the parsed registration document. The raw wire text
// lives in lti_tool_registration_log, which is text so malformed JSON can be kept.
array( "{$CFG->dbprefix}lti_tool_registration",
"create table {$CFG->dbprefix}lti_tool_registration (
    registration_id     INTEGER NOT NULL AUTO_INCREMENT,
    key_id              INTEGER NOT NULL,
    org_id              INTEGER NULL,
    created_by_user_id  INTEGER NULL,

    title               VARCHAR(512) NOT NULL,

    -- One registration is LTI 1.1 or LTI 1.3, never both.
    lti_version         VARCHAR(8) NOT NULL DEFAULT '1.3',
    lti11_key           VARCHAR(255) NULL,
    lti11_secret        TEXT NULL,
    lti11_url           TEXT NULL,

    client_id           VARCHAR(255) NULL,
    oidc_login_url      TEXT NULL,
    jwks_url            TEXT NULL,
    launch_url          TEXT NULL,
    redirect_uri        TEXT NULL,
    json                MEDIUMTEXT NULL,
    registration_json   JSON NULL,

    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_const_pk` PRIMARY KEY (registration_id),
    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_const_1` UNIQUE (registration_id, key_id),

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_chk_1` CHECK (
        (lti_version = '1.1'
            AND lti11_key IS NOT NULL AND lti11_key <> ''
            AND lti11_secret IS NOT NULL AND lti11_secret <> ''
            AND lti11_url IS NOT NULL AND lti11_url <> '')
        OR
        (lti_version = '1.3'
            AND lti11_key IS NULL
            AND lti11_secret IS NULL
            AND lti11_url IS NULL)
    ),

    INDEX `{$CFG->dbprefix}lti_tool_registration_indx_1` (key_id),
    INDEX `{$CFG->dbprefix}lti_tool_registration_indx_2` (org_id, key_id),

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- ON DELETE RESTRICT: deleteOrg clears org_id first. CASCADE would delete
    -- the registration, and that would delete its deployments.
    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_ibfk_2`
        FOREIGN KEY (`org_id`, `key_id`)
        REFERENCES `{$CFG->dbprefix}lti_org` (`org_id`, `key_id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_ibfk_3`
        FOREIGN KEY (`created_by_user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// One registration deployed to exactly one org or exactly one course.
// key_id is copied from the registration so the target foreign keys stay on that tenant key.
// Foreign keys are added in DATABASE_UPGRADE, after existing lti_context rows gain (context_id, key_id).
array( "{$CFG->dbprefix}lti_tool_deployment",
"create table {$CFG->dbprefix}lti_tool_deployment (
    tool_deployment_id  INTEGER NOT NULL AUTO_INCREMENT,
    registration_id     INTEGER NOT NULL,
    key_id              INTEGER NOT NULL,
    org_id              INTEGER NULL,
    context_id          INTEGER NULL,

    deployment_id       VARCHAR(255) NULL,

    -- 1 only when this row deploys the registration to the whole key. NULL for
    -- an org or course row. const_3 then allows many of those NULLs and one
    -- key row. A generated column cannot read registration_id: that column is
    -- in a foreign key, and both MariaDB 10.5 and MySQL 8 reject the key.
    key_level           TINYINT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_tool_deployment_const_pk` PRIMARY KEY (tool_deployment_id),

    CONSTRAINT `{$CFG->dbprefix}lti_tool_deployment_const_1` UNIQUE (registration_id, org_id),
    CONSTRAINT `{$CFG->dbprefix}lti_tool_deployment_const_2` UNIQUE (registration_id, context_id),
    CONSTRAINT `{$CFG->dbprefix}lti_tool_deployment_const_3` UNIQUE (registration_id, key_level),

    -- One target, the other target, or neither. Neither is a key deployment
    -- and key_level is 1. key_id stays required. Both targets set is rejected.
    CONSTRAINT `{$CFG->dbprefix}lti_tool_deployment_chk_1` CHECK (
        (org_id IS NULL AND context_id IS NULL AND key_level = 1)
        OR (org_id IS NOT NULL AND context_id IS NULL AND key_level IS NULL)
        OR (org_id IS NULL AND context_id IS NOT NULL AND key_level IS NULL)
    ),

    INDEX `{$CFG->dbprefix}lti_tool_deployment_indx_1` (registration_id, key_id),
    INDEX `{$CFG->dbprefix}lti_tool_deployment_indx_2` (org_id, key_id),
    INDEX `{$CFG->dbprefix}lti_tool_deployment_indx_3` (context_id, key_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// One row per Dynamic Registration messages[] entry, in source-document order.
// sequence is the array index, starting at 0. It is not a UI sort order.
// message_json keeps the whole parsed descriptor, including placements.
array( "{$CFG->dbprefix}lti_tool_message",
"create table {$CFG->dbprefix}lti_tool_message (
    message_id          INTEGER NOT NULL AUTO_INCREMENT,
    registration_id     INTEGER NOT NULL,
    sequence            INTEGER NOT NULL,

    message_type        VARCHAR(255) NOT NULL,
    target_link_uri     TEXT NULL,
    label               TEXT NULL,
    icon_uri            TEXT NULL,
    message_json        JSON NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_tool_message_const_pk` PRIMARY KEY (message_id),
    CONSTRAINT `{$CFG->dbprefix}lti_tool_message_const_1` UNIQUE (registration_id, sequence),

    INDEX `{$CFG->dbprefix}lti_tool_message_indx_1` (message_type),

    CONSTRAINT `{$CFG->dbprefix}lti_tool_message_ibfk_1`
        FOREIGN KEY (`registration_id`)
        REFERENCES `{$CFG->dbprefix}lti_tool_registration` (`registration_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

// Append-only raw Dynamic Registration traffic. payload_text is LONGTEXT, not
// JSON, so a body that is not valid JSON is still stored.
// registration_id stays nullable and is set null when the registration is
// deleted. cc_import_log cascades away with its import. This log is the
// certification record, so the payload text survives the registration row.
array( "{$CFG->dbprefix}lti_tool_registration_log",
"create table {$CFG->dbprefix}lti_tool_registration_log (
    log_id              INTEGER NOT NULL AUTO_INCREMENT,
    registration_id     INTEGER NULL,
    sequence            INTEGER NOT NULL,

    direction           VARCHAR(16) NOT NULL,
    phase               VARCHAR(64) NOT NULL,
    content_type        VARCHAR(255) NULL,
    http_status         INTEGER NULL,
    payload_text        LONGTEXT NOT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_log_const_pk` PRIMARY KEY (log_id),

    INDEX `{$CFG->dbprefix}lti_tool_registration_log_indx_1` (registration_id, sequence),
    INDEX `{$CFG->dbprefix}lti_tool_registration_log_indx_2` (created_at),

    CONSTRAINT `{$CFG->dbprefix}lti_tool_registration_log_ibfk_1`
        FOREIGN KEY (`registration_id`)
        REFERENCES `{$CFG->dbprefix}lti_tool_registration` (`registration_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

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
        array('lti_key', 'key_title', 'TEXT NULL'),
        array('lti_link', 'lti13_lineitem', 'TEXT NULL'),
        array('lti_context', 'lti13_lineitems', 'TEXT NULL'),
        array('lti_context', 'user_json', 'MEDIUMTEXT NULL'),
        array('lti_context', 'lti13_membership_url', 'TEXT NULL'),
        array('lti_key', 'deploy_key', 'TEXT NULL'),
        array('lti_key', 'user_json', 'MEDIUMTEXT NULL'),
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

        array('lti_result', 'grading_progress', 'VARCHAR(30) NOT NULL DEFAULT \'NotReady\''),
        array('lti_result', 'activity_progress', 'VARCHAR(30) NOT NULL DEFAULT \'Initialized\''),
        array('lti_link', 'score_maximum', 'DOUBLE NULL'),

        // 2025-03-10 AGS Phase 1: result schema for Assignments and Grades Service
        array('lti_result', 'result_maximum', 'DOUBLE NULL'),
        array('lti_result', 'lti13_result_id', 'TEXT NULL'),
        array('lti_result', 'scoring_user_id', 'INTEGER NULL'),
        array('lti_result', 'score_timestamp', 'TIMESTAMP NULL'),
        array('lti_result', 'submitted_at', 'TIMESTAMP NULL'),

        // 2023-05-11
        array('lti_key', 'unlock_code', 'MEDIUMTEXT NULL'),

        // 2023-07-29 Adding Context Groups Service
        array('lti_context', 'lti13_context_groups_url', 'TEXT NULL'),

        // 2024-09-17
        array('lti_result', 'attempts', 'INTEGER NULL'),
        array('lti_result', 'attempted_at', 'TIMESTAMP NULL'),

        // 2025-03-11 AGS LineItem due dates (startDateTime/endDateTime)
        array('lti_link', 'start_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'end_datetime', 'TIMESTAMP NULL'),

        // 2026-03-29 Per-context and per-membership visibility for due dates (existing rows default to on)
        array('lti_context', 'viewDueDates', 'TINYINT(1) NOT NULL DEFAULT 1'),
        array('lti_membership', 'viewDueDates', 'TINYINT(1) NOT NULL DEFAULT 1'),

        // 2026-08-28 Active course manifest version (NULL = file-based $CFG->lessons)
        array('lti_context', 'manifest_id', 'INTEGER NULL'),

        // 2026-09-23 Short course title (first six characters seed the home label)
        array('lti_context', 'short_title', 'VARCHAR(64) NULL'),

        // 2026-08-28 Named course theme key (NULL = site $CFG->theme)
        array('manifest', 'theme', 'VARCHAR(64) NULL'),

        // 2026-09-15 Teacher-edited course top nav JSON (NULL = default)
        array('manifest', 'navigation', 'MEDIUMTEXT NULL'),

        // 2026-08-28 User-level capability to create site-login courses
        array('lti_user', 'create_courses', 'TINYINT(1) NOT NULL DEFAULT 0'),

        // 2026-09-18 Last time this user entered this course (site-login flyout recency)
        array('lti_membership', 'visited_at', 'TIMESTAMP NULL'),

        // 2026-09-23 Gradebook workflow on the link. Existing rows stay published.
        // start_datetime / end_datetime are left alone; they are still the live due UI.
        array('lti_link', 'published', 'TINYINT(1) NOT NULL DEFAULT 1'),
        array('lti_link', 'grades_released', 'TINYINT(1) NULL'),
        array('lti_link', 'available_start_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'available_end_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'submission_start_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'submission_end_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'due_datetime', 'TIMESTAMP NULL'),
        array('lti_link', 'ags_resource_id', 'VARCHAR(256) NULL'),
        array('lti_link', 'ags_tag', 'VARCHAR(256) NULL'),

        // 2026-09-23 Numerator and AGS submission.startedAt on the result.
        array('lti_result', 'score_given', 'DOUBLE NULL'),
        array('lti_result', 'started_at', 'TIMESTAMP NULL'),

        // 2026-09-30 Optional organization placement. Existing courses stay unplaced.
        array('lti_context', 'org_id', 'INTEGER NULL'),

        // 2026-09-30 A registration is LTI 1.3 unless it carries LTI 1.1 credentials.
        array('lti_tool_registration', 'lti_version', "VARCHAR(8) NOT NULL DEFAULT '1.3'"),
        array('lti_tool_registration', 'lti11_key', 'VARCHAR(255) NULL'),
        array('lti_tool_registration', 'lti11_secret', 'TEXT NULL'),
        array('lti_tool_registration', 'lti11_url', 'TEXT NULL'),

        // 2026-09-30 Parsed Dynamic Registration document. Raw traffic is not stored here.
        array('lti_tool_registration', 'registration_json', 'JSON NULL'),
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
    );

    $indexes = $PDOX->indexes($CFG->dbprefix."lti_key");

    // DROP INDEX index_name ON tbl_name
    $keep_issuer_index = $PDOX->columnExists('issuer_id', $CFG->dbprefix."lti_key");
    foreach($indexes as $index) {
        if ( strcasecmp($index, "PRIMARY") == 0 ) continue;
        if ( strcasecmp($index, "ibfk") == 0 ) continue;
        // lti_key_const_2 backs issuer_id until lti_issuer phase 3 (2026-10-01 UTC)
        if ( $keep_issuer_index && $index === $CFG->dbprefix."lti_key_const_2" ) continue;
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
        'lti_membership', 'lti_service', 'lti_result', 'lti_domain');
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

    // Legacy lti_issuer column renames and issuer_guid (only while table still exists)
    $issuer_table = "{$CFG->dbprefix}lti_issuer";
    if ( $PDOX->metadata($issuer_table) !== false ) {

    // Note still have to edit the entry to get the sha256 properly set
    if ( $PDOX->columnExists('issuer_issuer', $issuer_table) &&
         ! $PDOX->columnExists('issuer_key', $issuer_table) ) {
        $sql= "ALTER TABLE {$issuer_table} CHANGE issuer_issuer issuer_key TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('issuer_client_id', $issuer_table) &&
         ! $PDOX->columnExists('issuer_client', $issuer_table) ) {
        $sql= "ALTER TABLE {$issuer_table} CHANGE issuer_client_id issuer_client TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Add the issuer_guid field
    if ( ! $PDOX->columnExists('issuer_guid', $issuer_table) ) {
        $sql= "ALTER TABLE {$issuer_table} ADD issuer_guid CHAR(36) NULL DEFAULT '42'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "UPDATE {$issuer_table} SET issuer_guid=(SELECT UUID()) WHERE issuer_guid='42'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$issuer_table} ADD
                   CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_guid`
                   UNIQUE (`issuer_guid`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        // CONSTRAINT `{$CFG->dbprefix}lti_issuer_const_1` UNIQUE(issuer_sha256),
        $sql= "ALTER TABLE {$issuer_table} DROP KEY `{$CFG->dbprefix}lti_issuer_const_1`";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

    }

    } // end legacy lti_issuer column migrations

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

    // Auto populate and/or rotate the lti_keyset data
    echo("Checking lti_keyset<br/>\n");
    $success = \Tsugi\Core\Keyset::maintain();
    if ( is_string($success) ) {
        error_log("Unable to generate public/private pair: ".$success);
        echo("Unable to generate public/private pair: ".$success."<br/>\n");
    }

    // Add indexes that might not be there
    $indexes_to_create = array(
        "{$CFG->dbprefix}lti_result_indx_link_deleted_grade" => 
            "CREATE INDEX `{$CFG->dbprefix}lti_result_indx_link_deleted_grade` ON {$CFG->dbprefix}lti_result (link_id, deleted, grade)",
        "{$CFG->dbprefix}lti_result_indx_link_user_grade" => 
            "CREATE INDEX `{$CFG->dbprefix}lti_result_indx_link_user_grade` ON {$CFG->dbprefix}lti_result (link_id, user_id, grade, deleted)"
    );
    
    foreach($indexes_to_create as $index_name => $sql) {
        if ( ! $PDOX->indexExists($index_name, "{$CFG->dbprefix}lti_result") ) {
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) {
                $message = "Non-Fatal error creating index: ".$q->errorImplode;
                error_log($message);
                echo($message."<br/>\n");
            }
        }
    }

    // 2025-03-10 AGS Phase 1: Replace unused TINYINT grading/activity_progress with VARCHAR(30)
    // Two separate checks: if TINYINT drop it, if column missing add it. Columns were never read/written.
    if ( $PDOX->isMySQL() ) {
        if ( $PDOX->columnExists('grading_progress', "{$CFG->dbprefix}lti_result") ) {
            $grading_col = $PDOX->describeColumn('grading_progress', "{$CFG->dbprefix}lti_result");
            $grading_type = $grading_col ? strtolower(\Tsugi\Util\U::get($grading_col, "Type", "")) : "";
            if ( strpos($grading_type, 'tinyint') !== false ) {
                $sql = "ALTER TABLE {$CFG->dbprefix}lti_result DROP COLUMN grading_progress";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $PDOX->queryReturnError($sql);
            }
        }
        if ( ! $PDOX->columnExists('grading_progress', "{$CFG->dbprefix}lti_result") ) {
            $sql = "ALTER TABLE {$CFG->dbprefix}lti_result ADD COLUMN grading_progress VARCHAR(30) NOT NULL DEFAULT 'NotReady'";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $PDOX->queryReturnError($sql);
        }
        if ( $PDOX->columnExists('activity_progress', "{$CFG->dbprefix}lti_result") ) {
            $activity_col = $PDOX->describeColumn('activity_progress', "{$CFG->dbprefix}lti_result");
            $activity_type = $activity_col ? strtolower(\Tsugi\Util\U::get($activity_col, "Type", "")) : "";
            if ( strpos($activity_type, 'tinyint') !== false ) {
                $sql = "ALTER TABLE {$CFG->dbprefix}lti_result DROP COLUMN activity_progress";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $PDOX->queryReturnError($sql);
            }
        }
        if ( ! $PDOX->columnExists('activity_progress', "{$CFG->dbprefix}lti_result") ) {
            $sql = "ALTER TABLE {$CFG->dbprefix}lti_result ADD COLUMN activity_progress VARCHAR(30) NOT NULL DEFAULT 'Initialized'";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $PDOX->queryReturnError($sql);
        }
    }

    // The score comment lives in lti_result.comment. Copy note into comment once,
    // in the upgrade that creates the column, then leave both columns alone.
    $result_table = "{$CFG->dbprefix}lti_result";
    if ( $PDOX->columnExists('note', $result_table) && ! $PDOX->columnExists('comment', $result_table) ) {
        $sql = "ALTER TABLE {$result_table} ADD comment MEDIUMTEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( $q->success ) {
            $sql = "UPDATE {$result_table} SET comment = note WHERE note IS NOT NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( $q->success ) {
                $sql = "UPDATE {$result_table} SET note = NULL WHERE note IS NOT NULL";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $PDOX->queryReturnError($sql);
            }
        }
    }

    // Add FK for scoring_user_id if column exists and FK does not
    if ( $PDOX->columnExists('scoring_user_id', "{$CFG->dbprefix}lti_result") ) {
        $indexes = $PDOX->indexes("{$CFG->dbprefix}lti_result");
        $fk_name = "{$CFG->dbprefix}lti_result_ibfk_4";
        if ( ! in_array($fk_name, $indexes) ) {
            $sql = "ALTER TABLE {$CFG->dbprefix}lti_result ADD CONSTRAINT `{$fk_name}`
                FOREIGN KEY (`scoring_user_id`) REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
                ON DELETE SET NULL ON UPDATE CASCADE";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) {
                $message = "Non-Fatal adding scoring_user_id FK: ".$q->errorImplode;
                error_log($message);
                echo($message."<br/>\n");
            }
        }
    }

    // Issue #226 phase 1: copy lti_issuer data into lti_key for all linked keys (idempotent).
    // Clears issuer_id, lms_issuer, and lms_issuer_sha256 on lti_key; copies other lms_*
    // from issuer (issuer precedence, key fallback). Safe to run until phase 3 drops the table.
    if ( $PDOX->metadata($issuer_table) !== false
        && $PDOX->columnExists('lms_issuer', "{$CFG->dbprefix}lti_key") ) {
        $lti_migration_coalesce = function($issuer_val, $key_val) {
            if ( $issuer_val !== null && $issuer_val !== '' && strlen(trim((string) $issuer_val)) > 0 ) {
                return $issuer_val;
            }
            if ( $key_val !== null && $key_val !== '' && strlen(trim((string) $key_val)) > 0 ) {
                return $key_val;
            }
            return null;
        };
        $sql = "SELECT I.*, K.key_id,
                K.lms_client AS key_lms_client, K.lms_oidc_auth AS key_lms_oidc_auth,
                K.lms_keyset_url AS key_lms_keyset_url, K.lms_token_url AS key_lms_token_url,
                K.lms_token_audience AS key_lms_token_audience,
                C.keys_per_issuer
            FROM {$issuer_table} AS I
            INNER JOIN {$CFG->dbprefix}lti_key AS K ON I.issuer_id = K.issuer_id
                AND (K.deleted IS NULL OR K.deleted = 0)
            INNER JOIN (
                SELECT I2.issuer_id, COUNT(K2.key_id) AS keys_per_issuer
                FROM {$issuer_table} AS I2
                LEFT JOIN {$CFG->dbprefix}lti_key AS K2 ON I2.issuer_id = K2.issuer_id
                    AND (K2.deleted IS NULL OR K2.deleted = 0)
                WHERE (I2.deleted IS NULL OR I2.deleted = 0)
                GROUP BY I2.issuer_id
            ) AS C ON C.issuer_id = I.issuer_id
            WHERE (I.deleted IS NULL OR I.deleted = 0)
                AND K.issuer_id IS NOT NULL AND K.issuer_id > 0
            ORDER BY I.issuer_id ASC, K.key_id ASC";
        $stmt = $PDOX->queryReturnError($sql, false, false);
        $moved = 0;
        if ( ! $stmt || ! $stmt->success ) {
            $err = ($stmt && isset($stmt->errorImplode)) ? $stmt->errorImplode : 'unknown error';
            echo("lti_issuer migration SELECT failed: ".htmlentities($err)."<br/>\n");
            error_log('lti_issuer migration SELECT failed: '.$err);
        } else {
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $row_count = is_array($rows) ? count($rows) : 0;
            if ( is_array($rows) && $row_count > 0 ) {
                $multi_issuers = array();
                foreach ( $rows as $row ) {
                    $keys_per_issuer = (int) $row['keys_per_issuer'];
                    if ( $keys_per_issuer > 1 ) {
                        $multi_issuers[$row['issuer_id']] = $keys_per_issuer;
                    }
                }
                echo("lti_issuer migration: {$row_count} lti_key row(s) still linked to lti_issuer<br/>\n");
                error_log("lti_issuer migration: {$row_count} lti_key row(s) still linked to lti_issuer");
                if ( count($multi_issuers) > 0 ) {
                    foreach ( $multi_issuers as $mid => $mcount ) {
                        echo("lti_issuer migration WARNING: lti_issuer issuer_id={$mid}"
                            ." has {$mcount} lti_key rows pointing to it (multi-key issuer)<br/>\n");
                        error_log("lti_issuer migration WARNING: lti_issuer issuer_id={$mid}"
                            ." has {$mcount} lti_key rows pointing to it (multi-key issuer)");
                    }
                }
                $update_sql = "UPDATE {$CFG->dbprefix}lti_key SET
                        issuer_id = NULL,
                        lms_issuer = NULL,
                        lms_issuer_sha256 = NULL,
                        lms_client = :lms_client,
                        lms_oidc_auth = :lms_oidc_auth,
                        lms_keyset_url = :lms_keyset_url,
                        lms_token_url = :lms_token_url,
                        lms_token_audience = :lms_token_audience,
                        updated_at = NOW()
                    WHERE key_id = :ID AND issuer_id = :old_issuer_id";
                foreach ( $rows as $row ) {
                    $key_id = $row['key_id'];
                    $old_issuer_id = $row['issuer_id'];
                    $values = array(
                        ':ID' => $key_id,
                        ':old_issuer_id' => $old_issuer_id,
                        ':lms_client' => $lti_migration_coalesce($row['issuer_client'], $row['key_lms_client']),
                        ':lms_oidc_auth' => $lti_migration_coalesce($row['lti13_oidc_auth'], $row['key_lms_oidc_auth']),
                        ':lms_keyset_url' => $lti_migration_coalesce($row['lti13_keyset_url'], $row['key_lms_keyset_url']),
                        ':lms_token_url' => $lti_migration_coalesce($row['lti13_token_url'], $row['key_lms_token_url']),
                        ':lms_token_audience' => $lti_migration_coalesce($row['lti13_token_audience'], $row['key_lms_token_audience']),
                    );
                    $q = $PDOX->queryReturnError($update_sql, $values);
                    if ( ! $q->success ) {
                        echo("lti_issuer migration UPDATE failed for lti_key key_id={$key_id}"
                            ." (issuer_id was {$old_issuer_id}): "
                            .htmlentities($q->errorImplode)."<br/>\n");
                        error_log("lti_issuer migration UPDATE failed for lti_key key_id={$key_id}"
                            ." (issuer_id was {$old_issuer_id}): ".$q->errorImplode);
                    } else if ( $q->rowCount() > 0 ) {
                        $moved++;
                    }
                }
            }
        }
        if ( $moved > 0 ) {
            $summary = 'lti_issuer migration complete: broke FK on '.$moved.' lti_key row(s)'
                .' (issuer_id nulled, LMS endpoints copied from lti_issuer)';
            echo($summary."<br/>\n");
            error_log($summary);
        }
    }

    // Issue #226 phase 3: drop lti_issuer table and issuer_id column (on or after 2026-10-01 UTC)
    $issuer_drop_after = gmmktime(0, 0, 0, 10, 1, 2026);
    if ( time() >= $issuer_drop_after ) {
        $key_table = "{$CFG->dbprefix}lti_key";
        $have_issuer_table = ($PDOX->metadata($issuer_table) !== false);
        $have_issuer_id = $PDOX->columnExists('issuer_id', $key_table);

        if ( $have_issuer_table || $have_issuer_id ) {
            if ( $have_issuer_id ) {
                $linked_stmt = $PDOX->queryReturnError(
                    "SELECT COUNT(*) AS linked_key_count FROM {$key_table}
                        WHERE issuer_id IS NOT NULL AND issuer_id > 0
                        AND (deleted IS NULL OR deleted = 0)",
                    false,
                    false
                );
                if ( $linked_stmt && $linked_stmt->success ) {
                    $linked_row = $linked_stmt->fetch(\PDO::FETCH_ASSOC);
                    $linked_key_count = (int) \Tsugi\Util\U::get($linked_row, 'linked_key_count', 0);
                    if ( $linked_key_count > 0 ) {
                        $warn = "lti_issuer phase 3 WARNING: {$linked_key_count} lti_key row(s)"
                            ." still have issuer_id set; dropping legacy schema anyway";
                        echo(htmlentities($warn)."<br/>\n");
                        error_log($warn);
                    }
                }
            }

            $key_indexes = $PDOX->indexes($key_table);
            $fk_name = "{$CFG->dbprefix}lti_key_ibfk_1";
            if ( in_array($fk_name, $key_indexes) ) {
                $sql = "ALTER TABLE {$key_table} DROP FOREIGN KEY `{$fk_name}`";
                echo("Upgrading: ".htmlentities($sql)."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) {
                    $message = "lti_issuer phase 3 drop FK failed: ".$q->errorImplode;
                    error_log($message);
                    echo(htmlentities($message)."<br/>\n");
                }
                $key_indexes = $PDOX->indexes($key_table);
            }

            $const2_name = "{$CFG->dbprefix}lti_key_const_2";
            if ( in_array($const2_name, $key_indexes) ) {
                $sql = "ALTER TABLE {$key_table} DROP INDEX `{$const2_name}`";
                echo("Upgrading: ".htmlentities($sql)."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) {
                    $message = "lti_issuer phase 3 drop index failed: ".$q->errorImplode;
                    error_log($message);
                    echo(htmlentities($message)."<br/>\n");
                }
            }

            if ( $have_issuer_id ) {
                $sql = "ALTER TABLE {$key_table} DROP COLUMN issuer_id";
                echo("Upgrading: ".htmlentities($sql)."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) {
                    $message = "lti_issuer phase 3 drop issuer_id failed: ".$q->errorImplode;
                    error_log($message);
                    echo(htmlentities($message)."<br/>\n");
                }
            }

            if ( $have_issuer_table ) {
                $sql = "DROP TABLE IF EXISTS {$issuer_table}";
                echo("Upgrading: ".htmlentities($sql)."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) {
                    $message = "lti_issuer phase 3 drop table failed: ".$q->errorImplode;
                    error_log($message);
                    echo(htmlentities($message)."<br/>\n");
                } else {
                    $summary = 'lti_issuer phase 3 complete: dropped lti_issuer table'
                        .($have_issuer_id ? ' and lti_key.issuer_id column' : '');
                    echo(htmlentities($summary)."<br/>\n");
                    error_log($summary);
                }
            }
        }
    }

    // 2026-09-30 Organization placement and outbound deployment foreign keys.
    // New lti_org / lti_tool_* tables come from $DATABASE_INSTALL. This block
    // finishes indexes and foreign keys that existing lti_context rows need first.
    $p = $CFG->dbprefix;
    $context_table = "{$p}lti_context";
    $org_table = "{$p}lti_org";
    $deployment_table = "{$p}lti_tool_deployment";

    $constraint_exists = function($table, $name) use ($PDOX) {
        $sql = "SELECT CONSTRAINT_NAME AS constraint_name
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND CONSTRAINT_NAME = :constraint_name";
        $row = $PDOX->rowDie($sql, array(
            ':table_name' => $table,
            ':constraint_name' => $name,
        ));
        return is_array($row);
    };

    if ( $PDOX->metadata($org_table) !== false && $PDOX->columnExists('org_id', $context_table) ) {
        $org_index = "{$p}lti_context_indx_1";
        if ( ! $PDOX->indexExists($org_index, $context_table) ) {
            $sql = "ALTER TABLE {$context_table} ADD INDEX `{$org_index}` (`org_id`)";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to index lti_context.org_id: ".$q->errorImplode."<br/>\n");
        }

        $context_key = "{$p}lti_context_const_2";
        if ( ! $PDOX->indexExists($context_key, $context_table) ) {
            $sql = "ALTER TABLE {$context_table} ADD CONSTRAINT `{$context_key}` UNIQUE (`context_id`, `key_id`)";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add lti_context (context_id, key_id) key: ".$q->errorImplode."<br/>\n");
        }

        $context_fk = "{$p}lti_context_ibfk_3";
        if ( ! $constraint_exists($context_table, $context_fk) ) {
            $sql = "ALTER TABLE {$context_table} ADD CONSTRAINT `{$context_fk}`
                FOREIGN KEY (`org_id`) REFERENCES `{$org_table}` (`org_id`)
                ON DELETE SET NULL ON UPDATE CASCADE";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add lti_context.org_id foreign key: ".$q->errorImplode."<br/>\n");
        }
    }

    $registration_table = "{$p}lti_tool_registration";
    if ( $PDOX->metadata($registration_table) !== false
        && $PDOX->columnExists('lti_version', $registration_table)
        && $PDOX->columnExists('lti11_url', $registration_table) ) {
        $registration_check = "{$p}lti_tool_registration_chk_1";
        if ( ! $constraint_exists($registration_table, $registration_check) ) {
            $sql = "ALTER TABLE {$registration_table} ADD CONSTRAINT `{$registration_check}` CHECK (
                (lti_version = '1.1'
                    AND lti11_key IS NOT NULL AND lti11_key <> ''
                    AND lti11_secret IS NOT NULL AND lti11_secret <> ''
                    AND lti11_url IS NOT NULL AND lti11_url <> '')
                OR
                (lti_version = '1.3'
                    AND lti11_key IS NULL
                    AND lti11_secret IS NULL
                    AND lti11_url IS NULL)
            )";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add {$registration_check}: ".$q->errorImplode."<br/>\n");
        }
    }

    if ( $PDOX->metadata($deployment_table) !== false ) {
        // ON UPDATE RESTRICT: MySQL error 3823 rejects ON UPDATE CASCADE on a
        // column named by lti_tool_deployment_chk_1. ON DELETE CASCADE is allowed.
        $deployment_fks = array(
            "{$p}lti_tool_deployment_ibfk_1" => "FOREIGN KEY (`registration_id`, `key_id`) REFERENCES `{$p}lti_tool_registration` (`registration_id`, `key_id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "{$p}lti_tool_deployment_ibfk_2" => "FOREIGN KEY (`org_id`, `key_id`) REFERENCES `{$org_table}` (`org_id`, `key_id`) ON DELETE RESTRICT ON UPDATE RESTRICT",
            "{$p}lti_tool_deployment_ibfk_3" => "FOREIGN KEY (`context_id`, `key_id`) REFERENCES `{$context_table}` (`context_id`, `key_id`) ON DELETE CASCADE ON UPDATE RESTRICT",
        );
        foreach ( $deployment_fks as $fk_name => $fk_sql ) {
            if ( $constraint_exists($deployment_table, $fk_name) ) continue;
            $sql = "ALTER TABLE {$deployment_table} ADD CONSTRAINT `{$fk_name}` {$fk_sql}";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add {$fk_name}: ".$q->errorImplode."<br/>\n");
        }

        // deleteOrg moves rows off an org before deleting it. RESTRICT makes a
        // raw DELETE fail instead of cascading into children, registrations,
        // or deployments. SET NULL on these composite keys would also null key_id.
        $restrict_org_delete = array(
            $org_table => array(
                "{$p}lti_org_ibfk_2" => "FOREIGN KEY (`parent_org_id`, `key_id`) REFERENCES `{$org_table}` (`org_id`, `key_id`) ON DELETE RESTRICT ON UPDATE CASCADE",
            ),
            $registration_table => array(
                "{$p}lti_tool_registration_ibfk_2" => "FOREIGN KEY (`org_id`, `key_id`) REFERENCES `{$org_table}` (`org_id`, `key_id`) ON DELETE RESTRICT ON UPDATE CASCADE",
            ),
            $deployment_table => array(
                "{$p}lti_tool_deployment_ibfk_2" => "FOREIGN KEY (`org_id`, `key_id`) REFERENCES `{$org_table}` (`org_id`, `key_id`) ON DELETE RESTRICT ON UPDATE RESTRICT",
            ),
        );
        foreach ( $restrict_org_delete as $rule_table => $rules ) {
            if ( $PDOX->metadata($rule_table) === false ) {
                continue;
            }
            foreach ( $rules as $rule_name => $rule_sql ) {
                $rule_row = $PDOX->rowDie(
                    "SELECT DELETE_RULE AS delete_rule
                     FROM information_schema.REFERENTIAL_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                       AND TABLE_NAME = :table_name
                       AND CONSTRAINT_NAME = :constraint_name",
                    array(
                        ':table_name' => $rule_table,
                        ':constraint_name' => $rule_name,
                    )
                );
                $delete_rule = is_array($rule_row) ? strtoupper((string) $rule_row['delete_rule']) : '';
                if ( $delete_rule === 'RESTRICT' ) {
                    continue;
                }
                if ( $constraint_exists($rule_table, $rule_name) ) {
                    $sql = "ALTER TABLE {$rule_table} DROP FOREIGN KEY `{$rule_name}`";
                    echo("Upgrading: ".$sql."<br/>\n");
                    error_log("Upgrading: ".$sql);
                    $q = $PDOX->queryReturnError($sql);
                    if ( ! $q->success ) die("Unable to drop {$rule_name}: ".$q->errorImplode."<br/>\n");
                }
                $sql = "ALTER TABLE {$rule_table} ADD CONSTRAINT `{$rule_name}` {$rule_sql}";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) die("Unable to add {$rule_name}: ".$q->errorImplode."<br/>\n");
            }
        }

        $key_deployment = "{$p}lti_tool_deployment_const_3";
        if ( $PDOX->columnExists('key_registration_id', $deployment_table) ) {
            if ( $PDOX->indexExists($key_deployment, $deployment_table) ) {
                $sql = "ALTER TABLE {$deployment_table} DROP INDEX `{$key_deployment}`";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) die("Unable to drop {$key_deployment}: ".$q->errorImplode."<br/>\n");
            }
            $sql = "ALTER TABLE {$deployment_table} DROP COLUMN key_registration_id";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to drop key_registration_id: ".$q->errorImplode."<br/>\n");
        }
        if ( ! $PDOX->columnExists('key_level', $deployment_table) ) {
            $sql = "ALTER TABLE {$deployment_table} ADD COLUMN key_level TINYINT NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add key_level: ".$q->errorImplode."<br/>\n");
            $sql = "UPDATE {$deployment_table} SET key_level = 1
                WHERE org_id IS NULL AND context_id IS NULL";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to mark key deployments: ".$q->errorImplode."<br/>\n");
            if ( $PDOX->indexExists($key_deployment, $deployment_table) ) {
                $sql = "ALTER TABLE {$deployment_table} DROP INDEX `{$key_deployment}`";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) die("Unable to drop {$key_deployment}: ".$q->errorImplode."<br/>\n");
            }
            $sql = "ALTER TABLE {$deployment_table} ADD CONSTRAINT `{$key_deployment}` UNIQUE (registration_id, key_level)";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add {$key_deployment}: ".$q->errorImplode."<br/>\n");
        }

        $deployment_check = "{$p}lti_tool_deployment_chk_1";
        $check_row = $PDOX->rowDie(
            "SELECT CHECK_CLAUSE AS check_clause
             FROM information_schema.CHECK_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = :constraint_name",
            array(':constraint_name' => $deployment_check)
        );
        $check_clause = is_array($check_row) ? (string) $check_row['check_clause'] : '';
        if ( $check_clause === '' || stripos($check_clause, 'key_level') === false ) {
            if ( $constraint_exists($deployment_table, $deployment_check) ) {
                $sql = "ALTER TABLE {$deployment_table} DROP CHECK `{$deployment_check}`";
                echo("Upgrading: ".$sql."<br/>\n");
                error_log("Upgrading: ".$sql);
                $q = $PDOX->queryReturnError($sql);
                if ( ! $q->success ) die("Unable to drop {$deployment_check}: ".$q->errorImplode."<br/>\n");
            }
            $sql = "ALTER TABLE {$deployment_table} ADD CONSTRAINT `{$deployment_check}` CHECK (
                (org_id IS NULL AND context_id IS NULL AND key_level = 1)
                OR (org_id IS NOT NULL AND context_id IS NULL AND key_level IS NULL)
                OR (org_id IS NULL AND context_id IS NOT NULL AND key_level IS NULL)
            )";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) die("Unable to add {$deployment_check}: ".$q->errorImplode."<br/>\n");
        }
    }

    // When you increase this number in any database.php file,
    // make sure to update the global value in setup.php
    return 202610010013;

}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
}
