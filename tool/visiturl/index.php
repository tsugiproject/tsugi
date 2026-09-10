<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

$LAUNCH = LTIX::requireData();

if ( SettingsForm::isSettingsPost() ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect(addSession('index.php')) ) {
        return;
    }
    $url = trim(U::get($_POST, 'url', ''));
    if ( strlen($url) > 0 && ! visiturl_valid_url($url) ) {
        $_SESSION['error'] = __('URL must be a valid http:// or https:// address');
        header('Location: '.addSession('index.php'));
        return;
    }
    $minutes = trim(U::get($_POST, 'minutes', ''));
    if ( $minutes !== '' && ( ! is_numeric($minutes) || ($minutes + 0) < 0 ) ) {
        $_SESSION['error'] = __('Estimated length must be a number of minutes');
        header('Location: '.addSession('index.php'));
        return;
    }
    $_POST['url'] = $url;
    SettingsForm::handleSettingsPost();
    header('Location: '.addSession('index.php'));
    return;
}

if ( isset($_POST['watched']) ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect(addSession('index.php')) ) {
        return;
    }
    $url = Settings::linkGet('url', false);
    if ( ! visiturl_timer_mode() || ! visiturl_valid_url($url) ) {
        header('Location: '.addSession('index.php'));
        return;
    }
    if ( ! visiturl_has_visit($url) ) {
        $_SESSION['error'] = __('Open the video first, then mark it as watched.');
        header('Location: '.addSession('index.php'));
        return;
    }
    if ( ! visiturl_watch_unlocked($url) ) {
        $_SESSION['error'] = __('Please come back after you have finished watching.');
        header('Location: '.addSession('index.php'));
        return;
    }
    visiturl_mark_watched($url);
    visiturl_send_grade();
    $_SESSION['success'] = __('Thanks for watching.');
    header('Location: '.addSession('index.php'));
    return;
}

$LAUNCH->link->settingsDefaultsFromCustom(array('url', 'title', 'instructions', 'grade', 'minutes'));

$url = Settings::linkGet('url', false);
if ( $USER->instructor && ! $url && isset($_GET['url']) ) {
    $url = $_GET['url'];
}
$title = Settings::linkGet('title');
if ( ! is_string($title) || strlen(trim($title)) < 1 ) {
    $title = $LAUNCH->link->title;
}
if ( ! is_string($title) || strlen(trim($title)) < 1 ) {
    $title = __('Visit URL');
}
$instructions = Settings::linkGet('instructions', '');
$dueDate = SettingsForm::getDueDate();
$timer = visiturl_timer_mode();
$already = visiturl_valid_url($url) && visiturl_is_done($url);
$visited = visiturl_valid_url($url) && visiturl_has_visit($url);
$unlock_at = 0;
$watch_ready = false;
if ( $timer && visiturl_valid_url($url) && ! $already ) {
    $unlock_at = visiturl_unlock_at($url);
    $watch_ready = $visited && visiturl_watch_unlocked($url);
}

$menu = false;
if ( $USER->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    $menu->addLeft(__('Student Data'), 'grades.php');

    $submenu = new \Tsugi\UI\Menu();
    $submenu->addLink(__('Settings'), '#', /* push */ false, SettingsForm::attr());
    if ( $CFG->launchactivity ) {
        $submenu->addLink(__('Analytics'), 'analytics');
    }
    $menu->addRight(__('Instructor'), $submenu);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->welcomeUserCourse();

SettingsForm::start();
SettingsForm::text('url', __('URL students should visit (http:// or https://)'));
SettingsForm::text('title', __('Optional title to show on this page'));
SettingsForm::textarea('instructions', __('Optional instructions for students'));
SettingsForm::checkbox('grade', __('Give the student a 100% grade when they visit the URL'));
SettingsForm::number('minutes', __('Estimated length in minutes. If you set this, students keep this window open and mark the video as watched after they finish. The grade is sent then, not when they open the URL.'));
SettingsForm::dueDate();
SettingsForm::end();

$OUTPUT->flashMessages();

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

if ( ! visiturl_valid_url($url) ) {
    echo('<p class="alert alert-warning" style="clear:both;">');
    echo(__('This URL has not yet been configured.'));
    if ( $USER->instructor ) {
        echo(' '.__('Use Settings to enter the web address students should visit.'));
    }
    echo("</p>\n");
    $OUTPUT->footer();
    return;
}

echo('<div style="clear:both;">'."\n");
echo('<h3>'.htmlentities($title)."</h3>\n");

if ( is_string($instructions) && strlen(trim($instructions)) > 0 ) {
    echo('<p>'.nl2br(htmlentities($instructions))."</p>\n");
}

if ( $already ) {
    if ( $timer ) {
        echo('<p class="alert alert-success">'.__('You have marked this as watched.')."</p>\n");
    } else {
        echo('<p class="alert alert-success">'.__('You have already visited this URL.')."</p>\n");
    }
}

$wait_minutes = 0;
if ( $timer && ! $already ) {
    $minutes = visiturl_minutes();
    $wait_minutes = (int) round(visiturl_unlock_seconds($minutes) / 60.0);
    if ( $wait_minutes < 1 ) $wait_minutes = 1;
    echo('<p>');
    echo(htmlentities(sprintf(__('This is a %s video. Keep this window open. Open the video in a new tab, watch it, then come back here and press I watched this.'), visiturl_duration_label($minutes))));
    echo("</p>\n");
}

echo('<p>');
echo('<a class="btn btn-primary" href="'.U::safe_href(addSession('go.php')).'"');
echo(' target="_blank" rel="noopener noreferrer"');
echo(' onclick="setTimeout(function(){ window.location.href='.htmlspecialchars(json_encode(addSession('index.php')), ENT_QUOTES).'; }, 1500);"');
echo('>');
echo(__('Visit URL'));
echo("</a></p>\n");

if ( $timer && ! $already ) {
    $egg = 'Wait '.$wait_minutes.' minutes in case the user watches at 2x speed.';
    echo('<!-- '.$egg.' -->'."\n");
    echo('<script>window.setTimeout(function(){ console.log('.json_encode($egg).'); }, 5000);</script>'."\n");
    echo('<form method="post">'."\n");
    echo(\Tsugi\Controllers\Tool::csrfField()."\n");
    echo('<button type="submit" name="watched" value="1" id="visiturl-watched" class="btn btn-success"');
    if ( ! $watch_ready ) {
        echo(' disabled="disabled"');
    }
    echo(' data-unlock-at="'.htmlentities((string) $unlock_at).'"');
    echo(' data-visited="'.($visited ? '1' : '0').'"');
    echo('>');
    echo(__('I watched this'));
    echo("</button></form>\n");
}

if ( $USER->instructor ) {
    echo('<p class="text-muted">'.htmlentities($url)."</p>\n");
}

echo("</div>\n");

if ( $timer && ! $already ) {
    $OUTPUT->footerStart();
?>
<script>
(function () {
    var btn = document.getElementById('visiturl-watched');
    if ( ! btn || btn.getAttribute('data-visited') !== '1' ) return;
    var unlock = parseInt(btn.getAttribute('data-unlock-at'), 10);
    if ( ! unlock ) return;
    var wait = (unlock * 1000) - Date.now();
    if ( wait <= 0 ) {
        btn.removeAttribute('disabled');
        return;
    }
    window.setTimeout(function () { btn.removeAttribute('disabled'); }, wait);
})();
</script>
<?php
    $OUTPUT->footerEnd();
} else {
    $OUTPUT->footer();
}
