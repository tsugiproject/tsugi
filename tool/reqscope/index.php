<?php

require_once __DIR__ . '/../config.php';

use Tsugi\Core\LTIX;
use Tsugi\Controllers\ReqScopeDebug;
use Tsugi\Controllers\Tool;

$LAUNCH = LTIX::requireData();

if ( ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_string($_POST['grade'] ?? null) ) {
    if ( Tool::csrfRedirect(addSession('index.php')) ) {
        return;
    }
    ReqScopeDebug::acceptGrade();
    header('Location: '.addSession('index.php'));
    return;
}

ReqScopeDebug::render($LAUNCH, ReqScopeDebug::hasGradeResult(), 'index.php');
