<?php 

// TODO: Remove lti global functions when all the code is cleaned up

require_once('net.class.php');
require_once("oauth.class.php");
require_once("lti.class.php");
require_once("ltix.class.php");

use \Tsugi\LTIX;

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiRequireData($needed) {
    return LTIX::requireData($needed);
}

