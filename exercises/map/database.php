<?php

$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}sample_map";

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}sample_map",
"create table {$CFG->dbprefix}sample_map (
    context_id  INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    lat         FLOAT,
    lng         FLOAT,
    color       INTEGER,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}sample_map_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}sample_map_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(context_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"));

