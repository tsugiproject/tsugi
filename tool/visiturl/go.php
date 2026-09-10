<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

LTIX::requireData();

$url = Settings::linkGet('url', false);
if ( ! visiturl_valid_url($url) ) {
    $_SESSION['error'] = __('This URL has not yet been configured.');
    header('Location: '.addSession('index.php'));
    return;
}

visiturl_record_visit($url);

if ( visiturl_grade_on_visit() ) {
    visiturl_send_grade();
}

header('Location: '.$url);
