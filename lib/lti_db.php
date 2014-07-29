<?php 

// TODO: Remove lti global functions when all the code is cleaned up

require_once 'lti_util.php';
require_once 'ltidb.class.php';

use \Tsugi\LTIDB;

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiLaunchCheck() {
    return LTIDB::launchCheck();
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiSetupSession($pdo=false) {
    return LTIDB::setupSession($pdo);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiExtractPost() {
    return LTIDB::extractPost();
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetCompositeKey($post, $session_secret) {
    return LTIDB::getCompositeKey($post, $session_secret);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiLoadAllData($pdo, $p, $profile_table, $post) {
    return LTIDB::loadAllData($pdo, $p, $profile_table, $post);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiAdjustData($pdo, $p, &$row, $post) {
    return LTIDB::adjustData($pdo, $p, $row, $post);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function lti_verify_key_and_secret($key, $secret) {
    return LTIDB::verifyKeyAndSecret($key, $secret);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetCustom($varname, $default=false) {
    return LTIDB::getCustom($varname, $default);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiRequireData($needed) {
    return LTIDB::requireData($needed);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetDueDate() {
    return LTIDB::getDueDate();
}

