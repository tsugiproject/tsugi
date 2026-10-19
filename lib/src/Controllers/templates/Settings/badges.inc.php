<?php
/**
 * Course badge authoring.
 *
 * Expected: $badges (list of manifest badge arrays), $edit_index (int|null),
 * $edit (badge array|null), $public_images, $assignment_choices,
 * $save_url, $badges_url, $files_public_url, $linkedin_add (bool)
 */
use Tsugi\Services\Badges\CourseBadges;

$editing = is_array($edit);
$edit_title = $editing && isset($edit['title']) ? (string) $edit['title'] : '';
$edit_image = $editing && isset($edit['image']) ? (string) $edit['image'] : '';
$edit_threshold = $editing ? 100 : 80;
if ( $editing && isset($edit['threshold']) && is_numeric($edit['threshold']) ) {
    $edit_threshold = (int) round(((float) $edit['threshold']) * 100);
}
$edit_completion = $editing && ! empty($edit['completion']);
$edit_assignments = array();
if ( $editing && isset($edit['assignments']) && is_array($edit['assignments']) ) {
    foreach ( $edit['assignments'] as $rlid ) {
        $edit_assignments[] = (string) $rlid;
    }
}
$edit_canonical = CourseBadges::canonicalPublicPath($edit_image);
?>
<p><?= __('Badge pictures live in Files → Public. The file name, without .png, is the badge code. Use letters, numbers, and underscores, like hello_world.png.') ?></p>
<p><a href="<?= htmlspecialchars($files_public_url) ?>"><?= __('Open Public files') ?></a></p>
<?php
$unsafe_images = array();
foreach ( $public_images as $png ) {
    if ( empty($png['safe']) ) {
        $unsafe_images[] = $png['path'];
    }
}
?>
<?php if ( count($public_images) < 1 ) { ?>
<p class="alert alert-info"><?= __('Upload a PNG into Public before you add a badge.') ?></p>
<?php } elseif ( count($unsafe_images) > 0 ) { ?>
<p class="alert alert-info"><?= __('These Public PNGs are not listed because the file name must use only letters, numbers, and underscores:') ?> <?= htmlspecialchars(implode(', ', $unsafe_images)) ?></p>
<?php } ?>

<?php if ( count($badges) < 1 ) { ?>
<p><?= __('No badges yet.') ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= __('Badge') ?></th>
            <th><?= __('Image') ?></th>
            <th><?= __('Percent') ?></th>
            <th><?= __('Assignments') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $badges as $i => $badge ) {
        $title = isset($badge['title']) ? (string) $badge['title'] : '';
        $image = isset($badge['image']) ? (string) $badge['image'] : '';
        $thumb = CourseBadges::displayUrl($image);
        $percent = (isset($badge['threshold']) && is_numeric($badge['threshold']))
            ? (int) round(((float) $badge['threshold']) * 100)
            : 100;
        $count = (isset($badge['assignments']) && is_array($badge['assignments'])) ? count($badge['assignments']) : 0;
        $edit_url = $badges_url . (strpos($badges_url, '?') === false ? '?' : '&') . 'badge=' . (int) $i;
        ?>
        <tr>
            <td><?= htmlspecialchars($title) ?></td>
            <td>
                <?php if ( $thumb !== '' ) { ?>
                <img src="<?= htmlspecialchars($thumb) ?>" alt="" style="width: 3rem;">
                <?php } ?>
                <?= htmlspecialchars($image) ?>
            </td>
            <td><?= (int) $percent ?></td>
            <td><?= (int) $count ?></td>
            <td>
                <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($edit_url) ?>"><?= __('Edit') ?></a>
                <?php if ( $i > 0 ) { ?>
                <form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;">
                    <?= \Tsugi\Controllers\Settings::csrfField() ?>
                    <input type="hidden" name="badge_action" value="move">
                    <input type="hidden" name="move_dir" value="up">
                    <input type="hidden" name="badge_index" value="<?= (int) $i ?>">
                    <button type="submit" class="btn btn-default btn-sm"><?= __('Up') ?></button>
                </form>
                <?php } ?>
                <?php if ( $i < count($badges) - 1 ) { ?>
                <form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;">
                    <?= \Tsugi\Controllers\Settings::csrfField() ?>
                    <input type="hidden" name="badge_action" value="move">
                    <input type="hidden" name="move_dir" value="down">
                    <input type="hidden" name="badge_index" value="<?= (int) $i ?>">
                    <button type="submit" class="btn btn-default btn-sm"><?= __('Down') ?></button>
                </form>
                <?php } ?>
                <form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this badge?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= \Tsugi\Controllers\Settings::csrfField() ?>
                    <input type="hidden" name="badge_action" value="delete">
                    <input type="hidden" name="badge_index" value="<?= (int) $i ?>">
                    <button type="submit" class="btn btn-default btn-sm"><?= __('Delete') ?></button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<h2><?= $editing ? __('Edit badge') : __('Add badge') ?></h2>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="badge_action" value="save">
    <input type="hidden" name="badge_index" value="<?= $editing ? (int) $edit_index : '' ?>">
    <div class="form-group">
        <label for="badge-title"><?= __('Title') ?></label>
        <input type="text" class="form-control" id="badge-title" name="title" required value="<?= htmlspecialchars($edit_title, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-group">
        <label for="badge-image"><?= __('Image in Public') ?></label>
        <select class="form-control" id="badge-image" name="image" required>
            <option value=""><?= __('Choose a PNG') ?></option>
            <?php
            $saw_edit = false;
            foreach ( $public_images as $png ) {
                if ( ! $png['safe'] ) {
                    continue;
                }
                if ( $edit_canonical !== null && $png['path'] === $edit_canonical ) {
                    $saw_edit = true;
                }
                $selected = ($edit_canonical !== null && $png['path'] === $edit_canonical) ? ' selected' : '';
                ?>
            <option value="<?= htmlspecialchars($png['path'], ENT_QUOTES, 'UTF-8') ?>"<?= $selected ?>><?= htmlspecialchars($png['path']) ?></option>
            <?php } ?>
            <?php if ( $editing && $edit_canonical !== null && ! $saw_edit ) { ?>
            <option value="<?= htmlspecialchars($edit_canonical, ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars($edit_canonical) ?> <?= __('(missing)') ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="badge-threshold"><?= __('Percent required') ?></label>
        <input type="number" class="form-control" id="badge-threshold" name="threshold_percent" min="0" max="100" step="1" required value="<?= (int) $edit_threshold ?>" style="max-width: 8em;">
        <p class="help-block"><?= __('With no assignments, the badge is awarded as soon as the learner opens Badges. Otherwise this is the percent of those assignments they need.') ?></p>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="completion" value="1"<?= $edit_completion ? ' checked' : '' ?>>
            <?= __('Completion badge') ?>
        </label>
        <?php if ( ! empty($linkedin_add) ) { ?>
        <p class="help-block"><?= __('Only completion badges have the option to add the credential to LinkedIn.') ?></p>
        <?php } ?>
    </div>
    <fieldset class="form-group">
        <legend style="font-size: 1em; font-weight: 700; border: 0; margin-bottom: 0.4em;"><?= __('Assignments that count') ?></legend>
        <?php if ( count($assignment_choices) < 1 ) { ?>
        <p class="help-block"><?= __('No published graded items yet. Publish a quiz or another graded item, then come back.') ?></p>
        <?php } else { ?>
        <?php
        $assignment_n = 0;
        foreach ( $assignment_choices as $rlid => $label ) {
            $checked = in_array((string) $rlid, $edit_assignments, true) ? ' checked' : '';
            $input_id = 'badge-assignment-' . $assignment_n;
            $assignment_n++;
            ?>
        <div class="checkbox" style="margin-top: 0; margin-bottom: 0.2em;">
            <label for="<?= htmlspecialchars($input_id, ENT_QUOTES, 'UTF-8') ?>">
                <input type="checkbox" id="<?= htmlspecialchars($input_id, ENT_QUOTES, 'UTF-8') ?>" name="assignments[]" value="<?= htmlspecialchars((string) $rlid, ENT_QUOTES, 'UTF-8') ?>"<?= $checked ?>>
                <?= htmlspecialchars($label) ?>
            </label>
        </div>
        <?php } ?>
        <p class="help-block"><?= __('These are the published items that record a grade, including quizzes. Leave them unchecked for a badge with no assignments.') ?></p>
        <?php } ?>
    </fieldset>
    <p>
        <button type="submit" class="btn btn-primary"><?= $editing ? __('Save badge') : __('Add badge') ?></button>
        <?php if ( $editing ) { ?>
        <a href="<?= htmlspecialchars($badges_url) ?>" class="btn btn-default"><?= __('Cancel') ?></a>
        <?php } ?>
    </p>
</form>
