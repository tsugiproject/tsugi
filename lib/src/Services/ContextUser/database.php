<?php

if ( ! isset($CFG) ) exit;

// One row per person per course: the many-to-many of lti_context and lti_user,
// same shape as lti_membership. The note is its own column. Other preferences
// for that person in that course live in settings (JSON). Kept off
// lti_membership so a save does not rewrite the membership row.
//
// lti_student_note was an earlier name for this row. It was not shipped.
// Upgrade drops it. Nothing is copied.
// TODO: After January 1, 2027, remove the lti_student_note drop below
// (the uninstall line and the upgrade that looks for the old table).

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_context_user_settings",
"create table {$CFG->dbprefix}lti_context_user_settings (
    settings_id         INTEGER NOT NULL AUTO_INCREMENT,
    context_id          INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    note                VARCHAR(500) NOT NULL DEFAULT '',
    settings            TEXT NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_context_user_settings_pk` PRIMARY KEY (settings_id),

    CONSTRAINT `{$CFG->dbprefix}lti_context_user_settings_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_user_settings_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_context_user_settings_unique` UNIQUE (context_id, user_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_context_user_settings",
"drop table if exists {$CFG->dbprefix}lti_student_note"
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // TODO: After January 1, 2027, delete this drop of lti_student_note.
    $old = "{$CFG->dbprefix}lti_student_note";
    $exists = $PDOX->rowDie(
        "SELECT table_name FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :T",
        array(':T' => $old)
    );
    if ( is_array($exists) ) {
        $sql = "DROP TABLE IF EXISTS {$old}";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            echo("Warning: Could not drop {$old}: ".$q->errorImplode."<br/>\n");
            error_log("Warning: Could not drop {$old}: ".$q->errorImplode);
        }
    }

    return $oldversion;
};
