<?php

use \Tsugi\Services\Admin\AdminService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) {
    die('Must be admin');
}

$key_admin = $CFG->wwwroot.'/admin/key/';

if ( count($_POST) > 0 ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect($key_admin.'registration-add') ) return;
}

$registration_values = array();
if ( isset($_POST['doSave']) ) {
    $id = AdminService::saveInboundRegistration(null, $_POST);
    if ( $id ) {
        header('Location: '.$key_admin.'registration-detail?lti_registration_id='.(int) $id);
        return;
    }
    $registration_values = $_POST;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Adding LTI 1.3 Registration
<a class="btn btn-default" href="<?= $key_admin ?>registrations">Exit</a>
</h1>
<p>
These are the same platform values a key used to store itself.
</p>
<?php
$registration_action = $key_admin.'registration-add';
$registration_cancel = $key_admin.'registrations';
require __DIR__ . '/registration-fields.php';
?>
<?php
$OUTPUT->footer();
