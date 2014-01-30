<?php

$DATABASE_INSTALL = array(
"drop table if exists {$CFG->dbprefix}attend",
"drop table if exists {$CFG->dbprefix}attend_code"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}attend_code",
"create table {$CFG->dbprefix}attend_code (
    link_id     INTEGER NOT NULL,
    code        varchar(64) NOT NULL,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}attend_code_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}attend",
"create table {$CFG->dbprefix}attend (
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    attend      DATE NOT NULL,
    ipaddr      varchar(64),
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

