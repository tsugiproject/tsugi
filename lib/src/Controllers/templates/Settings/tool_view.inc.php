<?php
/**
 * Read-only LTI 1.3 registration for this course.
 *
 * Expected: $tool_view, $tools_url, $save_url, $course_owned
 */
if ( ! isset($tool_view) || ! is_array($tool_view) ) {
    $tool_view = array();
}
$title = isset($tool_view['title']) ? (string) $tool_view['title'] : '';
$registrationId = isset($tool_view['registration_id']) ? (int) $tool_view['registration_id'] : 0;
$messages = isset($tool_view['messages']) && is_array($tool_view['messages']) ? $tool_view['messages'] : array();
$deployments = isset($tool_view['deployments']) && is_array($tool_view['deployments']) ? $tool_view['deployments'] : array();
$redirects = isset($tool_view['redirect_uris']) && is_array($tool_view['redirect_uris']) ? $tool_view['redirect_uris'] : array();
$json = isset($tool_view['registration_json']) ? (string) $tool_view['registration_json'] : '';

$field = function ($value) {
    $value = (string) $value;
    if ( $value === '' ) {
        return '<span class="text-muted">'.htmlspecialchars(__('None')).'</span>';
    }
    return htmlspecialchars($value);
};
$list = function ($items) {
    if ( ! is_array($items) || count($items) === 0 ) {
        return '<span class="text-muted">'.htmlspecialchars(__('None')).'</span>';
    }
    $html = '<ul class="list-unstyled" style="margin:0;">';
    foreach ( $items as $item ) {
        $html .= '<li style="word-break:break-all;">'.htmlspecialchars((string) $item).'</li>';
    }
    $html .= '</ul>';
    return $html;
};
?>
<p><a href="<?= htmlspecialchars(isset($tools_url) ? (string) $tools_url : '') ?>"><?= __('Back to course tools') ?></a></p>
<h2 style="margin-top:0;"><?= htmlspecialchars($title) ?> <span class="text-muted">(LTI 1.3)</span></h2>
<dl>
    <dt><?= __('Client ID') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field(isset($tool_view['client_id']) ? $tool_view['client_id'] : '') ?></dd>
    <dt><?= __('Login URL') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field(isset($tool_view['oidc_login_url']) ? $tool_view['oidc_login_url'] : '') ?></dd>
    <dt><?= __('Keyset URL') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field(isset($tool_view['jwks_url']) ? $tool_view['jwks_url'] : '') ?></dd>
    <dt><?= __('Launch URL') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field(isset($tool_view['launch_url']) ? $tool_view['launch_url'] : '') ?></dd>
    <dt><?= __('Redirect URIs') ?></dt>
    <dd style="margin-bottom:8px;"><?= $list($redirects) ?></dd>
</dl>
<?php foreach ( $deployments as $deployment ) {
    $deploymentId = isset($deployment['deployment_id']) ? (string) $deployment['deployment_id'] : '';
    $claims = isset($deployment['allowed_claims']) && is_array($deployment['allowed_claims']) ? $deployment['allowed_claims'] : array();
    $scopes = isset($deployment['allowed_scopes']) && is_array($deployment['allowed_scopes']) ? $deployment['allowed_scopes'] : array();
    $enabled = isset($deployment['enabled_placements']) && is_array($deployment['enabled_placements']) ? $deployment['enabled_placements'] : array();
    ?>
<h3><?= __('Deployment') ?></h3>
<dl>
    <dt><?= __('Deployment ID') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field($deploymentId) ?></dd>
    <dt><?= __('Allowed claims') ?></dt>
    <dd style="margin-bottom:8px;"><?= $list($claims) ?></dd>
    <dt><?= __('Allowed scopes') ?></dt>
    <dd style="margin-bottom:8px;"><?= $list($scopes) ?></dd>
    <dt><?= __('Enabled placements') ?></dt>
    <dd style="margin-bottom:8px;"><?= $list($enabled) ?></dd>
</dl>
<?php } ?>
<h3><?= __('Messages') ?></h3>
<?php if ( count($messages) === 0 ) { ?>
<p class="text-muted"><?= __('The tool did not declare any messages.') ?></p>
<?php } else { ?>
<?php foreach ( $messages as $message ) {
    $placements = isset($message['placements']) && is_array($message['placements']) ? $message['placements'] : array();
    ?>
<dl>
    <dt><?= __('Type') ?></dt>
    <dd style="margin-bottom:8px;"><?= $field(isset($message['message_type']) ? $message['message_type'] : '') ?></dd>
    <dt><?= __('Label') ?></dt>
    <dd style="margin-bottom:8px;"><?= $field(isset($message['label']) ? $message['label'] : '') ?></dd>
    <dt><?= __('Target link') ?></dt>
    <dd style="word-break:break-all;margin-bottom:8px;"><?= $field(isset($message['target_link_uri']) ? $message['target_link_uri'] : '') ?></dd>
    <dt><?= __('Placements') ?></dt>
    <dd style="margin-bottom:8px;"><?= $list($placements) ?></dd>
</dl>
<?php } ?>
<?php } ?>
<?php if ( $json !== '' ) { ?>
<details>
    <summary><?= __('Registration document') ?></summary>
    <pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($json) ?></pre>
</details>
<?php } ?>
<p style="margin-top:16px;">
<?php if ( $registrationId > 0 && isset($test_url) && is_string($test_url) && $test_url !== '' ) {
    $testSep = strpos($test_url, '?') === false ? '?' : '&';
    ?>
<a class="btn btn-default" href="<?= htmlspecialchars($test_url.$testSep.'registration_id='.$registrationId) ?>"><?= __('Test') ?></a>
<?php } ?>
<?php if ( ! empty($course_owned) && $registrationId > 0 && isset($save_url) && is_string($save_url) && $save_url !== '' ) { ?>
<form method="post" action="<?= htmlspecialchars($save_url) ?>" style="display:inline;" data-confirm="<?= htmlspecialchars(__('Delete this tool from the course?')) ?>" onsubmit="return confirm(this.getAttribute('data-confirm'));">
    <?= \Tsugi\Controllers\Settings::csrfField() ?>
    <input type="hidden" name="registration_id" value="<?= $registrationId ?>">
    <input type="hidden" name="tool_action" value="delete">
    <button type="submit" class="btn btn-danger"><?= __('Delete') ?></button>
</form>
<?php } else { ?>
<span class="text-muted"><?= __('Shared with this course') ?></span>
<?php } ?>
</p>
