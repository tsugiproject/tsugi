<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\Access;

// Sanity checks
$LAUNCH = LTIX::requireData(array(LTIX::CONTEXT, LTIX::LINK));

Access::serveContent();
