<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";
require_once "../settings/settings_util.php";

session_start();

$_SESSION['login_return'] = $CFG->wwwroot . '/store';

LTIX::getConnection();

$p = $CFG->dbprefix;

$OUTPUT->header();
?>
<style>
    .panel-default {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    }
    .panel-default:hover{
        border: 1px solid #aaa;
    }
    .approw {
        margin: 0;
    }
    .appcolumn {
        padding: 0 4px;
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

$registrations = findAllRegistrations(false, true);
if ( count($registrations) < 1 ) $registrations = false;

$key_count = settings_key_count();

$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<?php

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

// Handle the tool install
if ( isset($_GET['install']) ) {
    $install = $_GET['install'];
    if ( ! isset($registrations[$install])) {
        echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
        $OUTPUT->footer();
        return;
    }
    $tool = $registrations[$install];

    $title = $tool['name'];
    $text = $tool['description'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    if ( $fa_icon ) {
        echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
    }
    echo('<center>');
    echo("<h1>".htmlent_utf8($title)."</h1>\n");
    echo("<p>".htmlent_utf8($text)."</p>\n");
    $script = isset($tool['script']) ? $tool['script'] : "index";
    $path = $tool['url'];

    // Set up to send the response
    $retval = new ContentItem();
    $points = false;
    $activity_id = false;
    if ( isset($tool['messages']) && is_array($tool['messages']) &&
        array_search('launch_grade', $tool['messages']) !== false ) {
        $points = 10;
        $activity_id = $install;
    }
    $custom = false;
    $retval->addLtiLinkItem($path, $title, $title, $icon, $fa_icon, $custom, $points, $activity_id);
    $endform = '<a href="." class="btn btn-warning">Back to Store</a>';
    $content = $retval->prepareResponse($endform);
    echo($content);
    echo("</center>\n");
    // echo("<pre>\n");print_r($tool);echo("</pre>\n");
    $OUTPUT->footer();
    return;
} 

// Tell them what is going on...
echo(settings_status($key_count));

// Render the tools in the site
    echo('<div id="box">'."\n");

    $count = 0;
    foreach($registrations as $name => $tool ) {

        // This will keep the rows nice
        if ($count % 3 == 0) {
            if ($count > 0) {
                echo('</div>');
            }
            echo('<div class="row approw">');
        }

        $title = $tool['name'];
        $text = $tool['description'];
        $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
        $icon = false;
        if ( $fa_icon !== false ) {
            $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
        }

        echo('<div class="col-sm-4 appcolumn">
        <div class="panel panel-default">
        <div class="panel-body">');
        if ( $fa_icon ) {
            echo('<a href="details/'.urlencode($name).'">');
            echo('<i class="fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
            echo('</a>');
        }
        echo('<h3 style="margin-top:.5em;">'.htmlent_utf8($title)."</h3>");
        echo('<p>'.htmlent_utf8($text)."</p>\n
            </div><div class=\"panel-footer\">");
        echo('<a href="details/'.urlencode($name).'" class="btn btn-default" role="button">Details</a> ');

        $ltiurl = $tool['url'];
        if ( isset($_SESSION['gc_count']) ) {
            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
            echo('" title="Install in Classroom" target="iframe-frame"'."\n");
            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" class=\"pull-right\">\n");
            echo('<img height=32 width=32 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
        }
        echo("</div></div></div>\n");

        $count++;
    }
    if ( $count < 1 ) {
        echo("<p>No available tools</p>\n");
    } else {
        echo("</div>");
    }

echo("</div>\n");
// echo("<pre>\n");print_r($tool);echo("</pre>\n");

$OUTPUT->footerStart();
$OUTPUT->footerend();
