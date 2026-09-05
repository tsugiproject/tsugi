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
$module_count = (int) $counts['modules'];
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

<?php if ( $canvas_return_url ) { ?>
<p><?= __('Modules:') ?> <?= $module_count ?></p>
<p><?= __('Resources:') ?> <?= $resource_count ?></p>
<p><?= __('Assignments:') ?> <?= $assignment_count ?></p>
<p><?= __('Discussion topics:') ?> <?= $discussion_count ?></p>
<form action="<?= htmlspecialchars($download_url) ?>" method="get">
<input type="hidden" name="tsugi_lms" value="canvas" />
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full"><?= __('How would you like to import discussions/topics?') ?></label>
<select name="topic" id="topic_select_full">
  <option value="none"><?= __('Do not import discussion topics') ?></option>
  <option value="lti_grade"><?= __('Use discussion tool on this server (LTI) with grade passback') ?></option>
  <option value="lms"><?= __('Use the Canvas discussion tool') ?></option>
</select>
</p>
<?php } ?>
<?php if ( $youtube_enabled ) { ?>
<p>
<label for="youtube_select_full"><?= __('Would you like YouTube Tracked URLs?') ?></label>
<select name="youtube" id="youtube_select_full">
  <option value="no"><?= __('No - Launch directly to YouTube') ?></option>
  <option value="track_grade"><?= __('Use LTI launch to track access and send grades') ?></option>
</select>
</p>
<?php } ?>
<p>
<input type="submit" onclick="sendToCanvas(); return false;" class="btn btn-primary" value="<?= htmlspecialchars(__('Import modules')) ?>" />
</p>
</form>
<?php } else { ?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#allcontent" data-toggle="tab" aria-expanded="true"><?= __('All Content') ?></a></li>
  <li><a href="#select" data-toggle="tab" aria-expanded="false"><?= __('Select Content') ?></a></li>
</ul>

<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="allcontent">
<p><?= __('You can download all the modules in a single cartridge, or you can download any combination of the modules.') ?></p>
<p><?= __('Modules:') ?> <?= $module_count ?></p>
<p><?= __('Resources:') ?> <?= $resource_count ?></p>
<p><?= __('Assignments:') ?> <?= $assignment_count ?></p>
<p><?= __('Discussion topics:') ?> <?= $discussion_count ?></p>
<form action="<?= htmlspecialchars($download_url) ?>" method="get">
<p>
<label for="tsugi_lms_select_full"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_full">
  <option value="generic"><?= __('Generic') ?></option>
  <option value="canvas">Canvas</option>
  <option value="sakai">Sakai</option>
</select>
</p>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_full"><?= __('How would you like to import discussions/topics?') ?></label>
<select name="topic" id="topic_select_full">
  <option value="none"><?= __('Do not import discussion topics') ?></option>
  <option value="lti"><?= __('Use discussion tool on this server (LTI)') ?></option>
  <option value="lms"><?= __('Use the LMS Discussion Tool') ?></option>
  <option value="lti_grade"><?= __('Use discussion tool on this server (LTI) with grade passback') ?></option>
</select>
</p>
<?php } ?>
<?php if ( $youtube_enabled ) { ?>
<p>
<label for="youtube_select_full"><?= __('Would you like YouTube Tracked URLs?') ?></label>
<select name="youtube" id="youtube_select_full">
  <option value="no"><?= __('No - Launch directly to YouTube') ?></option>
  <option value="track"><?= __('Use LTI launch to track access') ?></option>
  <option value="track_grade"><?= __('Use LTI launch to track access and send grades') ?></option>
</select>
</p>
<?php } ?>
<p>
<input type="submit" class="btn btn-primary" value="<?= htmlspecialchars(__('Download modules')) ?>" />
</p>
</form>
<?php if ( $youtube_enabled ) { ?>
<p>
<?= __('If you select YouTube tracked URLs, each YouTube URL will be launched via LTI to a YouTube tracking tool on this server so you can get analytics on who watches your YouTube videos through the LMS. Some LMS\'s do not do well with tracked URLs because they treat every LTI link as a gradable link.') ?>
</p>
<?php } ?>
</div>
<div class="tab-pane fade" id="select">
<p><?= __('Select the modules to include, and download below. You must select at least one module.') ?></p>
<form id="void">
<p>
<label for="tsugi_lms_select_partial"><?= __('Choose the LMS that will use this cartridge:') ?></label>
<select name="tsugi_lms" id="tsugi_lms_select_partial">
  <option value="generic"><?= __('Generic') ?></option>
  <option value="canvas">Canvas</option>
  <option value="sakai">Sakai</option>
</select>
</p>
<?php if ( $youtube_enabled ) { ?>
<p>
<label for="youtube_select_partial"><?= __('Would you like YouTube Tracked URLs?') ?></label>
<select name="youtube" id="youtube_select_partial">
  <option value="no"><?= __('No - Launch directly to YouTube') ?></option>
  <option value="track"><?= __('Use LTI launch to track access') ?></option>
  <option value="track_grade"><?= __('Use LTI launch to track access and send grades') ?></option>
</select>
</p>
<?php } ?>
<?php if ( $discussion_count > 0 ) { ?>
<p>
<label for="topic_select_partial"><?= __('How would you like to import discussions/topics?') ?></label>
<select name="topic" id="topic_select_partial">
  <option value="none"><?= __('Do not import discussion topics') ?></option>
  <option value="lti"><?= __('Use discussion tool on this server (LTI)') ?></option>
  <option value="lms"><?= __('Use the LMS Discussion Tool') ?></option>
  <option value="lti_grade"><?= __('Use discussion tool on this server (LTI) with grade passback') ?></option>
</select>
</p>
<?php } ?>
<?php foreach ( $modules as $module ) {
    $mc = \Tsugi\UI\LessonsCartridge::moduleCounts($module);
    echo('<input type="checkbox" name="'.htmlspecialchars($module->anchor).'" value="'.htmlspecialchars($module->anchor).'">'."\n");
    echo(htmlentities($module->title));
    echo("<ul>\n");
    echo("<li>".__('Resources in this module:').' '.$mc['resources']."</li>\n");
    echo("<li>".__('Assignments in this module:').' '.$mc['assignments']."</li>\n");
    echo("<li>".__('Discussions in this module:').' '.$mc['discussions']."</li>\n");
    echo("</ul>\n");
} ?>
<p>
<input type="submit" value="<?= htmlspecialchars(__('Download selected modules')) ?>" class="btn btn-primary" onclick="myfunc(); return false;"/>
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
<?php } ?>
