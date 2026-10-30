<?php
/**
 * Read-only registration JSON.
 *
 * The log is the body that arrived. The registration document is what is stored now.
 *
 * Expected: $registration_json (string), $registration_logs (array)
 */
$registration_json = isset($registration_json) ? (string) $registration_json : '';
$registration_logs = isset($registration_logs) && is_array($registration_logs) ? $registration_logs : array();

$phaseLabel = function ($phase) {
    $labels = array(
        'registration_request' => __('Registration request'),
        'registration_response' => __('Registration response'),
        'openid_configuration' => __('OpenID configuration'),
        'registration' => __('Registration'),
    );
    $phase = (string) $phase;
    return isset($labels[$phase]) ? $labels[$phase] : $phase;
};
$showJson = function ($text) {
    $text = (string) $text;
    $decoded = json_decode($text);
    if ( json_last_error() === JSON_ERROR_NONE && $decoded !== null ) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ( is_string($pretty) ) {
            return $pretty;
        }
    }
    return $text;
};
?>
<h3><?= __('Registration JSON') ?></h3>
<?php if ( count($registration_logs) === 0 && $registration_json === '' ) { ?>
<p class="text-muted"><?= __('None') ?></p>
<?php } else { ?>
<p class="text-muted"><?= __('Read only.') ?></p>
<?php foreach ( $registration_logs as $log ) {
    if ( ! is_array($log) ) {
        continue;
    }
    $text = isset($log['payload_text']) ? (string) $log['payload_text'] : '';
    if ( $text === '' ) {
        continue;
    }
    $summary = $phaseLabel(isset($log['phase']) ? $log['phase'] : '');
    if ( isset($log['direction']) && (string) $log['direction'] === 'outbound' ) {
        $summary .= ' ('.__('sent').')';
    }
    ?>
<details>
    <summary><?= htmlspecialchars($summary) ?></summary>
    <pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($showJson($text)) ?></pre>
</details>
<?php } ?>
<?php if ( $registration_json !== '' ) { ?>
<details>
    <summary><?= __('Registration document') ?></summary>
    <pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($registration_json) ?></pre>
</details>
<?php } ?>
<?php } ?>
