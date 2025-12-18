<?php
/**
 * Shared LMS analytics viewer (cookie-session).
 *
 * Usage (wrapper per tool):
 *   - Create `/lms/<tool>/analytics.php` that sets:
 *       $LMS_ANALYTICS_TOOL         = '<tool>';
 *       $LMS_ANALYTICS_TITLE        = 'Human Title';
 *       $LMS_ANALYTICS_BACK         = '<tool>/';   // relative to /lms/
 *       $LMS_ANALYTICS_STABLE_PATH  = '/lms/<tool>';
 *     then `require_once(__DIR__.'/../tool_support/analytics.php');`
 */

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

header('Content-Type: text/html; charset=utf-8');

// Validate required variables are set by wrapper
if ( ! isset($LMS_ANALYTICS_STABLE_PATH) || ! isset($LMS_ANALYTICS_TITLE) || ! isset($LMS_ANALYTICS_BACK) || ! isset($LMS_ANALYTICS_TOOL) ) {
    die_with_error_log('Missing required variables: LMS_ANALYTICS_STABLE_PATH, LMS_ANALYTICS_TITLE, LMS_ANALYTICS_BACK, LMS_ANALYTICS_TOOL');
}

session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($_SESSION['context_id']) ) {
    die('Context required');
}

$is_instructor = isInstructor();
$is_admin = isAdmin();
if ( ! $is_instructor && ! $is_admin ) {
    die('Not authorized');
}

LTIX::getConnection();

$context_id = $_SESSION['context_id'] + 0;

// Use pre-computed link_id if wrapper provided it, otherwise compute it
if ( isset($LMS_ANALYTICS_LINK_ID) && $LMS_ANALYTICS_LINK_ID > 0 ) {
    $analytics_link_id = $LMS_ANALYTICS_LINK_ID + 0;
} else {
    $link_key = lmsAnalyticsKey($LMS_ANALYTICS_STABLE_PATH);
    $analytics_link_id = lmsEnsureAnalyticsLink($context_id, $link_key, $LMS_ANALYTICS_TITLE, $LMS_ANALYTICS_STABLE_PATH);
    if ( ! $analytics_link_id ) {
        die_with_error_log('Unable to locate analytics link');
    }
}

// Build back URL
if ( strpos($LMS_ANALYTICS_BACK, 'http://') === 0 || strpos($LMS_ANALYTICS_BACK, 'https://') === 0 || strpos($LMS_ANALYTICS_BACK, '/') === 0 ) {
    $back_url = $LMS_ANALYTICS_BACK;
} else {
    $back_url = $CFG->wwwroot . '/lms/' . $LMS_ANALYTICS_BACK;
}

$analytics_url = $CFG->wwwroot . '/api/analytics_cookie.php?link_id=' . $analytics_link_id;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
    <p>
        <a href="<?= $back_url ?>" class="btn btn-default">Back</a>
    </p>
    <h1>Analytics: <?= htmlspecialchars($LMS_ANALYTICS_TITLE) ?></h1>
    <?= \Tsugi\UI\Analytics::graphBody() ?>
</div>
<?php
$OUTPUT->footerStart();
echo(\Tsugi\UI\Analytics::graphScript($analytics_url));
$OUTPUT->footerEnd();

