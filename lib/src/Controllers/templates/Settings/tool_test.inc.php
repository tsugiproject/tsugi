<?php
/**
 * Send a test launch for one tool on this course.
 *
 * Expected: $tools_url, $test_url, $registrationId, $launch_choices, $launch, $launch_html
 */
if ( ! isset($launch_choices) || ! is_array($launch_choices) ) {
    $launch_choices = array();
}
if ( ! isset($launch) || ! is_array($launch) ) {
    $launch = null;
}
if ( ! isset($launch_html) || ! is_string($launch_html) ) {
    $launch_html = '';
}
$registrationId = isset($registrationId) ? (int) $registrationId : 0;
$testSep = (isset($test_url) && is_string($test_url) && strpos($test_url, '?') !== false) ? '&' : '?';
$launchRole = is_array($launch) && isset($launch['role']) ? (string) $launch['role'] : 'Instructor';
$launchMessage = is_array($launch) && isset($launch['message_type']) ? (string) $launch['message_type'] : '';
?>
<p>
    <a href="<?= htmlspecialchars((string) $tools_url) ?>"><?= __('Back to tools') ?></a>
</p>
<?php if ( $launch === null ) { ?>
<p><?= __('This tool has no launches to test.') ?></p>
<?php } else { ?>
<h2><?= htmlspecialchars($launch['title']) ?></h2>
<p><?= htmlspecialchars(sprintf(__('Test launch: %s'), $launch['label'])) ?></p>
<p>
<?php foreach ( array('Instructor', 'Learner') as $roleName ) {
    $href = $test_url.$testSep.'registration_id='.$registrationId.'&message='.rawurlencode($launchMessage).'&role='.rawurlencode($roleName);
    $current = $roleName === $launchRole;
    ?>
    <?php if ( $current ) { ?>
    <span class="btn btn-primary btn-sm"><?= htmlspecialchars(__($roleName)) ?></span>
    <?php } else { ?>
    <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars(__($roleName)) ?></a>
    <?php } ?>
<?php } ?>
</p>
<?php if ( count($launch_choices) > 1 ) { ?>
<p>
<?php foreach ( $launch_choices as $choice ) {
    $href = $test_url.$testSep.'registration_id='.$registrationId.'&message='.rawurlencode((string) $choice['type']).'&role='.rawurlencode($launchRole);
    $current = $choice['type'] === $launch['message_type'];
    ?>
    <?php if ( $current ) { ?>
    <span class="btn btn-primary btn-sm"><?= htmlspecialchars(__($choice['label'])) ?></span>
    <?php } else { ?>
    <a class="btn btn-default btn-sm" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars(__($choice['label'])) ?></a>
    <?php } ?>
<?php } ?>
</p>
<?php } ?>
<?php if ( $launch['ready'] ) { ?>
<p><?= htmlspecialchars(sprintf(__('This sends a %s launch to %s. The other launch types will use this same test.'), $launch['label'], $launch['endpoint'])) ?></p>
<?= $launch_html ?>
<?php } else { ?>
<p><?= htmlspecialchars(sprintf(__('A %s test launch is not available yet.'), $launch['label'])) ?></p>
<?php } ?>
<?php } ?>
