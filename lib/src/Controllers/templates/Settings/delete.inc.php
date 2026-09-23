<?php
/**
 * Delete the current course after the same typed confirmation as import wipe.
 *
 * Expected: $delete_url (string), $delete_blocked (bool)
 */
if ( ! isset($delete_url) || ! is_string($delete_url) ) {
    $delete_url = '';
}
if ( ! isset($delete_blocked) ) {
    $delete_blocked = false;
}

$delete_site_domain = \Tsugi\Controllers\Settings::importSiteDomain();
$delete_course_title = \Tsugi\Controllers\Settings::importReplaceCourseTitle();
$delete_member_count = \Tsugi\Controllers\Settings::importReplaceMemberCount();
$delete_member_label = $delete_member_count === null
    ? __('this course')
    : (string) $delete_member_count;
if ( $delete_member_count === null ) {
    $delete_member_sentence = __('This course has an unknown number of members.');
} else if ( $delete_member_count === 1 ) {
    $delete_member_sentence = __('This course has 1 member.');
} else {
    $delete_member_sentence = sprintf(__('This course has %s members.'), number_format($delete_member_count));
}
?>
<p><?= __('Delete this course from the site. When it is gone, you are sent back to the app home.') ?></p>
<?php if ( $delete_blocked ) { ?>
<div class="alert alert-warning" role="alert">
    <p style="margin-bottom:0;"><?= __('The site home course cannot be deleted.') ?></p>
</div>
<?php } else { ?>
<div class="alert alert-danger" role="alert" style="margin-top:16px;padding:18px 20px;border-width:3px;">
    <p style="font-size:1.75em;font-weight:bold;margin:0 0 12px 0;line-height:1.2;"><?= __('Warning: this cannot be undone') ?></p>
    <p style="font-size:1.15em;margin-bottom:10px;"><?= __('Deleting this course permanently removes:') ?></p>
    <ul style="font-size:1.1em;margin-bottom:12px;">
      <li><?= __('All pages, files, and quizzes') ?></li>
      <li><?= __('All resource links (assignments, discussions, and LTI items)') ?></li>
      <li><?= __('All gradebook results — student scores in this course will be deleted') ?></li>
      <li><?= __('Lessons, images, membership, and the course itself') ?></li>
      <li><?= __('Published badges stay. Their public URL keeps working after the course is gone.') ?></li>
    </ul>
    <p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;"><?= __('File bytes still used by another course are kept. This course and everything else listed above are permanently removed.') ?></p>
    <p style="font-size:1.15em;font-weight:bold;margin-bottom:12px;"><?= htmlspecialchars($delete_member_sentence) ?></p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="delete_domain_input"><?= sprintf(__('Type the domain name of this site (%s).'), htmlspecialchars($delete_site_domain !== '' ? $delete_site_domain : __('this site'))) ?></label>
    </p>
    <p style="margin-bottom:12px;">
      <input type="text" id="delete_domain_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="delete_title_input"><?= sprintf(__('Type the title of this course (%s).'), htmlspecialchars($delete_course_title !== '' ? $delete_course_title : __('this course'))) ?></label>
    </p>
    <p style="margin-bottom:12px;">
      <input type="text" id="delete_title_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </p>
    <p style="font-size:1.1em;margin-bottom:8px;">
      <label for="delete_members_input"><?= sprintf(__('Type the number of members in this course (%s).'), htmlspecialchars($delete_member_label)) ?></label>
    </p>
    <p style="margin-bottom:0;">
      <input type="text" id="delete_members_input" class="form-control" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="numeric" aria-describedby="delete_confirm_help">
    </p>
    <p id="delete_confirm_help" class="help-block" style="margin-top:8px;margin-bottom:0;"><?= __('Delete stays disabled until the domain, course title, and member count all match exactly.') ?></p>
</div>
<form method="post" action="<?= htmlspecialchars($delete_url) ?>" onsubmit="return confirmDeleteCourse(this.querySelector('.delete-submit'));">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="delete_domain" value="">
    <input type="hidden" name="delete_title" value="">
    <input type="hidden" name="delete_members" value="">
    <p>
        <button type="submit" class="btn btn-danger delete-submit" disabled><?= __('Delete course') ?></button>
    </p>
</form>
<?php } ?>
