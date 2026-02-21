<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Profile extends Controller {

    const ROUTE = '/profile';
    const REDIRECT = 'tsugi_controllers_profile';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function(Request $request) use ($app) {
            return Profile::getProfile($app);
        });
        $app->router->get('/'.self::REDIRECT, function(Request $request) use ($app) {
            return Profile::getProfile($app);
        });
        $app->router->get($prefix.'/', function(Request $request) use ($app) {
            return Profile::getProfile($app);
        });
        $app->router->post($prefix, function(Request $request) use ($app) {
            return Profile::postProfile($app);
        });
        $app->router->post($prefix.'/', function(Request $request) use ($app) {
            return Profile::postProfile($app);
        });
    }
    public static function getProfile(Application $app)
    {
        global $CFG, $PDOX, $OUTPUT;
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;

        if ( ! isset($_SESSION['profile_id']) ) {
            return new RedirectResponse($home);
        }
        $stmt = $PDOX->queryDie(
                "SELECT json FROM {$CFG->dbprefix}profile WHERE profile_id = :PID",
                array('PID' => $_SESSION['profile_id'])
                );
        $profile_row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $profile_row === false ) {
            $app->tsugiFlashError(__('Unable to load profile'));
            return new RedirectResponse($home);
        }

        $json_string = $profile_row['json'] ?? '';
        $profile = json_decode($json_string);
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

        $context = array();
        $context['defaultLat'] = $defaultLat;
        $context['defaultLng'] = $defaultLng;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();

        if ( isset($CFG->google_map_api_key) && ! $CFG->OFFLINE ) { ?>
                <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?= htmlspecialchars($CFG->google_map_api_key) ?>"></script>
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
    <main class="container" id="main-content">
    <?php

echo('<h1>');echo(htmlentities($_SESSION['displayname']));
echo(' ('.htmlentities($_SESSION['email']).")</h1>\n");
        ?>

        <form method="POST">
            <div style="display: flex; justify-content: space-between;">
                <fieldset class="control-group">
                    <legend>Would you like to set a theme override?</legend>
                    <p><em>Overrides will only show if theme information is set in the launch data (such as from an LMS) or configured.</em></p>
                    <div class="controls">
                        <label class="radio">
                            <?php self::radio('theme',0,$themeId); ?>
                            Use the default configuration
                        </label>
                        <label class="radio">
                            <?php self::radio('theme',1,$themeId); ?>
                            Use light theme
                        </label>
                        <label class="radio">
                            <?php self::radio('theme',2,$themeId); ?>
                            Use dark theme
                        </label>
                    </div>
                </fieldset>
                <div class="control-group pull-right" style="margin-top: 20px">
                    <button type="submit" class="btn btn-primary visible-phone">Save</button>
                    <a href="<?= htmlspecialchars($CFG->apphome) ?>/index.php" class="btn btn-warning" aria-label="<?= htmlspecialchars(__('Cancel and return')) ?>">Cancel</a>
                </div>
            </div>
            <hr class="hidden-phone"/>
            <div style="display: flex; justify-content: space-between;">
        <fieldset class="control-group">
        <legend>How much mail would you like us to send?</legend>
        <div class="controls">
        <label class="radio">
        <?php self::radio('subscribe',-1,$subscribe); ?>
        No mail will be sent.
        </label>
        <label class="radio">
        <?php self::radio('subscribe',0,$subscribe); ?>
        Keep the mail level as low as possible.
        </label>
        <label class="radio">
<?php self::radio('subscribe',1,$subscribe); ?>
Send only announcements.
</label>
<label class="radio">
<?php self::radio('subscribe',2,$subscribe); ?>
Send me notification mail for important things like my assignment was graded.
</label>
</div>
</fieldset>
<div class="control-group pull-right" style="margin-top: 20px">
    <button type="submit" class="btn btn-primary visible-phone">Save</button>
    <a href="<?= $CFG->apphome ?>/index.php" class="btn btn-warning">Cancel</a>
</div>
</div>
<?php if ( isset($CFG->google_map_api_key) && ! $CFG->OFFLINE ) { ?>
    <hr class="hidden-phone"/>
        <label for="map_select">How would you like to be shown in maps?</label>
        <select name="map" id="map_select">
        <option value="0">--- Please Select ---</option>
        <option <?php self::option(1,$map); ?>>Don't show me at all</option>
        <option <?php self::option(2,$map); ?>>Show only my location with no identifying information</option>
        <option <?php self::option(3,$map); ?>>Show my name (<?php echo(htmlentities($_SESSION['displayname'] ?? '', ENT_QUOTES, 'UTF-8')); ?>)</option>
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

        <div id="map_canvas" style="margin: 10px; width:400px; max-width: 100%; height:400px" role="img" aria-label="Map showing your location. Drag the marker to update your position."></div>

        <div id="latlong" style="display:none" class="control-group">
        <p><label for="latbox">Latitude:</label> <input size="30" type="text" id="latbox" name="lat" class="disabled"
        <?php echo(' value="'.htmlent_utf8($lat).'" '); ?>
        ></p>
        <p><label for="lngbox">Longitude:</label> <input size="30" type="text" id="lngbox" name="lng" class="disabled"
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
        </main>
        <?php

        // After jquery gets loaded at the *very* end...
        $OUTPUT->footerStart();
        ?>
        <script type="text/javascript">
        $(document).ready(function() { initialize(); } );
        </script>
        <?php
        $OUTPUT->footerEnd();
        return "";
    }

    public static function radio($var, $num, $val) {
        $ret =  '<input type="radio" name="'.$var.'" id="'.$var.$num.'" value="'.$num.'" ';
        if ( $num == $val ) $ret .= ' checked ';
        $ret .= '>';
        echo($ret);
    }

    public static function option($num, $val) {
        echo(' value="'.$num.'" ');
        if ( $num == $val ) echo(' selected ');
    }

    public static function checkbox($var, $num, $initialVal) {
        $ret = '<input type="checkbox" name="'.$var.'" id="'.$var.$num.'" value="'.$num.'" ';
        if ( $num == $initialVal ) $ret .= ' checked ';
        echo($ret);
    }

    public static function postProfile(Application $app)
    {
        global $CFG, $PDOX;
        $p = $CFG->dbprefix;

        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;

        if ( ! isset($_SESSION['profile_id']) ) {
            return new RedirectResponse($home);
        }

        $stmt = $PDOX->queryDie(
            "SELECT json FROM {$CFG->dbprefix}profile WHERE profile_id = :PID",
            array('PID' => $_SESSION['profile_id'])
            );
        $profile_row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $profile_row === false ) {
            $app->tsugiFlashError(__('Unable to load profile'));
            return new RedirectResponse($home);
        }

        $json_string = $profile_row['json'] ?? '';
        $profile = json_decode($json_string);
        if ( ! is_object($profile) ) $profile = new \stdClass();

        $profile->subscribe = $_POST['subscribe']+0 ;

        if ($_POST['theme'] == 1) {
            $profile->theme_override = 'light';
        } else if ($_POST['theme'] == 2) {
            $profile->theme_override = 'dark';
        } else {
            $profile->theme_override = null;
        }

        if ( isset($_POST['map']) ) {
            $profile->map = $_POST['map']+0 ;
            $profile->lat = $_POST['lat']+0.0 ;
            $profile->lng = $_POST['lng']+0.0 ;
        }

        $new_json = json_encode($profile);
        $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}profile SET json= :JSON
                WHERE profile_id = :PID",
                array('JSON' => $new_json, 'PID' => $_SESSION['profile_id'])
                );
        $app->tsugiFlashSuccess(__('Profile updated.'));
        return new RedirectResponse($home);
    }
}

