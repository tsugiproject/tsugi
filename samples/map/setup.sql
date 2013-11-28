
drop table if exists webauto_context_map;
create table webauto_context_map (
    context_id  MEDIUMINT NOT NULL,
    user_id     MEDIUMINT NOT NULL,
    attend      DATE NOT NULL,
    lat         FLOAT,
    lng         FLOAT,
    color       INTEGER,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `webauto_context_map_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `webauto_lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `webauto_context_map_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `webauto_lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(context_id, user_id, attend)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

