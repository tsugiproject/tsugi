<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}mail_send",
"drop table if exists {$CFG->dbprefix}mail_bulk");

$DATABASE_INSTALL = array(

array( "{$CFG->dbprefix}mail_bulk",
"create table {$CFG->dbprefix}mail_bulk (
    bulk_id             INTEGER NOT NULL AUTO_INCREMENT,

    user_id             INTEGER NOT NULL,
    context_id          INTEGER NOT NULL,

    subject             VARCHAR(256) NULL,
    body                TEXT NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_bulk_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,

    PRIMARY KEY (bulk_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}mail_sent",
"create table {$CFG->dbprefix}mail_sent(
    sent_id             INTEGER NOT NULL AUTO_INCREMENT,

    context_id          INTEGER NOT NULL,
    link_id             INTEGER NULL,

    user_to             INTEGER NULL,
    user_from           INTEGER NULL,

    subject             VARCHAR(256) NULL,
    body                TEXT NULL,

    json                TEXT NULL,
    created_at          DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_2`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_3`
        FOREIGN KEY (`user_to`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,

    CONSTRAINT `{$CFG->dbprefix}mail_sent_ibfk_4`
        FOREIGN KEY (`user_from`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE NO ACTION ON UPDATE NO ACTION,

    PRIMARY KEY (sent_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

$DATABASE_UPGRADE = function($oldversion) {
    global $CFG;

    return 1;
}; // Don't forget the semicolon on anonymous functions :)

