<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}context_map"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}context_map",
"create table {$CFG->dbprefix}context_map (
    context_id  INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    lat         FLOAT,
    lng         FLOAT,
    color       INTEGER,
    email       TINYINT,
    name        TINYINT,
    first       TINYINT,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}context_map_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}context_map_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(context_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")

);


$DATABASE_UPGRADE = function($pdo, $oldversion) {
    global $CFG;

    // Version 2014041200 improvements
    if ( $oldversion < 2014042000 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}context_map ADD email TINYINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = pdo_query_die($pdo, $sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}context_map ADD name TINYINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = pdo_query_die($pdo, $sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}context_map ADD first TINYINT";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = pdo_query_die($pdo, $sql);
    }
    return 2014042000;
};
