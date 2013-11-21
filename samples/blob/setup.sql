drop table if exists webauto_blob;
create table webauto_blob (
    file_id      MEDIUMINT NOT NULL KEY AUTO_INCREMENT,
    file_sha256  CHAR(64) NOT NULL, 

    context_id   MEDIUMINT NULL,
	file_name    VARCHAR(2048),
    deleted      TINYINT(1),
    content      LONGBLOB NULL,
	contenttype  VARCHAR(256) NULL,
    path         VARCHAR(2048) NULL,

    json         TEXT NULL,
    created_at   DATETIME NOT NULL,
    accessed_at  DATETIME NOT NULL,

    INDEX `webauto_blob_indx_1` USING HASH (`file_sha256`),

    CONSTRAINT `webauto_blob_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `webauto_lti_context` (`context_id`)
        ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE = InnoDB DEFAULT CHARSET=utf8;

