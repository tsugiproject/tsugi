<?php
require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

use \Tsugi\Core\LTIX;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<h1>Privacy Management</h1>
<p>
To access the data expiry features of
<?= $CFG->servicename ?> you must log in using your tenant owner credentials.
</p>
<script>
if ( inIframe() ) {
    document.write('<'); // So we don't get the session id
    document.write('form action="<?= $CFG->wwwroot ?>/settings/expire" target="_blank">');
} else {
    document.write('<'); // So we don't get the session id
    document.write('form action="<?= $CFG->wwwroot ?>/settings/expire">');
}
</script>
<input type="hidden" name="key_id" value="<?= $LAUNCH->key->id ?>">

<?php if ( isset($LAUNCH->context->id) && $LAUNCH->context->id ) { ?>
<input type="hidden" name="context_id" value="<?= $LAUNCH->context->id ?>">
<?php } ?>

<?php if ( isset($LAUNCH->for_user->id) && $LAUNCH->for_user->id) { ?>
<input type="hidden" name="for_user_id" value="<?= $LAUNCH->for_user-id ?>">
<?php } ?>
<br/>
<input type="submit" value="Continue to Data Expiry" class="btn btn-primary">
</form>
<?php
$OUTPUT->footer();
