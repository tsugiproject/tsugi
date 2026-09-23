<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Services\Lessons\LessonsCartridge;
use \Tsugi\Services\Lessons\LessonsLegacyFiles;
use \Tsugi\Services\Lessons\LessonsLegacyGift;
use \Tsugi\Util\U;
use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;
use \Tsugi\Util\CC_WebLink;

require_once __DIR__ . '/legacy_form.php';
require_once __DIR__ . '/../config.php';

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$OUTPUT->header();
$OUTPUT->bodystart(false);

    // Check if wwwroot is localhost and show warning
    if ( strpos($CFG->wwwroot, '//localhost') !== false ) {
        echo('<div class="alert alert-warning" role="alert">');
        echo('<strong>Warning:</strong> You are running on localhost. ');
        echo('Cartridges exported from localhost may have problems importing into cloud-based LMS systems. ');
        echo('The URLs in the cartridge will point to localhost, which will not be accessible from cloud LMS instances.');
        echo('</div>');
    }

    echo("<p>Course: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>".htmlentities($l->lessons->description)."</p>\n");
    $resource_count = 0;
    $assignment_count = 0;
    $discussion_count = (int) LessonsCartridge::summarize($l)['discussions'];
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
    $file_scan = LessonsLegacyFiles::summarize($l);
    $gift_scan = LessonsLegacyGift::summarize($l);

?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#allcontent" data-toggle="tab" aria-expanded="true">All Content</a></li>
  <li><a href="#select" data-toggle="tab" aria-expanded="false">Select Content</a></li>
</ul>

<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="allcontent">
<p>You can download all the modules in a single cartridge, or you can download any
combination of the modules.</p>
<form action="export">
<p>
<label for="tsugi_lms_select_full">Choose the LMS that will use this cartridge:</label>
<select name="tsugi_lms" id="tsugi_lms_select_full">
<?php foreach ( \Tsugi\Services\Lessons\LessonsCartridge::exportFlavorLabels() as $value => $label ) { ?>
  <option value="<?= htmlspecialchars($value) ?>"<?= $value === 'generic' ? ' selected' : '' ?>><?= htmlentities($label) ?></option>
<?php } ?>
</select>
</p>
<p>Generic (CC 1.2) is a standards-only Common Cartridge with no LMS extensions. Generic (CC 1.1) and Moodle are the same Generic content as Common Cartridge 1.1 only. Tsugi and Canvas share Canvas's cartridge format so a Canvas export can import into Tsugi later. Sakai's cartridge format has significant overlap with Canvas's. When exporting to an LMS that is not on this list, use one of the Generic formats to be safe.</p>
<?php CcExportForm::echoCartridgeSelect('cartridge_select_full'); ?>
<?php CcExportForm::echoGiftQtiSelect('gift_qti_select_full'); ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full">How would you like to export discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <option value="lms" selected>Use the LMS Discussion Tool</option>
  <option value="none">Do not export discussion topics</option>
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
</select>
</p>
<?php } ?>
<?php
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo("<p>Discussion topics: $discussion_count </p>\n");
    CcExportForm::echoFilesPreview($file_scan);
    CcExportForm::echoGiftPreview($gift_scan);
?>
<p>
<input type="submit" class="btn btn-primary" value="Download modules" />
</p>
</form>
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
<?php foreach ( \Tsugi\Services\Lessons\LessonsCartridge::exportFlavorLabels() as $value => $label ) { ?>
  <option value="<?= htmlspecialchars($value) ?>"<?= $value === 'generic' ? ' selected' : '' ?>><?= htmlentities($label) ?></option>
<?php } ?>
</select>
</p>
<p>Generic (CC 1.2) is a standards-only Common Cartridge with no LMS extensions. Generic (CC 1.1) and Moodle are the same Generic content as Common Cartridge 1.1 only. Tsugi and Canvas share Canvas's cartridge format so a Canvas export can import into Tsugi later. Sakai's cartridge format has significant overlap with Canvas's. When exporting to an LMS that is not on this list, use one of the Generic formats to be safe.</p>
<?php CcExportForm::echoCartridgeSelect('cartridge_select_partial'); ?>
<?php CcExportForm::echoGiftQtiSelect('gift_qti_select_partial'); ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_partial">How would you like to export discussions/topics?</label>
<select name="topic" id="topic_select_partial">
  <option value="lms" selected>Use the LMS Discussion Tool</option>
  <option value="none">Do not export discussion topics</option>
  <option value="lti">Use discussion tool on this server (LTI)</option>
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
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
    if ( isset($module->lti) ) {
        echo("<li>Assignments in this module: ".count($module->lti)."</li>\n");
    }
    $mc = LessonsCartridge::moduleCounts($module);
    $mod_disc = (int) $mc['discussions'];
    if ( isset($module->discussions) && is_array($module->discussions) ) {
        $mod_disc += count($module->discussions);
    }
    if ( $mod_disc > 0 ) {
        echo("<li>Discussions in this module: ".$mod_disc."</li>\n");
    }
    $mod_files = 0;
    if ( isset($module->anchor) && isset($file_scan['by_module'][$module->anchor]) ) {
        $mod_files = (int) $file_scan['by_module'][$module->anchor]['files'];
    }
    if ( $mod_files > 0 ) {
        echo("<li>Files available for a thick cartridge: ".$mod_files."</li>\n");
    }
    $mod_gift = 0;
    if ( isset($module->anchor) && isset($gift_scan['by_module'][$module->anchor]) ) {
        $mod_gift = (int) $gift_scan['by_module'][$module->anchor]['found'];
    }
    if ( $mod_gift > 0 ) {
        echo("<li>GIFT quizzes available to convert to QTI: ".$mod_gift."</li>\n");
    }
    echo("</ul>\n");
}
?>
<p>
<input type="submit" value="Download selected modules" class="btn btn-primary" onclick=";myfunc(); return false;"/>
</p>
</form>
<form id="real" action="export">
<input id="tsugi_lms_real" type="hidden" name="tsugi_lms" />
<input id="topic_real" type="hidden" name="topic" />
<input id="cartridge_real" type="hidden" name="cartridge" />
<input id="gift_qti_real" type="hidden" name="gift_qti" />
<input id="res" type="hidden" name="anchors" value=""/>
</form>
</div>
</div>
<?php

$OUTPUT->footerStart();
?>
<script>
// https://stackoverflow.com/questions/13830276/how-to-append-multiple-values-to-a-single-parameter-in-html-form
function myfunc(){
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
    var topic = $("#topic_select_partial").val() || 'lms';
    $("#topic_real").val(topic);
    var cartridge = $("#cartridge_select_partial").val();
    $("#cartridge_real").val(cartridge);
    var gift_qti = $("#gift_qti_select_partial").val();
    $("#gift_qti_real").val(gift_qti);

    if ( stuff.length < 1 ) {
        alert('<?= _m("Please select at least one module") ?>');
    } else {
        $("#real").submit();
    }
}
</script>
<?php
$OUTPUT->footerEnd();
