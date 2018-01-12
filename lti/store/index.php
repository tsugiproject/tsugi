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
use \Tsugi\Core\ContentItem;
use \Tsugi\Util\LTI;
use \Tsugi\UI\Lessons;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Model
$p = $CFG->dbprefix;

$return_url = ContentItem::returnUrl();
$allow_lti = ContentItem::allowLtiLinkItem();
$allow_web = ContentItem::allowContentItem();
$allow_import = ContentItem::allowImportItem();

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
$contents = false;
$import = false;
if ( ($allow_lti || $allow_web || $allow_import) && isset($CFG->lessons) ) {
    $l = new Lessons($CFG->lessons);
    if ( $allow_web ) $contents = true;
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
    echo("<p>This tool must be with LTI Link Content Item support</p>");
    $OUTPUT->footer();
    return;
}

if ( ! ( $assignments || $contents || $import || $registrations ) ) {
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

    $title = $tool['name'];
    $fa_icon = isset($tool['FontAwesome']) ? $tool['FontAwesome'] : false;
    $icon = false;
    if ( $fa_icon !== false ) {
        $icon = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
    }

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
    $return_url = $retval->returnUrl();
    $params = $retval->getContentItemSelection();
    $params = LTIX::signParameters($params, $return_url, "POST", "Install Content");
    $content = LTI::postLaunchHTML($params, $return_url, false, false, false);
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
    $retval = new ContentItem();
    $retval->addLtiLinkItem($path, $title, $title, $icon, $fa_icon, $custom, 10, $lti->resource_link_id);
    $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
    $content = $retval->prepareResponse($endform);
    echo('<center>');
    if ( $fa_icon ) {
        echo('<i class="fa '.$fa_icon.' fa-3x" style="color: #1894C7; float:right; margin: 2px"></i>');
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
foreach($_GET as $k => $v) {
    if ($v == 'on') $content_items[] = $k;
}
if ($l && count($content_items) > 0 ) {
    $retval = new ContentItem();
    $count = 0;
    foreach($content_items as $ci) {
        $pieces = explode('::', $ci);
        if ( count($pieces) != 2 ) continue;
        if ( ! is_numeric($pieces[1]) ) continue;
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
if ( $l && $allow_web ) {
    echo('<li class="'.$active.'"><a href="#content" data-toggle="tab" aria-expanded="false">Content</a></li>'."\n");
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

        echo('<div class="col-sm-4 appcolumn">');

        echo('<div class="panel panel-default" data-keywords="'.$keywords.'">');

        $phase = isset($tool['tool_phase']) ? $tool['tool_phase'] : false;
        if ($phase !== false) {
            echo('<div class="ribbon ribbon-top-left"><span>'.$phase.'</span></div>');
        }

        echo('<div class="panel-body">');
        if ( $fa_icon ) {
            echo('<a href="index.php?install='.urlencode($name).'">');
            echo('<i class="fa '.$fa_icon.' fa-2x" style="color: #1894C7; float:right; margin: 2px"></i>');
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
        echo('<a href="index.php?install='.urlencode($name).'" class="btn btn-success pull-right" role="button"><span class="fa fa-plus" aria-hidden="true"></span> Install</a>');
        echo('<a href="details/'.urlencode($name).'" class="btn btn-default" role="button">Details</a>');
        echo("</div></div></div>\n");

        $count++;
    }
    if ( $count < 1 ) {
        echo("<p>No available tools</p>\n");
    } else {
        echo("</div>");
    }
    echo("</div>\n");
}

if ( $l && $allow_web ) {
    echo('<div class="tab-pane fade '.$active.' in" id="content">'."\n");
    $active = '';

    echo("&nbsp;<br/>\n");
    echo("<form>\n");
    $count = 0;
    foreach($l->lessons->modules as $module) {
        $resources = Lessons::getUrlResources($module);
        if ( count($resources) < 1 ) continue;
        if ( $count == 0 ) {
            echo('<p><input type="submit" class="btn btn-default" value="Install"></p>'."\n");
            echo("<ul>\n");
        }
        $count++;
        echo('<li>'.$module->title."\n");
        echo('<ul style="list-style-type: none;">'."\n");
        for($i=0; $i<count($resources); $i++ ) {
            $resource = $resources[$i];
            echo('<li>');
            echo('<input type="checkbox" value="on", name="'.$module->anchor.'::'.$i.'">');
            echo('<a href="'.$resource->url.'" target="_blank">');
            echo(htmlentities($resource->title));
            echo("</a>\n");
            echo('</li>');
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

        $(document).ready(function() {
            filter.setUpListener();
        });
    </script>
<?php
$OUTPUT->footerend();
