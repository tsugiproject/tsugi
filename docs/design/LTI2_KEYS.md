Managing LTI 2.x Re-Registration
================================

Password management in LTI 2.1 (in development) has a pseudo-transactional aspect 
when handling a re-registration process.   Re-registration starts in the LMS, is 
half-finished by the external tool and then finished in the LMS and a commit message 
is sent to the Tool.   It is only partially transactional - if a new re-registration 
is sent from the same LMS the old "in progress registration" is discarded.  During 
the phase where the tool has processed the "change password" but the LMS has not 
committed it, it is up to the External tool as to what password to accept.  I decided 
to accept both passwords during the "not yet committed phase".

Because there is never more than one "in flight" transaction allowed and because I 
wanted to accept either password during the transaction, I took an approach of 
storing the new data in the same row as the old data in the lti_key table - 
which now looks like this:

    key_id              INTEGER NOT NULL AUTO_INCREMENT,
    key_sha256          CHAR(64) NOT NULL UNIQUE,
    key_key             VARCHAR(4096) NOT NULL,
    user_id             INTEGER NULL,

    secret              VARCHAR(4096) NULL,
    new_secret          VARCHAR(4096) NULL,

    consumer_profile    TEXT NULL,
    new_consumer_profile  TEXT NULL,

During re-registration, The external tool puts new_secret and new_consumer_profile 
into the table.  When it gets the commit message from the LMS, it (in a short-lived 
real transaction) copies the new info to the old info and nulls out the new info.

Launch handling looks at the old secret first and if that fails or old secret is null, 
it checks new secret.  You can see this new logic at line in:

https://github.com/csev/tsugi/blob/master/lib/ltix.class.php

Uniqueness of oauth_consumer_key values in LTI 2.x
--------------------------------------------------

This is tricky because because in LTI 2.x, the LMS chooses the oauth_consumer_key (proxy_guid) 
and the External tool chooses the secret as part of the security contract.  In a multi-tenant 
environment I need all the oauth_consumer_keys in my table to be absolutely unique.  The spec 
says that keys need to be guids - but to protect against an LMS trying to hijack a key, 
there is tricky interplay between the user_id (local owner of the key in Tsugi) and the key_id
and the INSERT / UPDATE (update will be needed for re-registration).  This logic can be seen 
in:

https://github.com/csev/tsugi/blob/master/lti/lti2.php

Basically the goal of that code is to make sure that whichever user "gets" a particular
key - all other registrations or re-registrations for that key are rejected unless the same
user is doing the re-registration using their Tsugi-local login.
