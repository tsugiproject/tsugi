<?php
use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../settings_util.php");
require_once("expire_util.php");

session_start();

if ( ! U::get($_SESSION,'id') ) {
    $login_return = U::reconstruct_query($CFG->wwwroot . '/settings/expire');
    $_SESSION['login_return'] = $login_return;
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

\Tsugi\Core\LTIX::getConnection();

$tenant_count = get_count_table('lti_key');
$context_count = get_count_table('lti_context');
$user_count = get_count_table('lti_user');

// $tenant_days = isset($CFG->expire_tenant_days) ? $CFG->expire_tenant_days : 1000;
// $tenant_days = U::get($_GET,'tenant_days',$tenant_days);
// $context_days = isset($CFG->expire_context_days) ? $CFG->expire_context_days : 600;
// $context_days = U::get($_GET,'context_days',$context_days);
// $user_days = isset($CFG->expire_user_days) ? $CFG->expire_user_days : 600;
// $user_days = U::get($_GET,'user_days',$user_days);
$pii_days = isset($CFG->expire_pii_days) ? $CFG->expire_pii_days : 180;
$pii_days = U::get($_GET,'pii_days',$pii_days);

// $user_expire =  get_expirable_records('lti_user', $user_days);
// $context_expire =  get_expirable_records('lti_context', $context_days);
// $tenant_expire =  get_expirable_records('lti_key', $tenant_days);
$pii_expire =  get_pii_count($pii_days);

$check = sanity_check_days();
if ( is_string($check) ) U::flashError($check);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;" role="dialog" aria-modal="true" aria-label="Read only content">
   <div id="iframe-spinner" role="status" aria-live="polite">
   <img src="<?= htmlspecialchars($OUTPUT->getSpinnerUrl(), ENT_QUOTES, 'UTF-8') ?>" alt="" role="presentation"><br/>
   <span class="sr-only">Loading content</span>
   </div>
   <iframe name="iframe-frame" style="height:600px" id="iframe-frame" title="Data expiry content viewer"
    onload="document.getElementById('iframe-spinner').style.display='none';">
   </iframe>
</div>
<h1>Manage Data Expiry</h1>
<p>
  <a href="<?= htmlspecialchars($CFG->wwwroot, ENT_QUOTES, 'UTF-8') ?>/settings" class="btn btn-default" aria-label="My Settings">My Settings</a>
</p>
<form>
<ul>
<li>User count: <?= (int)$user_count ?>  <br/>
<ul>
<li>
<label for="pii_days">Users with PII and no activity in</label>
<input type="text" name="pii_days" id="pii_days" size=5 class="auto_days" value="<?= htmlspecialchars($pii_days, ENT_QUOTES, 'UTF-8') ?>"> days:
<?= (int)$pii_expire ?>
<?php if ( $pii_expire > 0 ) { ?>
  <br/>
  <a href="pii-detail?pii_days=<?= urlencode($pii_days) ?>" class="auto_expire btn btn-xs btn-default" aria-label="View PII expiry details">View</a>
  <a href="#" title="Expire PII" class="auto_expire btn btn-xs btn-danger" role="button" aria-label="Expire PII older than <?= (int)$pii_days ?> days"
  onclick="showModalIframeUrl(this.title, 'iframe-dialog', 'iframe-frame', 'pii-expire?pii_days=<?= urlencode($pii_days) ?>', _TSUGI.spinnerUrl, true); return false;" >
  Expire PII &gt; <?= (int)$pii_days ?> Days
  </a>
<?php } ?>
</li>
</ul>
</ul>
<input type="submit" value="Update">
</form>
<?php
$OUTPUT->footerStart();
?>
<script>
$('.auto_days').on('change', function() {
  $(".auto_expire").hide();
  $(this).closest('form').submit();
});
</script>
<?php
$OUTPUT->footerEnd();

