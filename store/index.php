<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";

session_start();

LTIX::getConnection();

$p = $CFG->dbprefix;

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

$registrations = findAllRegistrations();
if ( count($registrations) < 1 ) $registrations = false;

$sql = "SELECT count(key_id) AS count
        FROM {$CFG->dbprefix}lti_key
        WHERE user_id = :UID";
$key_count = 0;
if ( U::get($_SESSION, 'id') ) {
    $row = $PDOX->rowDie($sql, array(':UID' => $_SESSION['id']));
    $key_count = U::get($row, 'count', 0);
}

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

if ( ! U::get($_SESSION,'id') ) {
    echo("<p>You must log in to use these tools in your learning management system or Google Classroom.</p>\n");
} else if ( ! U::get($_SESSION,'gc_courses') && $key_count < 1 && 
    ( isset($CFG->providekeys) || isset($CFG->google_classroom_secret) ) ) {
    echo("<p>You need to ");
    if ( $CFG->providekeys ) {
        echo('have an approved <a href="'.$CFG->wwwroot.'/settings">LTI key</a>');
        if ( $CFG->google_classroom_secret ) {
            echo(" or\n");
        }
    }
    if ( $CFG->google_classroom_secret ) {
        echo('log in to <a href="'.$CFG->wwwroot.'/gclass/login">Google Classroom</a>');
    }
    echo(" to use these tools.\n");
}

// Render the tools in the site
    echo('<div id="box">'."\n");

    $count = 0;
    foreach($registrations as $name => $tool ) {

        $title = $tool['name'];
        $text = $tool['description'];
        $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
        $icon = false;
        if ( $fa_icon !== false ) {
            $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
        }

        echo('<div style="border: 2px, solid, red;" class="card">');
        if ( $fa_icon ) {
            echo('<a href="details/'.urlencode($name).'">');
            echo('<i class="fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
            echo('</a>');
        }
        echo('<p><strong>'.htmlent_utf8($title)."</strong></p>");
        echo('<p>'.htmlent_utf8($text)."</p>\n");
        echo('<center><a href="details/'.urlencode($name).'" class="btn btn-default" role="button">Details</a> ');

        $ltiurl = $tool['url'];
        if ( isset($_SESSION['gc_courses']) ) {
            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
            echo('" title="Install in Classroom" target="iframe-frame"'."\n");
            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
            echo('<img height=16 width=16 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
        }
        echo('</center>');
        echo("</div>\n");

        $count++;
    }
    if ( $count < 1 ) {
        echo("<p>No available tools</p>\n");
    }

echo("</div>\n");
// echo("<pre>\n");print_r($tool);echo("</pre>\n");

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
