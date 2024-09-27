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
        #body_container.container-fluid {
            padding: 0;
        }
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
        .overlay{
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: none;
            background-color: rgba(0,0,0,0.5); /*dim the background*/
            justify-content: center;
            align-items: center;
            }
            #overlay_img {
                max-width: 90%;
            }
            .slider-thumbnail {
                height: 100%;
                display: flex;
                align-items: center;
                background-color: var(--background-focus);
            }
            .detail-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 2rem;
                background-color: var(--background-focus);
                border-bottom: 8px solid var(--secondary);
                flex-wrap: wrap;
            }
            .detail-header-icon {
                font-size: 3rem;
                margin-right: 20px;
                color: var(--text);
            }
            .detail-header-text {
                font-size: 3rem;
                line-height: 3rem;
                color: var(--text);
            }
            @media(max-width: 768px) {
                .title-container {
                    display: flex;
                    width: 100%;
                    justify-content: center;
                }
                .install-button-container {
                    width: 100%;
                    text-align: center;
                }
                .install-button-container .btn {
                    width: 275px;
                    margin-top: 20px;
                }
            }
            .breadcrumb {
                background-color: var(--background-color);
                margin-bottom: 5px;
                padding: 5px 15px;
            }
            .breadcrumb>.active {
                color: var(--text-light);
            }
            .summary-text {
                margin-bottom: 40px;
                font-size: large;
            }
            .video-frame {
                max-width: 100%;
            }
            .content-container {
                display: flex;
                padding: 20px;
                padding-top: 40px;
                flex-wrap: wrap;
                justify-content: space-around;
                gap: 20px;
            }
            .summary-column {
                display: flex;
                flex-direction: column;
                width: 100%;
                max-width: 600px;
                justify-content: flex-start;
            }
            .details-column {
                width: 275px;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
            }
            .details-column input {
                margin-bottom: 20px;
            }
            .button-container {
                display: flex;
                justify-content: center;
            }
            .button-container input {
                width: 100%;
            }
            .tool-link-container {
                padding: 8px 0;
            }
            .sidebar-header {
                font-size: large;
            }
            a.tool-url-link {
                text-decoration: none;
                padding: 0;
            }
            a.tool-url-link:hover, a.tool-url-link:focus, a.tool-url-link:active {
                color: var(--text-light);
                text-decoration: none;
            }
            .bx-wrapper .bx-viewport {
                background-color: var(--background-focus);
                border-width: 2px;
                border-color: var(--secondary);
                box-shadow: none;
                -webkit-box-shadow: none;
                -moz-box-shadow: none;
            }
            .bxslider {
                display: flex;
                align-items: center;
                height: 315px;
                width: 560px;
            }
            .bx-pager-item {
                line-height: normal;
            }
            body .bx-wrapper .bx-pager.bx-default-pager a {
                border: 1px solid var(--text);
            }
    </style>
<?php

$registrations = findAllRegistrations(false, true);
if ( count($registrations) < 1 ) $registrations = false;

$OUTPUT->bodyStart();
?>
<div id="overlay" class="overlay" onclick="document.getElementById('overlay').style.display = 'none';" >
    <!-- NEED TO RESTYLE OVERLAY -->
    <img id="overlay_img" src="<?= $OUTPUT->getSpinnerUrl(); ?>">
    <!-- <img id="overlay_img" style="width:90%;margin-top: 50px;" src="<?= $OUTPUT->getSpinnerUrl(); ?>"> -->
</div>
<?php
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
$video = U::get($tool, 'video');
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

    <!-- Install modal -->
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

    <!-- Banner/Header Section -->
    <div class="header-back-nav">
        <ol class="breadcrumb">
        <li><a href="../index.php">Store</a></li>
        <li class="active"><?= htmlent_utf8($title) ?></li>
        </ol>
    </div>
    <div class="detail-header">
        <div class="title-container">
            <?php
            if ( $fa_icon ) {
                ?>
                <span class="fa <?= $fa_icon; ?> detail-header-icon"></span>
                <?php
            }
            ?>
            <span class="detail-header-text"><?= htmlent_utf8($title); ?></span>
        </div>
        <div class="install-button-container">
        <?php
        echo('<button type="button" class="btn btn-success" role="button" data-toggle="modal" data-target="#'.urlencode($install).'_modal"><span class="fa fa-plus" aria-hidden="true"></span> Install</button>');
        ?>
        </div>
    </div>

    <div class="content-container">
    <div class="summary-column">
        <span class="summary-text"> <?= htmlent_utf8($text); ?></span>
        <?php
        if ($video || $screen_shots) {
            ?>
            <div class="bxslider">
                <?php
                
                if (is_string($video)) { ?>
                    <iframe class="video-frame" height="315" width="560" src="<?= $video; ?>" frameborder="0" scrolling="0" allow="autoplay *; encrypted-media *; fullscreen *; picture-in-picture *;" allowfullscreen></iframe>
                    <?php
                }
                if ($screen_shots) {
                    foreach($screen_shots as $idx=>$screen_shot ) { ?>
                        <div class="slider-thumbnail" id="slider-thumbnail-<?= $idx; ?>"><img src="<?= $screen_shot; ?>" title="<?= htmlentities($title); ?>"></div>
                        <?php
                    } 
                }
            ?>
            </div>
        <?php
        }
        ?>
    </div>
    <div class="details-column">
        <?php
            
            echo('<form method="GET" style="display:inline" action="'.$rest_path->parent.'/test/'.urlencode($install).'">');
            echo('<div class="button-container">');
            echo('<input type="submit" class="btn btn-primary" value="Try It"></form> ');
            echo('</div>');
            if ( is_array(U::get($tool, 'languages')) ) {
                ?>
                <div class="tool-link-container">
                    <div class="sidebar-header">Languages</div>
                    <?php
                    $first = true;
                    foreach(U::get($tool, 'languages') as $language) {
                        if ( ! $first ) echo(', ');
                        $first = false;
                        echo(htmlentities($language));
                    }
                    ?>
                </div>
                <?php
            }
            if ( is_string(U::get($tool, 'license')) ) {
                ?>
                <div class="tool-link-container">
                    <div class="sidebar-header">License</div>
                    <?php
                        echo(htmlentities(U::get($tool, 'license')));
                    ?>
                </div>
                <?php
            }
            $source_url = U::get($tool, 'source_url');
            if ( is_string($source_url) ) {
                ?>
                <div class="tool-link-container">
                    <a href="<?= $source_url; ?>" target="_blank" class="tool-url-link sidebar-header">Source Code <span class="fa fa-chevron-right"></span></a>
                </div>
                <?php
            }
            echo('<div class="tool-link-container">');
            echo('<a href="#" class="tool-url-link sidebar-header" role="button" onclick="showModal(\'Tool URLs\',\'url-dialog\');">Tool URLs <span class="fa fa-chevron-right"></span></a>');
            echo('</div>');

            if (is_array($keywords)) {
                sort($keywords);
                ?>
                <div class="tool-link-container">
                    <div class="sidebar-header">Tags</div>
                    <h3 style="margin-top: 0;">

                    <?php
                        foreach ($keywords as $tag) {
                            ?>
                                <span class="label label-default"><?= $tag; ?></span>
                            <?php
                        }
                    ?>
                    </h3>
                </div>
                <?php
            }
            
            ?>
            <div class="tool-link-container">
                <div class="sidebar-header">Additional Details</div>
            </div>
            <?php
$script = isset($tool['script']) ? $tool['script'] : "index";
$path = $tool['url'];
echo("<p><ul>\n");
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
if ( isset($CFG->sla_url) ) {
    echo('<li><p><a href="'.$CFG->sla_url.'"
       target="_blank">'._m('Service Level Agreement').'</a></p></li>'."\n");
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
echo("</div>\n");
echo("</div>\n");

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
                // slideWidth: "240px",
                infiniteLoop: false,
                // maxSlides: 3
            });

            <?php
            if ($screen_shots) {
                foreach($screen_shots as $idx=>$screen_shot) {
                    ?>
                    $('#slider-thumbnail-<?= $idx; ?>').click(function () {
                        $('#overlay_img').attr('src', "<?= $screen_shot; ?>");
                        document.getElementById('overlay').style.display = 'flex';
                    });
                    <?php
                }
            }
            ?>
        });
    </script>
<?php
$OUTPUT->footerEnd();
