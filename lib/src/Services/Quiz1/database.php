<?php

if ( ! isset($CFG) ) exit;

// Native Quiz1 authoring tables. QTI XML is never stored; these hold
// semantic quiz / question / answer records for editing and export.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}quiz1_quiz",
"create table {$CFG->dbprefix}quiz1_quiz (
    quiz_id                INTEGER NOT NULL AUTO_INCREMENT,
    context_id             INTEGER NOT NULL,
    user_id                INTEGER NOT NULL,
    title                  VARCHAR(512) NOT NULL,
    instructions           TEXT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}quiz1_quiz_pk` PRIMARY KEY (quiz_id),

    CONSTRAINT `{$CFG->dbprefix}quiz1_quiz_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}quiz1_quiz_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}quiz1_quiz_indx_1` ( context_id )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}quiz1_question",
"create table {$CFG->dbprefix}quiz1_question (
    question_id            INTEGER NOT NULL AUTO_INCREMENT,
    quiz_id                INTEGER NOT NULL,
    sequence               INTEGER NOT NULL DEFAULT 1,
    qtype                  VARCHAR(32) NOT NULL,
    title                  VARCHAR(512) NULL,
    prompt                 TEXT NOT NULL,
    points                 INTEGER NOT NULL DEFAULT 1,
    feedback               TEXT NULL,
    extra                  TEXT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}quiz1_question_pk` PRIMARY KEY (question_id),

    CONSTRAINT `{$CFG->dbprefix}quiz1_question_ibfk_1`
        FOREIGN KEY (`quiz_id`)
        REFERENCES `{$CFG->dbprefix}quiz1_quiz` (`quiz_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}quiz1_question_indx_1` ( quiz_id, sequence )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}quiz1_answer",
"create table {$CFG->dbprefix}quiz1_answer (
    answer_id              INTEGER NOT NULL AUTO_INCREMENT,
    question_id            INTEGER NOT NULL,
    sequence               INTEGER NOT NULL DEFAULT 1,
    answer_text            TEXT NOT NULL,
    is_correct             TINYINT(1) NOT NULL DEFAULT 0,
    feedback               TEXT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}quiz1_answer_pk` PRIMARY KEY (answer_id),

    CONSTRAINT `{$CFG->dbprefix}quiz1_answer_ibfk_1`
        FOREIGN KEY (`question_id`)
        REFERENCES `{$CFG->dbprefix}quiz1_question` (`question_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}quiz1_answer_indx_1` ( question_id, sequence )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}quiz1_answer",
"drop table if exists {$CFG->dbprefix}quiz1_question",
"drop table if exists {$CFG->dbprefix}quiz1_quiz"
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    return 202610010004;
};
