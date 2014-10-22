<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}rps"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}rps",
"create table {$CFG->dbprefix}rps (
    rps_guid    varchar(64) NOT NULL,
    link_id     INTEGER NOT NULL,
    user1_id    INTEGER NOT NULL,
    play1       INTEGER NOT NULL,
    user2_id    INTEGER,
    play2       INTEGER,
    started_at  DATETIME NOT NULL,
    finished_at  DATETIME,

    CONSTRAINT `{$CFG->dbprefix}rps_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}rps_ibfk_2`
        FOREIGN KEY (`user1_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}rps_ibfk_3`
        FOREIGN KEY (`user2_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `{$CFG->dbprefix}rps_indx_1` USING HASH (`rps_guid`),
    UNIQUE(rps_guid)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

