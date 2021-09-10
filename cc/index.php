<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Util\U;
use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;
use \Tsugi\Util\CC_WebLink;

require_once "../config.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$OUTPUT->header();
$OUTPUT->bodystart(false);

    echo("<p>Course: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>".htmlentities($l->lessons->description)."</p>\n");
    $resource_count = 0;
    $assignment_count = 0;
    $discussion_count = 0;
    foreach($l->lessons->modules as $module) {
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

?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#allcontent" data-toggle="tab" aria-expanded="true">All Content</a></li>
  <li><a href="#select" data-toggle="tab" aria-expanded="false">Select Content</a></li>
</ul>

<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="allcontent">
<p>You can download all the modules in a single cartridge, or you can download any
combination of the modules.</p>
<?php
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo("<p>Discussion topics: $discussion_count </p>\n");
?>
<p>
<form action="export">
<p>
<label for="tsugi_lms_select_full">Choose the LMS that will use this cartridge:</label>
<select name="tsugi_lms" id="tsugi_lms_select_full">
  <option value="generic">Generic</option>
  <option value="canvas">Canvas</option>
  <option value="sakai">Sakai</option>
</select>
</p>
<?php if ( $discussion_count > 0 ) {
    if (isset($CFG->tdiscus) ) { ?>
<p>
<label for="topic_select_full">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lms">Use the LMS Discussion Tool</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
  <option value="none">Do not import discussion topics</option>
</select>
</p>
<?php } else { ?>
<input type="hidden" name="topic" value="lms" />
<?php } ?>
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
<?php } ?>
<p>
<input type="submit" class="btn btn-primary" value="Download modules" />
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
<p>
<label for="tsugi_lms_select_partial">Choose the LMS that will use this cartridge:</label>
<select name="tsugi_lms" id="tsugi_lms_select_partial">
  <option value="generic">Generic</option>
  <option value="canvas">Canvas</option>
  <option value="sakai">Sakai</option>
</select>
</p>
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
<?php if ( $discussion_count > 0 ) {
    if ( isset($CFG->tdiscus) ) { ?>
<p>
<label for="topic_select_full">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_partial">
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lms">Use the LMS Discussion Tool</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
  <option value="none">Do not import discussion topics</option>
</select>
</p>
<?php } else { ?>
<input type="hidden" name="topic" value="lms" id="topic_select_partial"/>
<?php }
}

foreach($l->lessons->modules as $module) {
    echo('<input type="checkbox" name="'.$module->anchor.'" value="'.$module->anchor.'">'."\n");
    echo(htmlentities($module->title));
    $resources = Lessons::getUrlResources($module);
    if ( ! $resources ) continue;
    echo("<ul>\n");
    echo("<li>Resources in this module: ".count($resources)."</li>\n");
    if ( isset($module->lti) ) {
        echo("<li>Assignments in this module: ".count($module->lti)."</li>\n");
    }
    if ( isset($module->discussions) ) {
        echo("<li>Discussions in this module: ".count($module->discussions)."</li>\n");
    }
    echo("</ul>\n");
}
?>
<p>
<input type="submit" value="Download selected modules" class="btn btn-primary" onclick=";myfunc(''); return false;"/>
</p>
</form>
<form id="real" action="export">
<input id="youtube_real" type="hidden" name="youtube"/>
<input id="tsugi_lms_real" type="hidden" name="tsugi_lms" />
<input id="topic_real" type="hidden" name="topic" />
<input id="res" type="hidden" name="anchors" value=""/>
</form>
</div>
</div>
<?php

$OUTPUT->footerStart();
?>
<script>
// https://stackoverflow.com/questions/13830276/how-to-append-multiple-values-to-a-single-parameter-in-html-form
function myfunc(youtube){
    var b = '';
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

    var tsugi_lms = $("#tsugi_lms_select_partial").val();
    $("#tsugi_lms_real").val(tsugi_lms);
    var stuff = $("#res").val();
    var youtube = $("#youtube_select_partial").val();
    $("#youtube_real").val(youtube);
    var topic = $("#topic_select_partial").val();
    $("#topic_real").val(topic);

    if ( stuff.length < 1 ) {
        alert('<?= _m("Please select at least one module") ?>');
    } else {
        $("#real").submit();
    }
}
</script>
<?php
$OUTPUT->footerEnd();
