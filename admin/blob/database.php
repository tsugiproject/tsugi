<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1

// Note that as of 2018-02, new installs dont have a content
// column in blob_file.
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}blob_file",
"create table {$CFG->dbprefix}blob_file (
    file_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL,

    context_id   INTEGER NULL,
    link_id      INTEGER NULL,
    file_name    VARCHAR(2048),
    deleted      TINYINT(1),
    contenttype  VARCHAR(256) NULL,
    path         VARCHAR(2048) NULL,

    blob_id      INTEGER,

    json         TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accessed_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    INDEX `{$CFG->dbprefix}blob_indx_1` USING HASH ( file_sha256 ),
    INDEX `{$CFG->dbprefix}blob_indx_2` ( path (128) ),
    INDEX `{$CFG->dbprefix}blob_indx_4` ( context_id ),

    CONSTRAINT `{$CFG->dbprefix}blob_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
array( "{$CFG->dbprefix}blob_blob",
"create table {$CFG->dbprefix}blob_blob (
    blob_id      INTEGER NOT NULL AUTO_INCREMENT,
    blob_sha256  CHAR(64) NOT NULL,

    deleted      TINYINT(1),

    content      LONGBLOB NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accessed_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    INDEX `{$CFG->dbprefix}blob_indx_3` (`blob_sha256`),

    PRIMARY KEY(blob_id),
    UNIQUE(blob_sha256)

) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

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

    // Check the path in case an upgrade was missed
    if ( ! $PDOX->columnExists('path', "{$CFG->dbprefix}blob_file") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file ADD path VARCHAR(2048) NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    // 201802091240
    if ( ! $PDOX->columnExists('link_id', "{$CFG->dbprefix}blob_file") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file ADD link_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }
    if ( ! $PDOX->columnExists('blob_id', "{$CFG->dbprefix}blob_file") ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file ADD blob_id INTEGER NULL";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryDie($sql);
    }

    if ( $oldversion < 201803021044 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}blob_blob ADD INDEX `{$CFG->dbprefix}blob_indx_3` (`blob_sha256`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);

        $sql= "ALTER TABLE {$CFG->dbprefix}blob_file ADD INDEX `{$CFG->dbprefix}blob_indx_2` ( path(128))";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    if ( $oldversion < 201803050123 ) {
        $sql= "ALTER TABLE {$CFG->dbprefix}blob_blob ADD INDEX `{$CFG->dbprefix}blob_indx_4` (`context_id`)";
        echo("Upgrading: ".$sql."<br/>\n");
        error_log("Upgrading: ".$sql);
        $q = $PDOX->queryReturnError($sql);
    }

    return 201803050123;

};


