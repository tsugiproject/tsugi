<?php

use \Tsugi\Services\Lessons\LessonsService;
use \Tsugi\Services\Lessons\LessonsCartridge;
use \Tsugi\Services\Lessons\LessonsLegacyExport;
use \Tsugi\Services\Lessons\LessonsLegacyFiles;
use \Tsugi\Services\Lessons\LessonsLegacyGift;
use \Tsugi\Util\U;

require_once __DIR__ . '/legacy_form.php';
require_once __DIR__ . '/../config.php';

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new LessonsService($CFG->lessons);

// Check if this is a remote import from Canvas
if ( isset($_POST['ext_content_return_url']) ) {
    $return_url = $_POST['ext_content_return_url'];
    $return_url = U::add_url_parm($return_url, 'return_type', 'file');
    $return_url = U::add_url_parm($return_url, 'text', $CFG->servicename);

    $export_url = $CFG->wwwroot . '/cc/export?tsugi_lms=canvas';

    $return_url_normal = U::add_url_parm($return_url, 'url', $export_url);

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
        $resources = LessonsService::getUrlResources($module);
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
    CcExportForm::echoFilesPreview($file_scan);
    $gift_scan = LessonsLegacyGift::summarize($l);
    CcExportForm::echoGiftPreview($gift_scan);
?>
<p>
<form action="export">
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php CcExportForm::echoCartridgeSelect('cartridge_select_full'); ?>
<?php CcExportForm::echoGiftQtiSelect('gift_qti_select_full'); ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full">How would you like to export discussions/topics?</label>
<select name="topic" id="topic_select_full">
  <option value="lms" selected>Use the Canvas discussion tool</option>
  <option value="none">Do not export discussion topics</option>
  <!-- <option value="lti">Use discussion tool on this server (LTI)</option> -->
  <option value="lti_grade">Use discussion tool on this server (LTI) with grade passback</option>
</select>
</p>
<?php } ?>
<p>
<input type="submit" onclick= "sendToCanvas(); return false;" class="btn btn-primary" value="Import modules" />
</p>
</form>
<script>
function sendToCanvas() {
    let topic = $("#topic_select_full").val() || 'lms';
    let cartridge = $("#cartridge_select_full").val();
    let gift_qti = $("#gift_qti_select_full").val();
	let return_url = "<?= $return_url ?>";
	let export_url = "<?= $CFG->wwwroot.'/cc/export?tsugi_lms=canvas' ?>";
	export_url = export_url + '&topic=' + topic;
	export_url = export_url + '&cartridge=' + encodeURIComponent(cartridge);
	export_url = export_url + '&gift_qti=' + encodeURIComponent(gift_qti);
    return_url = return_url + "&url=" + encodeURIComponent(export_url);
    console.log(topic, cartridge, gift_qti, export_url);
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
$cartridge = U::get($_GET, 'cartridge', 'thick');
$gift_qti_raw = U::get($_GET, 'gift_qti', 'qti');
if ( isCli() ) {
    global $argv;
    if ( isset($argv) && is_array($argv) && in_array('thin', $argv, true) ) {
        $cartridge = 'thin';
    }
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

LessonsLegacyExport::writeZip($l, $zip, array(
    'tsugi_lms' => $tsugi_lms,
    'topic' => $topic,
    'anchors' => $anchors,
    'thick' => $thick,
    'gift_qti' => $convert_qti,
    'title' => $CFG->context_title.' import',
    'cli' => isCli(),
));

$zip->close();

if ( isCli() ) {
    echo("\nCLI run: Left zip file on $filename\n\n");
    return;
}

// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



