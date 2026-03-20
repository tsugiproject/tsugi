<?php

require_once('../config.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI13;
use \Tsugi\Services\ExternalTools\RemoteTsugiLaunchProvider;

use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;

$rest = U::rest_path();
// echo("<pre>\n");var_dump($rest);echo("</pre>"); return;

LTIX::getConnection();
$row = $PDOX->rowDie("SELECT * FROM {$CFG->dbprefix}lti_external WHERE endpoint = :endpoint",
    array(':endpoint' => $rest->controller) );

if ( $row === false ) {
    http_response_code(404);
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    echo("<h2>Page not found.</h2>\n");
    $OUTPUT->footer();
    return;
}

$privkey = U::get($row,'privkey');
$pubkey = U::get($row,'pubkey');
if ( U::strlen($privkey) < 1 || U::strlen($pubkey) < 1 ) {
    http_response_code(404);
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    echo("<h2>Public Key not found.</h2>\n");
    $OUTPUT->footer();
    return;
}

// echo("<pre>\n");var_dump($row);echo("</pre>"); return;

// Handle all forms of launch
$LTI = LTIX::requireData();

$json = $_SESSION[TSUGI_SESSION_LTI];

$endpoint = $CFG->wwwroot . "/api/rpc.php";
$token = $CFG->cookiepad . '::' . session_id();
$token = AesCtr::encrypt($token, $CFG->cookiesecret, 256) ;

$kid = LTIX::getKidForKey($pubkey);
$provider = new RemoteTsugiLaunchProvider();
$launch = $provider->build($json, $row['url'], U::addSession($endpoint), $token);
$jwt = LTI13::encode_jwt($launch->jwt_claim, $privkey, $kid);

echo(LTI13::build_jwt_html($launch->launch_url, $jwt, $launch->debug, $launch->extra));
