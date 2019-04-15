<?php

require_once('../config.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Firebase\JWT\JWT;
use \Tsugi\Util\LTI13;

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

// echo("<pre>\n");var_dump($row);echo("</pre>"); return;

// Handle all forms of launch
$LTI = LTIX::requireData();

// Handle all forms of launch
$LTI = LTIX::requireData();

$json = $_SESSION['lti'];
$jwt_claim = LTI13::base_jwt("iss","subj");
$jwt_claim["lti"] = $json;

$endpoint = $CFG->wwwroot . "/api/rpc.php";

$token = $CFG->cookiepad . '::' . session_id();
$token = AesCtr::encrypt($token, $CFG->cookiesecret, 256) ;

$jwt_claim["callback"] = array('endpoint' => U::addSession($endpoint),
    'token' => $token );

$jwt = LTI13::encode_jwt($jwt_claim, $CFG->external_private_key);

// http://localhost:8000/grade/launch
$launch_url = $row['url'];

echo(LTI13::build_jwt_html($launch_url, $jwt, true));

