<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}blob_file",
"create table {$CFG->dbprefix}blob_file (
    file_id      INTEGER NOT NULL KEY AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL,

    context_id   INTEGER NULL,
    file_name    VARCHAR(2048),
    deleted      TINYINT(1),
    contenttype  VARCHAR(256) NULL,
    path         VARCHAR(2048) NULL,

    content      LONGBLOB NULL,

    json         TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accessed_at          TIMESTAMP NOT NULL DEFAULT '1970-01-02 00:00:00',

    INDEX `{$CFG->dbprefix}blob_indx_1` USING HASH (`file_sha256`),

    CONSTRAINT `{$CFG->dbprefix}blob_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}blob_file";

// No upgrades yet
$DATABASE_UPGRADE = function($oldversion) { 
    global $CFG, $PDOX;

    if ( $oldversion < 201810151700 ) {
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

    return 201710151700;

};


