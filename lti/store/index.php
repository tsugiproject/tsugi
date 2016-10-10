<?php
require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

$local_path = route_get_local_path(__DIR__);
if ( $local_path == "canvas-config.xml" ) {
    require_once("canvas-config-xml.php");
    return;
}
if ( $local_path == "casa.json" ) {
    require_once("casa-json.php");
    return;
}

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\UI\Lessons;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$result_url = LTIX::ltiLinkUrl();

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

// Load Lessons Data
$l = false;
$assignments = false;
if ( isset($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
    $contents = true;
    foreach($l->lessons->modules as $module) {
        if ( isset($module->lti) ) {
            $assignments = true;
        }
    }
}

// Load Tool Registrations
$registrations = findAllRegistrations();
if ( count($registrations) < 1 ) $registrations = false;

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

if ( ! $USER->instructor ) {
    echo("<p>This tool must be launched by the instructor</p>");
    $OUTPUT->footer();
    exit();
}

if ( ! $result_url ) {
    echo("<p>This tool must be with LTI Link Content Item support</p>");
    $OUTPUT->footer();
    exit();
}

if ( ! ( $assignments || $contents || $registrations ) ) {
    echo("<p>No tools, content, or assignments found.</p>");
    $OUTPUT->footer();
    exit();
}

// Handle the tool install
if ( isset($_GET['install']) ) {
    $install = $_GET['install'];
    if ( ! isset($registrations[$install])) {
        echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
        $OUTPUT->footer();
        exit();
    }
    $tool = $registrations[$install];

    $title = $tool['name'];
    $text = $tool['description'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->staticroot.'/font-awesome-4.4.0/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    if ( $fa_icon ) {
        echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
    }
    echo('<center>');
    echo("<h1>".htmlent_utf8($title)."</h1>\n");
    echo("<p>".htmlent_utf8($text)."</p>\n");
    $script = isset($tool['script']) ? $tool['script'] : "index.php";
    $path = $tool['url'];

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
    $OUTPUT->footer();
    exit();
} 

// Handle the assignment install
if ( $l && isset($_GET['assignment']) ) {
    $lti = $l->getLtiByRlid($_GET['assignment']);
    if ( ! $lti ) {
        echo("<p>Assignment ".htmlentities($_GET['assignment'])." not found</p>\n");
        $OUTPUT->footer();
        exit();
    }

    $title = $lti->title;
    $path = $lti->launch;
    $path .= strpos($path,'?') === false ? '?' : '&';
    // Sigh - some LMSs don't handle custom - sigh
    $path .= 'inherit=' . urlencode($_GET['assignment']);
    $fa_icon = 'fa-check-square-o';
    $icon = $CFG->staticroot.'/font-awesome-4.4.0/png/'.str_replace('fa-','',$fa_icon).'.png';

    // Compute the custom values
    $custom = array();
    $custom['canvas_xapi_url'] = '$Canvas.xapi.url';
    if ( isset($lti->custom) ) {
        foreach($lti->custom as $entry) {
            if ( !isset($entry->key) ) continue;
            if ( isset($entry->json) ) {
                $value = json_encode($entry->json);
            } else if ( isset($entry->value) ) {
                $value = $entry->value;
            }
            $custom[$entry->key] = $value;
        }
    }

    // Title is for the href and text is for display
    $json = LTI::getLtiLinkJSON($path, $title, $title, $icon, $fa_icon, $custom);
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
    echo('<center>');
    if ( $fa_icon ) {
        echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
    }
    echo("<h1>".htmlent_utf8($title)."</h1>\n");
    $path = $lti->launch;
    echo($content);
    $OUTPUT->footer();
    exit();
} 

?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#box" data-toggle="tab" aria-expanded="true">Tools</a></li>
  <li class=""><a href="#content" data-toggle="tab" aria-expanded="false">Content</a></li>
  <li class=""><a href="#assignments" data-toggle="tab" aria-expanded="false">Assignments</a></li>
</ul>
<div id="myTabContent" class="tab-content">
  <div class="tab-pane fade active in" id="box">
<?php
$toolcount = 0;
foreach($registrations as $name => $tool ) {

    $title = $tool['name'];
    $text = $tool['description'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->staticroot.'/font-awesome-4.4.0/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    echo('<div style="border: 2px, solid, red;" class="card">');
    if ( $fa_icon ) {
        echo('<a href="index.php?install='.urlencode($name).'">');
        echo('<i class="fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
        echo('</a>');
    }
    echo('<p><strong>'.htmlent_utf8($title)."</strong></p>");
    echo('<p>'.htmlent_utf8($text)."</p>\n");
    echo('<center><a href="index.php?install='.urlencode($name).'" class="btn btn-default" role="button">Details</a></center>');
    echo("</div>\n");

    $toolcount++;
}
echo("</div>\n");

if ( $l ) {
?>
  <div class="tab-pane fade in" id="content">
<?php
        echo('<h1>'.$l->lessons->title."</h1>\n");
foreach($l->lessons->modules as $module) {
echo($module->anchor);
}
?>
  </div>
  <div class="tab-pane fade in" id="assignments">
<?php
echo('<h1>'.$l->lessons->title."</h1>\n");
echo("<ul>\n");
foreach($l->lessons->modules as $module) {
    if ( isset($module->lti) ) {
        foreach($module->lti as $lti) {
             echo('<li><a href="index.php?assignment='.$lti->resource_link_id.'">'.htmlentities($lti->title).'</a>');
             echo("</li>\n");
        }
        }
}
echo("</ul>\n");
echo("<pre>\n");
foreach($l->lessons->modules as $module) {
if ( isset($module->lti) ) {
echo($module->anchor."\n");
foreach($module->lti as $lti) {
print_r($lti);
}
}
}
echo("</pre>\n");
?>
  </div>
<?php
}
echo("</div>\n");

if ( $toolcount < 1 ) {
    lmsDie("No tools to register..");
}

$OUTPUT->footerStart();
// https://github.com/LinZap/jquery.waterfall
?>
<script type="text/javascript" src="<?= $CFG->staticroot ?>/js/waterfall-light.js"></script>
<script>
$(function(){
    $('#box').waterfall({refresh: 0})
});
</script>
<?php
$OUTPUT->footerend();
