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
    if ( ! visiturl_has_session_visit($url) ) {
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

$LAUNCH->link->settingsDefaultsFromCustom(array('url', 'title', 'instructions', 'grade', 'minutes', 'modal'));
visiturl_ensure_grade_default();

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
$modal = visiturl_modal_mode();
$already = visiturl_valid_url($url) && visiturl_is_done($url);
if ( $timer && visiturl_valid_url($url) && ! $already ) {
    visiturl_abandon_watch_unless_just_visited();
}
$visited = $timer
    ? (visiturl_valid_url($url) && visiturl_has_session_visit($url))
    : (visiturl_valid_url($url) && visiturl_has_visit($url));
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
SettingsForm::checkbox('modal', __('Show this in a modal iframe'));
SettingsForm::text('title', __('Optional title to show on this page'));
SettingsForm::textarea('instructions', __('Optional instructions for students'));
SettingsForm::checkbox('grade', __('Give the student a 100% grade when they visit the URL'));
SettingsForm::number('minutes', __('Estimated length in minutes. If you set this, students keep this window open while watching the video and mark it as watched after they finish. The grade is sent then, not when they open the URL.'));
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
    }
    if ( $timer || visiturl_grade_on_visit() ) {
        $pct = sprintf('%.1f', ((float) ($RESULT->grade ?? 0)) * 100.0);
        echo('<p class="alert alert-info">');
        echo(htmlentities(sprintf(__('Your current grade on this assignment is: %s%%'), $pct)));
        echo("</p>\n");
    }
}

$wait_minutes = 0;
if ( $timer && ! $already ) {
    $minutes = visiturl_minutes();
    $wait_minutes = (int) round(visiturl_unlock_seconds($minutes) / 60.0);
    if ( $wait_minutes < 1 ) $wait_minutes = 1;
    echo('<p>');
    if ( $modal ) {
        $watch_help = __('This is a %s video. Keep this window open while watching the video. Watch it in the window that opens, then close that window and press I watched this.');
    } else {
        $watch_help = __('This is a %s video. Keep this window open while watching the video. Open the video in a new tab, then come back here and press I watched this.');
    }
    echo(htmlentities(sprintf($watch_help, visiturl_duration_label($minutes))));
    echo("</p>\n");
}

echo('<p>');
$reload_url = json_encode(addSession('index.php'));
$reload_js = htmlspecialchars($reload_url, ENT_QUOTES);
$start_msg = '';
if ( $timer && ! $already && ! $visited ) {
    $n = $wait_minutes;
    if ( $n < 1 ) $n = 1;
    $start_msg = $n === 1
        ? 'Visit URL pressed; 1-minute timer started.'
        : 'Visit URL pressed; '.$n.'-minute timer started.';
}
if ( $modal ) {
    $modal_id = 'visiturl-link-modal';
    $go = U::safe_href(addSession('go.php'));
    $safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo('<button type="button" id="visiturl-open" class="btn btn-primary" onclick="tsugiOpenLinkModal(\''.$modal_id.'\');">');
    echo(__('Visit URL'));
    echo("</button></p>\n");
    visiturl_echo_modal($modal_id, $title, $go, $safe_url);
} else {
    echo('<a class="btn btn-primary" href="'.U::safe_href(addSession('go.php')).'"');
    echo(' target="_blank" rel="noopener noreferrer"');
    $onclick = 'setTimeout(function(){ window.location.href='.$reload_js.'; }, 1500);';
    if ( $start_msg !== '' ) {
        $onclick = 'console.log('.htmlspecialchars(json_encode($start_msg), ENT_QUOTES).');'.$onclick;
    }
    echo(' onclick="'.$onclick.'"');
    echo('>');
    echo(__('Visit URL'));
    echo("</a></p>\n");
}

if ( $timer && ! $already ) {
    if ( $visited ) {
        $egg = 'Wait '.$wait_minutes.' minutes in case the user watches at 2x speed.';
        echo('<!-- '.$egg.' -->'."\n");
        echo('<script>window.setTimeout(function(){ console.log('.json_encode($egg).'); }, 5000);</script>'."\n");
    }
    echo('<form method="post">'."\n");
    echo(\Tsugi\Controllers\Tool::csrfField()."\n");
    if ( ! $watch_ready ) {
        $hover = __('This will be enabled after you watch the video');
        echo('<span id="visiturl-watched-wrap" title="'.htmlentities($hover, ENT_QUOTES).'" style="display:inline-block; cursor:not-allowed;">');
    }
    echo('<button type="submit" name="watched" value="1" id="visiturl-watched" class="btn btn-success"');
    if ( ! $watch_ready ) {
        echo(' disabled="disabled" style="pointer-events:none;"');
    }
    echo(' data-unlock-at="'.htmlentities((string) $unlock_at).'"');
    echo(' data-visited="'.($visited ? '1' : '0').'"');
    echo(' data-minutes="'.htmlentities((string) $wait_minutes).'"');
    echo('>');
    echo(__('I watched this'));
    echo('</button>');
    if ( ! $watch_ready ) {
        echo('</span>');
    }
    echo("</form>\n");
}

if ( $USER->instructor ) {
    echo('<p class="text-muted">'.htmlentities($url)."</p>\n");
}

echo("</div>\n");

if ( $modal || ( $timer && ! $already && $visited ) ) {
    $OUTPUT->footerStart();
    if ( $modal ) {
        $start_js = json_encode($start_msg);
?>
<script>
var visiturlModalReturn = null;
var visiturlModalKeys = null;
var visiturlInertNodes = [];
function visiturlModalFocusables(overlay) {
    return Array.prototype.filter.call(
        overlay.querySelectorAll('a[href], button:not([disabled]), iframe'),
        function (el) { return !el.hasAttribute('disabled'); }
    );
}
function visiturlSetBackgroundInert(overlay) {
    visiturlInertNodes = [];
    var node = overlay;
    while (node.parentElement) {
        var parent = node.parentElement;
        Array.prototype.forEach.call(parent.children, function (kid) {
            if (kid !== node && !kid.hasAttribute('inert')) {
                kid.setAttribute('inert', '');
                visiturlInertNodes.push(kid);
            }
        });
        if (parent === document.body) {
            break;
        }
        node = parent;
    }
}
function visiturlClearBackgroundInert() {
    visiturlInertNodes.forEach(function (node) {
        node.removeAttribute('inert');
    });
    visiturlInertNodes = [];
}
function tsugiOpenLinkModal(id) {
    var overlay = document.getElementById(id);
    if (!overlay) {
        return;
    }
    var iframe = overlay.querySelector('iframe.tsugi-link-modal-frame');
    if (iframe && iframe.getAttribute('data-src')) {
        iframe.src = iframe.getAttribute('data-src');
    }
    visiturlModalReturn = document.getElementById('visiturl-open');
    overlay.style.display = 'block';
    overlay.setAttribute('data-opened-at', String(Date.now()));
    visiturlSetBackgroundInert(overlay);
    if (visiturlModalKeys) {
        document.removeEventListener('keydown', visiturlModalKeys);
    }
    visiturlModalKeys = function (event) {
        if (overlay.style.display === 'none') {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            tsugiCloseLinkModal(id);
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        var items = visiturlModalFocusables(overlay);
        if (!items.length) {
            return;
        }
        var first = items[0];
        var last = items[items.length - 1];
        var active = document.activeElement;
        if (event.shiftKey && (active === first || !overlay.contains(active))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (active === last || !overlay.contains(active))) {
            event.preventDefault();
            first.focus();
        }
    };
    document.addEventListener('keydown', visiturlModalKeys);
    var closeBtn = overlay.querySelector('.tsugi-link-modal-close');
    if (closeBtn) {
        closeBtn.focus();
    }
    var startMsg = <?= $start_js ?>;
    if (startMsg) {
        console.log(startMsg);
    }
}
function tsugiCloseLinkModal(id) {
    var overlay = document.getElementById(id);
    if (!overlay || overlay.style.display === 'none') {
        return;
    }
    var iframe = overlay.querySelector('iframe.tsugi-link-modal-frame');
    if (iframe) {
        iframe.src = 'about:blank';
    }
    if (visiturlModalKeys) {
        document.removeEventListener('keydown', visiturlModalKeys);
        visiturlModalKeys = null;
    }
    visiturlClearBackgroundInert();
    overlay.style.display = 'none';
    if (visiturlModalReturn && typeof visiturlModalReturn.focus === 'function') {
        visiturlModalReturn.focus();
    }
    var opened = parseInt(overlay.getAttribute('data-opened-at'), 10) || Date.now();
    var wait = 1500 - (Date.now() - opened);
    if (wait < 0) {
        wait = 0;
    }
    window.setTimeout(function () {
        window.location.href = <?= $reload_url ?>;
    }, wait);
}
// A click on the dimmed margin does not close this player. Closing
// reloads the page, so a miss while watching would throw the video away.
// Use the close button or Escape.
</script>
<?php
    }
    if ( $timer && ! $already && $visited ) {
?>
<script>
(function () {
    var btn = document.getElementById('visiturl-watched');
    if ( ! btn || btn.getAttribute('data-visited') !== '1' ) return;
    var unlock = parseInt(btn.getAttribute('data-unlock-at'), 10);
    if ( ! unlock ) return;
    var minutes = parseInt(btn.getAttribute('data-minutes'), 10) || 0;
    if ( minutes < 1 ) minutes = 1;
    function enableWatched() {
        btn.removeAttribute('disabled');
        btn.style.pointerEvents = 'auto';
        var wrap = document.getElementById('visiturl-watched-wrap');
        if ( wrap ) {
            wrap.removeAttribute('title');
            wrap.style.cursor = '';
        }
    }
    var wait = (unlock * 1000) - Date.now();
    if ( wait <= 0 ) {
        enableWatched();
        console.log('Watch timer already expired; enabling I watched this.');
        return;
    }
    console.log('Visit URL pressed; ' + minutes + '-minute timer started.');
    window.setTimeout(function () {
        enableWatched();
        console.log('Watch timer expired; enabling I watched this.');
    }, wait);
})();
</script>
<?php
    }
    $OUTPUT->footerEnd();
} else {
    $OUTPUT->footer();
}
