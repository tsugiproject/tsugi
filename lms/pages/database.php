<?php

// If the table does not exist, these create statements will be used
// And the version will be set to 1

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}pages",
"create table {$CFG->dbprefix}pages (
    page_id                INTEGER NOT NULL AUTO_INCREMENT,
    context_id             INTEGER NOT NULL,
    title                  VARCHAR(512) NOT NULL,
    logical_key            VARCHAR(99) NOT NULL,
    body                   TEXT NOT NULL,
    published              TINYINT(1) NOT NULL DEFAULT 0,
    is_main                TINYINT(1) NOT NULL DEFAULT 0,
    is_front_page          TINYINT(1) NOT NULL DEFAULT 0,
    user_id                INTEGER NOT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `{$CFG->dbprefix}pages_pk` PRIMARY KEY (page_id),

    CONSTRAINT `{$CFG->dbprefix}pages_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}pages_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `{$CFG->dbprefix}pages_unique` (`context_id`, `logical_key`),

    INDEX `{$CFG->dbprefix}pages_indx_1` ( context_id ),
    INDEX `{$CFG->dbprefix}pages_indx_2` ( logical_key ),
    INDEX `{$CFG->dbprefix}pages_indx_3` ( published ),
    INDEX `{$CFG->dbprefix}pages_indx_4` ( is_main ),
    INDEX `{$CFG->dbprefix}pages_indx_5` ( is_front_page )

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}pages"
);

// Database upgrades
$DATABASE_UPGRADE = function($oldversion) {
    global $CFG, $PDOX;
    
    // Add is_front_page column if it doesn't exist (migration for existing installations)
    if ( $oldversion < 202501180000 ) {
        if ( ! $PDOX->columnExists('is_front_page', "{$CFG->dbprefix}pages") ) {
            $sql = "ALTER TABLE {$CFG->dbprefix}pages ADD is_front_page TINYINT(1) NOT NULL DEFAULT 0";
            echo("Upgrading: ".$sql."<br/>\n");
            error_log("Upgrading: ".$sql);
            $q = $PDOX->queryReturnError($sql);
            if ( ! $q->success ) {
                echo("Warning: Could not add is_front_page column: ".$q->errorImplode."<br/>\n");
                error_log("Warning: Could not add is_front_page column: ".$q->errorImplode);
            } else {
                // Add index for is_front_page
                $sql_index = "ALTER TABLE {$CFG->dbprefix}pages ADD INDEX `{$CFG->dbprefix}pages_indx_5` ( is_front_page )";
                echo("Upgrading: ".$sql_index."<br/>\n");
                error_log("Upgrading: ".$sql_index);
                $q_index = $PDOX->queryReturnError($sql_index);
                if ( ! $q_index->success ) {
                    // Index might already exist, that's okay
                    echo("Note: Index may already exist (this is okay)<br/>\n");
                }
            }
        }
    }
    
    return 202501180000;
};
