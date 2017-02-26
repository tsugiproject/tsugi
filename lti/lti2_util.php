<?php

// See if this person is allowed to register a tool
function check_lti2_key() {
    global $PDOX, $CFG;

    if ( ! isset($_SESSION['id']) ) return 'You are not logged in.';
    $row = $PDOX->rowDie(
        "SELECT request_id, user_id, admin, state, lti
            FROM {$CFG->dbprefix}key_request
            WHERE user_id = :UID AND lti = 2 LIMIT 1",
        array(":UID" => $_SESSION['id'])
    );

    if ( $row === false ) {
        return 'You have not requested a key for this service.';
    }

    if ( $row['state'] == 0 ) {
        return 'You key has not yet been approved. '.$row['admin'];
    }

    if ( $row['state'] != 1 ) {
        return 'Your key request was not approved. '.$row['admin'];
    }

    if ( $row['lti'] != 2 ) {
        return 'Your did not request an LTI 2.0 key. '.$row['admin'];
    }

    return true;
}

function go_home()
{
    global $CFG;
    if ( isset($CFG->apphome) ) {
        header("Location: ".$CFG->apphome);
    }
    header("Location: ".$CFG->wwwroot);
}
