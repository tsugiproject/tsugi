<?php

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

