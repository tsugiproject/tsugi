<?php

$client_values = isset($client_values) && is_array($client_values) ? $client_values : array();
$client_fields = array(
    'title' => 'Title',
    'user_id' => 'User Id',
    'issuer' => 'LTI 1.3 Platform Issuer URL (required)',
    'unlock_code' => 'LTI 1.3: Dynamic Registration Unlock Code (one time use)',
    'client_id' => 'LTI 1.3 Platform Client ID - usually a GUID',
    'oidc_auth_url' => 'LTI 1.3 Platform OIDC Login / Authorization Endpoint URL',
    'keyset_url' => 'LTI 1.3 Platform KeySet URL',
    'token_url' => 'LTI 1.3 Platform Token URL',
    'token_audience' => 'LTI 1.3 Platform Audience (optional)',
    'notes' => 'Notes',
);
$client_action = isset($client_action) ? $client_action : '';
?>
<p>
Issuer is required. Client id and the other fields can wait until the platform assigns them.
A blank client id is stored as NULL, so more than one pending row can share an issuer.
</p>
<form method="post" action="<?= htmlspecialchars($client_action, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<?php foreach ( $client_fields as $client_name => $client_title ) {
    $client_value = isset($client_values[$client_name]) ? (string) $client_values[$client_name] : '';
?>
<div class="form-group">
<label for="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($client_title, ENT_QUOTES, 'UTF-8') ?><br/>
<?php if ( $client_name === 'notes' ) { ?>
<textarea rows="4" cols="80" id="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($client_value, ENT_QUOTES, 'UTF-8') ?></textarea>
<?php } else { ?>
<input type="text" size="80" id="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($client_value, ENT_QUOTES, 'UTF-8') ?>"<?= $client_name === 'issuer' ? ' required' : '' ?>>
<?php } ?>
</label>
</div>
<?php } ?>
<input type="submit" name="doSave" class="btn btn-normal" value="Save">
<a href="<?= htmlspecialchars(isset($client_cancel) ? $client_cancel : 'clients', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default">Cancel</a>
</form>
