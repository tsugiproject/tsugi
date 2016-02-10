<?php
require_once 'config.php';
require_once 'pdo.php';

use \Tsugi\Core\LTIX;

$session_id = LTIX::setupSession();

// See if we have a custom assignment setting.
if ( ! isset($_POST['custom_assn'] ) ) {
    require("lti/noredir.php");
    return;
} else {
    $url = $_POST['custom_assn'];
    $_SESSION['assn'] = $_POST['custom_assn'];
}

// Send us to where we are going next...
$query = false;
if ( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0) {
    $query = true;
    $url .= '?' . $_SERVER['QUERY_STRING'];
}

$location = addSession($url);
session_write_close();  // To avoid any race conditions...

if ( headers_sent() ) {
    echo('<p><a href="'.$url.'">Click to continue</a></p>');
} else {
    header('Location: '.$location);
}
?>
