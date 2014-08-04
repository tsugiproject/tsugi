<?php

namespace Tsugi\Core;

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
 *     $LTI = \Tsugi\Core\LTIX::requireData(array('context_id', 'role'));

 *     // Handle incoming POST data and redirect as necessary...
 *     if ( ... ) {
 *         header( 'Location: '.addSession('index.php') ) ;
 *     }
 *
 *     // Done with POST
 *     $OUTPUT->header();
 *     $OUTPUT->startBody();
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
class Output {

    function flashMessages() {
        if ( isset($_SESSION['error']) ) {
            echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
            unset($_SESSION['error']);
        }
        if ( isset($_SESSION['success']) ) {
            echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
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
        <title><?php echo($CFG->servicename); ?></title>
        <!-- Le styles -->
        <link href="<?php echo($CFG->staticroot); ?>/static/css/custom-theme/jquery-ui-1.10.0.custom.css" rel="stylesheet">
        <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap-3.1.1/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo($CFG->staticroot); ?>/static/bootstrap-3.1.1/css/bootstrap-theme.min.css" rel="stylesheet">

    <style> <!-- from navbar.css -->
    body {
      padding-top: 20px;
      padding-bottom: 20px;
    }

    .navbar {
      margin-bottom: 20px;
    }
    </style>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="<?php echo($CFG->wwwroot); ?>/static/html5shiv/html5shiv.js"></script>
          <script src="<?php echo($CFG->wwwroot); ?>/static/respond/respond.min.js"></script>
        <![endif]-->

    <?php
        if ( isset($_SESSION['CSRF_TOKEN']) ) {
            echo('<script type="text/javascript">CSRF_TOKEN = "'.$_SESSION['CSRF_TOKEN'].'";</script>'."\n");
        } else {
            echo('<script type="text/javascript">CSRF_TOKEN = "TODORemoveThis";</script>'."\n");
        }
        $HEAD_CONTENT_SENT = true;
    }

    function bodyStart() {
        echo("\n</head>\n<body style=\"padding: 15px 15px 15px 15px;\">\n");
        if ( count($_POST) > 0 ) {
            $dump = safe_var_dump($_POST);
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
        echo('<script src="'.$CFG->staticroot.'/static/js/jquery-1.10.2.min.js"></script>'."\n");
        echo('<script src="'.$CFG->staticroot.'/static/bootstrap-3.1.1/js/bootstrap.min.js"></script>'."\n");

        // Serve this locally during early development - Move to CDN when stable
        echo('<script src="'.$CFG->wwwroot.'/static/js/tsugiscripts.js"></script>'."\n");

        if ( isset($CFG->sessionlifetime) ) {
            $heartbeat = ( $CFG->sessionlifetime * 1000) / 2;
            // $heartbeat = 10000;
    ?>
    <script type="text/javascript">
    HEARTBEAT_URL = '<?php echo(addSession($CFG->wwwroot.'/core/util/heartbeat.php')); ?>';
    HEARTBEAT_INTERVAL = setInterval(doHeartBeat, <?php echo($heartbeat); ?>);
    </script>
    <?php
        }

        $this->doAnalytics();
    }

    function footerEnd() {
        echo("\n</body>\n</html>\n");
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
        if ( $CFG->analytics_key ) { ?>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', '<?php echo($CFG->analytics_key); ?>']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        // ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';

        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();

    <?php
            if ( isset($_SESSION) && isset($_SESSION['lti']) ) {
                if ( isset($_SESSION['lti']['key_key']) ) {
                    echo("_gaq.push(['_setCustomVar', 1, 'consumer_key', '".$_SESSION['lti']['key_key']."', 2]);\n");
                }
                if ( isset($_SESSION['lti']['context_id']) ) {
                    echo("_gaq.push(['_setCustomVar', 2, 'context_id', '".$_SESSION['lti']['context_id']."', 2]);\n");
                }
                if ( isset($_SESSION['lti']['context_title']) ) {
                    echo("_gaq.push(['_setCustomVar', 3, 'context_title', '".$_SESSION['lti']['context_title']."', 2]);\n");
                }
            }
            echo("</script>\n");
        }  // if analytics is on...
    }

    function doneButton() {
        $url = false;
        if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
            $url = $_SESSION['lti_post']['custom_done'];
        } else if ( isset($_GET["done"]) ) {
            $url = $_GET['done'];
        }
        if ( $url === false ) return;

        if ( $url == "_close" ) {
            echo("<button onclick=\"window.close();\" type=\"button\">Done</button>\n");
        } else if ( strpos($url, "http") !== false ) {
            echo("<button onclick=\"window.location='$url';\" type=\"button\">Done</button>\n");
        } else {
            echo("<button onclick=\"window.location='".addSession($url)."';\" type=\"button\">Done</button>\n");
        }
    }

    function doneBootstrap($text="Done") {
        $url = false;
        if ( isset($_SESSION['lti_post']) && isset($_SESSION['lti_post']['custom_done']) ) {
            $url = $_SESSION['lti_post']['custom_done'];
        } else if ( isset($_GET["done"]) ) {
            $url = $_GET['done'];
        }
        if ( $url === false ) return;

        $button = "btn-success";
        if ( $text == "Cancel" ) $button = "btn-warning";

        if ( $url == "_close" ) {
            echo("<a href=\"#\" onclick=\"window.close();\" class=\"btn ".$button."\">".$text."</a>\n");
        } else {
            echo("<a href==\"$url\"  class=\"btn ".$button."\">".$text."</button>\n");
        }
    }

    function togglePre($title, $html) {
        global $div_id;
        $div_id = $div_id + 1;
        echo('<strong>'.htmlpre_utf8($title));
        echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">Toggle</a>)</strong>'."\n");
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
              <a class="navbar-brand" href="index.php">TSUGI</a>
            </div>
            <div class="navbar-collapse collapse">
              <ul class="nav navbar-nav">
                <?php if ( $CFG->DEVELOPER ) { ?>
                <li><a href="<?php echo($R); ?>dev.php">Developer</a></li>
                <?php } ?>
                <?php if ( isset($_SESSION['id']) || $CFG->DEVELOPER ) { ?>
                <li><a href="<?php echo($R); ?>admin/index.php">Admin</a></li>
                <?php } ?>

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown">Links<b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="http://developers.imsglobal.org/" target="_blank">IMS LTI Documentation</a></li>
                    <li><a href="http://www.imsglobal.org/LTI/v1p1p1/ltiIMGv1p1p1.html" target="_new">IMS LTI 1.1 Spec</a></li>
                    <li><a href="https://vimeo.com/34168694" target="_new">IMS LTI Lecture</a></li>
                    <li><a href="http://www.oauth.net/" target="_blank">OAuth Documentation</a></li>
                  </ul>
                </li>
              </ul>
              <ul class="nav navbar-nav navbar-right">
                <li><a href="<?php echo($R); ?>about.php">About</a></li>
                <?php if ( isset($_SESSION['id']) ) { ?>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo($_SESSION['displayname']);?><b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="<?php echo($R); ?>profile.php">Profile</a></li>
                    <?php if ( $CFG->providekeys && $CFG->owneremail ) { ?>
                    <li><a href="<?php echo($R); ?>core/key/index.php">Use this service</a></li>
                    <?php } ?>
                    <li><a href="<?php echo($R); ?>logout.php">Logout</a></li>
                  </ul>
                </li>
                <?php } else { ?>
                <li><a href="<?php echo($R); ?>login.php">Login</a></li>
                <?php } ?>
              </ul>
            </div><!--/.nav-collapse -->
          </div>
    <?php
    }

    function getSpinnerUrl() {
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

}
