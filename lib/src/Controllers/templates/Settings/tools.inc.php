<?php
/**
 * Tools assigned to this course.
 *
 * Expected: $course_tools, $save_url (list, also the delete post), $add_url, $test_url
 */
if ( ! isset($course_tools) || ! is_array($course_tools) ) {
    $course_tools = array();
}
?>
<div style="overflow:auto;margin-bottom:10px;">
<?php if ( isset($add_url) && is_string($add_url) && $add_url !== '' ) { ?>
    <a class="btn btn-primary" style="float:right;" href="<?= htmlspecialchars($add_url) ?>"><?= __('+ Add Tool') ?></a>
<?php } ?>
</div>
<?php if ( count($course_tools) === 0 ) { ?>
<p><?= __('No external tools are assigned to this course yet.') ?></p>
<?php } else { ?>
<ul class="list-unstyled">
<?php foreach ( $course_tools as $tool ) {
    $title = isset($tool['title']) ? (string) $tool['title'] : '';
    $owned = ! empty($tool['course_owned']);
    $registrationId = isset($tool['registration_id']) ? (int) $tool['registration_id'] : 0;
    $editSep = (isset($add_url) && is_string($add_url) && strpos($add_url, '?') !== false) ? '&' : '?';
    $editUrl = (isset($add_url) ? $add_url : $save_url).$editSep.'registration_id='.$registrationId;
    ?>
    <li style="margin-bottom: 8px;">
        <?= htmlspecialchars($title) ?>
        <?php
        $otherLaunchUrls = isset($tool['other_launch_urls']) ? (int) $tool['other_launch_urls'] : 0;
        $launchNote = \Tsugi\Services\Outbound\Lti11CourseTool::launchUrlNote($otherLaunchUrls);
        if ( $launchNote !== '' ) { ?>
        <div class="text-muted"><?= htmlspecialchars($launchNote) ?></div>
        <?php } ?>
        <?php if ( ! empty($tool['can_test']) && $registrationId > 0 && isset($test_url) && is_string($test_url) && $test_url !== '' ) {
            $testSep = strpos($test_url, '?') === false ? '?' : '&';
            $toolTestUrl = $test_url.$testSep.'registration_id='.$registrationId;
            ?>
        <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($toolTestUrl) ?>"><?= __('Test') ?></a>
        <?php } ?>
        <?php if ( $owned && $registrationId > 0 ) { ?>
        <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($editUrl) ?>"><?= __('Edit') ?></a>
        <form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;" data-confirm="<?= htmlspecialchars(__('Delete this tool from the course?')) ?>" onsubmit="return confirm(this.getAttribute('data-confirm'));">
            <?= \Tsugi\Controllers\Settings::csrfField() ?>
            <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
            <input type="hidden" name="tool_action" value="delete">
            <button type="submit" class="btn btn-danger btn-sm"><?= __('Delete') ?></button>
        </form>
        <?php } else { ?>
        <span class="text-muted"><?= __('Shared with this course') ?></span>
        <?php } ?>
    </li>
<?php } ?>
</ul>
<?php } ?>
