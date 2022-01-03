<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;
use \Tsugi\Crypt\AesCtr;

require_once "../config.php";

$ORIGIN_CLAIM = "https://purl.sakailms.org/spec/lti/claim/origin";
$POSTVERIFY_CLAIM = "https://purl.sakailms.org/spec/lti/claim/postverify";

$verified = false;
$state = U::get($_POST, 'state');
$postmessage_form = U::get($_POST, 'postmessage', null);
$postverify_form = U::get($_POST, 'postverify', null);

// We will switch these defaults in the future...
$postverify_enabled = isset($CFG->postverify) ? $CFG->postverify : false;
$postmessage_enabled = isset($CFG->postmessage) ? $CFG->postmessage : false;

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
    LTIX::abort_with_error_log('Could not find session_password');
}

// Get the id_token - only take on the first post
if ( $postverify_form !== null || $postmessage_form !== null) {
    $id_token = U::get($_SESSION, 'id_token');
} else {
    $id_token = U::get($_POST, 'id_token');
    $_SESSION['id_token'] = $id_token;
}

if ( ! $id_token ) {
    $error_detail = U::get($_POST, "error_description", 'Missing id_token');
    LTIX::abort_with_error_log($error_detail);
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
    error_log("oidc_login verified from session");
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

// Get verification data from oidc_login via session
$our_kid = U::get($_SESSION, 'our_kid');
$our_keyset_url = U::get($_SESSION, 'our_keyset_url');
$our_keyset = U::get($_SESSION, 'our_keyset');
$platform_public_key = U::get($_SESSION, 'platform_public_key');
$lti13_oidc_auth = U::get($_SESSION, 'lti13_oidc_auth');
$lti_storage_target = U::get($_SESSION, 'lti_storage_target', null);
error_log("lti_storage_target = $lti_storage_target");

$put_data_supported = U::get($_SESSION, 'put_data_supported');
$issuer_id = U::get($_SESSION, 'issuer_id');
$key_id = U::get($_SESSION, 'key_id');

// Sakai postverify approach
$postverify_url = isset($jwt->body->{$POSTVERIFY_CLAIM}) ? $jwt->body->{$POSTVERIFY_CLAIM} : null;
$postverify_origin = isset($jwt->body->{$ORIGIN_CLAIM}) ? $jwt->body->{$ORIGIN_CLAIM} : null;
if ( $postverify_enabled && ! $verified && $sub && $postverify_url && $postverify_origin && $key_id ) {
    error_log("request_kid $request_kid iss $iss key_id $key_id issuer_id $isuer_id postverify_origin $postverify_origin postverify_url $postverify_url");
    $platform_public_key = LTIX::getPlatformPublicKey($issuer_id, $key_id, $request_kid, $our_kid, $platform_public_key, $our_keyset_url, $our_keyset);

    $e = LTI13::verifyPublicKey($id_token, $platform_public_key, array($jwt->header->alg));
    if ( $e !== true ) {
        LTIX::abort_with_error_log('JWT validation fail key='.$iss.' error='.$e->getMessage());
    }

    if ( $postverify_form === null ) {

        // Save for oidc_postverify
        $_SESSION['platform_public_key'] = $platform_public_key;
        $_SESSION['id_token'] = $id_token;
        $_SESSION['subject'] = $sub;

        $verify_url = $CFG->wwwroot . '/lti/oidc_postverify.php?sid=' . $sid;
        $postjson = new \stdClass();
        $postjson->subject = "org.sakailms.lti.postverify";
        $postjson->postverify = U::add_url_parm($postverify_url, 'callback', $verify_url) ;
        $postjson->sub = $sub;
        $poststr = json_encode($postjson);
?>
<form method="POST" id="oidc_postverify">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
<input type="hidden" id="postverify" name="postverify">
</form>
<script>
window.addEventListener('message', function (e) {
    console.log('oidc_launch received message');
    console.debug(e);
    console.debug((e.source == parent ? 'Source parent' : 'Source not parent '+e.source), '/',
                (e.origin == '<?= $postverify_origin ?>' ? 'Origin match' : 'Origin mismatch '+e.origin));
    if ( e.source == parent && e.origin == '<?= $postverify_origin ?>' ) {
        document.getElementById("postverify").value = 'done';
        document.getElementById("oidc_postverify").submit();
    }
});
console.log('sending org.sakailms.lti.postverify');
parent.postMessage('<?= $poststr ?>', '<?= $postverify_origin ?>');
</script>
<?php
        return;
   }
}

// Lets check if we need to verify the browser through window.postMessage
// https://github.com/MartinLenord/simple-lti-1p3/blob/cookie-shim/src/web/launch.php
if ( $put_data_supported && $postmessage_form === null && ! $verified && $sub && $lti13_oidc_auth ) {
    error_log("postmessage request_kid $request_kid iss $iss key_id $key_id lti13_oidc_auth $lti13_oidc_auth");

    $platform_login_auth_endpoint = $lti13_oidc_auth;
    $state_key = 'state_'.md5($state.$session_password);
    $post_frame = (is_string($lti_storage_target)) ? ('.frames["'.$lti_storage_target.'"]') : '';
?>
<html>
<head>
<script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
<script src="<?= $CFG->staticroot ?>/js/jquery-1.11.3.js"></script>
</head>
<body>
<form method="POST" id="oidc_postmessage">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
<input type="hidden" id="postmessage" name="postmessage" value="failure">
</form>
<script>
// No point to do postmessage in iframe
if ( ! inIframe() ) {
    document.getElementById("postmessage").value = 'success';
    document.getElementById("oidc_postmessage").submit();
} else {

    let state_set = false;
    setTimeout(() => {
        if (!state_set) {
            console.log('no response from platform');
            document.getElementById("postmessage").value = 'timeout';
            document.getElementById("oidc_postmessage").submit();
        }
    }, 2000);  // Timeout 2 seconds because Angular

    try {
        // Get data about the registration linked to the request
        let return_url = new URL(<?= json_encode($platform_login_auth_endpoint, JSON_UNESCAPED_SLASHES); ?>);

        let message_window = (window.opener || window.parent<?= $post_frame ?>);

        // Listen for response containing the id_token from the platform
        window.addEventListener("message", function(event) {
            console.log(window.location.origin + " Got post message from " + event.origin);
            console.debug(JSON.stringify(event.data, null, '    '));
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

            if (event.data.value !== '<?= $state ?>') {
                console.log([event.data.value, '<?= $state ?>']);
                alert('invalid state');
                return;
            }

            $.ajax
            ({
                type: "POST",
                url: "oidc_postmessage.php",
                headers: {
                    "X-Tsugi-Authorization": "TsugiOAuthVerify <?= $session_password ?>"
                },
                data: {
                    "state": "<?= $state ?>"
                },
                error: function (jqXHR, textStatus, errorThrown ){
                    console.log('Got error from oidc_postmessage '+errorThrown);
                    document.getElementById("postmessage").value = 'error';
                    document.getElementById("oidc_postmessage").submit();
                },
                success: function (){
                    document.getElementById("postmessage").value = 'success';
                    document.getElementById("oidc_postmessage").submit();
                }
            });
        }, false);

        // Send post message to platform window with login initiation data
        let send_data = {
            subject: 'org.imsglobal.lti.get_data',
            message_id: Math.random(),
            key: "<?= $state_key ?>",
        };
        console.debug(window.location.origin + " Sending post message to " + return_url.origin);
        console.debug(JSON.stringify(send_data, null, '    '));
        message_window.postMessage(send_data, return_url.origin);
    } catch (error) {
        console.log('Failure to exchange post message')
        console.log(error);
        document.getElementById("postmessage").value = 'error';
        document.getElementById("oidc_postmessage").submit();
    }
} // inIframe()
</script>
<p style="display: none;" id="waiting"><?= _m("Contacting LMS through postMessage...") ?></p>
<script>
setTimeout(function(){$("#waiting").show();}, 3000);
</script>
</body>
</html>
<?php
        return;
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
