-- This builds the tables for the MySql Data model

drop database if exists webauto;
create database webauto DEFAULT CHARACTER SET utf8;
grant all on webauto.* to ltiuser@'localhost' identified by 'ltipassword';
grant all on webauto.* to ltiuser@'127.0.0.1' identified by 'ltipassword';

use webauto;

drop table if exists sample_profile;
drop table if exists webauto_lti_result;
drop table if exists webauto_lti_service;
drop table if exists webauto_lti_membership;
drop table if exists webauto_lti_link;
drop table if exists webauto_lti_context;
drop table if exists webauto_lti_user;
drop table if exists webauto_lti_key;

create table webauto_lti_key (
	key_id			MEDIUMINT NOT NULL AUTO_INCREMENT,
	key_sha256		CHAR(64) NOT NULL UNIQUE,
	key_key			VARCHAR(4096) NOT NULL,

	secret			VARCHAR(4096) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

	UNIQUE(key_sha256),
	PRIMARY KEY (key_id)
 ) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_context (
	context_id		MEDIUMINT NOT NULL AUTO_INCREMENT,
	context_sha256	CHAR(64) NOT NULL,
	context_key		VARCHAR(4096) NOT NULL,

	key_id			MEDIUMINT NOT NULL, 

	title			VARCHAR(2048) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

    CONSTRAINT `webauto_lti_context_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `webauto_lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

	UNIQUE(key_id, context_sha256),
	PRIMARY KEY (context_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_link (
	link_id		MEDIUMINT NOT NULL AUTO_INCREMENT,
	link_sha256	CHAR(64) NOT NULL,
	link_key	VARCHAR(4096) NOT NULL,

	context_id		MEDIUMINT NOT NULL, 

	title			VARCHAR(2048) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

    CONSTRAINT `webauto_lti_link_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `webauto_lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

	UNIQUE(link_sha256),
	PRIMARY KEY (link_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_user (
	user_id			MEDIUMINT NOT NULL AUTO_INCREMENT,
	user_sha256		CHAR(64) NOT NULL,
	user_key		VARCHAR(4096) NOT NULL,

	key_id			MEDIUMINT NOT NULL,
	profile_id		MEDIUMINT NOT NULL,

	displayname		VARCHAR(2048) NULL,
	email			VARCHAR(2048) NULL,
	locale			CHAR(63) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

	CONSTRAINT `webauto_lti_user_ibfk_1` 
	    FOREIGN KEY (`key_id`) 
	    REFERENCES `webauto_lti_key` (`key_id`) 
	    ON DELETE CASCADE ON UPDATE CASCADE,

	UNIQUE(key_id, user_sha256),
	PRIMARY KEY (user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_membership (
	membership_id	MEDIUMINT NOT NULL AUTO_INCREMENT,

	context_id		MEDIUMINT NOT NULL, 
	user_id			MEDIUMINT NOT NULL, 

	role			SMALLINT NULL,

	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

    CONSTRAINT `webauto_lti_membership_ibfk_1`
        FOREIGN KEY (`context_id`)
        REFERENCES `webauto_lti_context` (`context_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `webauto_lti_membership_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `webauto_lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

	UNIQUE(context_id, user_id),
	PRIMARY KEY (membership_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_service (
	service_id		MEDIUMINT NOT NULL AUTO_INCREMENT,
	service_sha256	CHAR(64) NOT NULL,
	service_key		VARCHAR(4096) NOT NULL,

	key_id			MEDIUMINT NOT NULL, 

	format			VARCHAR(1024) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

    CONSTRAINT `webauto_lti_service_ibfk_1`
        FOREIGN KEY (`key_id`)
        REFERENCES `webauto_lti_key` (`key_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

	UNIQUE(key_id, service_sha256),
	PRIMARY KEY (service_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

create table webauto_lti_result (
	result_id		MEDIUMINT NOT NULL AUTO_INCREMENT,
	link_id			MEDIUMINT NOT NULL, 
	user_id			MEDIUMINT NOT NULL,

	sourcedid		VARCHAR(2048) NOT NULL,
	sourcedid_sha256	CHAR(64) NOT NULL,

	service_id		MEDIUMINT NULL,

	grade			FLOAT NULL,
	note			VARCHAR(2048) NOT NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

    CONSTRAINT `webauto_lti_result_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `webauto_lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `webauto_lti_result_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `webauto_lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `webauto_lti_result_ibfk_3`
        FOREIGN KEY (`service_id`)
        REFERENCES `webauto_lti_service` (`service_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

	-- Note service_id is not part of the key on purpose 
	-- It is data that can change and can be null in LTI 2.0
	UNIQUE(link_id, user_id, sourcedid_sha256),
	PRIMARY KEY (result_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- Profile hangs out as a leaf 
create table sample_profile (
	profile_id		MEDIUMINT NOT NULL AUTO_INCREMENT,

	displayname		VARCHAR(2048) NULL,
	email			VARCHAR(2048) NULL,
	locale			CHAR(63) NULL,

	json			TEXT NULL,
	created_at		DATETIME NOT NULL,
	updated_at		DATETIME NOT NULL,

	PRIMARY KEY (profile_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

insert into webauto_lti_key (key_sha256, key_key, secret) values ( '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5', '12345', 'secret')

