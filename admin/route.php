<?php

// Admin stays its own front controller. tsugi.php calls LTIX::session_start(),
// which needs a database. The passphrase screen and Upgrade Database must
// open a session when the database is down.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

// The router stores /catalog and /catalog/ as the same path. Drop the slash
// so /admin/catalog/ still matches. Leave /admin/ itself alone.
$adminUri = $_SERVER['REQUEST_URI'] ?? '';
$adminQ = strpos($adminUri, '?');
$adminPath = $adminQ === false ? $adminUri : substr($adminUri, 0, $adminQ);
$adminQuery = $adminQ === false ? '' : substr($adminUri, $adminQ);
if ( $adminPath !== '' && str_ends_with($adminPath, '/') && ! preg_match('#/admin/?$#', $adminPath) ) {
    $_SERVER['REQUEST_URI'] = rtrim($adminPath, '/').$adminQuery;
}

require_once __DIR__ . '/../config.php';

// Site menu callbacks assume a full schema; use the Tsugi default in admin.
unset($CFG->top_menu_callback);
\Tsugi\UI\Output::clearTopNavSession();

\Tsugi\Core\Admin::session_start();

global $OUTPUT, $LAUNCH;
if ( ! isset($OUTPUT) || ! is_object($OUTPUT) ) {
    $OUTPUT = new \Tsugi\UI\Output();
}
if ( ! isset($LAUNCH) || ! is_object($LAUNCH) ) {
    $LAUNCH = new \Tsugi\Core\Launch();
}
if ( ! isset($OUTPUT->launch) || ! is_object($OUTPUT->launch) ) {
    $OUTPUT->launch = $LAUNCH;
}
$OUTPUT->launch->output = $OUTPUT;

$app = new \Tsugi\Lumos\Application($OUTPUT->launch);
// Page scripts echo. The application constructor turns buffering on.
$OUTPUT->buffer = false;

\Tsugi\Controllers\Admin::routes($app);
$app->run();
