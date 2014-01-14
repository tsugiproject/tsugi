<?php

// TODO - Think about dependencies to force an order

// If the table does not exist, these create statements will be used
// And the version will be set to 1
$DATABASE_INSTALL = array( 
array( "{$CFG->dbprefix}sample_blob", 
"create table {$CFG->dbprefix}sample_blob (
    file_id      MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL, 

    context_id   MEDIUMINT NULL,
	file_name    VARCHAR(2048),
    deleted      TINYINT(1),
    content      LONGBLOB NULL,
	contenttype  VARCHAR(256) NULL,
    path         VARCHAR(2048) NULL,

    json         TEXT NULL,
    created_at   DATETIME NOT NULL,
    accessed_at  DATETIME NOT NULL,

    INDEX `{$CFG->dbprefix}blob_indx_1` USING HASH (`file_sha256`),

    CONSTRAINT `{$CFG->dbprefix}blob_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}sample_blob";

// No upgrades yet
$DATABASE_UPGRADE = function($oldversion) { return 1; };


