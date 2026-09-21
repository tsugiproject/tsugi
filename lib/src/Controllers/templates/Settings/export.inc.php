<?php
/**
 * Common Cartridge export form (same options as /tsugi/cc).
 *
 * Expected: $l, $counts, $download_url, $localhost_warning,
 * $canvas_return_url (string|false)
 */
$title = isset($l->lessons->title) ? $l->lessons->title : '';
$description = isset($l->lessons->description) ? $l->lessons->description : '';
$modules = isset($l->lessons->modules) && is_array($l->lessons->modules) ? $l->lessons->modules : array();
$resource_count = (int) $counts['resources'];
$assignment_count = (int) $counts['assignments'];
$discussion_count = (int) $counts['discussions'];
$quiz_count = isset($counts['quizzes']) ? (int) $counts['quizzes'] : 0;
$file_count = isset($counts['files']) ? (int) $counts['files'] : 0;
$page_count = isset($counts['pages']) ? (int) $counts['pages'] : 0;
$module_count = (int) $counts['modules'];
$is_canvas_return = is_string($canvas_return_url) && $canvas_return_url !== '';
?>
<?php if ( $localhost_warning ) { ?>
<div class="alert alert-warning" role="alert">
    <strong><?= __('Warning:') ?></strong>
    <?= __('You are running on localhost. Cartridges exported from localhost may have problems importing into cloud-based LMS systems. The URLs in the cartridge will point to localhost, which will not be accessible from cloud LMS instances.') ?>
</div>
<?php } ?>

<p><?= __('Course:') ?> <?= htmlentities($title) ?></p>
<?php if ( $description !== '' ) { ?>
<p><?= htmlentities($description) ?></p>
<?php } ?>

<ul class="nav nav-tabs">
  <li class="active"><a href="#export-allcontent" data-toggle="tab" aria-expanded="true"><?= __('All Content') ?></a></li>
  <li><a href="#export-selectcontent" data-toggle="tab" aria-expanded="false"><?= __('Select Content') ?></a></li>
</ul>

<div id="exportTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="export-allcontent">
<form action="<?= htmlspecialchars($download_url) ?>" method="get">
<?php if ( $is_canvas_return ) { ?>
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php } else { ?>
<p>
<label for="tsugi_lms_select_full"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_full">
<?php foreach ( \Tsugi\UI\LessonsCartridge::exportFlavorLabels() as $value => $label ) { ?>
  <option value="<?= htmlspecialchars($value) ?>"<?= $value === 'generic' ? ' selected' : '' ?>><?= htmlentities(__($label)) ?></option>
<?php } ?>
</select>
</p>
<p><?= __('Generic (CC 1.2) is a standards-only Common Cartridge with no LMS extensions. Generic (CC 1.1) and Moodle are the same Generic content as Common Cartridge 1.1 only. Tsugi and Canvas share Canvas\'s cartridge format so a Canvas export can import into Tsugi later. Sakai\'s cartridge format has significant overlap with Canvas\'s. When exporting to an LMS that is not on this list, use one of the Generic formats to be safe.') ?></p>
<?php } ?>
<p><?= __('You can download all the modules in a single cartridge, or you can download any combination of the modules.') ?></p>
<p><?= __('Modules:') ?> <?= $module_count ?></p>
<p><?= __('Resources:') ?> <?= $resource_count ?></p>
<p><?= __('Files:') ?> <?= $file_count ?></p>
<p><?= __('Pages:') ?> <?= $page_count ?></p>
<p><?= __('Assignments:') ?> <?= $assignment_count ?></p>
<p><?= __('Discussion topics:') ?> <?= $discussion_count ?></p>
<p><?= __('Quizzes:') ?> <?= $quiz_count ?></p>
<p>
<?php if ( $is_canvas_return ) { ?>
<input type="submit" onclick="sendToCanvas(); return false;" class="btn btn-primary" value="<?= htmlspecialchars(__('Import modules')) ?>" />
<?php } else { ?>
<input type="submit" class="btn btn-primary" value="<?= htmlspecialchars(__('Download modules')) ?>" />
<?php } ?>
</p>
</form>
</div>
<div class="tab-pane fade" id="export-selectcontent">
<?php if ( ! $is_canvas_return ) { ?>
<p>
<label for="tsugi_lms_select_partial"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_partial">
<?php foreach ( \Tsugi\UI\LessonsCartridge::exportFlavorLabels() as $value => $label ) { ?>
  <option value="<?= htmlspecialchars($value) ?>"<?= $value === 'generic' ? ' selected' : '' ?>><?= htmlentities(__($label)) ?></option>
<?php } ?>
</select>
</p>
<p><?= __('Generic (CC 1.2) is a standards-only Common Cartridge with no LMS extensions. Generic (CC 1.1) and Moodle are the same Generic content as Common Cartridge 1.1 only. Tsugi and Canvas share Canvas\'s cartridge format so a Canvas export can import into Tsugi later. Sakai\'s cartridge format has significant overlap with Canvas\'s. When exporting to an LMS that is not on this list, use one of the Generic formats to be safe.') ?></p>
<?php } ?>
<p><?= __('Select the modules to include, and download below. You must select at least one module.') ?></p>
<form id="void">
<?php foreach ( $modules as $module ) {
    $anchor = isset($module->anchor) ? (string) $module->anchor : '';
    $mod_title = isset($module->title) ? (string) $module->title : '';
    if ( $anchor === '' ) {
        continue;
    }
    $mc = \Tsugi\UI\LessonsCartridge::moduleCounts($module);
    echo('<label style="display:block;font-weight:normal;">'."\n");
    echo('<input type="checkbox" class="export-module-anchor" name="'
        .htmlspecialchars($anchor).'" value="'.htmlspecialchars($anchor).'"> ');
    echo(htmlentities($mod_title));
    echo("</label>\n");
    $lines = array();
    if ( $mc['resources'] > 0 ) {
        $lines[] = __('Resources in this module:').' '.$mc['resources'];
    }
    if ( $mc['files'] > 0 ) {
        $lines[] = __('Files in this module:').' '.$mc['files'];
    }
    if ( $mc['pages'] > 0 ) {
        $lines[] = __('Pages in this module:').' '.$mc['pages'];
    }
    if ( $mc['assignments'] > 0 ) {
        $lines[] = __('Assignments in this module:').' '.$mc['assignments'];
    }
    if ( $mc['discussions'] > 0 ) {
        $lines[] = __('Discussions in this module:').' '.$mc['discussions'];
    }
    if ( $mc['quizzes'] > 0 ) {
        $lines[] = __('Quizzes in this module:').' '.$mc['quizzes'];
    }
    if ( count($lines) > 0 ) {
        echo("<ul>\n");
        foreach ( $lines as $line ) {
            echo('<li>'.htmlentities($line)."</li>\n");
        }
        echo("</ul>\n");
    }
} ?>
<p>
<?php if ( $is_canvas_return ) { ?>
<input type="submit" value="<?= htmlspecialchars(__('Import selected modules')) ?>" class="btn btn-primary" onclick="sendToCanvasSelected(); return false;"/>
<?php } else { ?>
<input type="submit" value="<?= htmlspecialchars(__('Download selected modules')) ?>" class="btn btn-primary" onclick="myfunc(); return false;"/>
<?php } ?>
</p>
</form>
<form id="real" action="<?= htmlspecialchars($download_url) ?>" method="get">
<input id="tsugi_lms_real" type="hidden" name="tsugi_lms" />
<input id="res" type="hidden" name="anchors" value=""/>
</form>
</div>
</div>
