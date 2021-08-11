<?php

namespace Tsugi\UI;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Core\WebSocket;
use \Tsugi\Crypt\SecureCookie;
use Tsugi\UI\HandleBars;
use Tsugi\UI\Theme;

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
 *     use \Tsugi\Util\U;
 *
 *     // Require CONTEXT, USER, and LINK
 *     $LAUNCH = LTIX::requireData();
 *
 *     // Handle incoming POST data and redirect as necessary...
 *     if ( ... ) {
 *         header( 'Location: '.U::addSession('index.php') ) ;
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

class Output {

    /**
     * A reference to our containing launch
     */
    public $launch;

    // Pull in all the session access functions
    use \Tsugi\Core\SessionTrait;

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
        echo '<div id="flashmessages">';
        if ( $this->session_get('error') ) {
            echo '<div class="alert alert-danger alert-banner" style="clear:both">
                    <a href="#" class="close" data-dismiss="alert">&times;</a>'.
                $this->session_get('error')."</div>\n";
            $this->session_forget('error');
        } else if ( isset($_GET['lti_errormsg']) ) {
            echo '<div class="alert alert-danger alert-banner" style="clear:both">
                    <a href="#" class="close" data-dismiss="alert">&times;</a>'.
                htmlentities($_GET['lti_errormsg'])."</div>";

            if ( isset($_GET['detail']) ) {
                echo("\n<!--\n");
                echo(str_replace("-->","--:>",$_GET['detail']));
                echo("\n-->\n");
            }
        }

        if ( $this->session_get('success') ) {
            echo '<div class="alert alert-success alert-banner" style="clear:both">
                    <a href="#" class="close" data-dismiss="alert">&times;</a>'.
                $this->session_get('success')."</div>\n";

            $this->session_forget('success');
        }

        echo '</div>'; // End flash messages container

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * Start the header material of a normal Tsugi Page
     *
     * This outputs everything but does not close the <head>
     * tag so the tool can add its own head material before
     * calling bodyStart().
     *
     * If this class is set to buffer, the output is returned
     * in a string instead of being printed to the response.
     */
    function header() {
        global $HEAD_CONTENT_SENT, $CFG, $RUNNING_IN_TOOL, $CONTEXT, $USER, $LINK;

        if ( $HEAD_CONTENT_SENT === true ) return;
        header('Content-Type: text/html; charset=utf-8');
        $lang = isset($CFG->lang) && $CFG->lang ? $CFG->lang : 'en';
        ob_start();
    ?><!DOCTYPE html>
    <html lang="<?= $lang ?>">
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $CFG->servicename ?><?php if ( isset($CFG->context_title) ) echo(' - '.$CFG->context_title); ?></title>
<?php echo($this->headerData()); ?>
        <!-- Tiny bit of JS -->
        <script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
        <!-- Le styles -->
        <link href="<?= $CFG->staticroot ?>/bootstrap-3.4.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/js/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet">
        <?php if ( strpos($CFG->fontawesome, 'free-5.') > 0 ) { ?>
        <link href="<?= $CFG->fontawesome ?>/css/all.css" rel="stylesheet">
        <link href="<?= $CFG->fontawesome ?>/css/v4-shims.css" rel="stylesheet">
        <?php } else { ?>
        <link href="<?= $CFG->fontawesome ?>/css/font-awesome.min.css" rel="stylesheet">
        <?php }
        
        $theme = self::get_theme();
        if (isset($theme["font-url"])) {
            echo '<link href="'.$theme["font-url"].'" rel="stylesheet">';
        }
        
        self::output_theme_css($theme) ?>

          <link href="<?= $CFG->staticroot ?>/css/tsugi2.css" rel="stylesheet">

          <style>
              <?php
              if ( isset($CFG->extra_css) ) {
                  echo($CFG->extra_css."\n");
              }
              ?>
          </style>
<?php // https://lefkomedia.com/adding-external-link-indicator-with-css/
  if ( $CFG->google_translate ) { ?>
<style>
a[target="_blank"]:after {
    font-family: 'Font Awesome 5 Free';
    font-weight: 600;
    content: " \f35d";
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

    /**
     * Set the header variables for a Tsugi Page
     *
     * If this class is set to buffer, the output is returned
     * in a string instead of being printed to the response.
     */
    function headerData() {
        global $CFG, $CONTEXT, $USER, $LINK;

        // https://security.stackexchange.com/questions/110101/proper-way-to-protect-against-xss-when-output-is-directly-into-js-not-html
        $retval = "<script>\n var _TSUGI = {\n";
        if ( isset($CONTEXT->title) ) {
            $retval .= '            context_title: '.self::json_encode_string_value($CONTEXT->title).",\n";
        }
        if ( isset($LINK->title) ) {
            $retval .= '            link_title: '.self::json_encode_string_value($LINK->title).",\n";
        }
        if ( isset($USER->displayname) ) {
            $retval .= '            user_displayname: '.self::json_encode_string_value($USER->displayname).",\n";
        }
        if ( isset($USER->locale) ) {
            $retval .= '            user_locale: '.self::json_encode_string_value($USER->locale).",\n";
        }
        if ( strlen(session_id()) > 0 && ini_get('session.use_cookies') == '0' ) {
            $retval .= '            ajax_session: "'.urlencode(session_name()).'='.urlencode(session_id()).'"'.",\n";
        } else {
            $retval .= '            ajax_session: false,'."\n";
        }

        if ( isset($USER->instructor) && $USER->instructor ) {
            $retval .= '            instructor: true,  // Use only for UI display'."\n";
        }
        $heartbeat = 10*60*1000; // 10 minutes
        if ( isset($CFG->sessionlifetime) ) {
            $heartbeat = ( $CFG->sessionlifetime * 1000) / 2;
        }
        if ( $heartbeat < 10*60*1000 ) $heartbeat = 10*60*1000;   // Minumum 10 minutes
        // $heartbeat = 10000; // Debug 10 seconds
        $heartbeat_url = self::getUtilUrl('/heartbeat.php');
        $heartbeat_url = U::add_url_parm($heartbeat_url,'msec',$heartbeat);
        $heartbeat_url = U::addSession($heartbeat_url);

        $retval .= "heartbeat: ".$heartbeat.",\n";
        $retval .= "heartbeat_url: \"".$heartbeat_url."\",\n";
        $retval .= "rest_path: ".json_encode(U::rest_path()).",\n";
        $retval .= "spinnerUrl: \"".self::getSpinnerUrl()."\",\n";
        $retval .= "staticroot: \"".$CFG->staticroot."\",\n";
        if ( isset ($CFG->apphome) ) {
            $retval .= "apphome: \"".$CFG->apphome."\",\n";
        } else {
            $retval .= "apphome: false,\n";
        }
        $retval .= "wwwroot: \"".$CFG->wwwroot."\",\n";
        $websocket_url = (WebSocket::enabled() && $LINK) ? '"'.$CFG->websocket_url.'"' : 'false';
        $retval .= "websocket_url: ".$websocket_url.",\n";
        $websocket_token = (WebSocket::enabled() && $LINK) ? '"'.WebSocket::getToken($LINK->launch).'"' : 'false';
        $retval .= "websocket_token: ".$websocket_token.",\n";
        $retval .= "react_token: \"".session_id()."\",\n";
        $retval .= "window_close_message: \""._m('Application complete')."\",\n";
        $retval .= "session_expire_message: \""._m('Your session has expired')."\"\n";
        $retval .= "\n}\n</script>\n";
        return $retval;
    }

    /**
     * Finish the head and start the body of a Tsugi HTML page.
     *
     * By default this demands that we are in a GET request.  It
     * is a fatal error to call this code if we are responding
     * to a POST request unless this behavior is overidden.
     *
     * @param $checkpost (optional, boolean)  This can be set to
     * false to emit the body start even for a POST request.
     */
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
    document.getElementById("body_container").className = "container-fluid";
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
    * Outputs a splash page used if the tool is not configured or instructor has never been there before.
    *
    * @param $title Title of the tool
    * @param $msg Content of splash page messag
    * @param bool $link Set to href if want user to be able to click button to go to home
    * @return false|string
    */
    function splashPage($title, $msg, $link = false) {
        ob_start();
        self::header();
        echo '<style>body{background: var(--primary)}</style>';
        self::bodyStart();
        echo '<section class="splash-container">
                <article class="splash-content">
                <header><h1 class="splash-header">'.$title.'</h1></header>
                <p class="lead">'.$msg.'</p>';
        if ($link) {
            echo '<a href="'.$link.'" class="btn btn-success">Get Started</a>';
        }

        echo '</article></section>';
        
        self::footer();
        
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    function pageTitle($title, $show_help = false, $show_settings = false) {
        ob_start();
        echo '<div id="toolTitle" class="h1">';
        if ($show_help) {
            self::helpButton();
        }
        if ($show_settings) {
            SettingsForm::link(true);
        }
        echo '<span class="title-text-span">'.$title.'</span>';
        echo '</div>';
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    function modalString($title, $msg, $id) {
        $modal = <<< EOF
<div id="$id" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span class="fa fa-times" aria-hidden="true"></span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">$title</h4>
            </div>
            <div class="modal-body">$msg</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>
EOF;
        return $modal;
    }

    function helpModal($help_title = "Help", $help_msg = '<em>No help for this page.</em>') {
        $modal = $this->modalString($help_title, $help_msg, "helpModal");
        if ( $this->buffer ) return $modal;
        echo($modal);
    }
    
    function helpButton() {
        $button = '<button id="helpButton" type="button" class="btn btn-link pull-right" data-toggle="modal" data-target="#helpModal"><span class="fas fa-question-circle" aria-hidden="true"></span> Help</button>';
        if ( $this->buffer ) return $button;
        echo($button);
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
        echo('<script src="'.$CFG->staticroot.'/bootstrap-3.4.1/js/bootstrap.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/jquery-ui-1.11.4/jquery-ui.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/jquery.timeago-1.6.3.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/handlebars-v4.0.2.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/tmpljs-3.8.0/tmpl.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/tsugiscripts.js"></script>'."\n");

?>
<script type="text/javascript">
    HEARTBEAT_TIMEOUT = setTimeout(doHeartBeat, _TSUGI.heartbeat);
    tsugiEmbedMenu();
    $(document).ready(function() { jQuery("time.timeago").timeago(); });
</script>
<?php

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

        // This was originallly supposed to be Fixed in 7.1.9 - but this seems to regress
        // periodically in PHP so - we will just keep doing it

        // https://bugs.php.net/bug.php?id=74892
        // Worked in PHP 5.5
        // Failed in 7.0.0 - 7.1.8
        // Fixed in 7.1.9
        // https://www.php.net/ChangeLog-7.php#7.1.9
        // https://stackoverflow.com/questions/44980654/how-can-i-make-trans-sid-cookie-less-sessions-work-in-php-7-1
?>
<script>
$('a').each(function (x) {
    var href = $(this).attr('href');
    if ( ! href ) return;
    if ( ! href.startsWith('#') ) return;
    var pos = href.indexOf('/?');
    if ( pos < 1 ) return;
    // console.dir('Patching broken # href='+href);
    href = href.substring(0,pos);
    $(this).attr('href', href);
});
<?php
// Hack to compensate for PHP 7.0 cookiless session failure
    if ( ini_get('session.use_cookies') == '0' ) {
?>
$('a').each(function (x) {
    var href = $(this).attr('href');
    var sess_name = '<?= session_name() ?>';
    var sess_id = '<?= session_id() ?>';
    if ( ! href ) return;
    if ( href.startsWith('#') ) return;
    if ( href.indexOf(sess_name) > 0 ) return;
    if ( href.startsWith('javascript:') ) return;

    var localurl = true;
    if ( href.startsWith('http://') ) localurl = false;
    if ( href.startsWith('https://') ) localurl = false;
    if ( href.startsWith('//') ) localurl = false;

    if ( href.startsWith(_TSUGI.wwwroot) ) localurl = true;
    if ( _TSUGI.apphome && href.startsWith(_TSUGI.apphome) ) localurl = true;

    // console.log(href,localurl);
 
    if ( ! localurl ) return;
    if ( href.indexOf('?') > 0 ) {
        href = href + '&';
    } else {
        href = href + '?';
    }
    href = href + sess_name + '=' + sess_id;
    // console.dir('Patching missing session href='+href);
    $(this).attr('href', href);
});
<?php } ?>

</script>
<?php
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

        $session_id = U::get($_GET, session_name());
        if ( $session_id && $session_id != session_id() ) {
            error_log("Heartbeat session_id=".$session_id." session_start=".session_id());
        }

        if ( $session_id && ! U::get($_SESSION,'lti') ) {
            error_log("Heartbeat session_id=".$session_id." missing lti value");
        }

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

        if ( $USER->admin ) {
            echo(" "._m("(Instructor+Administrator)"));
        } else if ( $USER->instructor ) {
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
        $text = _m('Show/Hide');
        $detail = _m('characters retrieved');
        echo('<strong>'.htmlpre_utf8($title));
        echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">'.$text.'</a></strong>'."\n");
        echo(' '.strlen($html).' '.$detail.')'."\n");
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
        $set->setHome(_m('Done'), 'javascript:window.location.href=\''.urlencode($return_url).'\';');
        return $set;
    }

    function closeMenuSet() {
        global $CFG;
        $R = $CFG->wwwroot . '/';
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome(_m('Done'), 'javascript:window_close();');
        return $set;
    }

    function defaultMenuSet() {
        global $CFG;
        if ( isset($CFG->defaultmenu) ) return $CFG->defaultmenu;

        $R = $CFG->wwwroot . '/';
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome($CFG->servicename, $CFG->apphome);
        $set->addLeft(_m('Tools'), $R.'store');
        if ( $this->session_get('id') ) {
                $set->addLeft(_m('Settings'), $R . 'settings');
        }

        if ( $this->session_get('id') ) {
            $submenu = new \Tsugi\UI\Menu();
            $submenu->addLink(_m('Profile'), $R.'profile');
            if ( $CFG->DEVELOPER || U::get($_COOKIE, 'adminmenu') ) {
                $submenu->addLink(_m('Admin'), $R.'admin');
            }

            $submenu->addLink(_m('Logout'), $R.'logout');
            $set->addRight(htmlentities($this->session_get('displayname', '')), $submenu);
        } else {
            if ( $CFG->DEVELOPER || U::get($_COOKIE, 'adminmenu') ) {
                $set->addLeft(_m('Admin'), $R.'admin');
            }
            if ( $CFG->google_client_id ) {
                $set->addRight(_m('Login'), $R.'login');
            }
        }

        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('IMS LTI 1.1 Spec', 'http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html')
            ->addLink('IMS LTI Deep Linking', 'https://www.imsglobal.org/specs/lticiv1p0')
            ->addLink('IMS LTI 2.0 Spec', 'http://www.imsglobal.org/lti/ltiv2p0/ltiIMGv2p0.html')
            ->addLink('Google Classroom', 'https://classroom.google.com/')
            ->addLink('Tsugi Project Site', 'https://www.tsugi.org/');
        if ( $CFG->DEVELOPER) $set->addRight(_m('Links'), $submenu);

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
     * Emit the top navigation block and optionally the tool navigation
     *
     * Priority order:
     * (1) Navigation in the session
     * (2) If we are launched via LTI w/o a session
     */
    function topNav($tool_menu=false) {
        global $CFG, $TSUGI_LAUNCH;
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;

        $launch_return_url = LTIX::ltiRawParameter('launch_presentation_return_url', false);
        // Ways to know if this was a launch or are we stand alone
        $user_id = LTIX::ltiRawParameter('user_id', false);
        $oauth_nonce = LTIX::ltiRawParameter('oauth_nonce', false);

        $same_host = false;
        if ( $CFG->apphome && startsWith($launch_return_url, $CFG->apphome) ) $same_host = true;
        if ( $CFG->wwwroot && startsWith($launch_return_url, $CFG->wwwroot) ) $same_host = true;

        // Canvas bug: launch_target is iframe even in new window (2017-01-10)
        $launch_target = LTIX::ltiRawParameter('launch_presentation_document_target', false);
        $menu_set = false;
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
        } else if ( is_object($TSUGI_LAUNCH) && $TSUGI_LAUNCH->isCoursera() ) {
            $menu_set = self::closeMenuSet();
        // Since canvas does not set launch_target properly
        } else if ( $launch_target !== false && is_object($TSUGI_LAUNCH) && ( $TSUGI_LAUNCH->isCanvas() || $TSUGI_LAUNCH->isCoursera() ) ) {
            $menu_set = self::closeMenuSet();
        } else if ( $launch_return_url !== false && strlen($launch_return_url) > 0 ) {
            $menu_set = self::returnMenuSet($launch_return_url);
        // We are running stand alone (i.e. not from a real LTI Launch)
        } else if ( $user_id === false ) {
            $menu_set = self::defaultMenuSet();
        } else {
            $menu_set = self::closeMenuSet();
        }

        // Always put something out if we are an outer page - in an iframe, it will be hidden
        if ( $menu_set === false && defined('COOKIE_SESSION') ) {
            $menu_set = self::defaultMenuSet();
        }

        if ( $menu_set === false ) {
            $menu_set = self::closeMenuSet();
        }

        $menu_txt = self::menuNav($menu_set);
        if ( $tool_menu ) $menu_txt .= self::menuNav($tool_menu, true);

        // Show / hide / adjust the navigation
        $menu_txt .= "<script>\n";
        $menu_txt .= "if ( ! inIframe() ) {\n";
        $menu_txt .= "  document.getElementById('tsugi_main_nav_bar').style.display = 'block';\n";
        $menu_txt .= "  document.getElementsByTagName('body')[0].style.paddingTop = '5.93rem';\n";
        $menu_txt .= "} else {\n";
        if ( $tool_menu ) {
            $menu_txt .= "  document.getElementById('tsugi_tool_nav_bar').classList.add(\"navbar-fixed-top\");\n";
            $menu_txt .= "  document.getElementsByTagName('body')[0].style.paddingTop = '5.93rem';\n";
        } else {
            $menu_txt .= "  document.getElementsByTagName('body')[0].style.paddingTop = '1.0rem';\n";
        }
        $menu_txt .= "}\n";
        $menu_txt .= "</script>\n";

        // Send it back
        if ( $this->buffer ) return $menu_txt;
        echo($menu_txt);
    }

    private function recurseNav($entry, $depth, $is_tool_nav = false) {
        global $CFG;
        $current_url = $is_tool_nav ? basename($_SERVER['PHP_SELF']) : $CFG->getCurrentUrl();
        $retval = '';
        $pad = str_repeat('    ',$depth);
        if ( $depth > 10 ) return $retval;
        if ( !is_array($entry->href) ) {
            $target = '';
            $url = $entry->href;
            $attr = $entry->attr;
            if ( $url === false ) {
                $retval .= $pad.'<p class="navbar-text">'.$entry->link.'</p>'."\n";
                return $retval;
            }
            if ( (strpos($url,'http:') === 0 || strpos($url,'https:') === 0 ) &&
                ( ! is_string($CFG->apphome) || strpos($url, $CFG->apphome) === false ) &&
                ( ! is_string($CFG->wwwroot) || strpos($url, $CFG->wwwroot) === false ) ) {
                $target = ' target="_blank"';
            }
            $active = '';
            if ( $current_url == $url ) {
                $active = ' class="active"';
            }
            $retval .= $pad.'<li'.$active.'><a href="'.$url.'"'.$target.' '.$attr.'>'.$entry->link.'</a></li>'."\n";
            return $retval;
        }
        $retval .= $pad.'<li class="dropdown">'."\n";
        $dropdown_link_class = 'dropdown-toggle';
        if (strpos($entry->link, '<img') !== false) {
            // Drop down link contains an image so add class to style
            $dropdown_link_class .= ' dropdown-img';
        }
        $retval .= $pad.'  <a href="#" class="'.$dropdown_link_class.'" data-toggle="dropdown">'.$entry->link.' <span class="fa fa-caret-down" aria-hidden="true"></span></a>'."\n";
        $retval .= $pad.'  <ul class="dropdown-menu">'."\n";
        foreach($entry->href as $child) {
           $retval .= $this->recurseNav($child, $depth+1);
        }
        $retval .= $pad."  </ul>\n";
        $retval .= $pad."</li>\n";
        return $retval;
    }

    function menuNav($set, $is_tool_menu = false) {
        global $CFG, $TSUGI_LAUNCH;

        if ( $is_tool_menu ) {
            $retval = '<nav class="navbar navbar-default" role="navigation" id="tsugi_tool_nav_bar">';
        } else {
           $retval = '<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation" id="tsugi_main_nav_bar" style="display:none">';
        }

$retval .= <<< EOF
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
                $retval .= $this->recurseNav($entry, 2, $is_tool_menu);
            }
            $retval .= "      </ul>\n";
        }

        if ( $set->right && count($set->right->menu) > 0 ) {
            $retval .= '      <ul class="nav navbar-nav navbar-right">'."\n";
            foreach($set->right->menu as $entry) {
                $retval .= $this->recurseNav($entry, 2, $is_tool_menu);
            }
            $retval .= "      </ul>\n";
        }

        $retval .= "    </div> <!--/.nav-collapse -->\n";
        $retval .= "  </div> <!--container -->\n";
        $retval .= "</nav>\n";

        // See if the LTI login can be linked to the site login...
        if ( isset($_SESSION['lti']) && !defined('COOKIE_SESSION') &&  isset($_COOKIE[$CFG->cookiename])) {
            $ct = $_COOKIE[$CFG->cookiename];
            // error_log("Cookie: $ct \n");
            $pieces = SecureCookie::extract($ct);
            $lti = $_SESSION['lti'];
            // Contemplate: Do we care if the lti email matches the cookie email?
            if ( is_array($pieces) && count($pieces) == 3 && isset($lti['user_id']) && !isset($lti['profile_id']) && isset($lti['user_email']) ) {
                $linkprofile_url = self::getUtilUrl('/linkprofile.php');
                $linkprofile_url = U::addSession($linkprofile_url);
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
            if ( is_array($v) && count($v) > 1 ) {
                $this->togglePre($v[0], $v[1]);
            } else if ( is_array($v) ) {
                line_out($v[0]);
            } else if ( is_string($v) ) {
                line_out($v);
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
            // Fixed in 7.1.9
            // https://www.php.net/ChangeLog-7.php#7.1.9
            // https://bugs.php.net/bug.php?id=74892
            if ( version_compare(PHP_VERSION, '7.1.9') < 0 ) {
                $location = U::addSession($location);
            }
            echo('<a href="'.$location.'">Continue</a>'."\n");
        } else {
            if ( ini_get('session.use_cookies') == 0 ) {
                $location = U::addSession($location);
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

    public static function displaySize($size) {
        return U::displaySize($size);
    }

    // Clean out the array of 'secret' keys
    public static function safe_var_cleanup(&$x, $depth) {
        if ( $depth >= 5 ) return;
        if ( is_array($x) || is_object($x) ) {
            foreach($x as $k => $v ) {
                if (  is_string($v) && strlen($v) > 0 && strpos($k, 'secret') !== false || strpos($k, 'priv') !== false ) {
                    if ( is_array($x) ) {
                        $x[$k] = 'Hidden as MD5: '.MD5($v);
                    } else {
                        $x->{$k} = 'Hidden as MD5: '.MD5($v);
                    }
                }
                if ( is_array($v) || is_object($v) ) self::safe_var_cleanup($v,$depth+1);
            }
        }
    }

    public static function safe_var_dump($x) {
        ob_start();
        self::safe_var_cleanup($x, 0);
        var_dump($x);
        $result = ob_get_clean();
        return $result;
    }

    public static function safe_print_r($x) {
        ob_start();
        self::safe_var_cleanup($x, 0);
        print_r($x);
        $result = ob_get_clean();
        return $result;
    }

    public static function htmlError($message,$detail,$next=false) {
        global $CFG;
        if ( headers_sent() ) header('HTTP/1.1 400 '.$message);

    ?><!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $CFG->servicename ?><?php if ( isset($CFG->context_title) ) echo(' - '.$CFG->context_title); ?></title>
    <!-- Tiny bit of JS -->
    <script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
    <!-- Le styles -->
    <link href="<?= $CFG->staticroot ?>/bootstrap-3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $CFG->staticroot ?>/js/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet">
    <link href="<?= $CFG->fontawesome ?>/css/font-awesome.min.css" rel="stylesheet">

      <?php
        $theme = self::get_theme();
        if (isset($theme["font-url"])) {
            echo '<link href="'.$theme["font-url"].'" rel="stylesheet">';
        }
        
        self::output_theme_css($theme) ?>

      <link href="<?= $CFG->staticroot ?>/css/tsugi.css" rel="stylesheet">
   </head>
<body>
<div id="dialog-confirm" style="display:none;" title="<?= htmlentities($message) ?>">
<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span><?= $detail ?></p>
</div>
<?php
        echo('<script src="'.$CFG->staticroot.'/js/jquery-1.11.3.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/bootstrap-3.4.1/js/bootstrap.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/jquery-ui-1.11.4/jquery-ui.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/js/tsugiscripts.js"></script>'."\n");
?>
<script>
  $( function() {
    $( "#dialog-confirm" ).dialog({
      resizable: false,
      height: "auto",
      width: 400,
      modal: true,
      buttons: {
<?php if ( $next ) { ?>
        "Continue": function() {
            window.location.href = "<?= $next ?>";
        },
<?php } ?>
      }
    });
  } );
  </script>
</body>
<?php
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

    // https://security.stackexchange.com/questions/110101/proper-way-to-protect-against-xss-when-output-is-directly-into-js-not-html
    public static function json_encode_string_value($json_string) {
        return json_encode($json_string, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);
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

    public static function get_theme() {
        global $CFG, $TSUGI_LAUNCH;

        // TODO: Enable this
        if ( false && is_object($TSUGI_LAUNCH) ) {
            $theme = $TSUGI_LAUNCH->session_get('tsugi_theme');
            if ( is_array($theme) ) return $theme;
        }

        // Check if we are to construct a theme
        if ( is_object($TSUGI_LAUNCH) ) {
            $theme_base = $TSUGI_LAUNCH->settingsCascade('theme-base', false);
            $dark_mode = $TSUGI_LAUNCH->settingsCascade('theme-dark-mode', false);
            $dark_mode = ($dark_mode == 'true') || ($dark_mode == 'yes');
            if ( U::isValidCSSColor($theme_base) ) {
                $theme = Theme::getLegacyTheme($theme_base, $dark_mode);
                $TSUGI_LAUNCH->session_put('tsugi_theme', $theme);
                return $theme;
            }
        }

        // Construct a theme the old way
        $theme = array();
        if ( isset($CFG->theme) && is_array($CFG->theme) ) {
            $theme = $CFG->theme;
        }

        $theme = Theme::defaults($theme);

        // Check for individual overides from link, context, key, or launch
        foreach($theme as $name => $value ) {
            if ( is_object($TSUGI_LAUNCH) ) {
                $check = $TSUGI_LAUNCH->settingsCascade($name, $value);
                if ( U::isValidCSSColor($check) ) $theme[$name] = $check;
            }
        }

        if ( is_object($TSUGI_LAUNCH) ) {
            $TSUGI_LAUNCH->session_put('tsugi_theme', $theme);
        }

        return $theme;
    }

    /**
     * Adjust the theme from various places based on the following low-to-high precedence
     *
     * (4) From a Key Setting
     * (3) From a Context Setting
     * (2) From a Link Setting
     * (1) From a custom launch variable prefixed by "tsugi_theme_"
     */
    public static function adjust_theme(&$theme) {
        global $TSUGI_LAUNCH, $TSUGI_KEY, $LINK, $CONTEXT;

        $copy = $theme;

    }

    public static function output_theme_css($theme) {

        $style = '<style>:root {';
        foreach($theme as $name => $value ) {
            $style .= '--'.$name.':'.$value.";\n";
        }
        $style .= '}</style>';
        echo($style);
    }

}
