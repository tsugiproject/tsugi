<?php
// To allow this to be called directly or from admin/upgrade.php
if ( !isset($PDOX) ) {
    require_once "../../config.php";
    $CURRENT_FILE = __FILE__;
    require $CFG->dirroot."/admin/migrate-setup.php";
}

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}attend"
);

// The SQL to create the necessary tables is the don't exist
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}attend",
"create table {$CFG->dbprefix}attend (
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    attend      DATE NOT NULL,
    ipaddr      VARCHAR(64),
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}attend_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}attend_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id, attend)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

// Code for the post create hook
$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;

    if ( $table == "{$CFG->dbprefix}attend") {
        echo("If we needed a post-create hook for the attend table, it would go here.");
    }

}; // Don't forget the semicolon on anonymous functions :)

// Code to do the database upgrade
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    // A redundant ALTER TABLE sample just to show how this is done.
    if ( $oldversion < 201408240800 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}attend MODIFY updated_at DATETIME NOT NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    return 201408240800;

}; // Don't forget the semicolon on anonymous functions :)

// Do the actual migration if we are not in admin/upgrade.php
if ( isset($CURRENT_FILE) ) {
    include $CFG->dirroot."/admin/migrate-run.php";
    $OUTPUT->footer();
}
