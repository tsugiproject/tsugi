<?php
/**
 * This course's anonymous-lessons switch.
 *
 * Expected: $table_ok, $anonymous_lessons, $save_url
 */
if ( ! isset($table_ok) ) {
    $table_ok = false;
}
if ( ! isset($anonymous_lessons) ) {
    $anonymous_lessons = false;
}
?>
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
        <p class="help-block"><?= __('Anyone can open the lessons in this course, including people who are not logged in and people who are not in the class. The catalog can offer a preview of those lessons. Files a student can open are included. Private files stay closed. Discussions and external tools show Login Required. Quizzes and pages stay closed.') ?></p>
    </div>
    <p>
        <button type="submit" class="btn btn-primary"><?= __('Save') ?></button>
    </p>
</form>
