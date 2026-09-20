<?php

if ( ! isset($CFG) ) exit;

// Site-wide course catalog. A row is either an lti_context pointer or an
// external URL (XOR). Catalog-owned hero can override the course Settings image.

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}course_catalog",
"create table {$CFG->dbprefix}course_catalog (
    catalog_id           INTEGER NOT NULL AUTO_INCREMENT,
    context_id           INTEGER NULL,
    external_url         TEXT NULL,
    title                VARCHAR(512) NOT NULL,
    short_description    VARCHAR(512) NULL,
    description          MEDIUMTEXT NULL,
    published            TINYINT(1) NOT NULL DEFAULT 0,
    sort_order           INTEGER NOT NULL DEFAULT 0,
    new_window           TINYINT(1) NOT NULL DEFAULT 1,

    hero                 MEDIUMBLOB NULL,
    hero_mime            VARCHAR(64) NULL,
    hero_bytes           INTEGER NULL,
    hero_updated_at      TIMESTAMP NULL,

    user_id              INTEGER NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NULL,

    CONSTRAINT `{$CFG->dbprefix}course_catalog_pk` PRIMARY KEY (catalog_id),

    CONSTRAINT `{$CFG->dbprefix}course_catalog_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `{$CFG->dbprefix}lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}course_catalog_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    UNIQUE KEY `{$CFG->dbprefix}course_catalog_context` (`context_id`),
    INDEX `{$CFG->dbprefix}course_catalog_indx_1` (published, sort_order)

) ENGINE = InnoDB DEFAULT CHARSET=utf8;")
);

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}course_catalog"
);

$DATABASE_UPGRADE = function($oldversion) {
    // No ALTERs yet. Keep the version assigned on table create ($CFG->dbversion).
    return $oldversion;
};
