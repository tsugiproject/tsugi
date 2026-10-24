<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Plus\PlusShell;

// Cookie session, including when a cookieless id was pasted onto the URL.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);

if ( ! isset($CFG) ) {
    require_once __DIR__ . '/config.php';
}
unset($_GET[TSUGI_COOKIELESS_SESSION_NAME], $_REQUEST[TSUGI_COOKIELESS_SESSION_NAME]);

if ( PlusShell::needsBreakout() ) {
    PlusShell::renderBreakout();
    return;
}

if ( PlusShell::isSessionLaunch() ) {
    PlusShell::replaceCookieSession();
}

LTIX::session_start();

$result = PlusShell::openFromSession();
if ( isset($result['redirect']) ) {
    session_write_close();
    header('Location: ' . $result['redirect']);
    return;
}

$message = isset($result['error']) ? $result['error'] : 'Open Tsugi Plus from your LMS course navigation.';
if ( ! isset($OUTPUT) ) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo '<h1>Tsugi Plus</h1>';
echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
$OUTPUT->footer();
