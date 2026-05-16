<?php

use \Tsugi\Util\U;

/**
 * Normalize LTI 1.3 deployment id from a form field for database storage.
 *
 * Empty string, whitespace only, or the literal "null" are stored as NULL.
 * Leaving the field blank means the key accepts any deployment id sent by the LMS;
 * Tsugi still uses the deployment id from the launch JWT for LTI 1.3 services (for example grading).
 * Any other non-empty string is trimmed and stored as the specific deployment id.
 *
 * @param mixed $v Raw POST value
 * @return string|null
 */
function normalize_deploy_key_input($v) {
    if ( $v === null ) {
        return null;
    }
    if ( ! is_string($v) ) {
        return null;
    }
    $t = trim($v);
    if ( $t === '' || strcasecmp($t, 'null') === 0 ) {
        return null;
    }
    return $t;
}

/**
 * Resolve the LTI 1.3 (issuer URL, client id) pair for a tenant key.
 *
 * @return array{issuer_sha256:string,issuer_client:string}|null
 */
function resolve_lti13_issuer_client($issuer_id, $lms_issuer, $lms_client) {
    global $PDOX, $CFG;

    if ( ! empty($issuer_id) && (int) $issuer_id > 0 ) {
        $row = $PDOX->rowDie(
            "SELECT issuer_key, issuer_client, issuer_sha256 FROM {$CFG->dbprefix}lti_issuer
                WHERE issuer_id = :issuer_id AND (deleted IS NULL OR deleted = 0)",
            array(':issuer_id' => (int) $issuer_id)
        );
        if ( ! $row || ! U::isNotEmpty($row['issuer_client']) ) {
            return null;
        }
        $issuer_sha256 = U::isNotEmpty($row['issuer_sha256'])
            ? $row['issuer_sha256']
            : U::lti_sha256($row['issuer_key']);
        if ( ! $issuer_sha256 ) {
            return null;
        }
        return array(
            'issuer_sha256' => $issuer_sha256,
            'issuer_client' => $row['issuer_client'],
        );
    }

    if ( U::isNotEmpty($lms_issuer) && U::isNotEmpty($lms_client) ) {
        $issuer_sha256 = U::lti_sha256($lms_issuer);
        if ( ! $issuer_sha256 ) {
            return null;
        }
        return array(
            'issuer_sha256' => $issuer_sha256,
            'issuer_client' => $lms_client,
        );
    }

    return null;
}

/**
 * Reject a new or updated tenant when another key already matches the same
 * LTI 1.3 issuer URL and client id with a wildcard deployment (blank deploy_key).
 *
 * Keys with a specific deployment id may share the same issuer and client id.
 */
function validate_issuer_client_unique($issuer_id, $lms_issuer, $lms_client, $deploy_key, $exclude_key_id=null) {
    global $PDOX, $CFG;

    if ( U::isNotEmpty($deploy_key) ) {
        return true;
    }

    $pair = resolve_lti13_issuer_client($issuer_id, $lms_issuer, $lms_client);
    if ( $pair === null ) {
        return true;
    }

    $parms = array(
        ':issuer_sha256' => $pair['issuer_sha256'],
        ':issuer_client' => $pair['issuer_client'],
    );
    $exclude_sql = '';
    if ( ! empty($exclude_key_id) && (int) $exclude_key_id > 0 ) {
        $exclude_sql = ' AND k.key_id <> :exclude_key_id ';
        $parms[':exclude_key_id'] = (int) $exclude_key_id;
    }

    $row = $PDOX->rowDie(
        "SELECT k.key_id, k.key_title FROM {$CFG->dbprefix}lti_key AS k
            LEFT JOIN {$CFG->dbprefix}lti_issuer AS i ON k.issuer_id = i.issuer_id
                AND (i.deleted IS NULL OR i.deleted = 0)
            WHERE (k.deleted IS NULL OR k.deleted = 0)
                AND (k.deploy_key IS NULL OR TRIM(k.deploy_key) = '')
                $exclude_sql
                AND (
                    (i.issuer_client = :issuer_client AND (
                        i.issuer_sha256 = :issuer_sha256
                        OR (i.issuer_sha256 IS NULL AND SHA2(i.issuer_key, 256) = :issuer_sha256)
                    ))
                    OR (
                        (k.lms_issuer_sha256 IS NULL OR k.lms_issuer_sha256 = :issuer_sha256)
                        AND k.lms_client = :issuer_client
                    )
                )
            LIMIT 1",
        $parms
    );

    if ( $row ) {
        $label = U::isNotEmpty($row['key_title']) ? $row['key_title'] : ('key_id '.$row['key_id']);
        U::flashError('A tenant/key with this LTI 1.3 Platform Issuer URL and Client ID already exists ('.$label.')');
        return false;
    }

    return true;
}

function validate_key_details($key_key, $deploy_key, $issuer_id, $lms_issuer, $old_key_key=null, $old_deploy_key=null, $old_issuer_id=null, $lms_client=null, $exclude_key_id=null) {
    global $PDOX, $CFG;

    // Enforce in software because MySQL can't do it
    // CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    //  CHECK (
    //        (key_sha256 IS NOT NULL OR deploy_sha256 IS NOT NULL)
    //  )
    // deploy_key may be NULL/blank (accept any deployment id from the LMS; deploy_sha256 NULL); then we still need
    // either an LTI 1.1 consumer key or enough LTI 1.3 identity (global issuer or platform issuer + client).
    $have_oauth = U::isNotEmpty($key_key);
    $have_deploy = U::isNotEmpty($deploy_key);
    $have_global_issuer = ! empty($issuer_id) && (int) $issuer_id > 0;
    $have_per_tenant_lti13 = U::isNotEmpty($lms_issuer) && U::isNotEmpty($lms_client);
    if ( ! $have_oauth && ! $have_deploy && ! $have_global_issuer && ! $have_per_tenant_lti13 ) {
        U::flashError('Either an LTI 1.1 consumer key, a specific LTI 1.3 deployment id, or LTI 1.3 issuer information (global issuer or platform issuer URL and client id) is required');
        return false;
    }

    // Enforce in software because MySQL can't do it
    // CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
    //  CHECK (
    //     (deploy_key IS NOT NULL AND issuer_id IS NOT NULL)
    //     OR (deploy_key NOT NULL AND issuer_id NOT NULL)
    /*
    $have_issuer = !empty($issuer_id) || !empty($lms_issuer);
    if ( (!empty($deploy_key) && ! $have_issuer) ||
         (empty($deploy_key) && $have_issuer) 
    ) {
        U::flashError("You must specify an issuer when you specify a deployment id");
        return false;
    }
     */

    $key_sha256 = U::lti_sha256($key_key);
    $deploy_sha256 = U::lti_sha256($deploy_key);

    // TODO: Decide if we put this in the DB
    // Extra constraint in software is oauth key is there is must be unique
    if ( $key_key != $old_key_key && !empty($key_key) ) {
        $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :key_sha256",
            array(':key_sha256' => $key_sha256)
        );
        if ( $row ) {
            U::flashError("Cannot add the same OAuth Consumer Key more than once");
            return false;
        }
    }

    // Now check these
    // CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
    if ( ($key_key != $old_key_key || $deploy_key != $old_deploy_key) && 
        !empty($key_key) && !empty($deploy_key) ) {
        $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :key_sha256 AND deploy_sha256 = :deploy_sha256",
            array(':key_sha256' => $key_sha256, ":deploy_sha256" => $deploy_sha256)
        );
        if ( $row ) {
            U::flashError("The combination of Consumer key and Deployment ID must be unique");
            return false;
        }
    }

    // CONSTRAINT `{$CFG->dbprefix}lti_key_const_2` UNIQUE(issuer_id, deploy_sha256),
    if ( ($deploy_key != $old_deploy_key || $issuer_id != $old_issuer_id) &&
	$issuer_id > 0 &&
        !empty($issuer_id) && !empty($deploy_key) ) {
        $row = $PDOX->rowDie( "SELECT deploy_sha256 FROM {$CFG->dbprefix}lti_key
                WHERE issuer_id = :issuer_id AND deploy_sha256 = :deploy_sha256",
            array(':issuer_id' => $issuer_id, ":deploy_sha256" => $deploy_sha256)
        );
        if ( $row ) {
            U::flashError("The combination of Issuer and Deployment ID must be unique");
            return false;
        }
    }

    if ( ! validate_issuer_client_unique($issuer_id, $lms_issuer, $lms_client, $deploy_key, $exclude_key_id) ) {
        return false;
    }

    return true;
}
