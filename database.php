<?php

// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

if ( ! isset($CFG) ) exit;

// Dropping tables
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}peer_flag",
"drop table if exists {$CFG->dbprefix}peer_grade",
"drop table if exists {$CFG->dbprefix}peer_submit",
"drop table if exists {$CFG->dbprefix}peer_assn"
);

// Creating tables
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}peer_assn",
"create table {$CFG->dbprefix}peer_assn (
    assn_id    INTEGER NOT NULL KEY AUTO_INCREMENT,
    link_id    INTEGER NOT NULL,
    due_at     TIMESTAMP NULL,

    json       TEXT NULL,

    updated_at  TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}peer_assn_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}peer_submit",
"create table {$CFG->dbprefix}peer_submit (
    submit_id  INTEGER NOT NULL KEY AUTO_INCREMENT,
    assn_id    INTEGER NOT NULL,
    user_id    INTEGER NOT NULL,

    json       TEXT NULL,
    note       TEXT NULL,
    reflect    TEXT NULL,

    regrade    TINYINT NULL,

    inst_points   DOUBLE NULL,
    inst_note  TEXT NULL,
    inst_id    INTEGER NULL,

    peer_marks INTEGER DEFAULT 0,

    rating     INTEGER NULL,  -- Aggregate rating

    updated_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_2`
        FOREIGN KEY (`assn_id`)
        REFERENCES `{$CFG->dbprefix}peer_assn` (`assn_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(assn_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_text",
"create table {$CFG->dbprefix}peer_text (
    text_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    assn_id      INTEGER NOT NULL,
    user_id      INTEGER NOT NULL,

    data         TEXT NULL,
    json         TEXT NULL,

    updated_at  TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}peer_text_ibfk_1`
        FOREIGN KEY (`assn_id`)
        REFERENCES `{$CFG->dbprefix}peer_assn` (`assn_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}peer_text_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_grade",
"create table {$CFG->dbprefix}peer_grade (
    grade_id     INTEGER NOT NULL KEY AUTO_INCREMENT,
    submit_id    INTEGER NOT NULL,
    user_id      INTEGER NOT NULL, -- The user doing the grading

    points       DOUBLE NULL,
    rating       TINYINT NULL,  -- Individual rating on this assignment +1 or -1
    note         TEXT NULL,

    json         TEXT NULL,

    updated_at  TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}peer_grade_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(submit_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_flag",
"create table {$CFG->dbprefix}peer_flag (
    flag_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    submit_id    INTEGER NOT NULL,
    grade_id     INTEGER NULL,
    user_id      INTEGER NOT NULL, -- The user doing the flagging

    note         TEXT NULL,
    response     TEXT NULL,
    handled      BOOLEAN NOT NULL DEFAULT FALSE,
    respond_id   INTEGER NULL,  -- The responder's user_id

    json         TEXT NULL,

    updated_at  TIMESTAMP NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}peer_flag_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(submit_id, grade_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

);

// Database upgrade
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // Version 2014042200 improvements
    if ( $oldversion < 2014042200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD regrade TINYINT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Support for instructor grading
    if ( $oldversion < 201412222310 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_points DOUBLE NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_note TEXT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD inst_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Support for ratings 
    if ( $oldversion < 201602211200 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD rating INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}peer_grade ADD rating TINYINT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // Clean up the date fields a few times...
    // OK to run these more than once..
    $current_date = \Tsugi\Util\U::conversion_time();
    if ( $current_date < 201803010000 ) {
        echo("Double checking that dates are correct for the peer grader...<br/>\n");
        $sql= "UPDATE {$CFG->dbprefix}peer_assn SET due_at='1970-01-02 00:00:00' WHERE due_at < '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $tables = array(
            'peer_flag', 'peer_grade', 'peer_submit', 'peer_assn', 'lti_link');
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


        $sql= "ALTER TABLE {$CFG->dbprefix}peer_assn MODIFY due_at TIMESTAMP NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

    }

    if ( $oldversion < 201812291121 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_flag MODIFY respond_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    if ( $oldversion < 201901202122 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}peer_submit ADD peer_marks INTEGER DEFAULT 0";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    return 201901202122;
}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
}

