<?php

use \Tsugi\Services\Admin\AdminService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) {
    die('Must be admin');
}

$key_admin = $CFG->wwwroot.'/admin/key/';

if ( count($_POST) > 0 ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect($key_admin.'client-add') ) return;
}

$client_values = array();
if ( isset($_POST['doSave']) ) {
    $id = AdminService::saveInboundClient(null, $_POST);
    if ( $id ) {
        header('Location: '.$key_admin.'client-detail?lti_client_id='.(int) $id);
        return;
    }
    $client_values = $_POST;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Adding LTI 1.3 Client
<a class="btn btn-default" href="<?= $key_admin ?>clients">Exit</a>
</h1>
<p>
These are the same platform values a key can still store itself.
Dynamic registration continues to write those values on the key.
</p>
<?php
$client_action = $key_admin.'client-add';
$client_cancel = $key_admin.'clients';
require __DIR__ . '/client-fields.php';
?>
<?php
$OUTPUT->footer();
