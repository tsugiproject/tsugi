<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../admin/admin_util.php";

session_start();

$p = $CFG->dbprefix;

$OUTPUT->header();
?>
<link rel="stylesheet" type="text/css"
    href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css"/>
<?php

$registrations = findAllRegistrations();
if ( count($registrations) < 1 ) $registrations = false;

$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<div id="image-dialog" title="Image Dialog" style="display: none;">
    <img src="<?= $OUTPUT->getSpinnerUrl() ?>" style="width:100%" id="popup-image">
</div>
<?php

$rest_path = U::rest_path();
$install = $rest_path->extra;

    if ( ! isset($registrations[$install])) {
        echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
        $OUTPUT->footer();
        return;
    }
    $tool = $registrations[$install];

    $title = $tool['name'];
    $text = $tool['description'];
    $ltiurl = $tool['url'];
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

    echo('<a href="'.$rest_path->parent.'" class="btn btn-default" role="button">Back to Store</a>');
    echo(' ');
    if ( isset($_SESSION['gc_courses']) ) {
            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
            echo('" title="Install in Classroom" target="iframe-frame"'."\n");
            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
            echo('<img height=32 width=32 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
    }
    echo(' ');
    echo('<a href="'.$rest_path->parent.'/test/'.urlencode($install).'" class="btn btn-default" role="button">Test</a> ');

    $screen_shots = U::get($tool, 'screen_shots');
    if ( is_array($screen_shots) && count($screen_shots) > 0 ) {
        echo("<hr/>\n");
        echo('<div class="bxslider">');
        foreach($screen_shots as $screen_shot ) {
            echo('<div><img onclick="$(\'#popup-image\').attr(\'src\',this.src);showModal(this.title,\'image-dialog\');" src="'.$screen_shot.'"></div>'."\n");
        }
    }

    echo("</center>\n");
    echo("<!-- \n");print_r($tool);echo("\n-->\n");
    $OUTPUT->footerStart();
?>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.js">
</script>
<script>
$(document).ready(function() {
    $('.bxslider').bxSlider({
        useCSS: false,
        adaptiveHeight: false,
        slideWidth: "240px",
        infiniteLoop: false,
        maxSlides: 3
    });
});
</script>
<?php
    $OUTPUT->footerEnd();
