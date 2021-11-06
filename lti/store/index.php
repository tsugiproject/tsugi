<?php
require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\ContentItem;
use \Tsugi\Core\DeepLinkResponse;
use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Util\LTI13;
use \Tsugi\UI\Lessons;
use \Tsugi\Util\LTIConstants;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$message_type = $LAUNCH->ltiMessageType();
error_log("Store launch message_type=".$message_type);
if ( $message_type == LTI13::MESSAGE_TYPE_PRIVACY ) {
    header("Location: ".addSession("privacy.php"));
    return;
}

$deeplink = false;
if ( isset($LAUNCH->deeplink) ) $deeplink = $LAUNCH->deeplink;
if ( $deeplink ) {
    $return_url = $deeplink->returnUrl();
    $allow_lti = $deeplink->allowLtiLinkItem();
    $allow_link = $deeplink->allowLink();
    $allow_multiple = $deeplink->allowMultiple();
    $allow_import = $deeplink->allowImportItem();
    // These can be missing (null -> assume true), true, or false
    $accept_lineitem = $LAUNCH->deeplink->getClaim('accept_lineitem');
    if ( $accept_lineitem === null ) $accept_lineitem = $LAUNCH->deeplink->getClaim('https://www.sakailms.org/spec/lti-dl/accept_lineitem');
    if ( $accept_lineitem === null ) $accept_lineitem = $LAUNCH->deeplink->getClaim('https://www.moodle.org/spec/lti-dl/accept_lineitem');
    if ( $accept_lineitem !== false ) $accept_lineitem = true;
    $accept_available = $LAUNCH->deeplink->getClaim('https://www.sakailms.org/spec/lti-dl/accept_available', $accept_lineitem);
    if ( $accept_available !== false ) $accept_available = true;
    $accept_submission = $LAUNCH->deeplink->getClaim('https://www.sakailms.org/spec/lti-dl/accept_submission', $accept_lineitem);
    if ( $accept_submission !== false ) $accept_submission = true;
} else {
    $return_url = ContentItem::returnUrl();
    $allow_lti = ContentItem::allowLtiLinkItem();
    $allow_link = ContentItem::allowLink();
    $allow_import = ContentItem::allowImportItem();
    $allow_multiple = ContentItem::allowMultiple();
    $accept_lineitem = true;
    $accept_available = true;
    $accept_submission = true;
}

// Time zones are just too tricky
$accept_available = false;
$accept_submission = false;

$debug = false;  /* Pause when sending back */

$OUTPUT->header();
?>
<style>
    .panel-default {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        position: relative;
    }
    .panel-default:hover{
        border: 1px solid #aaa;
    }
    .panel-body h3 {
        margin-top: 0.5em;
    }
    .approw {
        margin: 0;
    }
    .appcolumn {
        padding: 0 4px;
    }
    h3.phase-title {
        padding-left: 10px;
    }
    .keywords {
        font-size: .85em;
        font-style: italic;
        color: #666;
        margin: 0;
        padding: 0;
    }
    .keyword-span {
        text-transform: lowercase;
    }
    /* Created with cssportal.com CSS Ribbon Generator */
    .ribbon {
        position: absolute;
        left: -5px; top: -5px;
        z-index: 1;
        overflow: hidden;
        width: 75px; height: 75px;
        text-align: right;
    }
    .ribbon span {
        font-size: 14px;
        font-weight: bold;
        color: #FFF;
        text-transform: uppercase;
        text-align: center;
        line-height: 20px;
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
        width: 100px;
        display: block;
        background: #dc3545;
        box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
        position: absolute;
        top: 19px; left: -21px;
    }
    .ribbon span::before {
        content: "";
        position: absolute; left: 0; top: 100%;
        z-index: -1;
        border-left: 3px solid #dc3545;
        border-right: 3px solid transparent;
        border-bottom: 3px solid transparent;
        border-top: 3px solid #dc3545;
    }
    .ribbon span::after {
        content: "";
        position: absolute; right: 0; top: 100%;
        z-index: -1;
        border-left: 3px solid transparent;
        border-right: 3px solid #dc3545;
        border-bottom: 3px solid transparent;
        border-top: 3px solid #dc3545;
    }
    #box {
        margin-top: 1em;
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
$links = false;
$import = false;
if ( ($allow_lti || $allow_link || $allow_import) && isset($CFG->lessons) && file_exists($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
    if ( $allow_link ) $links = true;
    if ( $allow_import ) $import = true;
    foreach($l->lessons->modules as $module) {
        if ( isset($module->lti) ) {
            if ( $allow_lti ) $assignments = true;
        }
    }
}

// Load Tool Registrations
if ( $allow_lti ) {
    $registrations = findAllRegistrations(false, true);

    // Filter the registrations
    if ( isset($CFG->storehide) && is_string($CFG->storehide) && ! $USER->admin ) {
        $filtered = array();
        foreach($registrations as $name => $tool ) {
            if ( isset($tool['tool_phase']) && is_string($tool['tool_phase']) && strlen($tool['tool_phase']) > 0 &&
            preg_match($CFG->storehide, $tool['tool_phase']) == 1 ) continue;
            $filtered[$name] = $tool;
        }
        $registrations = $filtered;
    }
    if ( count($registrations) < 1 ) $registrations = false;
} else {
    $registrations = false;
}

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

if ( ! $USER->instructor ) {
    echo("<p>This tool must be launched by the instructor</p>");
    $OUTPUT->footer();
    return;
}

if ( ! $return_url ) {
    echo("<p>This tool must be launched with a Deep Linking request</p>");
    $OUTPUT->footer();
    return;
}

if ( ! ( $assignments || $links || $import || $registrations ) ) {
    echo("<p>No tools, content, imports, or assignments found.</p>");
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

    $title = isset($_GET["title"]) ? $_GET["title"] : $tool['name'];
    $text = isset($_GET["description"]) ? $_GET["description"] : $tool['description'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $scoreMaximum = U::get($_GET, "scoreMaximum");
    $resourceId = U::get($_GET, "resourceId");
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

    $script = isset($tool['script']) ? $tool['script'] : "index";
    $path = $tool['url'];

    $submissionReview = U::get($tool,'submissionReview');

    // Set up to send the response
    if ( $deeplink ) {
        $retval = new DeepLinkResponse($deeplink);
    } else {
        $retval = new ContentItem();
    }

    $additionalParams = array();
    $extraParmList = array(
        "scoreMaximum", "resourceId", "tag", "availableStart", "availableEnd", "submissionStart", "submissionEnd",
        "presentationDocumentTarget", "placementWidth", "placementHeight"
    );
    foreach ( $extraParmList as $parm ) {
        $value = U::get($_GET, $parm);
        if ( ! $value ) continue;
        $additionalParams[$parm] = $value;
    }
    if ( is_array($submissionReview) ) $additionalParams['submissionReview'] = $submissionReview;

    $custom = array(
        'availableStart' => '$ResourceLink.available.startDateTime',
        'availableEnd' => '$ResourceLink.available.endDateTime',
        'submissionStart' => '$ResourceLink.submission.startDateTime',
        'submissionEnd' => '$ResourceLink.submission.endDateTime',
        'resourcelink_id_history' => '$ResourceLink.id.history',
        'context_id_history' => '$Context.id.history',
        'canvas_caliper_url' => '$Caliper.url',
    );

    $retval->addLtiLinkItem($path, $title, $text, $icon, $fa_icon, $custom, $scoreMaximum, $resourceId, $additionalParams);

    $iframeattr=false; $endform=false;
    $content = $retval->prepareResponse($endform, $debug, $iframeattr);
    echo($content);
    return;
}

// Handle the assignment install
if ( $l && isset($_GET['assignment']) ) {
    $lti = $l->getLtiByRlid($_GET['assignment']);
    if ( ! $lti ) {
        echo("<p>Assignment ".htmlentities($_GET['assignment'])." not found</p>\n");
        $OUTPUT->footer();
        return;
    }

    $title = $lti->title;
    $path = $lti->launch;
    $path .= strpos($path,'?') === false ? '?' : '&';
    // Sigh - some LMSs don't handle custom - sigh
    $path .= 'inherit=' . urlencode($_GET['assignment']);
    $fa_icon = 'fa-check-square-o';
    $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';

    // Compute the custom values
    $custom = array();
    $custom['canvas_caliper_url'] = '$Caliper.url';
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

    // Set up to send the response
    if ( $deeplink ) {
        $retval = new DeepLinkResponse($deeplink);
    } else {
        $retval = new ContentItem();
    }

    $retval->addLtiLinkItem($path, $title, $title, $icon, $fa_icon, $custom, 10, $lti->resource_link_id);
    $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
    $content = $retval->prepareResponse($endform);
    echo('<center>');
    if ( $fa_icon ) {
        echo('<span class="fa '.$fa_icon.' fa-3x" style="color: var(--primary); float:right; margin: 2px"></span>');
    }
    echo("<h1>".htmlent_utf8($title)."</h1>\n");
    echo($content);
    echo("</center>\n");
    // echo("<pre>\n");print_r($lti);echo("</pre>\n");
    $OUTPUT->footer();
    return;
}

// Handle the content install
$content_items = array();
if ( strlen(U::get($_GET,'radio_value')) > 0 ) {
    $content_items[] = U::get($_GET,'radio_value');
}
foreach($_GET as $k => $v) {
    if ($v == 'on') $content_items[] = $k;
}
if ($l && count($content_items) > 0 ) {
    // Set up to send the response
    if ( $deeplink ) {
        $retval = new DeepLinkResponse($deeplink);
    } else {
        $retval = new ContentItem();
    }

    $count = 0;
    foreach($content_items as $ci) {
        $pieces = explode('::', $ci);
        var_dump($pieces);
        if ( count($pieces) == 2 && is_numeric($pieces[1]) ) {
            $anchor = $pieces[0];
            $index = $pieces[1]+0;
            $module = $l->getModuleByAnchor($anchor);
            if ( ! $module ) continue;
            $resources = Lessons::getUrlResources($module);
            if ( ! $resources ) continue;
            if ( ! isset($resources[$index]) ) continue;
            $r = $resources[$index];
            $retval->addContentItem($r->url, $r->title, $r->title, $r->thumbnail, $r->icon);
            if ( $count == 0 ) {
                echo("<p>Selected items:</p>\n");
                echo("<ul>\n");
            }
            $count++;
            echo("<li>".htmlentities($r->title)."</li>\n");
        }
        if ( count($pieces) == 3 && $pieces[1] == 'lti' && is_numeric($pieces[2]) ) {
            $anchor = $pieces[0];
            $index = $pieces[2]+0;
            $module = $l->getModuleByAnchor($anchor);
            if ( ! $module ) continue;
            if ( ! isset($module->lti) ) continue;
            if ( ! is_array($module->lti) ) continue;
            $resources = $module->lti;
            if ( ! $resources ) continue;
            if ( ! isset($resources[$index]) ) continue;
            $lti = $resources[$index];

            $title = $lti->title;
            $path = $lti->launch;
            $path .= strpos($path,'?') === false ? '?' : '&';
            // Sigh - some LMSs don't handle custom - sigh
            $path .= 'inherit=' . urlencode($lti->resource_link_id);
            $fa_icon = 'fa-check-square-o';
            $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';

            // Compute the custom values
            $custom = array();
            $custom['canvas_caliper_url'] = '$Caliper.url';
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

            $retval->addLtiLinkItem($path, $title, $title, $icon, $fa_icon, $custom, 10, $lti->resource_link_id);

            if ( $count == 0 ) {
                echo("<p>Selected items:</p>\n");
                echo("<ul>\n");
            }
            $count++;
            echo("<li>".htmlentities($lti->title)."</li>\n");
        }

    }

    if ( $count < 1 ) {
        echo("<p>No valid content items to install</p>\n");
        $OUTPUT->footer();
        return;
    }
    echo("</ul>\n");

    // Set up to send the response
    $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
    $content = $retval->prepareResponse($endform);
    echo("<center>\n");
    echo($content);
    echo("</center>\n");
    $OUTPUT->footer();
    return;
}

if ( $l && isset($_GET['import']) ) {
    // Set up to send the response
    if ( $deeplink ) {
        $retval = new DeepLinkResponse($deeplink);
    } else {
        $retval = new ContentItem();
    }

    $url = $CFG->wwwroot . '/cc/export';
    if ( $LAUNCH->isSakai() ) $url .= '?tsugi_lms=sakai';
    else if ( $LAUNCH->isCanvas() ) $url .= '?tsugi_lms=canvas';
    if ( U::get($_GET, 'anchors') ) {
        $url = U::add_url_parm($url, 'anchors',  U::get($_GET, 'anchors'));
    }
    if ( U::get($_GET, 'youtube') ) {
        $url = U::add_url_parm($url, 'youtube', U::get($_GET, 'youtube') );
    }
    if ( U::get($_GET, 'topic') ) {
        $url = U::add_url_parm($url, 'topic', U::get($_GET, 'topic') );
    }
    error_log('Export url: '.$url);
    $additionalParams = array(
        'mediaType' => LTIConstants::MEDIA_CC_1_3,
    );
    $retval->addFileItem($url, $l->lessons->title, $additionalParams);
    $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
    $content = $retval->prepareResponse($endform);
    echo("<center>\n");
    echo($content);
    echo("</center>\n");
    $OUTPUT->footer();
    return;
}

$active = 'active';

echo('<ul class="nav nav-tabs">'."\n");
if ( $registrations && $allow_lti ) {
    echo('<li class="'.$active.'"><a href="#box" data-toggle="tab" aria-expanded="true">Tools</a></li>'."\n");
    $active = '';
}
if ( $l && ($allow_link || $allow_lti) ) {
    echo('<li class="'.$active.'"><a href="#content" data-toggle="tab" aria-expanded="false">Learning Objects</a></li>'."\n");
    $active = '';
}

if ( $l && $allow_lti ) {
    echo('<li class="'.$active.'"><a href="#assignments" data-toggle="tab" aria-expanded="false">Assignments</a></li>'."\n");
    $active = '';
}

if ( $l && $allow_import ) {
    echo('<li class="'.$active.'"><a href="#allcontent" data-toggle="tab" aria-expanded="false">All Content</a></li>'."\n");
    $active = '';
    echo('<li class="'.$active.'"><a href="#select" data-toggle="tab" aria-expanded="false">Select Content</a></li>'."\n");
}
echo("</ul>\n");

$active = 'active';
echo('<div id="myTabContent" class="tab-content">'."\n");

// Render the tools in the site
if ( $registrations && $allow_lti ) {
    echo('<div class="tab-pane fade '.$active.' in" id="box">'."\n");
    $active = '';

    echo('<p class="text-right">
        <label class="sr-only" for="keywordFilter">Filter by keyword</label>
        <input type="text" placeholder="Filter by keyword" id="keywordFilter">
        </p>');

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

        $keywords = '';
        if (isset($tool['keywords'])) {
            sort($tool['keywords']);
            $keywords = implode(", ", $tool['keywords']);
        }

        $grade_launch = false;
        if ( isset($tool['messages']) && is_array($tool['messages']) &&
            array_search('launch_grade', $tool['messages']) !== false ) {
            $grade_launch = true;
        }

        echo('<div class="col-sm-4 appcolumn">');

        echo('<div class="panel panel-default" data-keywords="'.$keywords.'">');

        $phase = isset($tool['tool_phase']) ? $tool['tool_phase'] : false;
        if ($phase !== false) {
            echo('<div class="ribbon ribbon-top-left"><span>'.$phase.'</span></div>');
        }

        echo('<div class="panel-body">');
        if ( $fa_icon ) {
            echo('<a href="index.php?install='.urlencode($name).'">');
            echo('<span class="fa '.$fa_icon.' fa-2x" style="float:right; margin: 2px"></span>');
            echo('</a>');
        }
        if ($phase !== false) {
            echo('<h3 class="phase-title">');
        } else {
            echo('<h3>');
        }
        echo(htmlent_utf8($title)."</h3>");
        echo('<p>'.htmlent_utf8($text)."</p>");
        if ($keywords !== '') {
            echo('<p class="keywords">Tags: <span class="keyword-span">'.$keywords.'</span></p>');
        }
        echo("</div><div class=\"panel-footer\">");
        echo('<button type="button" class="btn btn-success pull-right" role="button" data-toggle="modal" data-target="#'.urlencode($name).'_modal"><span class="fa fa-plus" aria-hidden="true"></span> Install</button>');
        echo('<a href="details/'.urlencode($name).'" class="btn btn-default" role="button">Details</a>');
        echo("</div></div></div>\n");

        // Deep Linking 1.0 / Content Item
        // https://www.imsglobal.org/specs/lticiv1p0/specification
        // Deep Linking 2.0
        // https://www.imsglobal.org/spec/lti-dl/v2p0

        // Add install details modal
        ?>
        <div id="<?=urlencode($name)?>_modal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span class="fa fa-times" aria-hidden="true"></span><span class="sr-only">Cancel</span></button>
                        <h4 class="modal-title">Install Learning App</h4>
                    </div>
                    <div class="modal-body">
                        <form method="get">
                            <input type="hidden" name="install" value="<?=urlencode($name)?>">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" class="form-control" name="title" value="<?=htmlent_utf8($title)?>">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" rows="5" name="description"><?=htmlent_utf8($text)?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="presentationDocumentTarget_<?= $count ?>">Link Target</label> (Not all LMS's support all options)
                                <select name="presentationDocumentTarget" id="presentationDocumentTarget_<?= $count ?>">
                                    <option value="none">Any</option>
                                    <option value="iframe">iFrame</option>
                                    <option value="window">New Window</option>
                                </select>
                            </div>
                            <div class="form-group tsugi-form-embedded-size" style="display:none;">
                                <label>Width (pixels)</label>
                                <input type="number" class="form-control placementWidth" name="placementWidth">
                            </div>
                            <div class="form-group tsugi-form-embedded-size" style="display:none;">
                                <label>Height (pixels)</label>
                                <input type="number" class="form-control" name="placementHeight">
                            </div>
                            <!-- https://www.imsglobal.org/spec/lti-dl/v2p0 -->
<?php if ( $grade_launch && $accept_lineitem ) { ?>
                            <div class="form-group">
                                <label for="lineitem_<?= $count ?>">Configure LineItem</label> (Not all LMS placements support all features)
                                <select name="lineitem" id="lineitem_<?= $count ?>" onchange="toggleLineItem(this, <?= $count ?>);">
                                    <option value="none">No LineItem</option>
                                    <option value="send">Send LineItem</option>
                                </select>
                            </div>
<div class="lineitem-fields" id="lineitem-fields_<?= $count ?>" style="display:none;">
                            <div class="form-group">
                                <label for="scoreMaximum_<?= $count ?>">Maximum possible score for an activity.</label>
                                <input type="number" class="form-control" id="scoreMaximum_<?= $count ?>" name="scoreMaximum">
                            </div>
                            <div class="form-group" for="resourceId_<?= $count ?>">
                                <label>Tool provided ID for the resource. (optional) This is opaque to the LMS.</label>
                                <input type="text" class="form-control" id="resourceId_<?= $count ?>" name="resourceId">
                            </div>
                            <div class="form-group">
                                <label for="tag_<?= $count ?>">A tag used to mark this item. (optional) This is opaque to the LMS</label>
                                <input type="text" class="form-control" id="tag_<?= $count ?>" name="tag">
                            </div>
<?php if ( $accept_available ) { ?>
                            <div class="form-group">
                                <label for="availableStart_<?= $count ?>">Available dates:</label>
                                <input type="date" id="availableStart_<?= $count ?>" name="availableStart"> - 
                                <input type="date" id="availableEnd_<?= $count ?>" name="availableEnd">
                            </div>
<?php } ?>
<?php if ( $accept_submission ) { ?>
                            <div class="form-group">
                                <label for="submissionStart_<?= $count ?>">Submission dates:</label>
                                <input type="date" id="submissionStart_<?= $count ?>" name="submissionStart"> - 
                                <input type="date" id="submissionEnd_<?= $count ?>" name="submissionEnd">
                            </div>
<?php } ?>
</div>
<?php } ?>
                            <div class="debug-claims" style="display:none;">
                            <div class="form-group">
                                <label>Msg claim</label>
                                <input type="text" class="form-control" name="message_msg">
                            </div>
                            <div class="form-group">
                                <label>Log claim</label>
                                <input type="text" class="form-control" name="message_log">
                            </div>
                            <div class="form-group">
                                <label>Errormsg claim</label>
                                <input type="text" class="form-control" name="message_errormsg">
                            </div>
                            <div class="form-group">
                                <label>Errorlog claim</label>
                                <input type="text" class="form-control" name="message_errorlog">
                            </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php

        $count++;
    }
    if ( $count < 1 ) {
        echo("<p>No available tools</p>\n");
    } else {
        echo("</div>");
    }
    echo("</div>\n");
}

if ( $l && ($allow_link || $allow_lti) ) {
    echo('<div class="tab-pane fade '.$active.' in" id="content">'."\n");
    echo("&nbsp;<br/>\n");
    $active = '';
    if ( ! $allow_multiple ) {
        echo("<p>The LMS has requested that we provide a single learning resource on this request.</p>\n");
    }
    echo("<form>\n");
    $count = 0;
    foreach($l->lessons->modules as $module) {
        $resources = Lessons::getUrlResources($module);
        if ( count($resources) < 1 ) continue;
        if ( $count == 0 ) {
            echo('<p>');
            echo('<input type="submit" class="btn btn-default" value="Install">'."\n");
            echo('<button class="btn btn-default" onclick="selectAll(this); return false;">Select All</button>'."\n");
            echo("</p>\n");
            echo("<ul>\n");
        }
        $count++;
        echo("<li>");
        if ( $allow_multiple ) {
            echo('<input type="checkbox" value="ignore" class="module_all" onclick="handleClick(this);" name="'.$module->anchor.'">');
        }
        echo(htmlentities($module->title)."\n");
        echo('<ul style="list-style-type: none;">'."\n");
        for($i=0; $i<count($resources); $i++ ) {
            $resource = $resources[$i];
            echo('<li>');
            if ( $allow_multiple ) {
                echo('<input type="checkbox" class="module_all '.$module->anchor.'_module" value="on", name="'.$module->anchor.'::'.$i.'">');
            } else {
                echo('<input type="radio" name="radio_value", value="'.$module->anchor.'::'.$i.'">');
            }
	    // TODO: Make this more clever
            if ( is_array($resource->url) ) {
                 $res_url = $resource->url[0];
            } else {
                 $res_url = $resource->url;
            }
            if ( is_array($resource->title) ) {
                 $res_title = $resource->title[0];
            } else {
                 $res_title = $resource->title;
            }
            echo('<a href="'.$res_url.'" target="_blank">');
            echo(htmlentities($res_title));
            echo("</a>\n");
            echo('</li>');
        }
        if ( isset($module->lti) && is_array($module->lti) ) {
            for($i=0; $i < count($module->lti); $i++) {
                $lti = $module->lti[$i];
                echo('<li>');
                if ( $allow_multiple ) {
                    echo('<input type="checkbox" value="on", class="module_all '.$module->anchor.'_module" name="'.$module->anchor.'::lti::'.$i.'">');
                } else {
                    echo('<input type="radio" name="radio_value", value="'.$module->anchor.'::lti::'.$i.'">');
                }
                echo('LTI Tool: ');
                echo(htmlentities($lti->title));
                echo('</li>');
            }
        }
        echo("</ul>\n");
        echo("</li>\n");
    }
    if ( $count < 1 ) {
        echo("<p>No available content.</p>\n");
    } else {
        echo("</ul>\n");
    }
    echo('<p><input type="submit" class="btn btn-default" value="Install"></p>'."\n");
    echo("</form>\n");
    echo("</div>\n");
}

// Render web assignments
if ( $l && $allow_lti ) {
    echo('<div class="tab-pane fade '.$active.' in" id="assignments">'."\n");
    $active = '';
    $count = 0;
    foreach($l->lessons->modules as $module) {
        if ( isset($module->lti) ) {
            foreach($module->lti as $lti) {
                if ( $count == 0 ) {
                    echo("<ul>\n");
                }
                $count++;
                echo('<li><a href="index.php?assignment='.$lti->resource_link_id.'">'.htmlentities($lti->title).'</a>');
                echo("</li>\n");
            }
        }
    }
    if ( $count < 1 ) {
        echo("<p>No available assignments.</p>\n");
    } else {
        echo("</ul>\n");
    }
    echo("</div>\n");
}

$resource_count = 0;
$assignment_count = 0;
$discussion_count = 0;
if ( $l ) foreach($l->lessons->modules as $module) {
    $resources = Lessons::getUrlResources($module);
    if ( ! $resources ) continue;
    $resource_count = $resource_count + count($resources);
    if ( isset($module->lti) ) {
        $assignment_count = $assignment_count + count($module->lti);
    }
    if ( isset($module->discussions) ) {
        $discussion_count = $discussion_count + count($module->discussions);
    }
}

if ( $l && $allow_import ) {
?>
    <div class="tab-pane fade <?= $active ?> in" id="allcontent">
<p>You can download all the modules in a single cartridge, or you can download any 
combination of the modules.</p>
<p>
<form>
<?php if ( $discussion_count > 0 && isset($CFG->tdiscus) ) { ?>
<p>
<label for="topic_select_full">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lms">Use the LMS Discussion Tool</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
</select>
</p>
<?php } ?>
<?php if ( isset($CFG->youtube_url) ) { ?>
<p>
<label for="youtube_select_full">Would you like YouTube Tracked URLs?</label>
<select name="youtube" id="youtube_select_full">
  <option value="no">No - Launch directly to YouTube</option>
  <option value="track">Use LTI launch to track access</option>
  <option value="track_grade">Use LTI launch to track access and send grades</option>
</select>
</p>
<?php 
    $active = '';
} ?>
<?php
    echo("<p>Course: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo("<p>Discussion topics: $discussion_count </p>\n");
?>
<p>
<input type="submit" class="btn btn-primary" value="Import modules" name="import"/>
</p>
</form>
<?php     if ( isset($CFG->youtube_url) ) { ?>
<p>
If you select YouTube tracked URLs, each YouTube URL will be launched via LTI
to a YouTube tracking tool on this server so you can get analytics on who
watches your YouTube videos through the LMS.  Some LMS's do not do well with
tracked URLs because they treat every LTI link as a gradable link.
</p>
<?php } ?>
</div>
<div class="tab-pane fade" id="select">
<p>Select the modules to include, and download below.  You must select at least one module.</p>
<?php
$resource_count = 0;
$assignment_count = 0;
echo('<form id="void">'."\n");
?>
<?php if ( $discussion_count > 0 && isset($CFG->tdiscus) ) { ?>
<p>
<label for="topic_select_partial">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_partial">
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lms">Use the LMS Discussion Tool</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
</select>
</p>
<?php } ?>
<?php if ( isset($CFG->youtube_url) ) { ?>
<p>
<label for="youtube_select_partial">Would you like YouTube Tracked URLs?</label>
<select name="youtube" id="youtube_select_partial">
  <option value="no">No - Launch directly to YouTube</option>
  <option value="track">Use LTI launch to track access</option>
  <option value="track_grade">Use LTI launch to track access and send grades</option>
</select>
</p>
<?php } ?>
<?php
if ( $l ) foreach($l->lessons->modules as $module) {
    echo('<input type="checkbox" name="'.$module->anchor.'" value="'.$module->anchor.'">'."\n");
    echo(htmlentities($module->title));
    $resources = Lessons::getUrlResources($module);
    if ( ! $resources ) continue;
    echo("<ul>\n");
    echo("<li>Resources in this module: ".count($resources)."</li>\n");
    $resource_count = $resource_count + count($resources);
    if ( isset($module->discussions) ) {
        echo("<li>Discussion topics in this module: ".count($module->discussions)."</li>\n");
    }
    if ( isset($module->lti) ) {
        echo("<li>Assignments in this module: ".count($module->lti)."</li>\n");
        $assignment_count = $assignment_count + count($module->lti);
    }
    echo("</ul>\n");
}
?>
<p>
<input type="submit" value="Import selected modules" class="btn btn-primary" onclick=";myfunc(''); return false;"/>
</p>
</form>
<form id="real">
<input id="youtube_real" type="hidden" name="youtube"/>
<input id="topic_real" type="hidden" name="topic"/>
<input id="res" type="hidden" name="anchors" value=""/>
<input type="hidden" name="import" value="import"/>
</form>
</div>
<?php } ?>
</div>
<?php

$OUTPUT->footerStart();
?>

<script>
// https://stackoverflow.com/questions/13830276/how-to-append-multiple-values-to-a-single-parameter-in-html-form
function myfunc(){
    $('#void input[type="checkbox"]').each(function(id,elem){
         console.log(this);
         if ( ! $(this).is(':checked') ) return;
         b = $("#res").val();
         if(b.length > 0){
            $("#res").val( b + ',' + $(this).val() );
        } else {
            $("#res").val( $(this).val() );
        }

    });

    var youtube = $("#youtube_select_partial").val();
    $("#youtube_real").val(youtube);
    var topic = $("#topic_select_partial").val();
    $("#topic_real").val(topic);
    console.log('topic',topic,'youtube',youtube);
    var stuff = $("#res").val();
    if ( stuff.length < 1 ) {
        alert('<?= _m("Please select at least one module") ?>');
    } else {
        $("#real").submit();
    }
}

function toggleLineItem(item, count) {
    // https://stackoverflow.com/questions/5416767/get-selected-value-text-from-select-on-change
    var x = (item.value || item.options[item.selectedIndex].value);
    if ( x == "send" ) {
       $('#lineitem-fields_'+count).show();
    } else {
       $('#lineitem-fields_'+count).hide();
      $('#scoreMaximum_'+$count).val('');
    }
}

</script>


    <script type="text/javascript">
        var filter = filter || {};

        filter.setUpListener = function() {
            $("#keywordFilter").on("keyup", function(){
                var search = $(this).val().toLowerCase();
                $(".appcolumn").each(function(){
                    var panel = $(this).find("div.panel");
                    var words = panel.data("keywords");
                    if (typeof words !== "undefined" && words.toLowerCase().indexOf(search) >= 0) {
                        $(this).fadeIn("slow");
                        var keywordSpan = panel.find("div.panel-body").find("p.keywords").find("span.keyword-span");
                        var keywordText = keywordSpan.text().toLowerCase();
                        keywordSpan.html(filter.boldSubstr(keywordText, search));
                    } else {
                        $(this).fadeOut("fast");
                    }
                });
            });
        };

        filter.boldSubstr = function(string, needle) {
            var regex = new RegExp(needle, 'g');
            return string.replace(regex, "<strong>" + needle + "</strong>");
        };

        $(document).on('change', '#presentationDocumentTarget',  function() {
            var value = $(this).find("option:selected").attr('value');
            console.log(value);
            if ( value == 'embed' || value == 'iframe' ) {
                console.log('Show');
                $('.tsugi-form-embedded-size').show();
            } else {
                $('.tsugi-form-embedded-size').hide();
            }
        });

        $(document).on('keyup', '.placementWidth',  function() {
            var value = $(this).val();
            console.log(this);
            console.log(value);
            if ( value == 4242 ) {
                alert("Achievement unlocked");
                $('.debug-claims').show();
            }
        });

        $(document).ready(function() {
            filter.setUpListener();
        });

        // https://stackoverflow.com/questions/4471401/getting-value-of-html-checkbox-from-onclick-onchange-events
        function handleClick(cb) {
            console.log("Clicked, name = " + cb.name);
            var name = '.' + cb.name + '_module';
            console.log(name);
            $(name).each(function () { $(this).prop("checked", cb.checked); } ) ;
        }

        function selectAll(button) {
            $('.module_all').each(function () { $(this).prop("checked", ! $(this).prop("checked")); } );
            return false;
        }
    </script>
<?php
$OUTPUT->footerend();
