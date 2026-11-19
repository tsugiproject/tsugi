<?php

$OUTPUT->header();
$OUTPUT->bodyStart(false);
$OUTPUT->topNav();
echo \Tsugi\Controllers\Admin::qaNotice();

$beaconUrl = (string) ($beaconUrl ?? '');
$beaconJson = (string) ($beaconJson ?? '');
$beaconComment = (string) ($beaconComment ?? '');
$beaconDefaultComment = (string) ($beaconDefaultComment ?? '');
$beaconError = (string) ($beaconError ?? '');
$beaconSkip = (string) ($beaconSkip ?? '');
$beaconSent = (isset($beaconSent) && is_array($beaconSent)) ? $beaconSent : null;
?>
<h1>Send Beacon</h1>
<p>This posts one report. It does not run a database upgrade.</p>
<p>URL: <code><?= htmlspecialchars($beaconUrl, ENT_QUOTES, 'UTF-8') ?></code></p>
<?php if ( $beaconSkip !== '' ) { ?>
<div class="alert alert-info" style="margin: 10px;">
Automatic reports are off (<?= htmlspecialchars($beaconSkip, ENT_QUOTES, 'UTF-8') ?>).
Sending from this page still posts to the URL above.
</div>
<?php } ?>
<?php if ( $beaconError !== '' ) { ?>
<div class="alert alert-danger" style="margin: 10px;"><?= htmlspecialchars($beaconError, ENT_QUOTES, 'UTF-8') ?></div>
<?php } ?>
<?php if ( is_array($beaconSent) ) { ?>
<div class="alert alert-<?= $beaconSent['ok'] ? 'success' : 'danger' ?>" style="margin: 10px;">
<?php if ( $beaconSent['ok'] ) { ?>
Posted (<?= htmlspecialchars((string) $beaconSent['detail'], ENT_QUOTES, 'UTF-8') ?>).
<?php } else { ?>
Not posted: <?= htmlspecialchars((string) $beaconSent['detail'], ENT_QUOTES, 'UTF-8') ?>.
<?php } ?>
</div>
<?php } ?>
<p><?= is_array($beaconSent) ? 'This is the report that was sent.' : 'This is the report a blank comment would send.' ?></p>
<pre><?= htmlspecialchars($beaconJson, ENT_QUOTES, 'UTF-8') ?></pre>
<form method="post" action="">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<p>
<label for="beacon-comment">Comment</label><br/>
<textarea id="beacon-comment" name="comment" rows="3" cols="80"><?= htmlspecialchars($beaconComment, ENT_QUOTES, 'UTF-8') ?></textarea>
</p>
<p>Leave the comment blank to send <code><?= htmlspecialchars($beaconDefaultComment, ENT_QUOTES, 'UTF-8') ?></code>.</p>
<p><button type="submit">Send Beacon</button></p>
</form>
<p><a href="<?= htmlspecialchars(rtrim((string) $CFG->wwwroot, '/').'/admin/', ENT_QUOTES, 'UTF-8') ?>">Back to the administration console</a></p>
<?php
$OUTPUT->footer();
