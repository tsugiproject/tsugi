<?php 

// TODO: Remove lti global functions when all the code is cleaned up

use \Tsugi\LTIX;

/**
 * @deprecated deprecated since refactor to classes
 */
function ltiRequireData($needed) {
    return LTIX::requireData($needed);
}

