<?php
/**
 * Common Cartridge import form.
 *
 * Expected: $import_url, $upload_url, $upload_limit_label (optional)
 */
?>
<p><?= __('Upload an IMS Common Cartridge (.imscc or .zip). Files, pages, quizzes, web links, LTI links, and discussion topics are added to this course. The Lessons outline is updated from the cartridge organization.') ?></p>
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
        <button type="submit" class="btn btn-primary"><?= __('Import cartridge') ?></button>
    </p>
</form>
<p class="text-muted"><?= __('A log of each run is stored in the cc_import, cc_import_log, and cc_object tables.') ?></p>
