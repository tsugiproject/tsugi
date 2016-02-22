<?php
require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

$local_path = route_get_local_path(__DIR__);
if ( $local_path == "canvas-config.xml" ) {
    require_once("canvas-config-xml.php");
    return;
}

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$result_url = LTIX::ltiLinkUrl();

// Scan the tools folders for registration settings
$tools = array();
if ( $USER->instructor && $result_url ) {
    $tools = findFiles("register.php","../../");
}

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
      z-index: 100;
}
#XbasicltiDebugToggle {
    display: none;
}
</style>
<?php
$OUTPUT->bodyStart();
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

$install = false;
if ( isset($_GET['install']) ) {
    $install = $_GET['install'];
}

if ( $install ) {
    echo('<div id="install">');
} else {
    echo '<div id="box">'."\n";
}
$toolcount = 0;
foreach($tools as $tool ) {

    if ( strpos($tool, '/store/') !== false ) continue;

    if ( $install && $install != $tool ) continue;

    $path = str_replace("../","",$tool);
    // echo("Checking $path ...<br/>\n");
    unset($REGISTER_LTI2);
    require($tool);
    if ( ! isset($REGISTER_LTI2) ) continue;
    if ( ! is_array($REGISTER_LTI2) ) continue;

    if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) && 
        isset($REGISTER_LTI2['description']) ) {
        // We are happy
    } else {
        lmsDie("Missing required name, short_name, and description in ".$tool);
    }

    $title = $REGISTER_LTI2['name'];
    $text = $REGISTER_LTI2['description'];
    $fa_icon = isset($REGISTER_LTI2['FontAwesome']) ? $REGISTER_LTI2['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->staticroot.'/static/font-awesome-4.4.0/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    if ( $install ) {
        if ( $fa_icon ) {
            echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
        }
        echo('<center>');
        echo("<h1>".htmlent_utf8($title)."</h1>\n");
        echo("<p>".htmlent_utf8($text)."</p>\n");
        $script = isset($REGISTER_LTI2['script']) ? $REGISTER_LTI2['script'] : "index.php";
        $path = $CFG->wwwroot . '/' . str_replace("register.php", $script, $path);

        // Title is for the href and text is for display
        $json = LTI::getLtiLinkJSON($path, $title, $title, $icon, $fa_icon);
        $retval = json_encode($json);

        $parms = array();
        $parms["lti_message_type"] = "ContentItemSelection";
        $parms["lti_version"] = "LTI-1p0";
        $parms["content_items"] = $retval;
        $data = LTIX::postGet('data');
        if ( $data ) $parms['data'] = $data;
    
        $parms = LTIX::signParameters($parms, $result_url, "POST", "Install Tool");
        $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
        $content = LTI::postLaunchHTML($parms, $result_url, true, false, $endform);
        echo($content);
    } else {
        echo('<div style="border: 2px, solid, red;" class="card">');
        if ( $fa_icon ) {
            echo('<a href="index.php?install='.urlencode($tool).'">');
            echo('<i class="fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
            echo('</a>');
        }
        echo('<p><strong>'.htmlent_utf8($title)."</strong></p>");
        echo('<p>'.htmlent_utf8($text)."</p>\n");
        echo('<center><a href="index.php?install='.urlencode($tool).'" class="btn btn-default" role="button">Details</a></center>');
        echo("</div>\n");
    }
    $toolcount++;
}
echo("</div>\n");

if ( $toolcount < 1 ) {
    lmsDie("No tools to register..");
}

$OUTPUT->footerStart();
// https://github.com/LinZap/jquery.waterfall
if ( ! $install ) {
?>
<script type="text/javascript" src="static/waterfall-light.js"></script>
<script>
$(function(){
    $('#box').waterfall({refresh: 0})
});
</script>
<?php
}
$OUTPUT->footerend();
