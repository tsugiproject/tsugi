<?php
/**
 * How student view works, and the four course-local test students.
 *
 * Expected: $rows (slug, name, user_id), $here
 */
$h = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<h1><?= __('Student View') ?></h1>
<p><?= __('Student View lets you open this course as a learner.') ?></p>
<p><?= __('There are four test students: Nancy Drew, Sue Bruno, Jane Ford, and Scott Land. Each account belongs to this course only. Enter creates that user the first time, enrolls them as a learner, and switches your login to that account. You then see the course the way they see it.') ?></p>
<p><?= __('A green frame around the page means you are in student view. The bottom of the page says who you are logged in as. Leave Student View switches you back to your instructor account and returns you here.') ?></p>
<p><?= __('Reset Student Data deletes that test student from this course. Their membership is removed, and the rows linked to that user — grades, discussion posts, and the rest of their course data — go with the account. The other test students are left alone. The next time you enter as that student, they start with no data.') ?></p>
<p><?= __('These are not real logins. They cannot sign in on their own.') ?></p>
<table class="table">
<thead>
<tr>
<th><?= __('Student') ?></th>
<th><?= __('Status') ?></th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ( $rows as $row ) {
    if ( ! is_array($row) ) {
        continue;
    }
    $slug = (string) ($row['slug'] ?? '');
    $name = (string) ($row['name'] ?? '');
    $exists = (int) ($row['user_id'] ?? 0) > 0;
    $confirm = sprintf(__('Reset %s? This deletes that test student and their work in this course.'), $name);
    ?>
<tr>
<td><?= $h($name) ?></td>
<td><?= $exists ? $h(__('In this course')) : $h(__('Not created yet')) ?></td>
<td>
<form method="post" action="<?= $h($here) ?>" style="display:inline">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<input type="hidden" name="action" value="enter">
<input type="hidden" name="slug" value="<?= $h($slug) ?>">
<button type="submit" class="btn btn-primary btn-sm"><?= __('Enter') ?></button>
</form>
<?php if ( $exists ) { ?>
<form method="post" action="<?= $h($here) ?>" style="display:inline;margin-left:0.4em" onsubmit="return confirm(<?= $h(json_encode($confirm)) ?>);">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<input type="hidden" name="action" value="reset">
<input type="hidden" name="slug" value="<?= $h($slug) ?>">
<button type="submit" class="btn btn-danger btn-sm"><?= __('Reset Student Data') ?></button>
</form>
<?php } ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
