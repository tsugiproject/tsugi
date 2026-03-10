<?php

if ( ! isset($CFG) ) exit;

// Badge assignments - maps badge_code to required resource_link_ids (from lessons.json)
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}badge_assignments",
"create table {$CFG->dbprefix}badge_assignments (
    badge_code         VARCHAR(128) NOT NULL,
    resource_link_id   VARCHAR(256) NOT NULL,
    resource_link_title VARCHAR(512) NOT NULL DEFAULT '',
    seq                SMALLINT NOT NULL DEFAULT 0,

    PRIMARY KEY (badge_code, resource_link_id),
    INDEX {$CFG->dbprefix}badge_assignments_indx_code (badge_code)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),

// Minted badges table - denormalized data for durable badge assertions
// Once minted, badge validity does not depend on lti_user, lti_context, or lti_result
array( "{$CFG->dbprefix}badges",
"create table {$CFG->dbprefix}badges (
    badge_guid         VARCHAR(40) NOT NULL,
    user_id            INTEGER NOT NULL,
    context_id         INTEGER NOT NULL,
    badge_code         VARCHAR(128) NOT NULL,
    user_displayname   VARCHAR(512) NOT NULL,
    user_email         VARCHAR(512) NOT NULL,
    context_title      VARCHAR(512) NOT NULL,
    badge_title        VARCHAR(512) NOT NULL,
    issued_at          DATETIME NOT NULL,

    PRIMARY KEY (badge_guid),
    UNIQUE KEY {$CFG->dbprefix}badges_unique_user_context_code (user_id, context_id, badge_code),
    INDEX {$CFG->dbprefix}badges_indx_user (user_id),
    INDEX {$CFG->dbprefix}badges_indx_context (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}badges",
"drop table if exists {$CFG->dbprefix}badge_assignments"
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;
    return 202512130002;
};
