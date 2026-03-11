<?php

if ( ! isset($CFG) ) exit;

// Profile is denormalized and not tightly connected to allow
// for disconnecting and reconnecting various user_id values
$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}profile"
);

$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}profile",
        "create table {$CFG->dbprefix}profile (
    profile_id          INTEGER NOT NULL AUTO_INCREMENT,
    profile_sha256      CHAR(64) NOT NULL UNIQUE,
    profile_key         TEXT NOT NULL,
    deleted             TINYINT(1) NOT NULL DEFAULT 0,

    key_id              INTEGER NOT NULL,

    displayname         TEXT NULL,
    email               TEXT NULL,
    image               TEXT NULL,
    locale              CHAR(63) NULL,
    subscribe           SMALLINT NULL,
    google_translate    TINYINT(1) NOT NULL DEFAULT 0,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}profile_const_pk` PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $add_some_fields = array(
        array('profile', 'google_translate', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('profile', 'deleted_at', 'TIMESTAMP NULL'),
        array('profile', 'updated_at', 'TIMESTAMP NULL'),
        array('profile', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'),
    );

    foreach ( $add_some_fields as $add_field ) {
        if (count($add_field) != 3) continue;
        $table = $add_field[0];
        $column = $add_field[1];
        $type = $add_field[2];
        if ( $PDOX->columnExists($column, "{$CFG->dbprefix}".$table) ) continue;
        $sql = "ALTER TABLE {$CFG->dbprefix}$table ADD $column $type";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $PDOX->queryReturnError($sql);
    }

    return 202503100000;
};
