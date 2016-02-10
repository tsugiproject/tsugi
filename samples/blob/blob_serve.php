<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\Access;

// Sanity checks
$LTI = LTIX::requireData(array(LTIX::CONTEXT, LTIX::LINK));

if ( ! $USER->instructor ) die("Must be instructor");

Access::serveContent();
