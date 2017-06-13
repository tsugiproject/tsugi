<?php

namespace Tsugi\UI;

use Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

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
        global $HEAD_CONTENT_SENT, $CFG, $RUNNING_IN_TOOL;
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
        <!-- Le styles -->
        <link href="<?= $CFG->staticroot ?>/bootstrap-3.1.1/css/<?php
            if ( isset($CFG->bootswatch) ) echo('bootswatch/'.$CFG->bootswatch.'/'); ?>bootstrap.min.css" rel="stylesheet">

        <link href="<?= $CFG->staticroot ?>/js/jquery-ui-1.11.4/jquery-ui.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/font-awesome-4.4.0/css/font-awesome.min.css" rel="stylesheet">
        <link href="<?= $CFG->staticroot ?>/css/tsugi.css" rel="stylesheet">
        <script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
        <script>
        var _TSUGI = {
            spinnerUrl: "<?= self::getSpinnerUrl() ?>"
        }
        </script>

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
    // If we are in an iframe use different margins
        ob_start();
?>
</head>
<body prefix="oer: http://oerschema.org">
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

        $HEAD_CONTENT_SENT = true;

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $this->buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * templateInclude - Include a handlebars template, dealing with i18n
     *
     * This is a normal handlebars template except we can ask for a translation
     * of text as follows:
     *
     *    ...
     *    {{__ 'Hello world' }}
     *    ...
     *
     * The i18n replacement will be handled in the server in the template.  Single
     * or Double quotes can be used.
     */
    function templateInclude($name) {
        if ( is_array($name) ) {
            foreach($name as $n) {
                self::templateInclude($n);
            }
            return;
        }
        echo('<script id="template-'.$name.'" type="text/x-handlebars-template">'."\n");
        $template = file_get_contents('templates/'.$name.'.hbs');
        $new = preg_replace_callback(
            '|{{__ *[\'"]([^\'"]*)[\'"].*?}}|',
            function ($matches) {
                return __(htmlent_utf8(trim($matches[1])));
            },
            $template
        );
        echo($new);
        echo("</script>\n");
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

        if ( $CFG->google_translate ) {
    ?>
<div id="google_translate_element" style="position: fixed; right: 1em; bottom: 0.25em;"></div><script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.SIMPLE
<?php
    if ( $CFG->universal_analytics ) {
        echo(', gaTrack: true, gaId: "'.$CFG->universal_analytics.'"'."\n");
    }
?>
    }, "google_translate_element");
}
</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<?php

        }

        if ( $this->session_get('APP_FOOTER') ) echo($this->session_get('APP_FOOTER'));

        $this->doAnalytics();

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
        $retval['lti'] = LTIX::wrapped_session_get($session_object, 'lti');
        // $retval['lti'] = false;
        $retval['sessionlifetime'] = $CFG->sessionlifetime;
        echo(json_encode($retval));
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
        $set->setHome('Done', 'javascript:window.close();');
        return $set;
    }

    function defaultMenuSet() {
        global $CFG;
        $R = $CFG->wwwroot . '/';
        $set = new \Tsugi\UI\MenuSet();
        $set->setHome($CFG->servicename, $CFG->apphome);

        if ( $CFG->DEVELOPER ) {
            $set->addLeft('Developer', $R.'dev');
        }
        if ( $this->session_get('id') || $CFG->DEVELOPER ) {
            $set->addLeft('Admin', $R.'admin/index.php');
        }

        $submenu = new \Tsugi\UI\Menu();
        $submenu->addLink('IMS LTI 1.1 Spec', 'http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html')
            ->addLink('IMS LTI 2.0 Spec', 'http://www.imsglobal.org/lti/ltiv2p0/ltiIMGv2p0.html')
            ->addLink('Tsugi Project Site', 'https://www.tsugi.org/');

        $set->addLeft('Links', $submenu);

        if ( $this->session_get('id') ) {
            $submenu = new \Tsugi\UI\Menu();
            $submenu->addLink('Profile', $R.'profile')
                ->addLink('Use this Service', $R . 'admin/key/index.php')
                ->addLink('Logout', $R.'logout');
            $set->addRight(htmlentities($this->session_get('displayname', '')), $submenu);
        } else {
            $set->addRight('Login', $R.'login');
        }

        $set->addRight('<img style="width:4em;" src="'. $CFG->staticroot . '/img/logos/tsugi-logo.png' .'">', $R.'about');
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
        global $CFG;
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        $launch_return_url = LTIX::ltiRawParameter('launch_presentation_return_url', false);
        // Canvas test
        $product = LTIX::ltiRawParameter('tool_consumer_info_product_family_code', false);
        $tci_description = LTIX::ltiRawParameter('tool_consumer_instance_description', false);
        if ( $product == 'ims' && $tci_description == 'Coursera') $product = 'coursera';

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
        } else if ( $product == 'coursera' ) {
            $menu_set = self::closeMenuSet();
        // Since canvas does not set launch_target properly
        } else if ( $launch_target !== false && ($product == 'canvas' || $product == 'coursera')) {
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
        global $CFG;

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
        $retval .= "<script>\n";
        $retval .= "if ( ! inIframe() ) {\n";
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
        // For now just use wwwroot to be safe
        // return $CFG->staticroot . "/" . $path;
        return $CFG->wwwroot . "/" . $path;
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

    // No Buffering
    public static function noBuffer() {
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
    }

}
