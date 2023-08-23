

    cd admin/expire
    php pii-batch.php 
    UPDATE lti_user
        SET displayname=NULL, email=NULL 
            WHERE created_at <= (CURRENT_DATE() - INTERVAL 150 DAY)
            AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL 150 DAY))
            AND (displayname IS NOT NULL OR email IS NOT NULL)
    This is a dry run, use 'php pii-batch.php remove' to actually remove the data.
    User records with PII and have not logged in in 150 days: 568730 

    php pii-batch.php remove
    UPDATE lti_user
        SET displayname=NULL, email=NULL 
            WHERE created_at <= (CURRENT_DATE() - INTERVAL 150 DAY)
            AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL 150 DAY))
            AND (displayname IS NOT NULL OR email IS NOT NULL)
    This IS NOT A DRILL!
    ...
    Rows updated: 568729

    Ellapsed time: 16 seconds

There are limits in `login-batch.php` that you can edit and extend.  Make sure
to `git checkout login-batch.php` to go back to the default version.

    php login-batch.php user remove
    DELETE FROM lti_user
    WHERE created_at <= (CURRENT_DATE() - INTERVAL 200 DAY)
            AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL 200 DAY))
    ORDER BY login_at LIMIT 50000
    This IS NOT A DRILL!
    ...
    Rows updated: 50000

    Ellapsed time: 77 seconds


    php login-batch.php context remove
    DELETE FROM lti_context
    WHERE created_at <= (CURRENT_DATE() - INTERVAL 600 DAY)
            AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL 600 DAY))
    ORDER BY login_at LIMIT 10
    This IS NOT A DRILL!
    ...
    Rows updated: 10

    Ellapsed time: 0 seconds



    php login-batch.php tenant remove
    DELETE FROM lti_key
    WHERE created_at <= (CURRENT_DATE() - INTERVAL 800 DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL 800 DAY))
        AND (key_key <> 'google.com' AND key_key <> '12345') ORDER BY login_at LIMIT 100
    This IS NOT A DRILL!
    ...
    Rows updated: 78

    Ellapsed time: 0 seconds




