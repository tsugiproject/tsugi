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

// Check if this is a remote import from Canvas
if ( isset($_POST['ext_content_return_url']) ) {
    $return_url = $_POST['ext_content_return_url'];
    $return_url = U::add_url_parm($return_url, 'return_type', 'file');
    $return_url = U::add_url_parm($return_url, 'text', $CFG->servicename);

    $export_url = $CFG->wwwroot . '/cc/export?tsugi_lms=canvas';
    $export_url_youtube = U::add_url_parm($export_url, 'youtube', 'yes');

    $return_url_normal = U::add_url_parm($return_url, 'url', $export_url);
    $return_url_youtube = U::add_url_parm($return_url, 'url', $export_url_youtube);

    $OUTPUT->header();
    $OUTPUT->bodystart(false);
    echo("<p>Course: ".htmlentities($l->lessons->title)."</p>\n");
    echo("<p>".htmlentities($l->lessons->description)."</p>\n");
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
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
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo("<p>Discussion topics: $discussion_count </p>\n");
?>
<p>
<form action="export">
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php if ( $discussion_count > 0 ) {
    if (isset($CFG->tdiscus) ) { ?>
<p>
<label for="topic_select_full">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <!-- <option value="lti">Use discussion tool on this server (LTI)</option> -->
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
  <option value="lms">Use the Canvas discussion tool</option>
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
  <!-- <option value="track">Use LTI launch to track access</option> -->
  <option value="track_grade">Use LTI launch to track access and send grades</option>
</select>
</p>
<input type="submit" onclick= "sendToCanvas(); return false;" class="btn btn-primary" value="Import modules" />
</p>
</form>
<?php } ?>
<script>
function sendToCanvas() {
    let youtube = $("#youtube_select_full").val();
    let topic = $("#topic_select_full").val();
	let return_url = "<?= $return_url ?>";
	let export_url = "<?= $CFG->wwwroot.'/cc/export?tsugi_lms=canvas' ?>";
	export_url = export_url + '&youtube=' + youtube;
	export_url = export_url + '&topic=' + topic;
    return_url = return_url + "&url=" + encodeURIComponent(export_url);
    console.log(youtube, topic, export_url);
    window.location.href = return_url;
}
</script>
<?php
    $OUTPUT->footer();
    return;
}

// Check to see if we are building a cartridge for a subset
$anchor_str = U::get($_GET, 'anchors', false);
$anchors = false;
if ( $anchor_str ) $anchors = explode(',', $anchor_str);
if ( ! is_array($anchors) || count($anchors) < 1 ) $anchors = false;
$anchor_count = 0;
if ( $anchors ) {
    foreach($l->lessons->modules as $module) {
        if ( in_array($module->anchor, $anchors) ) {
            $anchor_count++;
        }
    }
    if ( $anchor_count < 1 ) $anchors = false;
}

// here we go...
$service = strtolower($CFG->servicename);
$filename = tempnam(sys_get_temp_dir(), $CFG->servicename);
if ( isCli() ) $filename = 'cc.zip';
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("Cannot open $filename\n");
}

if ( ! isCli() ) {
    header( "Content-Type: application/x-zip" );
    header( "Content-Disposition: attachment; filename=\"".$service."_export.imscc\"" );
}

$tsugi_lms = U::get($_GET,'tsugi_lms', false);
$topic = U::get($_GET,'topic', false);
$youtube = U::get($_GET,'youtube', false);
if ( $youtube == 'no' ) $youtube = false;

$cc_dom = new CC();
$cc_dom->set_title($CFG->context_title.' import');
$top_module = false;
if ( $tsugi_lms == 'sakai' ) {
    $top_module = $cc_dom->add_module('Imported content');
}

// $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

foreach($l->lessons->modules as $module) {
    if ( isCli() ) echo("title=$module->title\n");
    if ( $anchors && ! in_array($module->anchor, $anchors) ) continue;
    if ( $top_module ) {
        $sub_module = $cc_dom->add_sub_module($top_module,$module->title);
    } else {
        $sub_module = $cc_dom->add_module($module->title);
    }

    if ( isset($module->videos) ) {
        foreach($module->videos as $video ) {
            $title = __('Video:').' '.$video->title;
            if ( $youtube && isset($CFG->youtube_url) ) {
                $custom_arr = array();
                $endpoint = U::absolute_url($CFG->youtube_url);
                $endpoint = U::add_url_parm($endpoint, 'v', $video->youtube);
                $extensions = array('apphome' => $CFG->apphome);
                if ( $youtube == 'track_grade' ) {
                    $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
                } else {
                    $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
                }
            } else {
                $url = 'https://www.youtube.com/watch?v=' . $video->youtube;
                $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
            }
        }
    }

    // Old way
    if ( isset($module->slides) && is_string($module->slides) ) {
        $url = U::absolute_url($module->slides);
        $title = 'Slides: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    // Array way
    if ( isset($module->slides) && is_array($module->slides) ) {
        foreach($module->slides as $slide) {
            if ( is_string($slide) ) {
                $slide_title = basename($slide);
                $slide_href = $slide;
            } else {
                $slide_title = $slide->title ;
                $slide_href = $slide->href ;
            }
            $url = U::absolute_url($slide_href);
            $title = 'Slides: '.$slide_title;
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
        }
    }

    if ( isset($module->assignment) ) {
        $url = U::absolute_url($module->assignment);
        $title = 'Assignment: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    if ( isset($module->solution) ) {
        $url = U::absolute_url($module->solution);
        $title = 'Solution: '.$module->title;
        $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
    }

    if ( isset($module->references) ) {
        foreach($module->references as $reference ) {
            $title = 'Reference: '.$reference->title;
            $url = U::absolute_url($reference->href);
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
        }
    }

    if ( isset($module->lti) ) {
        foreach($module->lti as $lti ) {
            $title = isset($lti->title) ? $lti->title : $module->title;
            $title = 'Tool: '.$title;
            $custom_arr = array();
            if ( isset($lti->custom) ) {
                foreach($lti->custom as $custom) {
                    if ( isset($custom->value) ) {
                        $custom_arr[$custom->key] = $custom->value;
                    }
                    if ( isset($custom->json) ) {
                        $custom_arr[$custom->key] = json_encode($custom->json);
                    }
                }
            }
            $endpoint = U::absolute_url($lti->launch);
            // Sigh - some LMSs don't handle custom - sigh
            $endpoint = U::add_url_parm($endpoint, 'inherit', $lti->resource_link_id);
            $extensions = array('apphome' => $CFG->apphome);
            $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
        }
    }

    if ( isset($module->discussions) && $topic !=  "none" ) {
        foreach($module->discussions as $discussion ) {
            $title = isset($discussion->title) ? $discussion->title : $module->title;
            $text = isset($discussion->description) ? $discussion->description : $module->description;

			// If there is no LTI involved
            if ( $topic ==  "lms" || ! isset($CFG->tdiscus) ) {
                $cc_dom->zip_add_topic_to_module($zip, $sub_module, $title, $text);
                continue;
            }

            $title = __('Discussion:').' '.$title;
            $custom_arr = array();
            if ( isset($discussion->custom) ) {
                foreach($discussion->custom as $custom) {
                    if ( isset($custom->value) ) {
                        $custom_arr[$custom->key] = $custom->value;
                    }
                    if ( isset($custom->json) ) {
                        $custom_arr[$custom->key] = json_encode($custom->json);
                    }
                }
            }

            $endpoint = U::absolute_url($CFG->tdiscus);
            $endpoint = U::add_url_parm($endpoint, 'inherit', $discussion->resource_link_id);
            $extensions = array('apphome' => $CFG->apphome);

            if ( $topic == 'lti_grade' ) {
                $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
            } else {
                $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions);
            }
        }
    }
}

$zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

$zip->close();

if ( isCli() ) {
    echo("\nCLI run: Left zip file on cc.zip\n\n");
    return;
}

// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



