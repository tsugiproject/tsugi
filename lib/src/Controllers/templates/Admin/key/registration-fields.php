<?php

$registration_values = isset($registration_values) && is_array($registration_values) ? $registration_values : array();
$registration_fields = array(
    'title' => 'Title',
    'issuer' => 'LTI 1.3 Platform Issuer URL (required)',
    'unlock_code' => 'LTI 1.3: Dynamic Registration Unlock Code (one time use)',
    'client_id' => 'LTI 1.3 Platform Client ID - usually a GUID',
    'oidc_auth_url' => 'LTI 1.3 Platform OIDC Login / Authorization Endpoint URL',
    'keyset_url' => 'LTI 1.3 Platform KeySet URL',
    'token_url' => 'LTI 1.3 Platform Token URL',
    'token_audience' => 'LTI 1.3 Platform Audience (optional)',
    'notes' => 'Notes',
);
$registration_action = isset($registration_action) ? $registration_action : '';
?>
<p>
Issuer is required. Client id and the other fields can wait until the platform assigns them.
</p>
<form method="post" action="<?= htmlspecialchars($registration_action, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<?php foreach ( $registration_fields as $registration_name => $registration_title ) {
    $registration_value = isset($registration_values[$registration_name]) ? (string) $registration_values[$registration_name] : '';
?>
<div class="form-group">
<label for="<?= htmlspecialchars($registration_name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($registration_title, ENT_QUOTES, 'UTF-8') ?><br/>
<?php if ( $registration_name === 'notes' ) { ?>
<textarea rows="4" cols="80" id="<?= htmlspecialchars($registration_name, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($registration_name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($registration_value, ENT_QUOTES, 'UTF-8') ?></textarea>
<?php } else { ?>
<input type="text" size="80" id="<?= htmlspecialchars($registration_name, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($registration_name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($registration_value, ENT_QUOTES, 'UTF-8') ?>"<?= $registration_name === 'issuer' ? ' required' : '' ?>>
<?php } ?>
</label>
</div>
<?php } ?>
<input type="submit" name="doSave" class="btn btn-normal" value="Save">
<a href="<?= htmlspecialchars(isset($registration_cancel) ? $registration_cancel : 'registrations', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default">Cancel</a>
</form>
