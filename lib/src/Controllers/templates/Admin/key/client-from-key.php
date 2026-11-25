<?php

use \Tsugi\Services\Admin\AdminService;
use \Tsugi\Controllers\Tool;

header('Content-Type: application/json; charset=utf-8');

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! Tool::csrfOk() ) {
    echo json_encode(array('ok' => false, 'error' => 'Missing or invalid CSRF token.'));
    return;
}

$create = isset($_POST['doCreate']) && (string) $_POST['doCreate'] === '1';
echo json_encode(AdminService::clientFromKeyRequest($_POST, $create));
