<?php
require_once "../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

if ( ! $USER->instructor ) {
    echo("<p>This tool must be launched by the instructor</p>");
    $OUTPUT->footer();
    exit();
}

// See https://canvas.instructure.com/doc/api/file.link_selection_tools.html

// Needed return values
$content_return_types = LTIX::ltiRawParameter("ext_content_return_types",false);
$content_return_url = LTIX::ltiRawParameter("ext_content_return_url",false);
if ( strlen($content_return_url) < 1 ) {
    lmsDie("Missing ext_content_return_url");
}
if ( strpos($content_return_types, "lti_launch_url") === false ) {
    lmsDie("This tool requires ext_content_return_types=lti_launch_url");
}

// Scan the tools folders for registration settings
$tools = findFiles("register.php","../");
if ( count($tools) < 1 ) {
    lmsDie("No register.php files found...<br/>\n");
}

echo"<ul>\n";
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

        $path = "/".str_replace("register.php", $script, $path);
        $url = $content_return_url;
        $url .= strpos($url,'?') ? '&' : '?';
        $url .= 'return_type=lti_launch_url&url=';
        $url .= urlencode($CFG->wwwroot . $path);
        $url .= "&title=".urlencode($REGISTER_LTI2['name']);
        $url .= "&text=".urlencode($REGISTER_LTI2['name']);
        $url .= "&description=".urlencode($REGISTER_LTI2['description']);
        echo('<li><a href="'.$url);
        echo('">Select Tool: '.htmlent_utf8($REGISTER_LTI2['name'])."</a><br/>");
        echo(htmlent_utf8($REGISTER_LTI2['description'])."</li>\n");
        $toolcount++;
    }
}
echo("</ul>\n");

if ( $toolcount < 1 ) {
    lmsDie("No tools to register..");
}

