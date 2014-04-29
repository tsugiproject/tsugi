<?php

if ( ! isset($CFG) ) {
    die("This file is not supposed to be accessed directly.  It is activated using the
        'Admin' feature from the main page of the application.");
}

$DATABASE_UNINSTALL = "drop table if exists {$CFG->dbprefix}sample_chat";

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}sample_chat",
"create table {$CFG->dbprefix}sample_chat (
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    chat        VARCHAR(1042) NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}sample_chat_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}sample_chat_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8"));

