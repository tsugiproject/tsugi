<?php
require_once "../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

// Model
$p = $CFG->dbprefix;
require_once("content_json_messages.php");

$result_url = LTIX::ltiLinkUrl();

// Scan the tools folders for registration settings
$tools = array();
if ( $USER->instructor && $result_url ) {
    $tools = findFiles("register.php","../");
}

// Handle the POST
// TBD

$OUTPUT->header();
?>
<style>
    .card {
        border: 1px solid black;
        margin: 5px;
        padding: 5px;
    }
#loader {
      position: fixed;
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      background-color: white;
      margin: 0;
      z-index: -100;
}
</style>
<?php
$OUTPUT->bodyStart();
?>
<!--
<div id="loader">
<center><img src="<?= $OUTPUT->getSpinnerUrl(); ?>" style="margin-top: 100px; width: 30px;"></center>
</div>
-->
<?php
$OUTPUT->flashMessages();

if ( ! $USER->instructor ) {
    echo("<p>This tool must be launched by the instructor</p>");
    $OUTPUT->footer();
    exit();
}

if ( ! $result_url ) {
    echo("<p>This tool must be with LTI Link Content Item support</p>");
    exit();
}

if ( count($tools) < 1 ) {
    error_log("No tools available");
    echo("<p>Not tools avaiable for registration.</p>");
    exit();
}

if ( isset($_GET['install']) ) {
    $install = $_GET['install'];
    echo("<h1>$install</h1>\n");
}

echo '<div id="box">'."\n";
$toolcount = 0;
foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    // echo("Checking $path ...<br/>\n");
    unset($REGISTER_LTI2);
    require($tool);
    if ( isset($REGISTER_LTI2) && is_array($REGISTER_LTI2) ) {
        if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) && 
            isset($REGISTER_LTI2['description']) ) {
        } else {
            lmsDie("Missing required name, short_name, and description in ".$tool);
        }

        $script = isset($REGISTER_LTI2['script']) ? $REGISTER_LTI2['script'] : "index.php";
        $icon = isset($REGISTER_LTI2['FontAwesome']) ? $REGISTER_LTI2['FontAwesome'] : false;

        $path = $CFG->wwwroot . '/' . str_replace("register.php", $script, $path);
        echo('<div style="border: 2px, solid, red;" class="card">');
        if ( $icon ) {
            echo('<i class="fa '.$icon.' fa-2x" style="float:right; margin: 2px"></i>');
        }
        echo('<strong>'.htmlent_utf8($REGISTER_LTI2['name'])."</strong><br>");
        // echo($path."<br/>\n");
        // echo($tool."<br/>\n");
        echo(htmlent_utf8($REGISTER_LTI2['description'])."<br/>\n");
        echo('<a href="store.php?install='.urlencode($tool).'" class="btn btn-info" role="button">Install Tool</a>');
        echo("</div>\n");
        $toolcount++;
    }
}
echo("</div>\n");

if ( $toolcount < 1 ) {
    lmsDie("No tools to register..");
}

$OUTPUT->footerStart();
// https://github.com/LinZap/jquery.waterfall
?>
<script type="text/javascript" src="static/waterfall-light.js"></script>
<script>
$(function(){
    $('#box').waterfall({refresh: 0})
});
</script>
<?php
$OUTPUT->footerend();
exit();



$content_url = isset($_REQUEST['content_url']) ? $_REQUEST['content_url'] : preg_replace("/json.*$/","tool.php?sakai=98765",curPageUrl());

$oauth_consumer_key = isset($_REQUEST['key']) ? $_REQUEST['key'] : $_SESSION['oauth_consumer_key'];
$oauth_consumer_secret = isset($_REQUEST['secret']) ? $_REQUEST['secret'] : 'secret';
$title = isset($_REQUEST['title']) ? $_REQUEST['title'] : "The Awesome Sakaiger Title";
$text = isset($_REQUEST['text']) ? $_REQUEST['text'] : "The Awesome Sakaiger Text";
$data = isset($_REQUEST['data']) ? $_REQUEST['data'] : "";

if (strlen($oauth_consumer_secret) < 1 || strlen($oauth_consumer_key) < 1 
    || strlen($result_url) < 1 ) {
    var_dump($_SESSION);
    die("Must have url, reg_password and reg_key in sesison or as GET parameters");
}

if ( isset($_REQUEST['send']) ) {
    $parms = array();
    $parms["lti_message_type"] = "ContentItemSelection";
    $parms["lti_version"] = "LTI-1p0";
    if ( isset($_REQUEST['data']) ) {
        $parms["data"] = $_REQUEST['data'];
    }
    $json = getLtiLinkJSON($content_url);
    $json->{'@graph'}[0]->{'title'} = $title;
    $json->{'@graph'}[0]->{'text'} = $text;
    $retval = json_encode($json);
    $parms["content_items"] = $retval;

    $parms = signParameters($parms, $result_url, "POST", 
        $oauth_consumer_key, $oauth_consumer_secret,
        "Finish Content Return");

    $content = postLaunchHTML($parms, $result_url, true);
    echo($content);
    return;
}

