<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}blob_file",
"create table {$CFG->dbprefix}blob_file (
    file_id      INTEGER NOT NULL AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL,

    context_id   INTEGER NULL,
    link_id      INTEGER NULL,
    backref      CHAR(128) NULL,
    file_name    VARCHAR(2048),
    bytelen      BIGINT NULL,
    deleted      TINYINT(1),
    contenttype  VARCHAR(256) NULL,
    path         VARCHAR(2048) NULL,

    blob_id      INTEGER,

    json         TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accessed_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    CONSTRAINT `{$CFG->dbprefix}lti_blob_file_pk` PRIMARY KEY (file_id),

    CONSTRAINT `{$CFG->dbprefix}blob_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8;"),
array( "{$CFG->dbprefix}blob_blob",
"create table {$CFG->dbprefix}blob_blob (
    blob_id      INTEGER NOT NULL AUTO_INCREMENT,
    blob_sha256  CHAR(64) NOT NULL,

    deleted      TINYINT(1),

    content      LONGBLOB NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accessed_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    PRIMARY KEY(blob_id),
    UNIQUE(blob_sha256)

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_POST_CREATE = function($table) {
    global $CFG, $PDOX;
    if ( $table == "{$CFG->dbprefix}blob_file" ) {
        $sql = "CREATE INDEX `{$CFG->dbprefix}blob_indx_1` ON {$CFG->dbprefix}blob_file ( file_sha256 ) USING HASH";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        if ( $PDOX->isMySQL() ) {
            $sql = "CREATE INDEX `{$CFG->dbprefix}blob_indx_2` ON {$CFG->dbprefix}blob_file ( path (128) )";
        } else {
            $sql = "CREATE INDEX `{$CFG->dbprefix}blob_indx_2` ON {$CFG->dbprefix}blob_file ( path )";
        }
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
        $sql = "CREATE INDEX `{$CFG->dbprefix}blob_indx_4` ON {$CFG->dbprefix}blob_file ( context_id )";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    if ( $table == "{$CFG->dbprefix}blob_blob" ) {
        $sql = "CREATE INDEX `{$CFG->dbprefix}blob_indx_3` ON {$CFG->dbprefix}blob_blob (`blob_sha256`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }
};

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}blob_file",
"drop table if exists {$CFG->dbprefix}blob_blob"
);


// No upgrades yet
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;

    if ( $oldversion < 201801011200 ) {
        $sql= "UPDATE {$CFG->dbprefix}blob_file SET created_at='1970-01-02 00:00:00' WHERE created_at < '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "UPDATE {$CFG->dbprefix}blob_file SET accessed_at='1970-01-02 00:00:00' WHERE accessed_at < '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file MODIFY created_at TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file MODIFY accessed_at TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00'";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // This is a place to make sure added fields are present
    // if you add a field to a table, put it in here and it will be auto-added
    $add_some_fields = array(
        array('blob_file', 'bytelen', 'BIGINT NULL'),
        array('blob_file', 'backref', 'CHAR(128) NULL'), // 202012201622
    );

    foreach ( $add_some_fields as $add_field ) {
        if (count($add_field) != 3 ) {
            echo("Badly formatted add_field");
            var_dump($add_field);
            continue;
        }
        $table = $add_field[0];
        $column = $add_field[1];
        $type = $add_field[2];
        if ( $PDOX->columnExists($column, "{$CFG->dbprefix}".$table ) ) continue;
        $sql= "ALTER TABLE {$CFG->dbprefix}$table ADD $column $type";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    // Removed 2018 and earlier as of 2012-12-20

    return 202012201622;
};


