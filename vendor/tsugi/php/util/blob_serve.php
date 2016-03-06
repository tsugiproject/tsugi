<?php
require_once "../../../../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\Access;

// Sanity checks
$LTI = LTIX::requireData(array(LTIX::CONTEXT, LTIX::LINK));

Access::serveContent();
