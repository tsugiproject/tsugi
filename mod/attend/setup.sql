drop table if exists webauto_attend_code;
create table webauto_attend_code (
    link_id     MEDIUMINT NOT NULL,
    code        varchar(64) NOT NULL,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `webauto_attend_code_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `webauto_lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

drop table if exists webauto_attend;
create table webauto_attend (
    link_id     MEDIUMINT NOT NULL,
    user_id     MEDIUMINT NOT NULL,
    attend      DATE NOT NULL,
    ipaddr      varchar(64),
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `webauto_attend_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `webauto_lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `webauto_attend_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `webauto_lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id, attend)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

