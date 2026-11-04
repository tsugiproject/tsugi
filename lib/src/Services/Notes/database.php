<?php

if ( ! isset($CFG) ) exit;

// One plain-text note per person per course. Kept off lti_membership so a
// 500-character save does not rewrite that row.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_student_note",
"create table {$CFG->dbprefix}lti_student_note (
    note_id             INTEGER NOT NULL AUTO_INCREMENT,
    context_id          INTEGER NOT NULL,
    user_id             INTEGER NOT NULL,
    body                VARCHAR(500) NOT NULL,
    updated_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_student_note_pk` PRIMARY KEY (note_id),

    CONSTRAINT `{$CFG->dbprefix}lti_student_note_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_student_note_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_student_note_unique` UNIQUE (context_id, user_id)

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_student_note"
);

$DATABASE_UPGRADE = function($oldversion) {
    return $oldversion;
};
