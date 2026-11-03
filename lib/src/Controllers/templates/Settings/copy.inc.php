<?php
/**
 * Pick a course to copy into the current manifest course.
 *
 * Expected: $copy_url, $copy_sources (list of id, title, kind)
 */
if ( ! isset($copy_sources) || ! is_array($copy_sources) ) {
    $copy_sources = array();
}
if ( ! isset($copy_url) || ! is_string($copy_url) ) {
    $copy_url = '';
}
?>
<p><?= __('Copy content into this course from the site course, or from another manifest course you own.') ?></p>
<p><?= __('Choose a course, then pick the modules to copy. You can add them to this course, or delete this course\'s content first.') ?></p>
<?php if ( count($copy_sources) < 1 ) { ?>
<p><?= __('There is no site course on this server, and you do not own another manifest course to copy from.') ?></p>
<?php } else { ?>
<form id="copy-source-form" method="post" action="<?= htmlspecialchars($copy_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <p>
        <label for="cc_copy_source"><?= __('Course to copy from') ?></label><br>
        <select name="cc_copy_source" id="cc_copy_source" class="form-control" required>
            <option value=""><?= __('Choose a course') ?></option>
            <?php foreach ( $copy_sources as $src ) {
                $id = isset($src['id']) ? (string) $src['id'] : '';
                $title = isset($src['title']) ? (string) $src['title'] : '';
                $kind = isset($src['kind']) ? (string) $src['kind'] : '';
                if ( $id === '' ) {
                    continue;
                }
                $label = $title !== '' ? $title : $id;
                if ( $kind === 'file' ) {
                    $label .= ' ('.__('site course').')';
                }
                echo '<option value="'.htmlspecialchars($id).'">'.htmlentities($label)."</option>\n";
            } ?>
        </select>
    </p>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Choose course') ?></button>
    </p>
</form>
<?php } ?>
