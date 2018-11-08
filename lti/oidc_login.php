<?php

use \Tsugi\Util\U;

require_once "../config.php";

$redirect = "http://localhost:8080/imsblis/lti13/oidc_auth";

if ( ! isset($_GET['login_hint']) ) {
    die('Missing login_hint');
}

$redirect = U::add_url_parm($redirect, "login_hint", $_GET['login_hint']);
$redirect = U::add_url_parm($redirect, "state", "42");

header("Location: ".$redirect);
