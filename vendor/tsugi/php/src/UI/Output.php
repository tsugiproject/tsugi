<?php

namespace Tsugi\UI;

use Tsugi\Core\LTIX;

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
 *     $LTI = LTIX::requireData();
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

class Output {

    /**
     * Set the JSON header
     */
    public static function headerJson() {
        header('Content-Type: application/json; charset=UTF-8');
        self::noCacheHeader();
    }

    function flashMessages() {
        if ( isset($_SESSION['error']) ) {
            echo '<div class="alert alert-danger" style="clear:both"><a href="#" class="close" data-dismiss="alert">&times;</a>'.
            $_SESSION['error']."</div>\n";
            unset($_SESSION['error']);
        }
        if ( isset($_SESSION['success']) ) {
            echo '<div class="alert alert-success" style="clear:both"><a href="#" class="close" data-dismiss="alert">&times;</a>'.
            $_SESSION['success']."</div>\n";
            unset($_SESSION['success']);
        }
    }

    /**
     * Emit the HTML for the header.
     */
    function header($headCSS=false) {
        global $HEAD_CONTENT_SENT, $CFG, $RUNNING_IN_TOOL;
        global $CFG;
        if ( $HEAD_CONTENT_SENT === true ) return;
        header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
    <html>
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $CFG->servicename ?></title>
        <!-- Le styles -->
        <link href="<?= $CFG->staticroot ?>/static/bootstrap-3.1.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/static/bootstrap-3.1.1/css/bootstrap-theme.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/static/js/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/static/font-awesome-4.4.0/css/font-awesome.min.css" rel="stylesheet">

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
    </style>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="<?= $CFG->wwwroot ?>/static/html5shiv/html5shiv.js"></script>
          <script src="<?= $CFG->wwwroot ?>/static/respond/respond.min.js"></script>
        <![endif]-->

    <?php
        if ( isset($_SESSION['CSRF_TOKEN']) ) {
            echo('<script type="text/javascript">CSRF_TOKEN = "'.$_SESSION['CSRF_TOKEN'].'";</script>'."\n");
        } else {
            echo('<script type="text/javascript">CSRF_TOKEN = "TODORemoveThis";</script>'."\n");
        }

    	// Set the containing frame id is we have one
        $element_id = LTIX::postGet('ext_lti_element_id', false);
        if ( $element_id ) {
            echo('<script type="text/javascript">LTI_PARENT_IFRAME_ID = "'.$element_id.'";</script>'."\n");
        }

        $HEAD_CONTENT_SENT = true;
    }

    function bodyStart($checkpost=true) {
    // If we are in an iframe use different margins
?>
</head>
<body>
<script>
if (window!=window.top) {
    document.write('<div class="container_iframe">');
} else {
    document.write('<div class="container">');
}
</script>
<?php
        if ( $checkpost && count($_POST) > 0 ) {
            $dump = self::safe_var_dump($_POST);
            echo('<p style="color:red">Error - Unhandled POST request</p>');
            echo("\n<pre>\n");
            echo($dump);
            echo("\n</pre>\n");
            error_log($dump);
            die_with_error_log("Unhandled POST request");
        }
    }

    function footerStart() {
        global $CFG;
        echo('<script src="'.$CFG->staticroot.'/static/js/jquery-1.11.3.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/static/bootstrap-3.1.1/js/bootstrap.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/static/js/jquery-ui-1.11.4/jquery-ui.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/static/js/handlebars-v4.0.2.js"></script>'."\n");

        // Serve this locally during early development - Move to CDN when stable
        echo('<script src="'.$CFG->wwwroot.'/static/js/tsugiscripts.js"></script>'."\n");

        if ( isset($CFG->sessionlifetime) ) {
            $heartbeat = ( $CFG->sessionlifetime * 1000) / 2;
            // $heartbeat = 10000;
            $heartbeat_url = self::getUtilUrl('/heartbeat.php');
            $heartbeat_url = addSession($heartbeat_url);
    ?>
    <script type="text/javascript">
    HEARTBEAT_URL = '<?= $heartbeat_url ?>';
    HEARTBEAT_INTERVAL = setInterval(doHeartBeat, <?= $heartbeat ?>);
    </script>
    <?php
        }

        $this->doAnalytics();
    }

    /**
     * getUtilUrl - Get a URL in the utility script space - does not add session
     *
     * @param $path - The path of the file - should start with a slash.
     */
    public static function getUtilUrl($path) 
    {
        global $CFG;
        $retval = $CFG->vendorroot.$path;
        if ( isset($CFG->utilroot) ) {
            $retval = $CFG->utilroot.$path;
        }
        return $retval;
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

        // See how long since the last update of the activity time
        $seconds = 0;
        $now = time();
        if ( isset($_SESSION['LAST_ACTIVITY']) ) {
            $seconds = $now - $_SESSION['LAST_ACTIVITY'];
        }
        $_SESSION['LAST_ACTIVITY'] = $now; // update last activity time stamp

        // Count the successive heartbeats without a request/response cycle
        if ( ! isset($_SESSION['HEARTBEAT_COUNT']) ) $_SESSION['HEARTBEAT_COUNT'] = 0;
        $count = $_SESSION['HEARTBEAT_COUNT']++;

        if ( $count > 10 && ( $count % 100 ) == 0 ) {
            error_log("Heartbeat.php ".session_id().' '.$count);
        }

        $retval = array("success" => true, "seconds" => $seconds,
                "now" => $now, "count" => $count, "cookie" => $cookie,
                "id" => session_id());
        $retval['lti'] = isset($_SESSION['lti']);
        // $retval['lti'] = false;
        $retval['sessionlifetime'] = $CFG->sessionlifetime;
        echo(json_encode($retval));
    }

    function footerEnd() {
        echo("\n</div></body>\n</html>\n");
    }

    function footer($onload=false) {
        global $CFG;
        $this->footerStart();
        if ( $onload !== false ) {
            echo("\n".$onload."\n");
        }
        $this->footerEnd();
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
        if ( $CFG->analytics_key ) { ?>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', '<?= $CFG->analytics_key ?>']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        // ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';

        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();

    <?php
            if ( LTIX::sessionGet('key_key') ) {
                echo("_gaq.push(['_setCustomVar', 1, 'consumer_key', '".$_SESSION['lti']['key_key']."', 2]);\n");
            }
            if ( LTIX::sessionGet('context_id') ) {
                echo("_gaq.push(['_setCustomVar', 2, 'context_id', '".$_SESSION['lti']['context_id']."', 2]);\n");
            }
            if ( LTIX::sessionGet('context_title') ) {
                echo("_gaq.push(['_setCustomVar', 3, 'context_title', '".$_SESSION['lti']['context_title']."', 2]);\n");
            }
            echo("</script>\n");
        }  // if analytics is on...
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
            if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
                $url = $_SESSION['lti_post']['custom_done'];
            } else if ( isset($_GET["done"]) ) {
                $url = $_GET['done'];
            }
        }
        // If we have no where to go and nothing to do,
        if ( $url === false || strlen($url) < 1 ) return;

        $button = "btn-success";
        if ( $text == "Cancel" || $text == _m("Cancel") ) $button = "btn-warning";

        if ( $url == "_close" ) {
            echo("<a href=\"#\" onclick=\"window.close();\" class=\"btn ".$button."\">".$text."</a>\n");
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
        echo("<a href=\"#\" onclick=\"window.close();\" class=\"btn ".$button."\">".$text."</a>\n");
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

    function topNav() {
        global $CFG;
        $R = $CFG->wwwroot . '/';
    ?>
        <div class="container">
          <!-- Static navbar -->
          <div class="navbar navbar-default" role="navigation">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="<?= $R ?>index.php"><img style="width:4em;" src="<?= $CFG->staticroot . '/static/img/logos/tsugi-logo.png' ?>"></a>
            </div>
            <div class="navbar-collapse collapse">
              <ul class="nav navbar-nav">
                <?php if ( $CFG->DEVELOPER ) { ?>
                <li><a href="<?= $R ?>dev.php">Developer</a></li>
                <?php } ?>
                <?php if ( isset($_SESSION['id']) || $CFG->DEVELOPER ) { ?>
                <li><a href="<?= $R ?>admin/index.php">Admin</a></li>
                <?php } ?>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Links<b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="http://developers.imsglobal.org/" target="_blank">IMS LTI Documentation</a></li>
                    <li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Spec</a></li>
                    <li><a href="http://www.imsglobal.org/lti/ltiv2p0/ltiIMGv2p0.html" target="_new">IMS LTI 2.0 Spec</a></li>
                    <li><a href="https://vimeo.com/34168694" target="_new">IMS LTI Lecture</a></li>
                    <li><a href="http://www.oauth.net/" target="_blank">OAuth Documentation</a></li>
                  </ul>
                </li>
              </ul>
              <ul class="nav navbar-nav navbar-right">
                <li><a href="<?= $R ?>about.php">About</a></li>
                <?php if ( isset($_SESSION['id']) ) { ?>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?= $_SESSION['displayname'] ?><b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="<?= $R ?>profile.php">Profile</a></li>
                    <?php if ( $CFG->providekeys && $CFG->owneremail ) { ?>
                    <li><a href="<?= $R ?>admin/key/index.php">Use this service</a></li>
                    <?php } ?>
                    <li><a href="<?= $R ?>logout.php">Logout</a></li>
                  </ul>
                </li>
                <?php } else { ?>
                <li><a href="<?= $R ?>login.php">Login</a></li>
                <?php } ?>
              </ul>
            </div><!--/.nav-collapse -->
          </div>
    <?php
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
        return $CFG->staticroot . '/static/img/spinner.gif';
    }

    /**
      * Get a fully-qualified URL for the default icon image.
      */
    function getDefaultIcon() {
        global $CFG;
        return $CFG->staticroot . '/static/img/spinner.gif';
    }

    /**
     * Redirect to a local URL, adding session if necessary
     *
     * Note that this is only needed for AJAX and header() calls
     * as &lt;form> and &lt;a href tags are properly handled already
     * by the PHP built-in "don't use cookies for session" support.
     */
    function doRedirect($location) {
        if ( headers_sent() ) {
            echo('<a href="'.htmlentities($location).'">Continue</a>'."\n");
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
    public static function getLocalStatic($file) {
        global $CFG;
        $path = $CFG->getPwd($file);
        return $CFG->staticroot . "/" . $path;
    }

    // http://stackoverflow.com/questions/49547/making-sure-a-web-page-is-not-cached-across-all-browsers
    // http://www.php.net/manual/en/public static function.header.php
    public static function noCacheHeader() {
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
        header('Pragma: no-cache'); // HTTP 1.0.
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past - proxies
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
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode(array("error" => $message, "detail" => $detail)));
    }

    public static function jsonOutput($json_data) {
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($json_data));
    }

    // No Buffering
    public static function noBuffer() {
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
    }

}
