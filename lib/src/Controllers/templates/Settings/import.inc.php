<?php
/**
 * Common Cartridge import form, or Select Content after a zip is stashed.
 *
 * Expected: $import_url, $upload_url, $upload_limit_label (optional),
 * $pending_token (string), $pending_name, $pending_title, $pending_modules (list)
 */
if ( ! isset($pending_token) || ! is_string($pending_token) ) {
    $pending_token = '';
}
if ( ! isset($pending_name) || ! is_string($pending_name) ) {
    $pending_name = '';
}
if ( ! isset($pending_title) || ! is_string($pending_title) ) {
    $pending_title = '';
}
if ( ! isset($pending_modules) || ! is_array($pending_modules) ) {
    $pending_modules = array();
}

if ( $pending_token !== '' ) {
    $module_count = count($pending_modules);
    ?>
<p><?= __('Uploaded cartridge:') ?> <?= htmlentities($pending_name !== '' ? $pending_name : __('cartridge')) ?></p>
<?php if ( $pending_title !== '' ) { ?>
<p><?= __('Course:') ?> <?= htmlentities($pending_title) ?></p>
<?php } ?>
<p><?= __('The file is held until you import or cancel (up to one hour).') ?></p>
<p><?= __('Modules:') ?> <?= (int) $module_count ?></p>

<fieldset style="margin:1em 0;border:0;padding:0;">
  <legend style="font-size:inherit;border:0;margin-bottom:0.5em;"><?= __('When importing:') ?></legend>
  <label style="display:block;font-weight:normal;">
    <input type="radio" name="cc_replace_choice" value="add" checked>
    <?= __('Add this import to the course\'s content') ?>
  </label>
  <label style="display:block;font-weight:normal;">
    <input type="radio" name="cc_replace_choice" value="replace">
    <?= __('Delete this course\'s content before importing') ?>
  </label>
  <div id="import-replace-warning" class="alert alert-danger" role="alert" style="display:none;margin-top:16px;padding:18px 20px;border-width:3px;">
    <p style="font-size:1.75em;font-weight:bold;margin:0 0 12px 0;line-height:1.2;"><?= __('Warning: this cannot be undone') ?></p>
    <p style="font-size:1.15em;margin-bottom:10px;"><?= __('If you import with this option selected, this course will be emptied first:') ?></p>
    <ul style="font-size:1.1em;margin-bottom:12px;">
      <li><?= __('All pages, files, and quizzes') ?></li>
      <li><?= __('All resource links (assignments and LTI items)') ?></li>
      <li><?= __('All gradebook results — student scores in this course will be deleted') ?></li>
      <li><?= __('The Lessons outline, which is reset to empty and then filled from the cartridge') ?></li>
    </ul>
    <p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;"><?= __('File content still used by another course is kept. Everything else listed above is permanently removed.') ?></p>
    <?php
    $import_site_domain = \Tsugi\Controllers\Settings::importSiteDomain();
    $import_course_title = \Tsugi\Controllers\Settings::importReplaceCourseTitle();
    $import_member_count = \Tsugi\Controllers\Settings::importReplaceMemberCount();
    $import_member_label = $import_member_count === null
        ? __('this course')
        : (string) $import_member_count;
    if ( $import_member_count === null ) {
        $import_member_sentence = __('This course has an unknown number of members.');
    } else if ( $import_member_count === 1 ) {
        $import_member_sentence = __('This course has 1 member.');
    } else {
        $import_member_sentence = sprintf(__('This course has %s members.'), number_format($import_member_count));
    }
    ?>
    <p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;"><?= htmlspecialchars($import_member_sentence) ?></p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="cc_replace_domain_input"><?= sprintf(__('Type the domain name of this site (%s).'), htmlspecialchars($import_site_domain !== '' ? $import_site_domain : __('this site'))) ?></label>
    </p>
    <p style="margin-bottom:12px;">
      <input type="text" id="cc_replace_domain_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="cc_replace_title_input"><?= sprintf(__('Type the title of this course (%s).'), htmlspecialchars($import_course_title !== '' ? $import_course_title : __('this course'))) ?></label>
    </p>
    <p style="margin-bottom:12px;">
      <input type="text" id="cc_replace_title_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="cc_replace_members_input"><?= sprintf(__('Type the number of members in this course (%s).'), htmlspecialchars($import_member_label)) ?></label>
    </p>
    <p style="margin-bottom:0;">
      <input type="text" id="cc_replace_members_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="numeric" aria-describedby="cc_replace_confirm_help">
    </p>
    <p id="cc_replace_confirm_help" class="help-block" style="margin-top:8px;margin-bottom:0;"><?= __('Import stays disabled until the domain, course title, and member count all match exactly.') ?></p>
  </div>
</fieldset>

<ul class="nav nav-tabs">
  <li class="active"><a href="#import-allcontent" data-toggle="tab" aria-expanded="true"><?= __('All Content') ?></a></li>
  <li><a href="#import-selectcontent" data-toggle="tab" aria-expanded="false"><?= __('Select Content') ?></a></li>
</ul>

<div id="importTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="import-allcontent">
<p><?= __('You can import all the modules, or you can import any combination of the modules.') ?></p>
<form method="post" action="<?= htmlspecialchars($import_url) ?>" onsubmit="return confirmImportReplace(this.querySelector('.import-submit'));">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="cc_pending" value="<?= htmlspecialchars($pending_token) ?>">
    <input type="hidden" name="cc_import_action" value="all">
    <input type="hidden" name="cc_replace" value="add">
    <input type="hidden" name="cc_replace_domain" value="">
    <input type="hidden" name="cc_replace_title" value="">
    <input type="hidden" name="cc_replace_members" value="">
    <p>
        <button type="submit" class="btn btn-primary import-submit"><?= __('Import all modules') ?></button>
    </p>
</form>
  </div>
  <div class="tab-pane fade" id="import-selectcontent">
<p><?= __('Select the modules to include, and import below. You must select at least one module.') ?></p>
<form id="void">
<?php foreach ( $pending_modules as $mod ) {
    $key = isset($mod['key']) ? (string) $mod['key'] : '';
    $mod_title = isset($mod['title']) ? (string) $mod['title'] : '';
    if ( $key === '' ) {
        continue;
    }
    $mc = isset($mod['counts']) && is_array($mod['counts']) ? $mod['counts'] : array();
    echo('<label style="display:block;font-weight:normal;">'."\n");
    echo('<input type="checkbox" class="import-module-key" name="'
        .htmlspecialchars($key).'" value="'.htmlspecialchars($key).'"> ');
    echo(htmlentities($mod_title));
    echo("</label>\n");
    $lines = array();
    foreach ( array(
        'resources' => __('Resources in this module:'),
        'files' => __('Files in this module:'),
        'pages' => __('Pages in this module:'),
        'assignments' => __('Assignments in this module:'),
        'discussions' => __('Discussions in this module:'),
        'quizzes' => __('Quizzes in this module:'),
    ) as $ck => $label ) {
        $n = isset($mc[$ck]) ? (int) $mc[$ck] : 0;
        if ( $n > 0 ) {
            $lines[] = $label.' '.$n;
        }
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
<button type="submit" class="btn btn-primary import-submit" onclick="return importSelectedModules(this);"><?= __('Import selected modules') ?></button>
</p>
</form>
<form id="import-selected-real" method="post" action="<?= htmlspecialchars($import_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="cc_pending" value="<?= htmlspecialchars($pending_token) ?>">
    <input type="hidden" name="cc_import_action" value="selected">
    <input type="hidden" name="cc_replace" value="add">
    <input type="hidden" name="cc_replace_domain" value="">
    <input type="hidden" name="cc_replace_title" value="">
    <input type="hidden" name="cc_replace_members" value="">
    <input id="import_modules_real" type="hidden" name="modules" value="">
</form>
  </div>
</div>
<form method="post" action="<?= htmlspecialchars($import_url) ?>" style="margin-top:1em;">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="cc_pending" value="<?= htmlspecialchars($pending_token) ?>">
    <input type="hidden" name="cc_import_action" value="cancel">
    <button type="submit" class="btn btn-default"><?= __('Cancel upload') ?></button>
</form>
    <?php
    return;
}

?>
<p><?= __('Upload an IMS Common Cartridge (.imscc or .zip). Files, pages, quizzes, web links, LTI links, and discussion topics are added to this course. The Lessons outline is updated from the cartridge organization.') ?></p>
<p><?= __('After upload you can import all modules or select which modules to include.') ?></p>
<p><?= __('The same cartridge imported again is treated as duplicates and skipped. After you edit a local copy, importing the original again creates a new object.') ?></p>
<?php
if ( ! isset($upload_url) || ! is_string($upload_url) || $upload_url === '' ) {
    $upload_url = $import_url;
}
if ( ! isset($upload_limit_label) || ! is_string($upload_limit_label) || $upload_limit_label === '' ) {
    $upload_limit_label = \Tsugi\Controllers\Settings::CARTRIDGE_UPLOAD_MAX;
}
?>
<p><?= sprintf(__('This form posts to a dedicated uploader that allows files up to %s. Other pages on this site keep the smaller PHP post limit.'), htmlspecialchars($upload_limit_label)) ?></p>
<form method="post" action="<?= htmlspecialchars($upload_url) ?>" enctype="multipart/form-data">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="return" value="<?= htmlspecialchars($import_url) ?>">
    <input type="hidden" name="context" value="<?= (int) \Tsugi\Util\U::currentContextId() ?>">
    <p>
        <label for="cartridge_file"><?= __('Cartridge file') ?></label><br>
        <input type="file" name="cartridge" id="cartridge_file" accept=".imscc,.zip,application/zip">
    </p>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Upload cartridge') ?></button>
    </p>
</form>
<p class="text-muted"><?= __('A log of each run is stored in the cc_import, cc_import_log, and cc_object tables.') ?></p>
