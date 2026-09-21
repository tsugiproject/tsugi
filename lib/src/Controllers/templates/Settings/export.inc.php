<?php
/**
 * Common Cartridge export form (same options as /tsugi/cc).
 *
 * Expected: $l, $counts, $download_url, $youtube_enabled, $localhost_warning,
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
<p><?= __('You can download all the modules in a single cartridge, or you can download any combination of the modules.') ?></p>
<p><?= __('Modules:') ?> <?= $module_count ?></p>
<p><?= __('Resources:') ?> <?= $resource_count ?></p>
<p><?= __('Files:') ?> <?= $file_count ?></p>
<p><?= __('Pages:') ?> <?= $page_count ?></p>
<p><?= __('Assignments:') ?> <?= $assignment_count ?></p>
<p><?= __('Discussion topics:') ?> <?= $discussion_count ?></p>
<p><?= __('Quizzes:') ?> <?= $quiz_count ?></p>
<form action="<?= htmlspecialchars($download_url) ?>" method="get">
<?php if ( $is_canvas_return ) { ?>
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php } else { ?>
<p>
<label for="tsugi_lms_select_full"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_full">
  <option value="generic"><?= __('Generic') ?></option>
  <option value="canvas">Canvas</option>
  <option value="tsugi">Tsugi</option>
  <option value="sakai">Sakai</option>
</select>
</p>
<p><?= __('Generic is a standards-only Common Cartridge 1.2 with no LMS extensions. Tsugi and Canvas share Canvas\'s cartridge format so a Canvas export can import into Tsugi later.') ?></p>
<?php } ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full"><?= __('How would you like to import discussions/topics?') ?></label>
<select name="topic" id="topic_select_full">
  <option value="none"><?= __('Do not import discussion topics') ?></option>
<?php if ( ! $is_canvas_return ) { ?>
  <option value="lti"><?= __('Use discussion tool on this server (LTI)') ?></option>
<?php } ?>
  <option value="lms"><?= $is_canvas_return ? __('Use the Canvas discussion tool') : __('Use the LMS Discussion Tool') ?></option>
  <option value="lti_grade"><?= __('Use discussion tool on this server (LTI) with grade passback') ?></option>
</select>
</p>
<?php } ?>
<?php if ( $youtube_enabled ) { ?>
<p>
<label for="youtube_select_full"><?= __('Would you like YouTube Tracked URLs?') ?></label>
<select name="youtube" id="youtube_select_full">
  <option value="no"><?= __('No - Launch directly to YouTube') ?></option>
<?php if ( ! $is_canvas_return ) { ?>
  <option value="track"><?= __('Use LTI launch to track access') ?></option>
<?php } ?>
  <option value="track_grade"><?= __('Use LTI launch to track access and send grades') ?></option>
</select>
</p>
<?php } ?>
<p>
<?php if ( $is_canvas_return ) { ?>
<input type="submit" onclick="sendToCanvas(); return false;" class="btn btn-primary" value="<?= htmlspecialchars(__('Import modules')) ?>" />
<?php } else { ?>
<input type="submit" class="btn btn-primary" value="<?= htmlspecialchars(__('Download modules')) ?>" />
<?php } ?>
</p>
</form>
<?php if ( $youtube_enabled && ! $is_canvas_return ) { ?>
<p>
<?= __('If you select YouTube tracked URLs, each YouTube URL will be launched via LTI to a YouTube tracking tool on this server so you can get analytics on who watches your YouTube videos through the LMS. Some LMS\'s do not do well with tracked URLs because they treat every LTI link as a gradable link.') ?>
</p>
<?php } ?>
</div>
<div class="tab-pane fade" id="export-selectcontent">
<p><?= __('Select the modules to include, and download below. You must select at least one module.') ?></p>
<form id="void">
<?php if ( $is_canvas_return ) { ?>
<input type="hidden" name="tsugi_lms" id="tsugi_lms_select_partial" value="canvas" />
<?php } else { ?>
<p>
<label for="tsugi_lms_select_partial"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_partial">
  <option value="generic"><?= __('Generic') ?></option>
  <option value="canvas">Canvas</option>
  <option value="tsugi">Tsugi</option>
  <option value="sakai">Sakai</option>
</select>
</p>
<p><?= __('Generic is a standards-only Common Cartridge 1.2 with no LMS extensions. Tsugi and Canvas share Canvas\'s cartridge format so a Canvas export can import into Tsugi later.') ?></p>
<?php } ?>
<?php if ( $youtube_enabled ) { ?>
<p>
<label for="youtube_select_partial"><?= __('Would you like YouTube Tracked URLs?') ?></label>
<select name="youtube" id="youtube_select_partial">
  <option value="no"><?= __('No - Launch directly to YouTube') ?></option>
<?php if ( ! $is_canvas_return ) { ?>
  <option value="track"><?= __('Use LTI launch to track access') ?></option>
<?php } ?>
  <option value="track_grade"><?= __('Use LTI launch to track access and send grades') ?></option>
</select>
</p>
<?php } ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_partial"><?= __('How would you like to import discussions/topics?') ?></label>
<select name="topic" id="topic_select_partial">
  <option value="none"><?= __('Do not import discussion topics') ?></option>
<?php if ( ! $is_canvas_return ) { ?>
  <option value="lti"><?= __('Use discussion tool on this server (LTI)') ?></option>
<?php } ?>
  <option value="lms"><?= $is_canvas_return ? __('Use the Canvas discussion tool') : __('Use the LMS Discussion Tool') ?></option>
  <option value="lti_grade"><?= __('Use discussion tool on this server (LTI) with grade passback') ?></option>
</select>
</p>
<?php } ?>
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
<input id="youtube_real" type="hidden" name="youtube"/>
<input id="tsugi_lms_real" type="hidden" name="tsugi_lms" />
<input id="topic_real" type="hidden" name="topic" />
<input id="res" type="hidden" name="anchors" value=""/>
</form>
</div>
</div>
