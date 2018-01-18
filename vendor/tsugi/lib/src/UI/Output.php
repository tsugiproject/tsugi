<?php

namespace Tsugi\UI;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;
use Tsugi\UI\HandleBars;

/**
 * This is a class that captures the output conventions of Tusgi.
 *
 * In order to be consistent across Tsugi tools we capture the kinds of
 * HTML conventions we want to use.   This allows us to change our UI
 * in one place.
 *
 * This class is created automatially and placed in a global variable
 * called $OUTPUT
 *
 * A typical Tsugi Tool can get a lot done with the rough outline:
 *
 *     use \Tsugi\Core\LTIX;
 *
 *     // Require CONTEXT, USER, and LINK
 *     $LAUNCH = LTIX::requireData();
 *
 *     // Handle incoming POST data and redirect as necessary...
 *     if ( ... ) {
 *         header( 'Location: '.addSession('index.php') ) ;
 *     }
 *
 *     // Done with POST
 *     $OUTPUT->header();
 *     $OUTPUT->bodyStart();
 *     $OUTPUT->flashMessages();
 *
 *     // Output some HTML
 *
 *     $OUTPUT->footerStart();
 *     ?>
 *        // Stick some JavaScript here...
 *     <?php
 *     $OUTPUT->footerEnd();
 *
 * This class is likely to grow a bit to capture new needs as they arise.
 * You can look at the various bits of sample code in the mod and other
 * tool folders to see patterns of the use of this class.
 */

use \Tsugi\Core\Settings;

class Output extends \Tsugi\Core\SessionAccess {

    public $buffer = false;

    /**
     * Set the JSON header
     */
    public static function headerJson() {
        header('Content-Type: application/json; charset=UTF-8');
        self::noCacheHeader();
    }

    function flashMessages() {
        ob_start();
        if ( $this->session_get('error') ) {
            echo '<div class="alert alert-danger" style="clear:both"><a href="#" class="close" data-dismiss="alert">&times;</a>'.
            $this->session_get('error')."</div>\n";
            $this->session_forget('error');
        } else if ( isset($_GET['lti_errormsg']) ) {
            echo '<div class="alert alert-danger" style="clear:both"><a href="#" class="close" data-dismiss="alert">&times;</a>'.
            htmlentities($_GET['lti_errormsg'])."</div>";
            if ( isset($_GET['detail']) ) {
                echo("\n<!--\n");
                echo(str_replace("-->","--:>",$_GET['detail']));
                echo("\n-->\n");
            }
        }

        if ( $this->session_get('success') ) {
            echo '<div class="alert alert-success" style="clear:both"><a href="#" class="close" data-dismiss="alert">&times;</a>'.
            $this->session_get('success')."</div>\n";
            $this->session_forget('success');
        }

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * Emit the HTML for the header.
     */
    function header() {
        global $HEAD_CONTENT_SENT, $CFG, $RUNNING_IN_TOOL, $CONTEXT, $USER, $LINK;
        global $CFG;
        if ( $HEAD_CONTENT_SENT === true ) return;
        header('Content-Type: text/html; charset=utf-8');
        ob_start();
    ?><!DOCTYPE html>
    <html>
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $CFG->servicename ?><?php if ( isset($CFG->context_title) ) echo(' - '.$CFG->context_title); ?></title>
        <script>
        var _TSUGI = { 
<?php
            // https://stackoverflow.com/questions/23740548/how-to-pass-variables-and-data-from-php-to-javascript
            if ( isset($CONTEXT->title) ) {
                echo('            context_title: '.json_encode($CONTEXT->title).",\n");
            }
            if ( isset($LINK->title) ) {
                echo('            link_title: '.json_encode($LINK->title).",\n");
            }
            if ( isset($USER->displayname) ) {
                echo('            user_displayname: '.json_encode($USER->displayname).",\n");
            }
            if ( isset($USER->locale) ) {
                echo('            user_locale: '.json_encode($USER->locale).",\n");
            }
            if ( strlen(session_id()) > 0 && ini_get('session.use_cookies') == '0' ) {
                echo('            ajax_session: "'.urlencode(session_name()).'='.urlencode(session_id()).'"'.",\n");
            } else {
                echo('            ajax_session: false,'."\n");
            }

            if ( isset($USER->instructor) && $USER->instructor ) {
                echo('            instructor: true,  // Use only for UI display'."\n");
            }
?>
            spinnerUrl: "<?= self::getSpinnerUrl() ?>",
            staticroot: "<?= $CFG->staticroot ?>",
            window_close_message: "<?= _m('Application complete') ?>",
            session_expire_message: "<?= _m('Your session has expired') ?>"
        }
        </script>
        <!-- Tiny bit of JS -->
        <script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
        <!-- Le styles -->
        <link href="<?= $CFG->staticroot ?>/bootstrap-3.1.1/css/<?php
            if ( isset($CFG->bootswatch) ) echo('bootswatch/'.$CFG->bootswatch.'/'); ?>bootstrap.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/js/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet">
        <link href="<?= $CFG->fontawesome ?>/css/font-awesome.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/css/tsugi.css" rel="stylesheet">

    <style>
    body {
      padding-top: 10px;
      padding-bottom: 10px;
    }
    .navbar {
      margin-bottom: 20px;
    }
    .container_iframe {
        margin-left: 10px;
        margin-right: 10px;
    }
<?php
if ( isset($CFG->extra_css) ) {
    echo($CFG->extra_css."\n");
}
?>
<?php
if ( isset($CFG->bootswatch_color) ) {
    $grad = self::get_gradient($CFG->bootswatch_color);
?>
.navbar{
    background-image:linear-gradient(<?= $grad[0]?>,<?= $grad[1] ?> 60%,<?= $grad[2] ?>);
    background-image:-webkit-linear-gradient(<?= $grad[0]?>,<?= $grad[1] ?> 60%,<?= $grad[2] ?>);
    border-bottom:1px solid <?= $grad[3]?>;
    background-color: <?= $grad[3]?>;
}
.navbar-default .navbar-nav>li>a:hover, .navbar-default .navbar-nav>li>a:focus {
    background-color: <?= $grad[3]?>;
}

h1, h2, h3, h4 { color: <?= $grad[0]?>; }
<?php } ?>
</style>
<?php // https://lefkomedia.com/adding-external-link-indicator-with-css/
  if ( $CFG->google_translate ) { ?>
<style>
a[target="_blank"]:after {
    font-family: "FontAwesome";
    content: " \f08e";
}
.goog-te-banner-frame.skiptranslate {
    display: none !important;
    }
body {
    top: 0px !important;
    }
</style>
<?php } ?>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="<?= $CFG->vendorstatic ?>/js/html5shiv/html5shiv.js"></script>
          <script src="<?= $CFG->vendorstatic ?>/js/respond/respond.min.js"></script>
        <![endif]-->

    <?php
        if ( $this->session_get('CSRF_TOKEN') ) {
            echo('<script type="text/javascript">CSRF_TOKEN = "'.$this->session_get('CSRF_TOKEN').'";</script>'."\n");
        } else {
            echo('<script type="text/javascript">CSRF_TOKEN = "TODORemoveThis";</script>'."\n");
        }

        // Set the containing frame id if we have one
        $element_id = LTIX::ltiRawParameter('ext_lti_element_id', false);
        if ( $element_id ) {
            echo('<script type="text/javascript">LTI_PARENT_IFRAME_ID = "'.$element_id.'";</script>'."\n");
        }

        if ( $this->session_get('APP_HEADER') ) echo($this->session_get('APP_HEADER'));

        $HEAD_CONTENT_SENT = true;

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    function bodyStart($checkpost=true) {
        global $CFG;
        ob_start();
        // If we are in an iframe use different margins
?>
</head>
<body prefix="oer: http://oerschema.org">
<div id="body_container">
<script>
if (window!=window.top) {
    document.getElementById("body_container").className = "container_iframe";
} else {
    document.getElementById("body_container").className = "container";
}
</script>
<?php
        if ( $checkpost && count($_POST) > 0 ) {
            $dump = self::safe_var_dump($_POST);
            echo('<p style="color:red">Error - Unhandled POST request</p>');
            echo("\n<pre>\n");
            echo($dump);
            if ( count($_FILES) > 0 ) {
                $files = self::safe_var_dump($_FILES);
                echo($files);
            }
            echo("\n</pre>\n");
            error_log($dump);
            die_with_error_log("Unhandled POST request");
        }

        // Complain if this is a test key
        $key_key = $this->ltiParameter('key_key');
        if ( $key_key == '12345' &&
            strpos($CFG->wwwroot, '://localhost') === false ) {
            echo('<div style="background-color: orange; position: absolute; bottom: 5px; left: 5px;">');
            echo(_m('Test Key - Do not use for production'));
            echo('</div>');
        }

        $HEAD_CONTENT_SENT = true;

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * templateInclude - Include a handlebars template, dealing with i18n
     *
     * Deprecated - Moved to HandleBars
     */
    public static function templateInclude($name) {
        HandleBars::templateInclude($name);
    }

    /**
     * templateProcess - Process a handlebars template, dealing with i18n
     *
     * Deprecated - Moved to HandleBars
     */
    public static function templateProcess($template) {
        return HandleBars::templateProcess($template);
    }

    function footerStart() {
        global $CFG;
        ob_start();
        echo('<script src="'.$CFG->staticroot.'/js/jquery-1.11.3.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/bootstrap-3.1.1/js/bootstrap.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/jquery-ui-1.11.4/jquery-ui.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/handlebars-v4.0.2.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/tmpljs-3.8.0/tmpl.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/tsugiscripts.js"></script>'."\n");

        if ( isset($CFG->sessionlifetime) ) {
            $heartbeat = ( $CFG->sessionlifetime * 1000) / 2;
            // $heartbeat = 10000;
            $heartbeat_url = self::getUtilUrl('/heartbeat.php');
            $heartbeat_url = addSession($heartbeat_url);
    ?>
    <script type="text/javascript">
    HEARTBEAT_URL = '<?= $heartbeat_url ?>';
    HEARTBEAT_INTERVAL = setInterval(doHeartBeat, <?= $heartbeat ?>);
    tsugiEmbedMenu();
    </script>
    <?php
        }

        if ( U::allow_track() && $CFG->google_translate ) {
    ?>
<div id="google_translate_element" style="position: fixed; right: 1em; bottom: 0.25em;"></div><script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.SIMPLE
<?php
    if ( U::allow_track() && $CFG->universal_analytics ) {
        echo(', gaTrack: true, gaId: "'.$CFG->universal_analytics.'"'."\n");
    }
?>
    }, "google_translate_element");
}
</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<?php

        }

        if ( $this->session_get('APP_FOOTER') ) echo($this->session_get('APP_FOOTER'));

        if ( U::allow_track() ) $this->doAnalytics();

        // TODO: Remove this when PHP 7 is fixed..  Sigh.
        if ( PHP_VERSION_ID > 70000 ) {
?>
<script>
// PHP VERSION 7.0 and 7.1 HACK
// https://stackoverflow.com/questions/44980654/how-can-i-make-trans-sid-cookie-less-sessions-work-in-php-7-1
$('a').each(function (x) {
    var href = $(this).attr('href');
    if ( ! href ) return;
    if ( ! href.startsWith('#') ) return;
    var pos = href.indexOf('/?');
    if ( pos < 1 ) return;
    console.dir('Patching broken # href='+href);
    href = href.substring(0,pos);
    $(this).attr('href', href);
});
<?php
// Hack to compensate for PHP 7.0 cookiless session failure
    if ( ini_get('session.use_cookies') == '0' ) {
?>
console.log('Checking malformed href for php 7.0');
$('a').each(function (x) {
    var href = $(this).attr('href');
    var sess_name = '<?= session_name() ?>';
    var sess_id = '<?= session_id() ?>';
    if ( ! href ) return;
    if ( href.startsWith('#') ) return;
    if ( href.startsWith('http://') ) return;
    if ( href.startsWith('javascript:') ) return;
    if ( href.startsWith('https://') ) return;
    if ( href.startsWith('//') ) return;
    if ( href.indexOf(sess_name) > 0 ) return;
    if ( href.indexOf('?') > 0 ) {
        href = href + '&';
    } else {
        href = href + '?';
    }
    href = href + sess_name + '=' + sess_id;
    console.dir('Patching missing session href='+href);
    $(this).attr('href', href);
});
<?php } ?>

</script>
<?php
        }

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * getUtilUrl - Get a URL in the utility script space - does not add session
     *
     * @param $path - The path of the file - should start with a slash.
     */
    public static function getUtilUrl($path)
    {
        global $CFG;
        if ( isset($CFG->utilroot) ) {
            return $CFG->utilroot.$path;
        }

        // From wwwroot
        $path = str_replace('.php','',$path);
        $retval = $CFG->wwwroot . '/util' . $path;
        return $retval;

        // The old way from "vendor"
        // return $CFG->vendorroot.$path;
    }

    /**
     * Handle the heartbeat calls. This is UI code basically.
     *
     * Make sure when you call this, you have handled whether
     * the session is cookie based or not and included config.php
     * appropriately
     *
     *    if ( isset($_GET[session_name()]) ) {
     *        $cookie = false;
     *    } else {
     *        define('COOKIE_SESSION', true);
     *        $cookie = true;
     *    }
     *
     *    require_once "../config.php";
     *
     *    \Tsugi\UI\Output::handleHeartBeat($cookie);
     *
     */
    public static function handleHeartBeat($cookie)
    {
        global $CFG;

        self::headerJson();

        session_start();
        $session_object = null;

        // TODO: Make sure to do the right thing with the session eventially

        // See how long since the last update of the activity time
        $now = time();
        $seconds = $now - LTIX::wrapped_session_get($session_object, 'LAST_ACTIVITY', $now);
        LTIX::wrapped_session_put($session_object, 'LAST_ACTIVITY', $now); // update last activity time stamp

        // Count the successive heartbeats without a request/response cycle
        $count = LTIX::wrapped_session_get($session_object, 'HEARTBEAT_COUNT', 0);
        $count++;
        LTIX::wrapped_session_put($session_object, 'HEARTBEAT_COUNT', $count);

        if ( $count > 10 && ( $count % 100 ) == 0 ) {
            error_log("Heartbeat.php ".session_id().' '.$count);
        }

        $retval = array("success" => true, "seconds" => $seconds,
                "now" => $now, "count" => $count, "cookie" => $cookie,
                "id" => session_id());
        $lti = LTIX::wrapped_session_get($session_object, 'lti');
        $retval['lti'] = is_array($lti) && U::get($lti, 'key_id');
        $retval['sessionlifetime'] = $CFG->sessionlifetime;
        return $retval;
    }

    function footerEnd() {
        $end = "\n</div></body>\n</html>\n";
        if ( $this->buffer ) {
            return $end;
        } else {
            echo($end);
        }
    }

    function footer() {
        global $CFG;
        if ( $this->buffer ) {
            return $this->footerStart() .  $this->footerEnd();
        } else {
            $this->footerStart();
            $this->footerEnd();
        }
    }

    function doAnalytics() {
        global $CFG;
        if ( $CFG->universal_analytics ) { ?>
            <script>
              (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
              (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
              m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
              })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

              ga('create', '<?= $CFG->universal_analytics ?>', 'auto');
              ga('send', 'pageview');

            </script>
        <?php }
    }

    /**
      * Welcome the user to the course
      */
    function welcomeUserCourse() {
        global $USER, $CONTEXT;
        if ( isset($USER->displayname) ) {
            if ( isset($CONTEXT->title) ) {
                printf(_m("<p>Welcome %s from %s"), htmlent_utf8($USER->displayname), htmlent_utf8($CONTEXT->title));
            } else {
                printf(_m("<p>Welcome %s"), htmlent_utf8($USER->displayname));
            }
        } else {
            if ( isset($CONTEXT->title) ) {
                printf(_m("<p>Welcome from %s"), htmlent_utf8($CONTEXT->title));
            } else {
                printf(_m("<p>Welcome "));
            }
        }

        if ( $USER->instructor ) {
            echo(" "._m("(Instructor)"));
        }
        echo("</p>\n");
    }

    /**
      * Emit a properly styled done button for use in the launched frame/window
      *
      * This is a bit tricky because custom settings can control the "Done"
      * behavior.  These settings can come from one of three places: (1)
      * in the link settings, (2) from a custom parameter named 'done', or
      * (3) from a GET parameter done=
      *
      * The value for this is a URL, "_close", or "_return".
      *
      * TODO: Implement _return
      */
    function exitButton($text=false) {
        if ( $text === false ) $text = _m("Exit");
        $url = Settings::linkGet('done');
        if ( $url == false ) {
            if ( $this->session_get('lti_post') && isset($this->session_get('lti_post')['custom_done']) ) {
                $url = $this->session_get('lti_post')['custom_done'];
            } else if ( isset($_GET["done"]) ) {
                $url = $_GET['done'];
            }
        }
        // If we have no where to go and nothing to do,
        if ( $url === false || strlen($url) < 1 ) return;

        $button = "btn-success";
        if ( $text == "Cancel" || $text == _m("Cancel") ) $button = "btn-warning";

        if ( $url == "_close" ) {
            echo("<a href=\"#\" onclick=\"window_close();\" class=\"btn ".$button."\">".$text."</a>\n");
        } else {
            echo("<a href==\"$url\"  class=\"btn ".$button."\">".$text."</button>\n");
        }
    }

    /**
      * Emit a properly styled close button for use in own popup
      */
    function closeButton($text=false) {
        if ( $text === false ) $text = _m("Exit");
        $button = "btn-success";
        if ( $text == "Cancel" || $text == _m("Cancel") ) $button = "btn-warning";
        echo("<a href=\"#\" onclick=\"window_close();\" class=\"btn ".$button."\">".$text."</a>\n");
    }

    function togglePre($title, $html) {
        global $div_id;
        $div_id = $div_id + 1;
        $text = _m('Toggle');
        echo('<strong>'.htmlpre_utf8($title));
        echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">'.$text.'</a>)</strong>'."\n");
        echo(' ('.strlen($html).' characters)'."\n");
        echo('<pre id="'.$div_id.'" style="display:none; border: solid 1px">'."\n");
        echo(htmlpre_utf8($html));
        echo("</pre><br/>\n");
    }

    function togglePreScript() {
    return '<script language="javascript">
    function dataToggle(divName) {
        var ele = document.getElementById(divName);
        if(ele.style.display == "block") {
            ele.style.display = "none";
        }
        else {
            ele.style.display = "block";
        }
    }
    </script>';
    }

    function returnMenuSet($return_url) {
        global $CFG;
        $R = $CFG->wwwroot . '/';
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Done', 'javascript:window.location.href=\''.urlencode($return_url).'\';');
        return $set;
    }

    function closeMenuSet() {
        global $CFG;
        $R = $CFG->wwwroot . '/';
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome('Done', 'javascript:window_close();');
        return $set;
    }

    function defaultMenuSet() {
        global $CFG;
        $R = $CFG->wwwroot . '/';
        $showadvanced = $CFG->DEVELOPER || ! $CFG->google_client_id;
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome($CFG->servicename, $CFG->apphome);
        $set->addLeft('Tools', $R.'store');
        if ( $this->session_get('id') ) {
                $set->addLeft('Settings', $R . 'settings');
        }

        if ( $this->session_get('id') ) {
            $submenu = new \Tsugi\UI\Menu();
            $submenu->addLink('Profile', $R.'profile');
            if ( $showadvanced || U::get($_COOKIE, 'adminmenu') ) {
                $submenu->addLink('Admin', $R.'admin');
            }
            if ( $showadvanced ) {
                $submenu->addLink('Developer', $R.'dev');
            }
            $submenu->addLink('Logout', $R.'logout');
            $set->addRight(htmlentities($this->session_get('displayname', '')), $submenu);
        } else {
            if ( $showadvanced ) {
                $set->addLeft('Admin', $R.'admin');
                $set->addLeft('Developer', $R.'dev');
            }
            $set->addRight('Login', $R.'login');
        }

        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('IMS LTI 1.1 Spec', 'http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html')
            ->addLink('IMS LTI 2.0 Spec', 'http://www.imsglobal.org/lti/ltiv2p0/ltiIMGv2p0.html')
            ->addLink('Tsugi Project Site', 'https://www.tsugi.org/');
        $set->addRight('Links', $submenu);

        return $set;
    }

    /**
     * Set header Content for any Tsugi-generated pages.
     */
    function setAppHeader($head) {
        if ( $this->session_get('APP_HEADER') !== $head) {
            $this->session_put('APP_HEADER', $head);
        }
    }

    /**
     * Set footer Content for any Tsugi-generated pages.
     */
    function setAppFooter($foot) {
        if ( $this->session_get('APP_FOOTER') !== $foot) {
            $this->session_put('APP_FOOTER', $foot);
        }
    }

    /**
     * Store the top navigation in the session
     */
    function topNavSession($menuset) {
        global $CFG;
        $export = $menuset->export();
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        if ( $this->session_get($sess_key) !== $export) {
            $this->session_put($sess_key, $export);
        }
    }

    /**
     * Emit the top navigation block
     *
     * Priority order:
     * (1) Navigation in the session
     * (2) If we are launched via LTI w/o a session
     */
    function topNav($menu_set=false) {
        global $CFG, $LAUNCH;
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        $launch_return_url = $LAUNCH->ltiRawParameter('launch_presentation_return_url', false);

        $same_host = false;
        if ( $CFG->apphome && startsWith($launch_return_url, $CFG->apphome) ) $same_host = true;
        if ( $CFG->wwwroot && startsWith($launch_return_url, $CFG->wwwroot) ) $same_host = true;

        // Canvas bug: launch_target is iframe even in new window (2017-01-10)
        $launch_target = LTIX::ltiRawParameter('launch_presentation_document_target', false);
        if ( $menu_set === false && $this->session_get($sess_key) ) {
            $menu_set = \Tsugi\UI\MenuSet::import($this->session_get($sess_key));
        } else if ( $menu_set === true ) {
            $menu_set = self::defaultMenuSet();
        } else if ( $same_host && $launch_return_url ) {
            // If we are in an iframe we will be hidden
            $menu_set = self::returnMenuSet($launch_return_url);
        } else if ( $launch_target !== false && strtolower($launch_target) == 'window' ) {
            $menu_set = self::closeMenuSet();
        // Since Coursers sets precious little
        } else if ( $LAUNCH->isCoursera() ) {
            $menu_set = self::closeMenuSet();
        // Since canvas does not set launch_target properly
        } else if ( $launch_target !== false && ( $LAUNCH->isCanvas() || $LAUNCH->isCoursera() ) ) {
            $menu_set = self::closeMenuSet();
        } else if ( $launch_return_url !== false ) {
            $menu_set = self::returnMenuSet($launch_return_url);
        }

        // Always put something out if we are an outer page - in an iframe, it will be hidden
        if ( $menu_set === false && defined('COOKIE_SESSION') ) {
            $menu_set = self::defaultMenuSet();
        }

        if ( $menu_set === false ) {
            $menu_set = self::closeMenuSet();
        }

        $menu_txt = self::menuNav($menu_set);
        if ( $this->buffer ) return $menu_txt;
        echo($menu_txt);
    }

    private function recurseNav($entry, $depth) {
        global $CFG;
        $current_url = $CFG->getCurrentUrl();
        $retval = '';
        $pad = str_repeat('    ',$depth);
        if ( $depth > 10 ) return $retval;
        if ( !is_array($entry->href) ) {
            $target = '';
            $url = $entry->href;
            if ( (strpos($url,'http:') === 0 || strpos($url,'https:') === 0 ) &&
                ( strpos($url, $CFG->apphome) === false && strpos($url, $CFG->wwwroot) === false ) ) {
                $target = ' target="_blank"';
            }
            $active = '';
            if ( $current_url == $url ) {
                $active = ' class="active"';
            }
            $retval .= $pad.'<li'.$active.'><a href="'.$url.'"'.$target.'>'.$entry->link.'</a></li>'."\n";
            return $retval;
        }
        $retval .= $pad.'<li class="dropdown">'."\n";
        $retval .= $pad.'  <a href="#" class="dropdown-toggle" data-toggle="dropdown">'.$entry->link.'<b class="caret"></b></a>'."\n";
        $retval .= $pad.'  <ul class="dropdown-menu">'."\n";
        foreach($entry->href as $child) {
           $retval .= $this->recurseNav($child, $depth+1);
        }
        $retval .= $pad."  </ul>\n";
        $retval .= $pad."</li>\n";
        return $retval;
    }

    function menuNav($set) {
        global $CFG, $LAUNCH;

$retval = <<< EOF
<nav class="navbar navbar-default navbar-fixed-top" role="navigation" id="tsugi_main_nav_bar" style="display:none">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>

EOF;

        if ( $set->home ) {
            $retval .= '      <a class="navbar-brand" href="'.$set->home->href.'">'.$set->home->link.'</a>'."\n";
        }
        $retval .= "    </div>\n";
        $retval .= '    <div class="navbar-collapse collapse">'."\n";

        if ( $set->left && count($set->left->menu) > 0 ) {
            $retval .= '      <ul class="nav navbar-nav navbar-main">'."\n";
            foreach($set->left->menu as $entry) {
                $retval .= $this->recurseNav($entry, 2);
            }
            $retval .= "      </ul>\n";
        }

        if ( $set->right && count($set->right->menu) > 0 ) {
            $retval .= '      <ul class="nav navbar-nav navbar-right">'."\n";
            foreach($set->right->menu as $entry) {
                $retval .= $this->recurseNav($entry, 2);
            }
            $retval .= "      </ul>\n";
        }

        $retval .= "    </div> <!--/.nav-collapse -->\n";
        $retval .= "  </div> <!--container-fluid -->\n";
        $retval .= "</nav>\n";
        $inmoodle = $LAUNCH->isMoodle() ? "true" : "false";
        $retval .= "<script>\n";
        $retval .= "if ( ".$inmoodle." || ! inIframe() ) {\n";
        $retval .= "  document.getElementById('tsugi_main_nav_bar').style.display = 'block';\n";
        $retval .= "  document.getElementsByTagName('body')[0].style.paddingTop = '70px';\n";
        $retval .= "}\n";
        $retval .= "</script>\n";

        // See if the LTI login can be linked to the site login...
    	if ( isset($_SESSION['lti']) && !defined('COOKIE_SESSION') &&  isset($_COOKIE[$CFG->cookiename])) {
            $ct = $_COOKIE[$CFG->cookiename];
            // error_log("Cookie: $ct \n");
            $pieces = SecureCookie::extract($ct);
            $lti = $_SESSION['lti'];
            // Contemplate: Do we care if the lti email matches the cookie email?
            if ( count($pieces) == 3 && isset($lti['user_id']) && !isset($lti['profile_id']) && isset($lti['user_email']) ) {
            	$linkprofile_url = self::getUtilUrl('/linkprofile.php');
            	$linkprofile_url = addSession($linkprofile_url);
                $retval .= self::embeddedMenu($linkprofile_url, $pieces[1], $lti['user_email']);
            }
	}
        return $retval;
    }

    /**
     * The embedded menu - for now just one button...
     */
    public static function embeddedMenu($url, $profile_email, $user_email) {
        global $CFG;
        if ( ! $CFG->unify ) return '';
        if ( isset($_COOKIE['TSUGILINKDISMISS']) ) return '';
        $message = htmlentities($profile_email);
return <<< EOF
<div id="tsugi-link-dialog" title="Read Only Dialog" style="display: none;">
<p>{$CFG->servicename} Message: You already have an account on this web site.  Your current
system-wide login is:
<pre>
$message
</pre>
Would you like to link these two accounts? </p><p>
<button type="button" class="btn btn-primary"
onclick="
$.getJSON('$url', function(retval) {
    tsugiSetCookie('TSUGILINKDISMISS', 'true', 30);
    $('#tsugi-link-dialog').dialog( 'close' );
    $('#tsugi-embed-menu').hide();
}).error(function() {
    alert('Error in JSON call');
});
return false;
">Link Accounts</button>
<button type="button" class="btn btn-secondary"
onclick="
tsugiSetCookie('TSUGILINKDISMISS', 'true', 30);
$('#tsugi-link-dialog').dialog( 'close' );
$('#tsugi-embed-menu').hide();
return false
;">Dismiss this notification</button>
</p>
</div>
<div id="tsugi-embed-menu" style="display:none; position: fixed; top: 100px; right: 5px; width: 150px; ">
<button
style="float: right"  class="btn btn-default"
onclick="tsugiEmbedKeep();showModal('Link Accounts','tsugi-link-dialog'); return false;"
>
<span class="fa-stack fa-2x has-badge" data-count="1">
  <i class="fa fa-square fa-stack-2x"></i>
  <i class="fa fa-paper-plane fa-stack-1x fa-inverse"></i>
</span>
</button></div>
EOF;
    }


    /**
     * Dump a debug array with messages and optional detail
     *
     * This kind of debug array comes back from some of the
     * grade calls.  We loop through printing the messages and
     * put the detail into a togglable pre tag is present.
     */
    function dumpDebugArray($debug_log) {
        if ( ! is_array($debug_log) ) return;

        foreach ( $debug_log as $k => $v ) {
            if ( count($v) > 1 ) {
                $this->togglePre($v[0], $v[1]);
            } else {
                line_out($v[0]);
            }
        }
    }

    /**
      * Get a fully-qualified URL for the spinner.
      */
    function getSpinnerUrl() {
        global $CFG;
        return $CFG->staticroot . '/img/spinner.gif';
    }

    /**
      * Get a fully-qualified URL for the default icon image.
      */
    function getDefaultIcon() {
        global $CFG;
        return $CFG->staticroot . '/img/default-icon.png';
    }

    /**
     * Return the text for a full-screen loader
     *
     *     echo($OUTPUT->getScreenOverlay(false));
     *         ...
     *     <script>
     *     showOverlay();
     *     setTimeout(function() { hideOverlay();} , 5000);
     *     </script>
     */
    function getScreenOverlay($show=true) {
        global $CFG;
        return
            '<div class="tsugi_overlay" id="tsugi_overlay" style="position: fixed, display:'.
            ($show ? 'block' : 'none'). '">' . "\n" .
            '<i style="color: blue;" class="fa fa-spinner fa-spin fa-5x fa-fw"></i>' . "\n" .
            // '<img src="'.$CFG->staticroot.'/img/logos/apereo-logo-blue-spin.svg" id="tsugi_overlay_spinner" width="100px" height="100px">' . "\n" .
            '</div>' . "\n" ;
    }

    /**
     * Embed a YouTube video using the standard pattern
     */
    function embedYouTube($id, $title) {
        echo('<div class="youtube-player" data-id="'.$id.'"></div>');
/*
        echo('<iframe src="https://www.youtube.com/embed/'.
            $video->youtube.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen '.
            ' alt="'.htmlentities($video->title).'"></iframe>'."\n");
*/
    }

    /**
     * Redirect to a local URL, adding session if necessary
     *
     * Note that this is only needed for AJAX and header() calls
     * as &lt;form> and &lt;a href tags are properly handled already
     * by the PHP built-in "don't use cookies for session" support.
     */
    public static function doRedirect($location) {
        if ( headers_sent() ) {
            // TODO: Check if this is fixed in PHP 70200
            // https://bugs.php.net/bug.php?id=74892
            if ( PHP_VERSION_ID >= 70000 ) {
                $location = U::addSession($location);
            }
            echo('<a href="'.$location.'">Continue</a>'."\n");
        } else {
            if ( ini_get('session.use_cookies') == 0 ) {
                $location = addSession($location);
            }
            header("Location: $location");
        }
    }

    /**
     * Gets an absolute static path to the specified file
     */
    public static function getLocalStatic() {
        return U::get_rest_parent();
    }

    // http://stackoverflow.com/questions/49547/making-sure-a-web-page-is-not-cached-across-all-browsers
    // http://www.php.net/manual/en/public static function.header.php
    public static function noCacheHeader() {
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
        header('Pragma: no-cache'); // HTTP 1.0.
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past - proxies
    }

    public static function maxCacheHeader($max_age=604800) {
        header('Cache-Control: max-age='.$max_age);  // A Week...
    }

    public static function get_gradient($pos) {
        $grads = array(
            array("#4c2119","#48241d","#46261f","#98331f"),
            array("#511e14","#4d2118","#4b221a","#a12d16"),
            array("#4c2a19","#482b1d","#462c1f","#98471f"),
            array("#512814","#4d2a18","#4b2a1a","#a14416"),
            array("#4c3319","#48331d","#46331f","#985b1f"),
            array("#513314","#4d3318","#4b331a","#a15b16"),
            array("#4c3b19","#483a1d","#46391f","#986f1f"),
            array("#513d14","#4d3b18","#4b3b1a","#a17316"),
            array("#334c19","#33481d","#33461f","#5b981f"),
            array("#2a4c19","#2b481d","#2c461f","#47981f"),
            array("#224c19","#24481d","#26461f","#33981f"),
            array("#1e5114","#214d18","#224b1a","#2da116"),
            array("#194c22","#1d4824","#1f4626","#1f9833"),
            array("#14511e","#184d21","#1a4b22","#16a12d"),
            array("#194c2a","#1d482b","#1f462c","#1f9847"),
            array("#145128","#184d2a","#1a4b2a","#16a144"),
            array("#194c33","#1d4833","#1f4633","#1f985b"),
            array("#194c3b","#1d483a","#1f4639","#1f986f"),
            array("#194c44","#1d4841","#1f463f","#1f9884"),
            array("#19434c","#1d4148","#1f3f46","#1f8498"),
            array("#144751","#18444d","#1a434b","#168aa1"),
            array("#193b4c","#1d3a48","#1f3946","#1f6f98"),
            array("#143d51","#183b4d","#1a3b4b","#1673a1"),
            array("#19324c","#1d3248","#1f3246","#1f5b98"),
            array("#143251","#18324d","#1a324b","#165ba1"),
            array("#264c72","#2c4c6c","#2f4c69","#2775c2"),
            array("#1e4c7a","#244c74","#274c71","#1c75ce"),
            array("#192a4c","#1d2b48","#1f2c46","#1f4798"),
            array("#142851","#182a4d","#1a2a4b","#1644a1"),
            array("#263f72","#2c416c","#2f4269","#275bc2"),
            array("#1e3d7a","#243f74","#274071","#1c57ce"),
            array("#32194c","#321d48","#321f46","#5b1f98"),
            array("#321451","#32184d","#321a4b","#5b16a1"),
            array("#4c2672","#4c2c6c","#4c2f69","#7527c2"),
            array("#4c1e7a","#4c2474","#4c2771","#751cce"),
            array("#3b194c","#3a1d48","#391f46","#6f1f98"),
            array("#3d1451","#3b184d","#3b1a4b","#7316a1"),
            array("#592672","#572c6c","#562f69","#8f27c2"),
            array("#5b1e7a","#592474","#582771","#931cce"),
            array("#43194c","#411d48","#3f1f46","#841f98"),
            array("#471451","#44184d","#431a4b","#8a16a1"),
            array("#4c194c","#481d48","#461f46","#981f98"),
            array("#511451","#4d184d","#4b1a4b","#a116a1"),
            array("#4c1944","#481d41","#461f3f","#981f84"),
            array("#511447","#4d1844","#4b1a43","#a1168a"),
            array("#4c193b","#481d3a","#461f39","#981f6f"),
            array("#51143d","#4d183b","#4b1a3b","#a11673"),
            array("#4c1933","#481d33","#461f33","#981f5b"),
            array("#511433","#4d1833","#4b1a33","#a1165b"),
            array("#4c192a","#481d2b","#461f2c","#981f47"),
            array("#511428","#4d182a","#4b1a2a","#a11644"),
            array("#4c1922","#481d24","#461f26","#981f33"),
            array("#51141e","#4d1821","#4b1a22","#a1162d")
        );

        $pos = $pos % count($grads);

        return $grads[$pos];
    }

    public static function displaySize($size) {
        if ( $size > 1024*1024*1024*2 ) {
            return (int) ($size/(1024*1024*1024))."GB";
        }
        if ( $size > 1024*1024*2 ) {
            return (int) ($size/(1024*1024))."MB";
        }
        if ( $size > 1024*2 ) {
            return (int) ($size/(1024))."KB";
        }
        return $size."B";
    }

    // Clean out the array of 'secret' keys
    public static function safe_var_dump($x) {
            ob_start();
            if ( isset($x['secret']) ) $x['secret'] = MD5($x['secret']);
            if ( is_array($x) ) foreach ( $x as &$v ) {
                if ( is_array($v) && isset($v['secret']) ) $v['secret'] = MD5($v['secret']);
            }
            var_dump($x);
            $result = ob_get_clean();
            return $result;
    }

    public static function jsonError($message,$detail="") {
        header('HTTP/1.1 400 '.$message);
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode(array("error" => $message, "detail" => $detail)));
    }

    public static function jsonAuthError($message,$detail="") {
        header('HTTP/1.1 403 '.$message);
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode(array("error" => $message, "detail" => $detail)));
    }

    public static function jsonOutput($json_data) {
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($json_data));
    }

    public static function xmlError($message,$detail="",$code=400) {
        header('HTTP/1.1 '.$code.' '.$message);
        header('Content-Type: text/xml; charset=utf-8');
        echo('<?xml version="1.0" encoding="UTF-8" standalone="yes"?'.">\n");
        echo("<failure>\n  <message>\n    ");
        echo(htmlentities($error));
        echo("  </message>\n");
        if ( strlen($detail) > 0 ) {
            echo("  <detail>\n    ");
            echo(htmlentities($detail));
            echo("  </detail>\n");
        }
        echo("</failure>\n");
    }

    public static function xmlAuthError($message,$detail="") {
        self::xmlError($message,$detail,403);
    }

    public static function xmlOutput($xml_data) {
        header('Content-Type: text/xml; charset=utf-8');
        echo($xml_data);
    }

    // No Buffering
    public static function noBuffer() {
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
    }

}
