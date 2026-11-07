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

    -- Map pin choice copied out of json. 0 not chosen, 1 hidden,
    -- 2 location, 3 location and name. The map page still reads json.
    map                 TINYINT NULL,

    premium             INTEGER NOT NULL DEFAULT 0,
    premium_at          TIMESTAMP NULL,
    premium_json        MEDIUMTEXT NULL,

    json                MEDIUMTEXT NULL,
    login_at            TIMESTAMP NULL,
    entity_version      INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    INDEX `{$CFG->dbprefix}profile_indx_email` (email(255)),

    CONSTRAINT `{$CFG->dbprefix}profile_const_pk` PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    $add_some_fields = array(
        array('profile', 'google_translate', 'TINYINT(1) NOT NULL DEFAULT 0'),
        array('profile', 'premium', 'INTEGER NOT NULL DEFAULT 0'),
        array('profile', 'premium_at', 'TIMESTAMP NULL'),
        array('profile', 'premium_json', 'MEDIUMTEXT NULL'),
        array('profile', 'deleted_at', 'TIMESTAMP NULL'),
        array('profile', 'updated_at', 'TIMESTAMP NULL'),
        array('profile', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'),
        array('profile', 'map', 'TINYINT NULL'),
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

    // email is TEXT. A 255-char prefix covers an address and lets
    // unsubscribe opt-out match email = :E instead of scanning LOWER(email).
    $email_index = "{$CFG->dbprefix}profile_indx_email";
    if ( ! $PDOX->indexExists($email_index, "{$CFG->dbprefix}profile") ) {
        $sql = "CREATE INDEX `{$email_index}` ON {$CFG->dbprefix}profile (email(255))";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $message = "Non-Fatal error creating index: ".$q->errorImplode;
            error_log($message);
            echo($message."<br/>\n");
        }
    }

    // Copy profile.json.map into the column once. Later upgrades leave the
    // column alone, and the map page still searches json.
    if ( $oldversion < 202611070002 ) {
        if ( ! $PDOX->columnExists('map', "{$CFG->dbprefix}profile") ) {
            die("Unable to add profile.map<br/>\n");
        }
        $p = $CFG->dbprefix;
        $from_json = "JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(json), json, NULL), '$.map'))";
        $sql = "UPDATE {$p}profile
            SET `map` = CAST({$from_json} AS SIGNED)
            WHERE `map` IS NULL
              AND {$from_json} REGEXP '^[0-3]\$'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        echo("Profiles with map copied from json: ".$q->rowCount()."<br/>\n");

        $counts = $PDOX->allRowsDie(
            "SELECT `map` AS map, COUNT(*) AS n FROM {$p}profile GROUP BY `map` ORDER BY `map`"
        );
        foreach ( $counts as $count ) {
            $value = $count['map'];
            $label = $value === null ? 'NULL' : $value;
            echo("profile.map ".$label.": ".$count['n']."<br/>\n");
        }

        $mismatch = $PDOX->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}profile
             WHERE {$from_json} REGEXP '^[0-3]\$'
               AND (`map` IS NULL OR `map` <> CAST({$from_json} AS SIGNED))"
        );
        if ( ! is_array($mismatch) || (int) $mismatch['n'] !== 0 ) {
            $n = is_array($mismatch) ? $mismatch['n'] : 'unknown';
            die("profile.map does not match json for {$n} rows<br/>\n");
        }
        echo("profile.map matches json for every 0-3 value<br/>\n");
    }

    return 202611070002;
};
