<?php

if ( ! isset($CFG) ) exit;

// Minted badges table - denormalized data for durable badge assertions
// Once minted, badge validity does not depend on lti_user, lti_context, or lti_result.
// A soft-deleted course stays joinable: badge reads do not filter lti_context.deleted.
// The later hard purge clears context_id; the row stays so the public URL works.
//
// badge_images is one row per distinct file. Many badges share a row when the
// SHA-256 matches. The blob is the original file, stored whole up to 5 MiB.
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}badge_images",
"create table {$CFG->dbprefix}badge_images (
    image_id           INTEGER NOT NULL AUTO_INCREMENT,
    sha256             CHAR(64) NOT NULL,
    mime_type          VARCHAR(128) NOT NULL,
    file_size          INTEGER NOT NULL,
    image_blob         MEDIUMBLOB NOT NULL,

    PRIMARY KEY (image_id),
    UNIQUE KEY {$CFG->dbprefix}badge_images_unique_sha (sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
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
    -- Frozen at publish: completion, narrative, issuer, image, evidence, assignment list.
    accomplishments     MEDIUMTEXT NULL,
    image_id           INTEGER NULL,
    image_sha256       CHAR(64) NULL,

    PRIMARY KEY (badge_guid),
    UNIQUE KEY {$CFG->dbprefix}badges_unique_user_context_code (user_id, context_id, badge_code),
    INDEX {$CFG->dbprefix}badges_indx_user (user_id),
    INDEX {$CFG->dbprefix}badges_indx_context (context_id),
    INDEX {$CFG->dbprefix}badges_indx_image (image_id),
    INDEX {$CFG->dbprefix}badges_indx_image_sha (image_sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}badges",
"drop table if exists {$CFG->dbprefix}badge_images"
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

    if ( $PDOX->columnExists('accomplishments', "{$CFG->dbprefix}badges") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}badges ADD accomplishments MEDIUMTEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    $cleared = \Tsugi\Services\Badges\BadgeService::clearSnapshotsMissingImageHash();
    if ( $cleared > 0 ) {
        echo("Upgrading: clearing {$cleared} badge snapshots that have no image hash<br/>\n");
        error_log("Upgrading: clearing {$cleared} badge snapshots that have no image hash");
    }

    if ( $PDOX->columnExists('image_id', "{$CFG->dbprefix}badges") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}badges ADD image_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    if ( $PDOX->columnExists('image_sha256', "{$CFG->dbprefix}badges") === false ) {
        $sql = "ALTER TABLE {$CFG->dbprefix}badges ADD image_sha256 CHAR(64) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    $image_index = "{$CFG->dbprefix}badges_indx_image";
    if ( $PDOX->indexExists($image_index, "{$CFG->dbprefix}badges") === false ) {
        $sql = "CREATE INDEX {$image_index} ON {$CFG->dbprefix}badges (image_id)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    $sha_index = "{$CFG->dbprefix}badges_indx_image_sha";
    if ( $PDOX->indexExists($sha_index, "{$CFG->dbprefix}badges") === false ) {
        $sql = "CREATE INDEX {$sha_index} ON {$CFG->dbprefix}badges (image_sha256)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    \Tsugi\Services\Badges\BadgeService::backfillMissingAccomplishments();
    \Tsugi\Services\Badges\BadgeService::backfillStoredImages();

    return 202609220001;
};
