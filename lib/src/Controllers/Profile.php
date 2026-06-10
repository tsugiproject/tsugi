<?php

namespace Tsugi\Controllers;

use Tsugi\Core\Launch;
use Tsugi\Core\Profile as UserProfile;
use Tsugi\Util\U;
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
        self::renderSupporterThankYou($CFG);
        self::renderSupporterInvite($CFG);
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
        <div class="control-group" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="<?= htmlspecialchars($CFG->apphome) ?>/index.php" class="btn btn-warning" aria-label="<?= htmlspecialchars(__('Cancel and return')) ?>">Cancel</a>
        </div>
        </form>
        <?php self::renderSupporterRenew($CFG); ?>
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

    /**
     * @return array<string,mixed>
     */
    protected static function supporterContext($CFG) {
        $launch = new Launch();
        $userProfile = UserProfile::fromLaunchRow(
            array('profile_id' => $_SESSION['profile_id']),
            $launch
        );

        $supporter_raw = self::supporterUrl($CFG);
        $site_name = self::supporterSiteName($CFG);
        $site_label = self::supporterSiteLabel($CFG);
        $supporter_label = self::supporterLabel($CFG);
        $premium_until = self::supporterPremiumUntil($userProfile);

        return array(
            'userProfile' => $userProfile,
            'supporter_url' => $supporter_raw ? htmlspecialchars($supporter_raw) : '',
            'site_name' => $site_name,
            'site_label' => $site_label,
            'supporter_label' => $supporter_label,
            'price_label' => self::supporterPriceLabel($CFG),
            'price_phrase' => self::supporterPricePhrase($CFG),
            'premium_until' => $premium_until,
            'is_active' => self::isSupporterActive($userProfile, $premium_until),
        );
    }

    /**
     * Thank-you / status for premium users (top of profile page).
     */
    protected static function renderSupporterThankYou($CFG) {
        $ctx = self::supporterContext($CFG);
        if ( ! $ctx['userProfile']->isPremium() ) {
            return;
        }

        echo('<section class="well" style="margin: 1.5em 0; padding: 1em 1.25em;">' . "\n");
        echo('<h2 style="margin-top: 0;">' . htmlspecialchars($ctx['supporter_label']) . '</h2>' . "\n");
        echo('<p><strong>Thank you for supporting '
            . htmlspecialchars($ctx['site_label']) . '!</strong></p>' . "\n");

        $premium_until = $ctx['premium_until'];
        if ( is_string($premium_until) && strlen($premium_until) > 0 ) {
            $until_label = self::formatSupporterUntil($premium_until, $CFG);
            if ( $ctx['is_active'] ) {
                echo('<p>Your supporter status is active through <strong>'
                    . htmlspecialchars($until_label) . '</strong>.</p>' . "\n");
            } else {
                echo('<p>Your supporter period ended on <strong>'
                    . htmlspecialchars($until_label) . '</strong>.</p>' . "\n");
            }
        } else {
            echo('<p>Your supporter status is active.</p>' . "\n");
        }

        echo('</section>' . "\n");
    }

    /**
     * Simple become-a-supporter line for non-premium users (top of profile page).
     */
    protected static function renderSupporterInvite($CFG) {
        $ctx = self::supporterContext($CFG);
        if ( ! $ctx['supporter_url'] || $ctx['userProfile']->isPremium() ) {
            return;
        }

        echo('<p style="margin: 0.75em 0 1.25em;">' . "\n");
        echo('<a href="' . $ctx['supporter_url'] . '">Become a supporter 💚</a>');
        if ( $ctx['price_phrase'] !== '' ) {
            echo(' <span style="opacity: 0.75;">— '
                . htmlspecialchars($ctx['price_phrase']) . '.</span>' . "\n");
        }
        echo('</p>' . "\n");
    }

    /**
     * Simple renew link for expired supporters (bottom of profile page).
     */
    protected static function renderSupporterRenew($CFG) {
        $ctx = self::supporterContext($CFG);
        if ( ! $ctx['supporter_url'] || ! $ctx['userProfile']->isPremium() || $ctx['is_active'] ) {
            return;
        }
        if ( ! is_string($ctx['premium_until']) || strlen($ctx['premium_until']) < 1 ) {
            return;
        }

        echo('<p style="margin: 1.5em 0 0;">' . "\n");
        echo('<a href="' . $ctx['supporter_url'] . '">Renew supporter status</a>');
        if ( $ctx['price_phrase'] !== '' ) {
            echo(' <span style="opacity: 0.75;">(' . htmlspecialchars($ctx['price_phrase']) . ')</span>');
        }
        echo("\n</p>\n");
    }

    /**
     * @return array<string,mixed>
     */
    public static function premiumConfig($CFG) {
        $cfg = $CFG->getExtension('premium');
        return is_array($cfg) ? $cfg : array();
    }

    public static function supporterSiteName($CFG) {
        if ( isset($CFG->servicename) && is_string($CFG->servicename) ) {
            $name = trim($CFG->servicename);
            if ( strlen($name) > 0 ) {
                return $name;
            }
        }
        return 'this site';
    }

    /**
     * Human-facing site label for supporter copy (servicedesc, then servicename).
     */
    public static function supporterSiteLabel($CFG) {
        if ( isset($CFG->servicedesc) && is_string($CFG->servicedesc) ) {
            $desc = trim(strip_tags($CFG->servicedesc));
            if ( strlen($desc) > 0 ) {
                return $desc;
            }
        }
        return self::supporterSiteName($CFG);
    }

    public static function supporterLabel($CFG) {
        return '💚 ' . self::supporterSiteName($CFG) . ' Supporter';
    }

    /**
     * Human-facing price from the premium extension (e.g. "$4.20").
     */
    public static function supporterPriceLabel($CFG) {
        return trim((string) U::get(self::premiumConfig($CFG), 'price', ''));
    }

    public static function supporterPricePhrase($CFG) {
        $price = self::supporterPriceLabel($CFG);
        if ( $price === '' ) {
            return '';
        }
        return 'about ' . $price . ' (local currency at checkout)';
    }

    public static function premiumMonths($CFG) {
        $months = (int) U::get(self::premiumConfig($CFG), 'premium_months', 12);
        if ( $months < 1 ) {
            $months = 12;
        }
        return $months;
    }

    public static function premiumMonthsLabel($CFG) {
        $months = self::premiumMonths($CFG);
        if ( $months === 1 ) {
            return 'one month';
        }
        if ( $months === 12 ) {
            return 'one year';
        }
        return $months . ' months';
    }

    /**
     * Optional refund policy text from the premium extension (plain text).
     */
    public static function refundPolicy($CFG) {
        return trim((string) U::get(self::premiumConfig($CFG), 'refund_policy', ''));
    }

    /**
     * Optional supporter landing URL from the premium extension (site-specific, not payment-provider specific).
     *
     * @return string|false
     */
    public static function supporterUrl($CFG) {
        $url = trim((string) U::get(self::premiumConfig($CFG), 'supporter_url', ''));
        if ( strlen($url) < 1 ) {
            return false;
        }
        return $url;
    }

    /**
     * @return string|false
     */
    public static function supporterPremiumUntil(UserProfile $profile) {
        $json = $profile->getPremiumJsonArray();
        $top = U::get($json, 'premium_until', false);
        if ( is_string($top) && strlen($top) > 0 ) {
            return $top;
        }

        $best = false;
        $best_dt = null;
        foreach ( $json as $block ) {
            if ( ! is_array($block) ) {
                continue;
            }
            $candidate = U::get($block, 'premium_until', false);
            if ( ! is_string($candidate) || strlen($candidate) < 1 ) {
                continue;
            }
            try {
                $dt = new \DateTimeImmutable($candidate);
                if ( $best_dt === null || $dt > $best_dt ) {
                    $best_dt = $dt;
                    $best = $candidate;
                }
            } catch (\Exception $e) {
                // ignore bad date values
            }
        }

        return $best;
    }

    protected static function isSupporterActive(UserProfile $profile, $premium_until) {
        if ( ! $profile->isPremium() ) {
            return false;
        }
        if ( ! is_string($premium_until) || strlen($premium_until) < 1 ) {
            return true;
        }
        try {
            $until = new \DateTimeImmutable($premium_until);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            return $until > $now;
        } catch (\Exception $e) {
            return $profile->isPremium();
        }
    }

    protected static function formatSupporterUntil($premium_until, $CFG) {
        try {
            $tz_name = (isset($CFG->timezone) && $CFG->timezone) ? $CFG->timezone : 'UTC';
            $dt = new \DateTimeImmutable($premium_until);
            return $dt->setTimezone(new \DateTimeZone($tz_name))->format('F j, Y');
        } catch (\Exception $e) {
            return (string) $premium_until;
        }
    }
}

