<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

// Helper functions
function radio($var, $num, $val) {
    $ret =  '<input type="radio" name="'.$var.'" id="'.$var.$num.'" value="'.$num.'" ';
    if ( $num == $val ) $ret .= ' checked ';
    echo($ret);
}

function option($num, $val) {
    echo(' value="'.$num.'" ');
    if ( $num == $val ) echo(' selected ');
}

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

// Handle POST (save profile)
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! isset($_SESSION['profile_id']) ) {
        $_SESSION['error'] = __('Unable to load profile');
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        header('Location: ' . $home);
        return;
    }

    $stmt = $PDOX->queryDie(
        "SELECT json FROM {$CFG->dbprefix}profile WHERE profile_id = :PID",
        array('PID' => $_SESSION['profile_id'])
    );
    $profile_row = $stmt->fetch(\PDO::FETCH_ASSOC);
    if ( $profile_row === false ) {
        $_SESSION['error'] = __('Unable to load profile');
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        header('Location: ' . $home);
        return;
    }

    $json_data = U::get($profile_row, 'json');
    $profile = null;
    if ( $json_data && U::strlen($json_data) > 0 ) {
        $profile = json_decode($json_data);
    }
    if ( ! is_object($profile) ) $profile = new \stdClass();

    $profile->subscribe = U::get($_POST, 'subscribe', 0) + 0;

    $theme = U::get($_POST, 'theme', 0) + 0;
    if ( $theme == 1 ) {
        $profile->theme_override = 'light';
    } else if ( $theme == 2 ) {
        $profile->theme_override = 'dark';
    } else {
        $profile->theme_override = null;
    }

    if ( isset($_POST['map']) ) {
        $profile->map = U::get($_POST, 'map', 0) + 0;
        $profile->lat = U::get($_POST, 'lat', 0.0) + 0.0;
        $profile->lng = U::get($_POST, 'lng', 0.0) + 0.0;
    }

    $new_json = json_encode($profile);
    $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}profile SET json= :JSON
        WHERE profile_id = :PID",
        array('JSON' => $new_json, 'PID' => $_SESSION['profile_id'])
    );
    $_SESSION['success'] = __('Profile updated.');
    $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
    header('Location: ' . $home);
    return;
}

// GET handler (show profile form)
$home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;

if ( ! isset($_SESSION['profile_id']) ) {
    header('Location: ' . $home);
    return;
}

$stmt = $PDOX->queryDie(
    "SELECT json FROM {$CFG->dbprefix}profile WHERE profile_id = :PID",
    array('PID' => $_SESSION['profile_id'])
);
$profile_row = $stmt->fetch(\PDO::FETCH_ASSOC);
if ( $profile_row === false ) {
    $_SESSION['error'] = __('Unable to load profile');
    header('Location: ' . $home);
    return;
}

$json_data = U::get($profile_row, 'json');
$profile = null;
if ( $json_data && U::strlen($json_data) > 0 ) {
    $profile = json_decode($json_data);
}
if ( ! is_object($profile) ) $profile = new \stdClass();

$themeId = 0;
// Load data from the profile, if it exists
if (isset($profile->theme_override)) {
    if ($profile->theme_override == 'light') {
        $themeId = 1;
    } else if ($profile->theme_override == 'dark') {
        $themeId = 2;
    }
}
$subscribe = isset($profile->subscribe) ? $profile->subscribe+0 : false;
$map = isset($profile->map) ? $profile->map+0 : false;
$lat = isset($profile->lat) ? $profile->lat+0.0 : 0.0;
$lng = isset($profile->lng) ? $profile->lng+0.0 : 0.0;

$defaultLat = $lat != 0.0 ? $lat : 42.279070216140425;
$defaultLng = $lng != 0.0 ? $lng : -83.73981015789798;

// Record learner analytics (synthetic lti_link in this context) - only if logged in with context
if ( U::get($_SESSION,'id') && isset($_SESSION['context_id']) ) {
    lmsRecordLaunchAnalytics('/lms/profile', 'Profile');
}

// Check if user is instructor/admin for analytics button (handles missing id/context gracefully)
$is_instructor = isInstructor();
$is_admin = isAdmin();
$show_analytics = $is_instructor || $is_admin;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
if ( $show_analytics ) {
    echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="analytics.php" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
}

if ( isset($CFG->google_map_api_key) && ! $CFG->OFFLINE ) { ?>
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?= $CFG->google_map_api_key ?>"></script>
        <script type="text/javascript">
        var map;

    function initialize() {
        var myLatlng = new google.maps.LatLng(<?php echo($defaultLat.", ".$defaultLng); ?>);

        var myOptions = {
zoom: 2,
  center: myLatlng,
  mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

        var marker = new google.maps.Marker({
draggable: true,
position: myLatlng,
map: map,
title: "Your location"
});

google.maps.event.addListener(marker, 'dragend', function (event) {
    document.getElementById("latbox").value = this.getPosition().lat();
    document.getElementById("lngbox").value = this.getPosition().lng();
    });

}
<?php } else { ?>
<script type="text/javascript">
    var map;

function initialize() { }

<?php } ?>

</script>
<div class="container">
<?php

echo('<h4>');echo($_SESSION['displayname']);
echo(' ('.$_SESSION['email'].")</h4>\n");
?>

    <p>
    <form method="POST">
        <div style="display: flex; justify-content: space-between;">
            <div class="control-group">
                <div class="controls">
                    <p>Would you like to set a theme override?</p>
                    <em>Overrides will only show if theme information is set in the launch data (such as from an LMS) or configured.</em>
                    <label class="radio">
                        <?php radio('theme',0,$themeId); ?> >
                        Use the default configuration
                    </label>
                    <label class="radio">
                        <?php radio('theme',1,$themeId); ?> >
                        Use light theme
                    </label>
                    <label class="radio">
                        <?php radio('theme',2,$themeId); ?> >
                        Use dark theme
                    </label>
                </div>
            </div>
            <div class="control-group pull-right" style="margin-top: 20px">
                <button type="submit" class="btn btn-primary visible-phone">Save</button>
                <input class="btn btn-warning" type="button" onclick="location.href='<?= $home ?>/index.php'; return false;" value="Cancel"/>
            </div>
        </div>
        <hr class="hidden-phone"/>
        <div style="display: flex; justify-content: space-between;">
    <div class="control-group">
    <div class="controls">
    How much mail would you like us to send?
    <label class="radio">
    <?php radio('subscribe',-1,$subscribe); ?> >
    No mail will be sent.
    </label>
    <label class="radio">
    <?php radio('subscribe',0,$subscribe); ?> >
    Keep the mail level as low as possible.
    </label>
    <label class="radio">
<?php radio('subscribe',1,$subscribe); ?> >
Send only announcements.
</label>
<label class="radio">
<?php radio('subscribe',2,$subscribe); ?> >
Send me notification mail for important things like my assignment was graded.
</label>
</div>
</div>
<div class="control-group pull-right" style="margin-top: 20px">
    <button type="submit" class="btn btn-primary visible-phone">Save</button>
    <input class="btn btn-warning" type="button" onclick="location.href='<?= $home ?>/index.php'; return false;" value="Cancel"/>
</div>
</div>
<?php if ( isset($CFG->google_map_api_key) && ! $CFG->OFFLINE ) { ?>
<hr class="hidden-phone"/>
    How would you like to be shown in maps.<br/>
    <select name="map">
    <option value="0">--- Please Select ---</option>
    <option <?php option(1,$map); ?>>Don't show me at all</option>
    <option <?php option(2,$map); ?>>Show only my location with no identifying information</option>
    <option <?php option(3,$map); ?>>Show my name (<?php echo($_SESSION['displayname']); ?>)</option>
    </select>
    <p>
    Move the pointer on the map below until it is at the correct location.
    If you are concerned about privacy, simply put the
    location somewhere <i>near</i> where you live.  Perhaps in the same country, state, or city
    instead of your exact location.
    </p>
    <div class="control-group pull-right hidden-phone">
    <button type="submit" style="margin-top: 40px" class="btn btn-primary">Save Profile Data</button>
    </div>

    <div id="map_canvas" style="margin: 10px; width:400px; max-width: 100%; height:400px"></div>

    <div id="latlong" style="display:none" class="control-group">
    <p>Latitude: <input size="30" type="text" id="latbox" name="lat" class="disabled"
    <?php echo(' value="'.htmlent_utf8($lat).'" '); ?>
    ></p>
    <p>Longitude: <input size="30" type="text" id="lngbox" name="lng" class="disabled"
    <?php echo(' value="'.htmlent_utf8($lng).'" '); ?>
    ></p>
    </div>

    <p>
    If you don't even want to reveal your country, put yourself
    in Greenland in the middle of a glacier. One person put their location
    in the middle of a bar.  :)
    </p>
    <?php } ?>
    </form>
    </div>
    <?php

    // After jquery gets loaded at the *very* end...
    $OUTPUT->footerStart();
    ?>
    <script type="text/javascript">
    $(document).ready(function() { initialize(); } );
    </script>
    <?php
    $OUTPUT->footerEnd();
