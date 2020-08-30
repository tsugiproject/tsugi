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

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$deeplink = false;
if ( isset($LAUNCH->deeplink) ) $deeplink = $LAUNCH->deeplink;
if ( $deeplink ) {
    $return_url = $deeplink->returnUrl();
    $allow_lti = $deeplink->allowLtiLinkItem();
    $allow_link = $deeplink->allowLink();
    $allow_multiple = $deeplink->allowMultiple();
    $allow_import = $deeplink->allowImportItem();
} else {
    $return_url = ContentItem::returnUrl();
    $allow_lti = ContentItem::allowLtiLinkItem();
    $allow_link = ContentItem::allowLink();
    $allow_import = ContentItem::allowImportItem();
    $allow_multiple = ContentItem::allowMultiple();
}
$debug = true;  /* Pause when sending back */

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

    $custom = false;
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
    $retval = new ContentItem();
    $url = $CFG->wwwroot . '/cc/export';
    if ( $LAUNCH->isSakai() ) $url .= '?tsugi_lms=sakai';
    else if ( $LAUNCH->isCanvas() ) $url .= '?tsugi_lms=canvas';
    $retval->addFileItem($url, $l->lessons->title);
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
    echo('<li class="'.$active.'"><a href="#Import" data-toggle="tab" aria-expanded="false">Import</a></li>'."\n");
    $active = '';
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
                                <label for="presentationDocumentTarget">Link Target</label> (Not all LMS's support all options)
                                <select name="presentationDocumentTarget" id="presentationDocumentTarget">
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
<?php if ( $grade_launch ) { ?>
<div class="form-group">
<a href="#" onclick="$('.assignment-fields').toggle(); return false;" class="btn btn-success">
<?= __("Assignment Setup") ?>
<span class="assignment-fields" style="display:inline;">&#9660;</span><span class="assignment-fields" style="display:none;">&#9650;</span>
</a>
</div>
<div class="assignment-fields" style="display:none;">
                            <div class="form-group">
                                <label for="scoreMaximum">Maximum possible score for an activity.</label>
                                <input type="number" class="form-control" id="scoreMaximum" name="scoreMaximum">
                            </div>
                            <div class="form-group" for="resourceId">
                                <label>Tool provided ID for the resource. (optional) This is opaque to the LMS.</label>
                                <input type="text" class="form-control" id="resourceId" name="resourceId">
                            </div>
                            <div class="form-group">
                                <label for="tag">A tag used to mark this item. (optional) This is opaque to the LMS</label>
                                <input type="text" class="form-control" id="tag" name="tag">
                            </div>
                            <div class="form-group">
                                <label for="availableStart">Available dates:</label>
                                <input type="date" id="availableStart" name="availableStart"> - 
                                <input type="date" id="availableEnd" name="availableEnd">
                            </div>
                            <div class="form-group">
                                <label for="submissionStart">Submission dates:</label>
                                <input type="date" id="submissionStart" name="submissionStart"> - 
                                <input type="date" id="submissionEnd" name="submissionEnd">
                            </div>
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

if ( $l && $allow_import ) {
    echo('<div class="tab-pane fade '.$active.' in" id="import">'."\n");
    $active = '';
    echo("&nbsp;<br/>\n");
    echo("<form>\n");
    echo("<p>Course: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>".htmlentities($l->lessons->description)."</p>\n");
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
    $resource_count = 0;
    $assignment_count = 0;
    foreach($l->lessons->modules as $module) {
        $resources = Lessons::getUrlResources($module);
        if ( ! $resources ) continue;
        $resource_count = $resource_count + count($resources);
        if ( isset($module->lti) ) {
            $assignment_count = $assignment_count + count($module->lti);
        }
    }
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo('<p><input type="submit" class="btn btn-default" name="import" value="Import"></p>'."\n");
    echo("</form>\n");
    echo("</div>\n");
}

echo("</div>\n"); // myTabContent

$OUTPUT->footerStart();
?>
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
