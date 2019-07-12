<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Util\Net;

require_once "../../config.php";
require_once "../../admin/admin_util.php";

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

$p = $CFG->dbprefix;

$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css"
          href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css"/>
    <style type="text/css">
        .row {
            margin: 0;
        }
        .keywords {
            font-size: .85em;
            font-style: italic;
            color: #666;
        }
        .keyword-span {
            text-transform: lowercase;
        }
    </style>
<?php

$registrations = findAllRegistrations(false, true);
if ( count($registrations) < 1 ) $registrations = false;

$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( ! ( $registrations ) ) {
    echo("<p>No tools found.</p>");
    $OUTPUT->footer();
    return;
}

$rest_path = U::rest_path();
$install = $rest_path->extra;

if ( ! isset($registrations[$install])) {
    echo("<p>Tool registration for ".htmlentities($install)." not found</p>\n");
    $OUTPUT->footer();
    return;
}
$tool = $registrations[$install];

$screen_shots = U::get($tool, 'screen_shots');
if ( !is_array($screen_shots) || count($screen_shots) < 1 ) $screen_shots = false;

$title = $tool['name'];
$text = $tool['description'];
$keywords = U::get($tool,'keywords');
$ltiurl = $tool['url'];
$fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
$icon = false;
if ( $fa_icon !== false ) {
    $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
}

// Check if the tsugi.php is upgraded for this tool
$register_json = $tool['url'].'register.json';
$json_str = Net::doGet($register_json);
$json_obj = json_decode($json_str);
$register_good = $json_obj && isset($json_obj->name);

// Start the output
?>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
        <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
                src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
    </div>
    <div id="image-dialog" title="Image Dialog" style="display: none;">
        <img src="<?= $OUTPUT->getSpinnerUrl() ?>" style="width:100%" id="popup-image">
    </div>
    <div id="url-dialog" title="URL Dialog" style="display: none;">
        <h1>Single Tool URLs</h1>
        <p>LTI 1.x Launch URL (Expects an LTI launch)<br/><?= $tool['url'] ?>
            (requires key and secret)
        </p>
        <?php if ( $register_good ) { ?>
            <p>Canvas Tool Configuration URL (XML)<br/>
                <a href="<?= $tool['url'] ?>canvas-config.xml" target="_blank"><?= $tool['url'] ?>canvas-config.xml</a></p>
            <p>Tsugi Registration URL (JSON)<br/>
                <a href="<?= $tool['url'] ?>register.json" target="_blank"><?= $tool['url'] ?>register.json</a></p>
        <?php } ?>
        <h1>Server-wide URLs</h1>
        <p>App Store (Supports IMS Deep Linking/Content Item)<br/>
            <?= $CFG->wwwroot ?>/lti/store
            (Requires key and secret)
        </p>
        <p>App Store Canvas Configuration URL<br/>
            <a href="<?= $CFG->wwwroot ?>/lti/store/canvas-config.xml" target="_blank"><?= $CFG->wwwroot ?>/lti/store/canvas-config.xml</a></p>
    </div>
<?php

if ( $fa_icon ) {
    echo('<span class="hidden-xs fa '.$fa_icon.' fa-2x" style="color: var(--primary); float:right; margin: 2px"></span>');
}
echo("<b>".htmlent_utf8($title)."</b>\n");
if (is_array($keywords)) {
    sort($keywords);
    echo('<p class="keywords">Tags: <span class="keyword-span">'.implode(", ", $keywords).'</span></p>');
}
echo('<p class="hidden-xs">'.htmlent_utf8($text)."</p>\n");

echo("<p class=\"tool-options\">\n");
echo('<a href="../index.php" class="btn btn-warning" role="button">Back to Store</a>');
echo(' ');
echo('<a href="#" class="btn btn-default" role="button" onclick="showModal(\'Tool URLs\',\'url-dialog\');">Tool URLs</a>');
echo(' ');
echo('<a href="'.$rest_path->parent.'/test/'.urlencode($install).'" class="btn btn-default" role="button">Test</a> ');
echo(' ');
echo('<button type="button" class="btn btn-success" role="button" data-toggle="modal" data-target="#'.urlencode($install).'_modal"><span class="fa fa-plus" aria-hidden="true"></span> Install</button>');
echo("</p>\n");

?>
    <div id="<?=urlencode($install)?>_modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span class="fa fa-times" aria-hidden="true"></span><span class="sr-only">Cancel</span></button>
                    <h4 class="modal-title">Install Learning App</h4>
                </div>
                <div class="modal-body">
                    <form method="get" action="../index.php">
                        <input type="hidden" name="install" value="<?=urlencode($install)?>">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" class="form-control" name="title" value="<?=htmlent_utf8($title)?>">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" rows="5" name="description"><?=htmlent_utf8($text)?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php

if ( $screen_shots ) {
    echo('<div class="row">'."\n");
    echo('<div class="col-sm-4">'."\n");
}

echo("<ul>\n");
if ( isset($CFG->privacy_url) ) {
    echo('<li><p><a href="'.$CFG->privacy_url.'"
       target="_blank">'._m('Privacy Policy').'</a></p></li>'."\n");
}
$privacy_description = array(
    'anonymous' => _m('Does not require student email or name.'),
    'public' => _m('Requires student email and name.'),
    'name_only' => _m('Requires student name but not email.')
);
$privacy_level = U::get($tool, 'privacy_level');
if ( is_string($privacy_level) ) {
    echo('<li><p>');
    echo(_m('Privacy Level:'));
    echo(' ');
    $pdesc = U::get($privacy_description, $privacy_level);
    if ( $pdesc ) {
        echo($pdesc);
    } else {
        echo($privacy_level);
    }
    echo("</p></li>\n");
}
$placements = U::get($tool, 'placements');
if ( is_array($placements) && in_array('homework_submission', $placements) ) {
    echo('<li><p>');
    echo(_m('Sends grades back to learning system'));
    echo("</p></li>\n");
}
$analytics = U::get($tool, 'analytics');
if ( is_array($analytics) && in_array('internal', $analytics) ) {
    echo('<li><p>');
    echo(_m('Has internal analytics visualization'));
    echo("</p></li>\n");
}
if ( $CFG->launchactivity ) {
    echo('<li><p>');
    echo(_m('Can send analytics to learning record store.'));
    echo("</p></li>\n");
}
if ( is_array(U::get($tool, 'languages')) ) {
    echo('<li><p>');
    echo(_m('Languages:'));
    echo(' ');
    $first = true;
    foreach(U::get($tool, 'languages') as $language) {
        if ( ! $first ) echo(', ');
        $first = false;
        echo(htmlentities($language));
    }
    echo("</p></li>\n");
}
if ( isset($CFG->sla_url) ) {
    echo('<li><p><a href="'.$CFG->sla_url.'"
       target="_blank">'._m('Service Level Agreement').'</a></p></li>'."\n");
}
if ( is_string(U::get($tool, 'license')) ) {
    echo('<li><p>');
    echo(_m('License:'));
    echo(' ');
    echo(htmlentities(U::get($tool, 'license')));
}
$source_url = U::get($tool, 'source_url');
if ( is_string($source_url) ) {
    echo('<li><p><a href="'.$source_url.'"
       target="_blank">'._m('Source code').'</a></p></li>'."\n");
}

if ( isset($CFG->google_classroom_secret) ) {
    echo('<li><p>');
    echo(_m('Supports'));
    echo(" Google Classroom");
    echo("</p></li>\n");
}

$launch_url = U::get($tool, 'url');
if ( $CFG->providekeys ) {
    echo('<li><p>');
    echo(_m('Supports'));
    echo(" IMS Learning Tools Interoperability&reg (LTI)");
    echo("</p></li>\n");
}

echo('<li><p>');
echo(_m('Supports'));
echo(' IMS Content Item&reg; / IMS Deep Linking');
echo("</p></li>\n");

echo('<li><p>');
echo(_m('Supports'));
echo(' Canvas Configuration URL');
echo("</p></li>\n");


echo("</ul>\n");

if ( $screen_shots ) {
    echo("<!-- .col-sm-4--></div>\n");
    echo('<div class="col-sm-8">'."\n");
    echo("<center>\n");
    echo('<div class="bxslider">');
    foreach($screen_shots as $screen_shot ) {
        echo('<div><img title="'.htmlentities($title).'" onclick="$(\'#popup-image\').attr(\'src\',this.src);showModal(this.title,\'image-dialog\');" src="'.$screen_shot.'"></div>'."\n");
    }
    echo("</center>\n");
    echo("<!-- .col-sm-8--></div>\n");
    echo("</div>\n");
}

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
