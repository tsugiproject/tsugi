<?php 

// TODO: Remove lti global functions when all the code is cleaned up

require_once 'lti_util.php';
require_once 'ltidb.class.php';

use \Tsugi\LTIDB;

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiLaunchCheck() {
    return LTIDB::LaunchCheck();
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiSetupSession($pdo=false) {
    return LTIDB::SetupSession($pdo);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiExtractPost() {
    return LTIDB::ExtractPost();
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetCompositeKey($post, $session_secret) {
    return LTIDB::GetCompositeKey($post, $session_secret);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiLoadAllData($pdo, $p, $profile_table, $post) {
    return LTIDB::LoadAllData($pdo, $p, $profile_table, $post);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiAdjustData($pdo, $p, &$row, $post) {
    return LTIDB::AdjustData($pdo, $p, $row, $post);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function lti_verify_key_and_secret($key, $secret) {
    return LTIDB::VerifyKeyAndSecret($key, $secret);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetCustom($varname, $default=false) {
    return LTIDB::GetCustom($varname, $default);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiRequireData($needed) {
    return LTIDB::RequireData($needed);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetDueDate() {
    return LTIDB::GetDueDate();
}

