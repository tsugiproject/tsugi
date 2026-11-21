<?php

use \Tsugi\Services\Admin\AdminService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) {
    die('Must be admin');
}

if ( count($_POST) > 0 ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect('registration-add') ) return;
}

if ( isset($_POST['doSave']) ) {
    $id = AdminService::saveInboundRegistration(null, $_POST);
    if ( $id ) {
        header('Location: registration-detail?lti_registration_id='.(int) $id);
        return;
    }
    header('Location: registration-add');
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Adding LTI 1.3 Registration
<a class="btn btn-default" href="registrations">Exit</a>
</h1>
<p>
These are the same platform values a key used to store itself.
</p>
<?php require __DIR__ . '/registration-fields.php'; ?>
<?php
$OUTPUT->footer();
