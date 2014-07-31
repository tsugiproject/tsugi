<?php 

// TODO: Remove lti global functions when all the code is cleaned up

require_once 'lti_util.php';
require_once 'ltix.class.php';

use \Tsugi\LTIX;

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiRequireData($needed) {
    return LTIX::requireData($needed);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiGetDueDate() {
    return LTIX::getDueDate();
}

