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
        position: relative;
    }
    .panel-default:hover{
        border: 1px solid #aaa;
    }
    .panel-description {
        display:block;
        overflow:hidden;
        height: 6em; 
        max-height: 6em; 
        /* border: 2px solid red; */
    }

    .panel-body h3 {
overflow: hidden;
white-space: nowrap;
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

// Filter the registrations
if ( isset($CFG->storehide) && strlen($CFG->storehide) > 0 && ! isAdmin() ) {
    $filtered = array();
    foreach($registrations as $name => $tool ) {
        if ( isset($tool['tool_phase']) &&
           preg_match($CFG->storehide, $tool['tool_phase']) == 1 ) continue;
        $filtered[$name] = $tool;
    }
    $registrations = $filtered;
}
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

// Tell them what is going on...
echo(settings_status($key_count));

// Render the tools in the site
    echo('<div id="box">'."\n");

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
            echo('<a href="details/'.urlencode($name).'">');
            echo('<span class="fa '.$fa_icon.' fa-2x" style="float:right; margin: 2px"></span>');
            echo('</a>');
        }
        if ($phase !== false) {
            echo('<h3 class="phase-title">');
        } else {
            echo('<h3>');
        }
        echo(htmlent_utf8($title)."</h3>");
        echo('<div class="panel-description js-ellipsis">');
        echo('<p>'.htmlent_utf8($text)."</p>");
        if ($keywords !== '') {
            echo('<p class="keywords">');
            echo(__('Tags:'));
            echo('<span class="keyword-span">'.$keywords.'</span></p>');
        }
        echo("</div></div><div class=\"panel-footer\">");
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
?>
<script type="text/javascript" src="static/ftellipsis.js"/>
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
<script>
// https://github.com/ftlabs/ftellipsis
var forEach = Array.prototype.forEach;
var els = document.getElementsByClassName('js-ellipsis');

forEach.call(els, function(el) {
    var ellipsis = new Ellipsis(el);
    ellipsis.calc();
    ellipsis.set();
});

</script>
<?php
$OUTPUT->footerend();
