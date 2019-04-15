<?php
// https://github.com/tsugiproject/trophy
require_once "../config.php";
# require_once "key.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Firebase\JWT\JWT;
use \Tsugi\Util\LTI13;

use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;

// Handle all forms of launch
$LTI = LTIX::requireData();

$json = $_SESSION['lti'];
$jwt_claim = LTI13::base_jwt("iss","subj");
$jwt_claim["lti"] = $json;

$endpoint = $CFG->wwwroot . "/api/rpc.php";

$token = $CFG->cookiepad . '::' . session_id();

$token = AesCtr::encrypt($token, $CFG->cookiesecret, 256) ;

$callback = array('endpoint' => addSession($endpoint),
    'token' => $token );

// $jwt = LTI13::encode_jwt($jwt_claim, $PRIVATE_KEY);
$jwt = LTI13::encode_jwt($jwt_claim, $CFG->external_private_key);

$jwt_claim["callback"] = $callback;

// $jwt = LTI13::encode_jwt($jwt_claim, $PRIVATE_KEY);

$jwt = LTI13::encode_jwt($jwt_claim, $CFG->external_private_key);

echo(LTI13::build_jwt_html("http://localhost:8000/grade/launch", $jwt, true));
