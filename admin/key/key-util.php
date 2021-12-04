<?php

use \Tsugi\Util\U;

function validate_key_details($key_key, $deploy_key, $issuer_id, $lms_issuer, $old_key_key=null, $old_deploy_key=null, $old_issuer_id=null) {
    global $PDOX, $CFG;

    // Enforce in software because MySQL can't do it
    // CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    //  CHECK (
    //        (key_sha256 IS NOT NULL OR deploy_sha256 IS NOT NULL)
    //  )
    if ( strlen($key_key) < 1 && strlen($deploy_key) < 1 ) {
        $_SESSION['error'] = "Either consumer key or deployment id are required";
        return false;
    }

    // Enforce in software because MySQL can't do it
    // CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    //  CHECK (
    //     (deploy_key IS NOT NULL AND issuer_id IS NOT NULL)
    //     OR (deploy_key NOT NULL AND issuer_id NOT NULL)
    /*
    $have_issuer = strlen($issuer_id) > 0 || strlen($lms_issuer) > 0;
    if ( (strlen($deploy_key) > 0 && ! $have_issuer) ||
         (strlen($deploy_key) < 1 && $have_issuer) 
    ) {
        $_SESSION['error'] = "You must specify an issuer when you specify a deployment id";
        return false;
    }
     */

    $key_sha256 = U::lti_sha256($key_key);
    $deploy_sha256 = U::lti_sha256($deploy_key);

    // TODO: Decide if we put this in the DB
    // Extra constraint in software is oauth key is there is must be unique
    if ( $key_key != $old_key_key && strlen($key_key) > 0 ) {
        $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :key_sha256",
            array(':key_sha256' => $key_sha256)
        );
        if ( $row ) {
            $_SESSION['error'] = "Cannot add the same OAuth Consumer Key more than once";
            return false;
        }
    }

    // Now check these
    // CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
    if ( ($key_key != $old_key_key || $deploy_key != $old_deploy_key) && 
        strlen($key_key) > 0 && strlen($deploy_key) > 0 ) {
        $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :key_sha256 AND deploy_sha256 = :deploy_sha256",
            array(':key_sha256' => $key_sha256, ":deploy_sha256" => $deploy_sha256)
        );
        if ( $row ) {
            $_SESSION['error'] = "The combination of Consumer key and Deployment ID must be unique";
            return false;
        }
    }

    // CONSTRAINT `{$CFG->dbprefix}lti_key_const_2` UNIQUE(issuer_id, deploy_sha256),
    if ( ($deploy_key != $old_deploy_key || $issuer_id != $old_issuer_id) &&
	$issuer_id > 0 &&
        strlen($issuer_id) > 0 && strlen($deploy_key) > 0 ) {
        $row = $PDOX->rowDie( "SELECT deploy_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE issuer_id = :issuer_id AND deploy_sha256 = :deploy_sha256",
            array(':issuer_id' => $issuer_id, ":deploy_sha256" => $deploy_sha256)
        );
        if ( $row ) {
            $_SESSION['error'] = "The combination of Issuer and Deployment ID must be unique";
            return false;
        }
    }

    return true;
}
