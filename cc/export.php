<?php

use \Tsugi\UI\Lessons;
use \Tsugi\UI\LessonsCartridge;
use \Tsugi\UI\LessonsLegacyFiles;
use \Tsugi\UI\LessonsLegacyGift;
use \Tsugi\UI\LessonsNormalize;
use \Tsugi\Util\U;
use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;
use \Tsugi\Util\CC_WebLink;

require_once __DIR__ . '/../config.php';

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

// Helper function to get module path from DOMNode
function get_module_path($module_node, $cc_dom) {
    // Use reflection to access private modulePaths property
    $reflection = new \ReflectionClass($cc_dom);
    $property = $reflection->getProperty('modulePaths');
    $property->setAccessible(true);
    $modulePaths = $property->getValue($cc_dom);
    $moduleHash = spl_object_hash($module_node);
    return isset($modulePaths[$moduleHash]) ? $modulePaths[$moduleHash] : '';
}

/**
 * Helper function to process a single item for CC export
 *
 * @param object $item_obj
 * @param object $module
 * @param \DOMNode $sub_module
 * @param \ZipArchive $zip
 * @param CC $cc_dom
 * @param string|false $youtube
 * @param string|false $topic
 * @param LessonsLegacyFiles $local_files
 * @param LessonsLegacyGift $gift_qti
 */
function process_cc_item($item_obj, $module, $sub_module, $zip, $cc_dom, $youtube, $topic, LessonsLegacyFiles $local_files, LessonsLegacyGift $gift_qti) {
    global $CFG;
    $type = isset($item_obj->type) ? $item_obj->type : '';
    $kind = LessonsNormalize::presentationKind($item_obj);
    
    // Get parent path for deterministic ID generation
    $parentPath = get_module_path($sub_module, $cc_dom);
    
    // Skip text type for now
    if ( $type == 'text' ) return;
    
    // Heading / legacy header: Canvas sub-header (no resource)
    if ( LessonsCartridge::addHeadingItem($cc_dom, $sub_module, $item_obj, $parentPath) ) {
        return;
    }
    
    // Video (legacy type=video or normalized web_link/video)
    if ( LessonsCartridge::addVideoItem($zip, $cc_dom, $sub_module, $item_obj, $youtube, $parentPath) ) {
        return;
    }
    
    // Handle slide type (legacy type=slide or normalized web_link/slides)
    if ( $type == 'slide' || $kind == 'slide' ) {
        $slide_title = isset($item_obj->title) ? $item_obj->title : basename(isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : ''));
        $slide_href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
        $slide_href = Lessons::expandLink($slide_href);
        $url = U::absolute_url($slide_href);
        $title = 'Slides: '.$slide_title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath);
        return;
    }
    
    // Handle reference type (legacy type=reference or normalized web_link/reference)
    if ( $type == 'reference' || $kind == 'reference' ) {
        $title = isset($item_obj->title) ? $item_obj->title : $module->title;
        $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
        $href = Lessons::expandLink($href);
        $url = U::absolute_url($href);
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath);
        return;
    }
    
    // Handle assignment type (legacy type=assignment or normalized web_link/assignment)
    if ( $type == 'assignment' || $kind == 'assignment' ) {
        $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
        $href = Lessons::expandLink($href);
        $url = U::absolute_url($href);
        $title = 'Assignment: '.$module->title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath);
        return;
    }
    
    // Handle solution type (legacy type=solution or normalized web_link/solution)
    if ( $type == 'solution' || $kind == 'solution' ) {
        $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
        $href = Lessons::expandLink($href);
        $url = U::absolute_url($href);
        $title = 'Solution: '.$module->title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath);
        return;
    }
    
    // Handle lti type — GIFT quizzes become QTI by default
    if ( $type == 'lti' ) {
        $gift_qti->addToModule($zip, $cc_dom, $sub_module, $item_obj, $module, $parentPath);
        return;
    }
    
    // Handle discussion type
    if ( $type == 'discussion' && $topic != "none" ) {
        $title = isset($item_obj->title) ? $item_obj->title : $module->title;
        $text = isset($item_obj->description) ? $item_obj->description : $module->description;

        // If there is no LTI involved
        if ( $topic == "lms" || ! isset($CFG->tdiscus) ) {
            $cc_dom->zip_add_topic_to_module($zip, $sub_module, $title, $text, $parentPath);
            return;
        }

        $title = __('Discussion:').' '.$title;
        $custom_arr = array();
        if ( isset($item_obj->custom) ) {
            foreach($item_obj->custom as $custom) {
                if ( isset($custom->value) ) {
                    $custom_arr[$custom->key] = $custom->value;
                }
                if ( isset($custom->json) ) {
                    $custom_arr[$custom->key] = json_encode($custom->json);
                }
            }
        }

        $endpoint = U::absolute_url($CFG->tdiscus);
        $endpoint = U::add_url_parm($endpoint, 'inherit', $item_obj->resource_link_id);
        $extensions = array('apphome' => $CFG->apphome);
        $resource_link_id = isset($item_obj->resource_link_id) ? $item_obj->resource_link_id : null;

        if ( $topic == 'lti_grade' ) {
            $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parentPath);
        } else {
            $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parentPath);
        }
        return;
    }
}

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
    echo("<p>Modules: ".count($l->lessons->modules)."</p>\n");
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
    echo("<p>Resources: $resource_count </p>\n");
    echo("<p>Assignments: $assignment_count </p>\n");
    echo("<p>Discussion topics: $discussion_count </p>\n");
    $file_scan = LessonsLegacyFiles::summarize($l);
    LessonsLegacyFiles::echoPreview($file_scan);
    $gift_scan = LessonsLegacyGift::summarize($l);
    LessonsLegacyGift::echoPreview($gift_scan);
?>
<p>
<form action="export">
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php LessonsLegacyFiles::echoCartridgeSelect('cartridge_select_full'); ?>
<?php LessonsLegacyGift::echoGiftQtiSelect('gift_qti_select_full'); ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full">How would you like to import discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <option value="none">Do not import discussion topics</option>
  <!-- <option value="lti">Use discussion tool on this server (LTI)</option> -->
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
  <option value="lms" selected>Use the Canvas discussion tool</option>
</select>
</p>
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
    let topic = $("#topic_select_full").val() || 'lms';
    let cartridge = $("#cartridge_select_full").val();
    let gift_qti = $("#gift_qti_select_full").val();
	let return_url = "<?= $return_url ?>";
	let export_url = "<?= $CFG->wwwroot.'/cc/export?tsugi_lms=canvas' ?>";
	export_url = export_url + '&youtube=' + youtube;
	export_url = export_url + '&topic=' + topic;
	export_url = export_url + '&cartridge=' + encodeURIComponent(cartridge);
	export_url = export_url + '&gift_qti=' + encodeURIComponent(gift_qti);
    return_url = return_url + "&url=" + encodeURIComponent(export_url);
    console.log(youtube, topic, cartridge, gift_qti, export_url);
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

$topic = LessonsCartridge::exportTopicMode(U::get($_GET,'topic', false));
$youtube = U::get($_GET,'youtube', false);
if ( $youtube == 'no' ) $youtube = false;
$cartridge = U::get($_GET, 'cartridge', 'thin');
$gift_qti_raw = U::get($_GET, 'gift_qti', 'qti');
if ( isCli() ) {
    global $argv;
    if ( isset($argv) && is_array($argv) && in_array('thick', $argv, true) ) {
        $cartridge = 'thick';
    }
    if ( isset($argv) && is_array($argv) && in_array('lti', $argv, true) ) {
        $gift_qti_raw = 'lti';
    }
    if ( isset($argv) && is_array($argv) && in_array('qti', $argv, true) ) {
        $gift_qti_raw = 'qti';
    }
}
$thick = LessonsLegacyFiles::wantsThickCartridge($cartridge);
$convert_qti = LessonsLegacyGift::wantsGiftQti($gift_qti_raw);

if ( isCli() ) {
    $file_scan = LessonsLegacyFiles::summarize($l, $anchors);
    echo('cartridge='.($thick ? 'thick' : 'thin').' files='.$file_scan['files'].' links='.$file_scan['links'].' scanned='.$file_scan['scanned']."\n");
    foreach ( $file_scan['paths'] as $path ) {
        echo('  '.$path."\n");
    }
    $gift_scan = LessonsLegacyGift::summarize($l, $anchors);
    echo('gift_qti='.($convert_qti ? 'qti' : 'lti').' gift='.$gift_scan['gift'].' found='.$gift_scan['found'].' lti='.$gift_scan['lti'].' scanned='.$gift_scan['scanned']."\n");
    foreach ( $gift_scan['paths'] as $path ) {
        echo('  '.$path."\n");
    }
    foreach ( $gift_scan['warnings'] as $w ) {
        echo('  warning: '.$w."\n");
    }
}

// here we go...
$tsugi_lms = LessonsCartridge::exportFlavor(U::get($_GET,'tsugi_lms', false));
// https://stackoverflow.com/questions/64698935/using-ziparchive-with-php-8-and-temporary-files:wq
$filename = tempnam(sys_get_temp_dir(), $CFG->servicename);
unlink($filename);
$kind = $thick ? 'thick' : 'thin';
if ( $convert_qti ) $kind .= '_qti';
if ( isCli() ) $filename = 'cc_'.$kind.'.zip';
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("Cannot open $filename\n");
}

if ( ! isCli() ) {
    $download = LessonsCartridge::downloadName($l, $tsugi_lms);
    $download = str_replace(array('\\', '"'), '', $download);
    if ( str_ends_with($download, '.imscc') ) {
        $download = substr($download, 0, -strlen('.imscc')).'_'.$kind.'.imscc';
    } else {
        $download .= '_'.$kind;
    }
    header( "Content-Type: application/x-zip" );
    header( "Content-Disposition: attachment; filename=\"".$download."\"" );
}

$cc_dom = new CC(CC::profileForFlavor($tsugi_lms));
if ( ! LessonsCartridge::wantsCanvasExtensions($tsugi_lms) ) {
    $cc_dom->disable_canvas_extensions();
}
if ( LessonsCartridge::usesCanvasCartridge($tsugi_lms) ) {
    $cc_dom->canvas_quiz_wrapper = true;
}
$cc_dom->set_title($CFG->context_title.' import');
$local_files = new LessonsLegacyFiles($thick);
$gift_qti = new LessonsLegacyGift($convert_qti);
$top_module = false;
if ( $tsugi_lms === 'sakai' ) {
    $top_module = $cc_dom->add_module('Modules (import)', '');
}

foreach($l->lessons->modules as $module) {
    if ( isCli() ) echo("title=$module->title\n");
    if ( $anchors && ! in_array($module->anchor, $anchors) ) continue;
    if ( $top_module ) {
        $parent_path = 'Modules (import)';
        $sub_module = $cc_dom->add_sub_module($top_module, $module->title, $parent_path);
    } else {
        $sub_module = $cc_dom->add_module($module->title, '');
    }

    // Check if module uses items array (new format)
    if ( isset($module->items) && is_array($module->items) && count($module->items) > 0 ) {
        // New format: process items array - each item is a flat object with a type field
        foreach($module->items as $item) {
            $item_obj = is_array($item) ? (object)$item : $item;
            process_cc_item($item_obj, $module, $sub_module, $zip, $cc_dom, $youtube, $topic, $local_files, $gift_qti);
        }
        // Skip legacy format if items array was processed
        continue;
    }

    // Get parent path for legacy format items
    $parent_path_legacy = get_module_path($sub_module, $cc_dom);

    // Legacy format: process old arrays (videos, lti, etc.)
    if ( isset($module->videos) ) {
        foreach($module->videos as $video ) {
            $v = is_object($video) ? clone $video : (object) $video;
            if ( ! isset($v->type) ) {
                $v->type = 'video';
            }
            LessonsCartridge::addVideoItem($zip, $cc_dom, $sub_module, $v, $youtube, $parent_path_legacy);
        }
    }

    // Old way
    if ( isset($module->slides) && is_string($module->slides) ) {
        $slide_href = Lessons::expandLink($module->slides);
        $url = U::absolute_url($slide_href);
        $title = 'Slides: '.$module->title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parent_path_legacy);
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
            $slide_href = Lessons::expandLink($slide_href);
            $url = U::absolute_url($slide_href);
            $title = 'Slides: '.$slide_title;
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parent_path_legacy);
        }
    }

    if ( isset($module->assignment) ) {
        $href = Lessons::expandLink($module->assignment);
        $url = U::absolute_url($href);
        $title = 'Assignment: '.$module->title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parent_path_legacy);
    }

    if ( isset($module->solution) ) {
        $href = Lessons::expandLink($module->solution);
        $url = U::absolute_url($href);
        $title = 'Solution: '.$module->title;
        $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parent_path_legacy);
    }

    if ( isset($module->references) ) {
        foreach($module->references as $reference ) {
            $title = 'Reference: '.$reference->title;
            $href = Lessons::expandLink($reference->href);
            $url = U::absolute_url($href);
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parent_path_legacy);
        }
    }

    if ( isset($module->lti) ) {
        foreach($module->lti as $lti ) {
            $gift_qti->addToModule($zip, $cc_dom, $sub_module, $lti, $module, $parent_path_legacy);
        }
    }

    if ( isset($module->discussions) && $topic !=  "none" ) {
        foreach($module->discussions as $discussion ) {
            $title = isset($discussion->title) ? $discussion->title : $module->title;
            $text = isset($discussion->description) ? $discussion->description : $module->description;

			// If there is no LTI involved
            if ( $topic ==  "lms" || ! isset($CFG->tdiscus) ) {
                $cc_dom->zip_add_topic_to_module($zip, $sub_module, $title, $text, $parent_path_legacy);
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
            $resource_link_id = isset($discussion->resource_link_id) ? $discussion->resource_link_id : null;

            if ( $topic == 'lti_grade' ) {
                $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parent_path_legacy);
            } else {
                $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parent_path_legacy);
            }
        }
    }
}

// Att Canvas meta data
$cc_dom->zip_add_canvas_module_meta($zip);

$zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

$zip->close();

if ( isCli() ) {
    foreach ( $gift_qti->warnings() as $w ) {
        echo('  warning: '.$w."\n");
    }
    echo("\nCLI run: Left zip file on $filename\n\n");
    return;
}

// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



