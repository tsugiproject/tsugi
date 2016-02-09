<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Blob\Access;

// Sanity checks
$LTI = LTIX::requireData(array(LTIX::CONTEXT, LTIX::LINK));

if ( ! $USER->instructor ) die("Must be instructor");

Access::serveContent();
