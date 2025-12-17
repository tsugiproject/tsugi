<?php

use \Tsugi\UI\Topics;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($CFG->topics) ) {
    die_with_error_log('Cannot find topics.json ($CFG->topics)');
}

// Get anchor from URL path
$anchor = null;
// Check PATH_INFO first (set by Apache rewrite)
if ( isset($_SERVER['PATH_INFO']) && U::strlen($_SERVER['PATH_INFO']) > 0 ) {
    $anchor = ltrim($_SERVER['PATH_INFO'], '/');
} else {
    $path = U::rest_path();
    if ( isset($path->action) && U::strlen($path->action) > 0 ) {
        $anchor = $path->action;
    } else if ( isset($path->parameters) && count($path->parameters) > 0 ) {
        $anchor = $path->parameters[0];
    }
}

// Turning on and off styling
if ( isset($_GET['nostyle']) ) {
    if ( $_GET['nostyle'] == 'yes' ) {
        $_SESSION['nostyle'] = 'yes';
    } else {
        unset($_SESSION['nostyle']);
    }
}

// Load the Topic
$t = new Topics($CFG->topics, $anchor);

$OUTPUT->header();
$OUTPUT->bodyStart();
$menu = false;
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
$t->header();
echo('<div class="container">');
ob_start();
$t->render();
$content = ob_get_clean();
// Replace launch URLs to always use /topics/launch/ instead of /topics/{anchor}_launch/ or /topics/{anchor}/launch/
// This ensures launch URLs are always relative to the topics base, not the module/anchor
$content = preg_replace('/(href=["\'])([^"\']*\/topics\/)[^\/]+(_launch\/|launch\/)([^"\']*)(["\'])/', '$1$2launch/$4$5', $content);
echo($content);
echo('</div>');
$OUTPUT->footerStart();
$t->footer();
$OUTPUT->footerEnd();
