<?php
/**
 * This course's title and anonymous-lessons switch.
 *
 * Expected: $table_ok, $anonymous_lessons, $course_title, $save_url
 */
if ( ! isset($table_ok) ) {
    $table_ok = false;
}
if ( ! isset($anonymous_lessons) ) {
    $anonymous_lessons = false;
}
if ( ! isset($course_title) || ! is_string($course_title) ) {
    $course_title = '';
}
?>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <div class="form-group" style="max-width:40em;">
        <label for="course_title"><?= __('Course title') ?></label>
        <input type="text" class="form-control" id="course_title" name="course_title" required maxlength="<?= (int) \Tsugi\Controllers\Settings::COURSE_TITLE_MAX ?>" value="<?= htmlspecialchars($course_title, ENT_QUOTES, 'UTF-8') ?>">
        <p class="help-block"><?= __('Shown on this course, the lessons page, and the course list.') ?></p>
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Save title') ?></button>
    </p>
</form>
<p><?= __('This option applies only to this course.') ?></p>
<?php if ( ! $table_ok ) { ?>
<p><?= __('The course column is missing. Run Upgrade Database in the Administration console, then return here.') ?></p>
<?php return; } ?>
<form method="post" action="<?= htmlspecialchars($save_url) ?>">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <div class="checkbox">
        <label for="anonymous_lessons">
            <input type="checkbox" name="anonymous_lessons" id="anonymous_lessons" value="1"<?= $anonymous_lessons ? ' checked' : '' ?>>
            <?= htmlspecialchars(__('Allow anonymous viewing of lessons')) ?>
        </label>
        <p class="help-block"><?= __('Anyone can open the lessons in this course, including people who are not logged in and people who are not in the class. The catalog can offer a preview of those lessons. Files a student can open are included. Private files stay closed. Published pages are included. Unpublished pages stay closed. Discussions and external tools show Login Required. Quizzes stay closed.') ?></p>
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Save') ?></button>
    </p>
</form>
