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
echo("<h1>Course: ".htmlentities($l->lessons->title)."</h1>\n");
echo("<p>".htmlentities($l->lessons->description)."</p>\n");
echo("<p>This course has: ".count($l->lessons->modules)." modules</p>\n");
?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#allcontent" data-toggle="tab" aria-expanded="true">All Content</a></li>
  <li><a href="#select" data-toggle="tab" aria-expanded="false">Select Content</a></li>
</ul>

<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="allcontent">
<p>You can download all the modules in a single cartridge, or you can download any 
combination of the modules.</p>
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
<?php     if ( isset($CFG->youtube_url) ) { ?>
<p>
<label for="youtube_select_full">Would you like Youtube Tracked URLs?</label>
<select name="youtube" id="youtube_select_full">
  <option value="no">No</option>
  <option value="yes">Yes</option>
</select>
</p>
<?php } ?>
<p>
<input type="submit" class="btn btn-primary" value="Download modules" />
</p>
</form>
<?php     if ( isset($CFG->youtube_url) ) { ?>
<p>
If you select YouTube tracked URLs, each Youtube URL will be launched via LTI
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
<label for="youtube_select_partial">Would you like Youtube Tracked URLs?</label>
<select name="youtube" id="youtube_select_partial">
  <option value="no">No</option>
  <option value="yes">Yes</option>
</select>
</p>
<?php } ?>
<?php
foreach($l->lessons->modules as $module) {
    echo('<input type="checkbox" name="'.$module->anchor.'" value="'.$module->anchor.'">'."\n");
    echo(htmlentities($module->title));
    $resources = Lessons::getUrlResources($module);
    if ( ! $resources ) continue;
    echo("<ul>\n");
    echo("<li>Resources in this module: ".count($resources)."</li>\n");
    $resource_count = $resource_count + count($resources);
    if ( isset($module->lti) ) {
        echo("<li>Assignments in this module: ".count($module->lti)."</li>\n");
        $assignment_count = $assignment_count + count($module->lti);
    }
    echo("</ul>\n");
}
?>
<p>
<input type="submit" value="Download selected modules" class="btn btn-primary" onclick=";myfunc(''); return false;"/>
</p>
</form>
<form id="real" action="export">
<input id="youtube" type="hidden" name="youtube"/>
<input id="tsugi_lms" type="hidden" name="tsugi_lms_real" />
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
    if ( stuff.length < 1 ) {
        alert('<?= _m("Please select at least one module") ?>');
    } else {
        if ( youtube == 'yes' ) {
            $("#youtube").val('yes');
        } else {
            $("#youtube").val('');
        }
        $("#real").submit();
    }
}
</script>
<?php
$OUTPUT->footerEnd();
