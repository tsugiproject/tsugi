<?php 

// TODO: Remove lti global functions when all the code is cleaned up

require_once 'lti_util.php';
require_once 'ltidb.class.php';

use \Tsugi\LTIDB;

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

