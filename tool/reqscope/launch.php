<?php

require_once __DIR__ . '/../config.php';

use Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

// requireData() already redirected this browser onto the cookieless session
// and filled ReqScope for this request. The controller reads ReqScope on the
// next request. script_path would keep that session inside tool/reqscope.
unset($_SESSION['script_path']);
session_write_close();

header('Location: '.addSession($CFG->wwwroot.'/reqscope'));
