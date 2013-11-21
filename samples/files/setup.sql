drop table if exists webauto_files;
create table webauto_files (
    file_id      MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL, 

    context_id   MEDIUMINT NULL,
    deleted      TINYINT(1),
    content      BLOB NULL,
    path         VARCHAR(2048) NULL,

    json         TEXT NULL,
    created_at   DATETIME NOT NULL,
    accessed_at  DATETIME NOT NULL,

    INDEX `webauto_chat_indx_1` USING HASH (`file_sha256`),

    CONSTRAINT `webauto_files_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `webauto_lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    UNIQUE(context_id, file_sha256)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

