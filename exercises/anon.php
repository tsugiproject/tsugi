<?php
/* Here are a set of queries that should anonomize most of the data in 
the db - but please check for yourself.

DELETE FROM t_blob_file;
DELETE FROM t_key_request;
DELETE FROM t_context_map;
DELETE FROM t_mail_bulk;
DELETE FROM t_mail_sent;
DELETE FROM t_sample_blob;
DELETE FROM t_sample_chat;
DELETE FROM t_profile;

-- Note that SHA2('str', 256) does not work on all MySql installs

UPDATE t_lti_key SET key_key=concat("key_",key_id), key_sha256=MD5(concat("key_",key_id)), secret=concat("secret_",key_id),new_secret=null, settings=null, user_id=null, consumer_profile=null, new_consumer_profile=null;
UPDATE t_lti_context SET title=concat("Context Title ",context_id), context_key=concat("context_key_",context_id), context_sha256=MD5(concat("key_",context_id)), settings_url=NULL, settings=NULL, json=NULL;
UPDATE t_lti_link SET title=concat("Link Title ",link_id), link_key=concat("link_key_",link_id), link_sha256=MD5(concat("link_key_",link_id)), settings_url=NULL, settings=NULL,json=NULL;
UPDATE t_lti_result SET sourcedid=concat("sourcedid_",result_id), grade=RAND(),server_grade=NULL, json=NULL;
UPDATE t_lti_service SET service_key=concat("http://www.example.com/service_",service_id), service_sha256=MD5(concat("service_id_",service_id)), json=NULL;
UPDATE t_lti_user SET user_key=concat("user_key_",user_id), user_sha256=MD5(concat("user_key_",user_id)), displayname=concat("Displayname ", user_id),email=concat("user",user_id,"@example.com"),created_at=NOW(), updated_at=NOW(), login_at=NOW(),json=NULL;
UPDATE t_peer_assn SET json=NULL;
UPDATE t_peer_flag SET note=concat("Flag Note ",flag_id),response=NULL;
UPDATE t_peer_grade SET note=concat("Grade Note ",grade_id), points=FLOOR(RAND()*11);
UPDATE t_peer_submit SET note=concat("Submit Note ",submit_id),inst_points=FLOOR(RAND()*11),inst_note=null,json=NULL, reflect=NULL;
UPDATE t_peer_text SET data=concat("Submit Text ",text_id),json=NULL;


*/
