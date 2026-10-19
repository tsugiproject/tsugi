<?php

if ( ! isset($CFG) ) exit;

// Minted badges table - denormalized data for durable badge assertions
// Once minted, badge validity does not depend on lti_user, lti_context, or lti_result.
// A soft-deleted course stays joinable: badge reads do not filter lti_context.deleted.
// The later hard purge clears context_id; the row stays so the public URL works.
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}badges",
"create table {$CFG->dbprefix}badges (
    badge_guid         VARCHAR(40) NOT NULL,
    user_id            INTEGER NOT NULL,
    context_id         INTEGER NULL,
    badge_code         VARCHAR(128) NOT NULL,
    user_displayname   VARCHAR(512) NOT NULL,
    user_email         VARCHAR(512) NOT NULL,
    context_title      VARCHAR(512) NOT NULL,
    badge_title        VARCHAR(512) NOT NULL,
    issued_at          DATETIME NOT NULL,
    linkedin_clicks    INTEGER NULL DEFAULT 0,

    PRIMARY KEY (badge_guid),
    UNIQUE KEY {$CFG->dbprefix}badges_unique_user_context_code (user_id, context_id, badge_code),
    INDEX {$CFG->dbprefix}badges_indx_user (user_id),
    INDEX {$CFG->dbprefix}badges_indx_context (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}badges"
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    if ( $PDOX->columnExists('linkedin_clicks', "{$CFG->dbprefix}badges") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}badges ADD linkedin_clicks INTEGER NULL DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('context_id', "{$CFG->dbprefix}badges")
        && $PDOX->columnIsNull('context_id', "{$CFG->dbprefix}badges") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}badges MODIFY context_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    // Unused. Evidence assignments come from the current lessons.
    $assignments = "{$CFG->dbprefix}badge_assignments";
    $exists = $PDOX->queryReturnError(
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tname LIMIT 1",
        array(':tname' => $assignments)
    );
    if ( $exists->success && $exists->rowCount() > 0 ) {
        $exists->closeCursor();
        $sql = "DROP TABLE {$assignments}";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    } else {
        $exists->closeCursor();
    }

    return 202609220001;
};
