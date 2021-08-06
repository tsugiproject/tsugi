<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;
use \Tsugi\Crypt\AesCtr;

require_once "../config.php";

$verified = false;
$state = U::get($_POST, 'state');
$verifydata = U::get($_POST, 'postverify');

if ( ! $state ) {
    LTIX::abort_with_error_log('Missing state');
}

try {
    $decoded = JWT::decode($state, $CFG->cookiesecret, array('HS256'));
} catch(Exception $e) {
    LTIX::abort_with_error_log('Unable to decode state value');
}

if ( ! is_object($decoded) ) {
    LTIX::abort_with_error_log('Incorrect state value');
}

if ( ! isset($decoded->time) ) {
    LTIX::abort_with_error_log('No time in state');
}

$delta = abs($decoded->time - time());
if ( $delta > 60 ) {
    LTIX::abort_with_error_log('Bad time value');
}

$sid = substr("log-".md5($state), 0, 20);
error_log(" =============== oidc_launch ===================== $sid");
session_id($sid);
session_start();
$session_state = U::get($_SESSION, 'state');
if ( $session_state != $state ) {
    LTIX::abort_with_error_log('Could not find state in session');
}
$session_password = U::get($_SESSION, 'password');
if ( ! $session_password ) {
    $session_password = uniqid();
    $_SESSION['password'] = $session_password;
}

// Get the id_token - only take on the first post
if ( $verifydata === null ) {
    $id_token = U::get($_POST, 'id_token');
    $_SESSION['id_token'] = $id_token;
} else {
    $id_token = U::get($_SESSION, 'id_token');
}

if ( ! $id_token ) {
    LTIX::abort_with_error_log('Missing id_token');
}

$signature_check = false;
if ( ! isset($decoded->signature) ) {
    LTIX::abort_with_error_log('No signature in state');
}

$signature = \Tsugi\Core\LTIX::getBrowserSignature();

if ( $signature != $decoded->signature ) {
    $raw = \Tsugi\Core\LTIX::getBrowserSignatureRaw();
    error_log('oidc_launch '.$raw);
    LTIX::abort_with_error_log("Invalid state signature value");
}
$signature_check = true;

$url_claim = "https://purl.imsglobal.org/spec/lti/claim/target_link_uri";

$jwt = LTI13::parse_jwt($id_token);

if ( ! $jwt ) {
    LTIX::abort_with_error_log("Unable to parse JWT");
}

if ( ! isset($jwt->body) ) {
    LTIX::abort_with_error_log("Missing body in JWT");
}

$launch_url = false;
if ( isset($jwt->body->{$url_claim}) && is_string($jwt->body->{$url_claim}) ) {
    $launch_url = $jwt->body->{$url_claim};
    error_log("target_link_uri from id_token ".$launch_url);
}

if ( ! $launch_url ) {
    LTIX::abort_with_error_log("Missing or incorrect launch_url claim in body");
}

// Double check that target_link_uri did not change from oidc_login to now
if ( isset($decoded->target_link_uri) && is_string($decoded->target_link_uri) ) {
    $state_target_link_uri = $decoded->target_link_uri;
    if ( $launch_url != $state_target_link_uri ) {
        LTIX::abort_with_error_log("Mis-match between claim target ($launch_url) and state target($state_target_link_uri)");
    }
}

// Check for bad places to go
$oidc_login = $CFG->wwwroot . '/lti/oidc_login';
$oidc_launch = $CFG->wwwroot . '/lti/oidc_launch';
if ( strpos($launch_url, $oidc_login) === 0 ) {
    LTIX::abort_with_error_log("target_link_uri cannot be the same as oidc_login - ".$launch_url);
}
if ( strpos($launch_url, $oidc_launch) === 0 ) {
    LTIX::abort_with_error_log("target_link_uri cannot be the same as oidc_launch - ".$launch_url);
}

if ( ! U::startsWith($launch_url, $CFG->apphome) ) {
    LTIX::abort_with_error_log("Launch_url must start with ".$CFG->apphome);
}

// Check if we are already verified
if ( U::get($_SESSION, 'verified') == 'yes' ) {
    error_log("oidc_login verified with postverify from session");
    $verified = true;
}

$cookie_state = U::get($_COOKIE, "TSUGI_STATE");
if ( $cookie_state == $state ) {
    error_log("oidc_login verified with cookie");
    $verified = true;
}

$sub = isset($jwt->body->sub) ? $jwt->body->sub : null;

$request_kid = isset($jwt->header->kid) ? $jwt->header->kid : null;
$iss = isset($jwt->body->iss) ? $jwt->body->iss : null;
$issuer_sha256 = $iss ? LTI13::extract_issuer_key_string($iss) : null;

// Get verification data from oidc_login via session
$our_kid = U::get($_SESSION, 'our_kid');
$our_keyset_url = U::get($_SESSION, 'our_keyset_url');
$our_keyset = U::get($_SESSION, 'our_keyset');
$platform_public_key = U::get($_SESSION, 'platform_public_key');
$lti13_oidc_auth = U::get($_SESSION, 'lti13_oidc_auth');

$tool_private_key_encr = U::get($_SESSION, 'tool_private_key_encr');
$tool_private_key = AesCtr::decrypt($tool_private_key_encr, $CFG->cookiesecret, 256) ;

// Lets check if we need to verify the browser through window.postMessage
// https://github.com/MartinLenord/simple-lti-1p3/blob/cookie-shim/src/web/launch.php
$postmessage_signal = true;  // I sure wish there were a signal :)
if ( $postmessage_signal && ! $verified && $sub && $issuer_sha256 && $lti13_oidc_auth ) {
    error_log("postmessage request_kid $request_kid iss $iss issuer_sha256 $issuer_sha256 lti13_oidc_auth $lti13_oidc_auth");
    
    $platform_login_auth_endpoint = $lti13_oidc_auth;
// TODO: If in iframe / fail gracefully
?>
<html>
<head>
<script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
</head>
<body>
<h1>Top</h1>
<script>
    // Get data about the registration linked to the request
    let return_url = new URL(<?= json_encode($platform_login_auth_endpoint, JSON_UNESCAPED_SLASHES); ?>);

    // Get the parent window or opener
    let message_window = (window.opener || window.parent)<?= isset($_REQUEST['ims_web_message_target']) ? '.frames["'.$_REQUEST['ims_web_message_target'].'"]' : ''; ?>;

    alert('Yada');

    let state_set = false;

    // Listen for response containing the id_token from the platform
    window.addEventListener("message", function(event) {
        console.log(window.location.origin + " Got post message from " + event.origin);
        console.log(JSON.stringify(event.data, null, '    '));
        state_set = true;

        // Origin MUST be the same as the registered oauth return url origin
        if (event.origin !== return_url.origin) {
            console.log('invalid origin');
            return;
        }

        // Check state matches the one sent to the platform
        if (event.data.subject !== 'org.imsglobal.lti.get_data.response' ) {
            console.log('invalid response');
            return;
        }

        // Good news here..
        console.log('Got some state', event.data.value);

        if (event.data.value !== '<?= $state ?>') {
            console.log([event.data.value, '<?= $state ?>']);
            alert('invalid state');
            return;
        }


        // We are feeling pretty groovy.
        console.log("Feeling groovy");

    }, false);

    // Send post message to platform window with login initiation data
    let send_data = {
        subject: 'org.imsglobal.lti.get_data',
        message_id: Math.random(),
        key: "state",
    };
    console.log(window.location.origin + " Sending post message to " + return_url.origin);
    console.log(JSON.stringify(send_data, null, '    '));
    message_window.postMessage(send_data, return_url.origin);
    setTimeout(() => { if (!state_set) { alert('no response from platform'); } }, 1000);
</script>
<h1>Yada</h1>
</body>
</html>
<?php
        return;
}


// Sakai postverify approach
$postverify = isset($jwt->body->{LTI13::POSTVERIFY_CLAIM}) ? $jwt->body->{LTI13::POSTVERIFY_CLAIM} : null;
$origin = isset($jwt->body->{LTI13::ORIGIN_CLAIM}) ? $jwt->body->{LTI13::ORIGIN_CLAIM} : null;
if ( ! $verified && $sub && $postverify && $origin && $issuer_sha256 ) {
    error_log("request_kid $request_kid iss $iss issuer_sha256 $issuer_sha256  origin $origin postverify $postverify");
    $platform_public_key = LTIX::getPlatformPublicKey($request_kid, $our_kid, $platform_public_key, $issuer_sha256, $our_keyset_url, $our_keyset);

    $e = LTI13::verifyPublicKey($id_token, $platform_public_key, array($jwt->header->alg));
    if ( $e !== true ) {
        LTIX::abort_with_error_log('JWT validation fail key='.$iss.' error='.$e->getMessage());
    }

    if ( $verifydata === null ) {

        // Save for oidc_verify
        $_SESSION['platform_public_key'] = $platform_public_key;
        $_SESSION['id_token'] = $id_token;
        $_SESSION['subject'] = $sub;

        $verify_url = $CFG->wwwroot . '/lti/oidc_verify.php?sid=' . $sid;
        $postjson = new \stdClass();
        $postjson->subject = "org.sakailms.lti.postverify";
        $postjson->postverify = U::add_url_parm($postverify, 'callback', $verify_url) ;
        $postjson->sub = $sub;
        $poststr = json_encode($postjson);
?>
<form method="POST" id="oidc_verify">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
<input type="hidden" id="postverify" name="postverify">
</form>
<script>
window.addEventListener('message', function (e) {
    console.log('oidc_launch received message');
    console.log(e);
    console.log((e.source == parent ? 'Source parent' : 'Source not parent '+e.source), '/',
                (e.origin == '<?= $origin ?>' ? 'Origin match' : 'Origin mismatch '+e.origin));
    if ( e.source == parent && e.origin == '<?= $origin ?>' ) {
        document.getElementById("postverify").value = 'done';
        document.getElementById("oidc_verify").submit();
    }
});
console.log('trophy sending org.sakailms.lti.postverify');
parent.postMessage('<?= $poststr ?>', '<?= $origin ?>');
</script>
<?php
        return;
   }
}

// Reclaim the short-lived session
unset($_SESSION);
session_destroy();

// Check if we are verified or have a fallback
if ( $verified ) {
    // Verified via cookie or post verify
} else if ( $signature_check ) {
    error_log("oidc_login verified with browser signature");
} else {
    LTIX::abort_with_error_log("Unable to verify subject ".$sub." iss ".$iss);
}

// Looks good - time to forward
?>
<form method="POST" id="oidc_forward" action="<?= htmlspecialchars($launch_url) ?>">
<input type="hidden" name="id_token" value="<?= htmlspecialchars($id_token) ?>">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
</form>
<script>
document.getElementById("oidc_forward").submit();
</script>
