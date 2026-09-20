<?php
/**
 * Common Cartridge import form.
 *
 * Expected: $import_url
 */
?>
<p><?= __('Upload an IMS Common Cartridge (.imscc or .zip). Files, pages, quizzes, web links, LTI links, and discussion topics are added to this course. The Lessons outline is updated from the cartridge organization.') ?></p>
<p><?= __('The same cartridge imported again is treated as duplicates and skipped. After you edit a local copy, importing the original again creates a new object.') ?></p>
<form method="post" action="<?= htmlspecialchars($import_url) ?>" enctype="multipart/form-data">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <p>
        <label for="cartridge_file"><?= __('Cartridge file') ?></label><br>
        <input type="file" name="cartridge" id="cartridge_file" accept=".imscc,.zip,application/zip">
    </p>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Import cartridge') ?></button>
    </p>
</form>
<p class="text-muted"><?= __('A log of each run is stored in the cc_import, cc_import_log, and cc_object tables.') ?></p>
