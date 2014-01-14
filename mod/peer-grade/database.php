<?php

$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}peer_flag",
"drop table if exists {$CFG->dbprefix}peer_grade",
"drop table if exists {$CFG->dbprefix}peer_part",
"drop table if exists {$CFG->dbprefix}peer_submit",
"drop table if exists {$CFG->dbprefix}peer_assn"
);

$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}peer_assn",
"create table {$CFG->dbprefix}peer_assn (
    assn_id    MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    link_id    MEDIUMINT NOT NULL,
    due_at     DATETIME NOT NULL,

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_assn_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),

array( "{$CFG->dbprefix}peer_submit",
"create table {$CFG->dbprefix}peer_submit (
    submit_id  MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    assn_id    MEDIUMINT NOT NULL,
    user_id    MEDIUMINT NOT NULL,

    json         TEXT NULL,
    note         TEXT NULL,
    reflect      TEXT NULL,
    flag         BOOLEAN,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_1`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}peer_submit_ibfk_2`
        FOREIGN KEY (`assn_id`)
        REFERENCES `{$CFG->dbprefix}peer_assn` (`assn_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(assn_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_part",
"create table {$CFG->dbprefix}peer_part (
    part_id      MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    submit_id    MEDIUMINT NOT NULL,
    partno       MEDIUMINT NOT NULL,

    note        TEXT NULL,
    blob_id     MEDIUMINT NULL,

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_part_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Perhaps should foreign key the blob

    UNIQUE(part_id, submit_id, partno)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

array( "{$CFG->dbprefix}peer_grade",
"create table {$CFG->dbprefix}peer_grade (
    grade_id     MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    submit_id    MEDIUMINT NOT NULL,
    user_id      MEDIUMINT NOT NULL, -- The user doing the grading

    flag         BOOLEAN,
    points       DOUBLE NULL,
    note         TEXT NULL,

    json         TEXT NULL,

    updated_at  DATETIME NOT NULL,
    created_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}peer_grade_ibfk_1`
        FOREIGN KEY (`submit_id`)
        REFERENCES `{$CFG->dbprefix}peer_submit` (`submit_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(submit_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8") ,

);

