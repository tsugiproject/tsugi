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
<link rel="stylesheet" type="text/css" href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css"/>
<style>
    .app-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 16px;
    }
    .app {
        width: 265px;
        box-sizing: border-box;
    }
    .app.featured {
        width: 90%;
        max-width: 350px;
    }
    .tool-icon {
        color: var(--secondary);
        margin-left: 8px;
    }
    .featured-panel-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding-top: 20px;
    }
    .panel {
        border: 1px solid transparent;
        background-color: transparent;
    }
    .panel-default {
        position: relative;
        box-shadow: none;
        -webkit-box-shadow: none;
    }
    .panel-default:hover {
        border-color: var(--background-focus);
    }
    .panel-description {
        display:block;
        overflow:hidden;
        height: 8em;
        max-height: 8em;
    }
    .panel-default > .panel-heading {
        border-bottom: none;
        background: var(--primary);
        color: #fff;
    }
    .panel-default > .panel-heading .heading-container h3 {
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0 0.5rem;
        font-weight: 500;
        line-height: normal;
        color: #fff;
    }
    .panel-body {
        border: 1px solid var(--background-focus);
        border-top: 5px solid var(--secondary);
        padding: 15px 25px 20px;
    }
    .panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        min-height: 75px;
        flex-wrap: wrap;
    }
    .panel-heading a {
        font-weight: 300;
        width: 100%;
    }
    .heading-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }
    .heading-container span.fa {
        font-size: 2.75rem;
    }
    h3.phase-title {
        padding-left: 15px;
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
    .action-button {
        width: 100%;
        margin-top: 15px;
    }
    .search-container {
        padding: 20px 0;
        display: flex;
        justify-content: flex-end;
    }
    .search-container input {
        width: 250px;
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
    .bx-wrapper {
        margin-bottom: 20px;
    }
    .bx-wrapper .bx-pager {
        bottom: -40px;
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
$count = 0;

// Sort tools alpha
array_multisort(array_column($registrations, 'name'), SORT_ASC, $registrations);

$newApps = array_filter($registrations, 'new_apps');
$theRest = array_filter($registrations, 'the_rest');

?>
<div>
    <div class="bxslider">
        <?php
        foreach ($newApps as $name => $newApp)
        {
            ?><div class="featured-panel-container"><?php
            renderAppPanel($name, $newApp, true);
            ?></div><?php
        }
        ?>
    </div>
</div>

<?php

echo('<div id="box">'."\n");

echo('<div class="search-container">
    <label class="sr-only" for="keywordFilter">Filter by name</label>
    <input type="text" class="form-control" placeholder="Filter by name" id="keywordFilter">
    </div>');

echo ('<div class="app-container">');
function new_apps($val)
{
    return isset($val['tool_phase']) && $val['tool_phase'] === 'new';
}
function the_rest($val)
{
    return !isset($val['tool_phase']);
}

// Tool Phase new at top
// $registrations = array_merge($newApps, $theRest);

foreach($registrations as $name => $tool ) {
    renderAppPanel($name, $tool);
    $count++;
}
echo ('</div>');
if ( $count < 1 ) {
    echo("<p>No available tools</p>\n");
}

echo("</div>\n");
// echo("<pre>\n");print_r($tool);echo("</pre>\n");

$OUTPUT->footerStart();

function renderAppPanel($name, $tool, $featured = false) {
    global $CFG;
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

    $filterClass = !$featured ? ' filterable' : '';
    $featuredClass = $featured ? ' featured' : '';
    
    echo('<div class="app' . $filterClass . $featuredClass . '">'); 
    echo('<div class="panel panel-default" data-keywords="'.$keywords.'">');

    $phase = isset($tool['tool_phase']) ? $tool['tool_phase'] : false;
    if ($phase !== false) {
        echo('<div class="ribbon ribbon-top-left"><span>'.$phase.'</span></div>');
    }

    echo('<div class="panel-heading">');
    echo('<a href="details/'.urlencode($name).'">');
    echo('<div class="heading-container">');
    if ($phase !== false) {
        echo('<h3 class="phase-title">');
    } else {
        echo('<h3>');
    }
    echo(htmlent_utf8($title)."</h3>");
    if ( $fa_icon ) {
        echo('<div><span class="tool-icon fa '.$fa_icon.'"></span></div>');
    }
    echo('</div>'); // end heading container
    echo('</a>');
    echo('</div><div class="panel-body">');
    echo('<div class="panel-description js-ellipsis">');
    echo('<p>'.htmlent_utf8($text)."</p>");
    if ($keywords !== '') {
        echo('<p class="keywords">');
        echo(__('Tags:'));
        echo('<span class="keyword-span">'.$keywords.'</span></p>');
    }
    echo("</div>");
    if ( isset($_SESSION['gc_count']) ) {
        echo('<div style="text-align: center;">');
        $ltiurl = $tool['url'];
        echo('<a href="details/'.urlencode($name).'" class="action-button" role="button">More Details</a> ');
        echo('<a class="btn btn-primary action-button" href="'.$CFG->wwwroot.'/gclass/assign?lti='.urlencode($ltiurl).'&title='.urlencode($tool['name']));
        echo('" title="Install in Classroom" target="iframe-frame"'."\n");
        echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\">\n");
        echo('<span class="fa fa-plus"></span> Install');
        echo('</a>'."\n");
        echo('</div>');
    } else {
        echo('<div stlye="display: flex;">');
        echo('<a href="details/'.urlencode($name).'" class="btn btn-primary action-button" role="button">Details</a> ');
        echo('</div>');
    }
    echo("</div></div></div>\n");
}

?>
<script type="text/javascript" src="static/ftellipsis.js"></script>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.js"></script>
<script type="text/javascript">
    var filter = filter || {};

    filter.setUpListener = function() {
        $("#keywordFilter").on("keyup", function(){
            var search = $(this).val().toLowerCase();
            $(".app.filterable").each(function(){
                var panel = $(this).find("div.panel");
                var title = $(this).find("h3").text().toLowerCase();
                if (title.indexOf(search) >= 0) {
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

        $('.bxslider').bxSlider({
            auto: true,
            // autoControls: true,
            pager: true,
            useCSS: false,
            adaptiveHeight: false,
            infiniteLoop: true,
            // slideWidth: "300px",
            randomStart: true,
            speed: 750,
            pause: 5000,
        });
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
