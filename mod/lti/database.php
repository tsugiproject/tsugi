<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}lti_domain"
);

// The SQL to create the tables if they don't exist
// The domain
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}lti_domain",
"create table {$CFG->dbprefix}lti_domain (
    key_id      INTEGER NOT NULL,
    context_id  INTEGER NULL,
    domain      VARCHAR(128),
    port        INTEGER NULL,
    consumer_key  TEXT,
    secret      TEXT,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `{$CFG->dbprefix}lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}lti_domain_ibfk_2`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(key_id, context_id, domain, port)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

