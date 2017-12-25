<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

function sanity_check() {
    global $CFG;

    if ( !isset($_SESSION['id']) ) {
        die_with_error_log('Error: Must be logged in to use Google Classroom');
    }

    if ( !isset($_SESSION['lti']) ) {
        $_SESSION['error'] = 'Please log out and back in.';
        header('Location: '.$CFG->apphome);
        return false;
    }

    if ( !isset($_SESSION['lti']['key_id']) ) {
        die_with_error_log('Error: Session is missing key_id');
    }

    return true;
}

