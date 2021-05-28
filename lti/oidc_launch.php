<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

require_once "../config.php";

$id_token = U::get($_POST, 'id_token');
$state = U::get($_POST, 'state');
$verifydata = U::get($_POST, 'postverify');
$verified = false;

if ( ! $state || ! $id_token ) {
    LTIX::abort_with_error_log('Missing id_token and/or state');
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
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1);
session_id($sid);
session_start();
$session_state = U::get($_SESSION, 'state');
if ( $session_state != $state ) {
    LTIX::abort_with_error_log('Could not find state in session');
}

if ( isset($decoded->type) && $decoded->type == "json" ) {
    // No browser signature check
    error_log("JSON STATE");
} else {
    if ( ! isset($decoded->signature) ) {
        LTIX::abort_with_error_log('No signature in state');
    }

    $signature = \Tsugi\Core\LTIX::getBrowserSignature();

    if ( $signature != $decoded->signature ) {
        if ( U::apcAvailable() ) {
            $found = false;
	        $previous = apc_fetch('oidc_login_state',$zap);
	        if ( $found ) error_log('oidc_state '.$previous);
        }
        $raw = \Tsugi\Core\LTIX::getBrowserSignatureRaw();
        error_log('oidc_launch '.$raw);
        LTIX::abort_with_error_log("Invalid state signature value");
    }
}

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
    error_log("Verified with session");
    $verified = true;
}

$cookie_state = U::get($_COOKIE, "TSUGI_STATE");
if ( $cookie_state == $state ) {
    error_log("Verified with cookie");
    $verified = true;
}


$sub = isset($jwt->body->sub) ? $jwt->body->sub : null;
// Lets check if we need to do a postverify
$postverify = isset($jwt->body->{LTI13::POSTVERIFY_CLAIM}) ? $jwt->body->{LTI13::POSTVERIFY_CLAIM} : null;
$origin = isset($jwt->body->{LTI13::ORIGIN_CLAIM}) ? $jwt->body->{LTI13::ORIGIN_CLAIM} : null;
$request_kid = isset($jwt->header->kid) ? $jwt->header->kid : null;
$iss = isset($jwt->body->iss) ? $jwt->body->iss : null;
$issuer_sha256 = $iss ? LTI13::extract_issuer_key_string($iss) : null;
$key_id = isset($decoded->key_id) ? $decoded->key_id : null;

if ( ! $verified && $sub && $postverify && $origin && $key_id && $issuer_sha256 ) {
    error_log("request_kid $request_kid iss $iss issuer_sha256 $issuer_sha256 postverify $postverify origin $origin key_id $key_id");
    $PDOX = \Tsugi\Core\LTIX::getConnection();
    $sql = "SELECT key_id, issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey, lti13_privkey
        FROM {$CFG->dbprefix}lti_issuer AS I
            JOIN {$CFG->dbprefix}lti_key AS K ON
                K.issuer_id = I.issuer_id
            WHERE K.key_id = :KID AND I.issuer_sha256 = :SHA";
    $row = $PDOX->rowDie($sql, array(":KID" => $key_id, ":SHA" => $issuer_sha256));

    $issuer_key = $row['issuer_key'];
    $platform_public_key = $row['lti13_platform_pubkey'];
    $our_kid = $row['lti13_kid'];
    $our_keyset_url = $row['lti13_keyset'];
    $our_keyset = $row['lti13_keyset'];

    $platform_public_key = LTIX::getPlatformPublicKey($request_kid, $our_kid, $platform_public_key, $issuer_sha256, $our_keyset_url, $our_keyset);

    error_log('id_token '.$id_token);
    error_log('platform_public_key '.$platform_public_key);

    $e = LTI13::verifyPublicKey($id_token, $platform_public_key, array($jwt->header->alg));
    if ( $e !== true ) {
        LTIX::abort_with_error_log('JWT validation fail key='.$issuer_key.' error='.$e->getMessage());
    }

    // The launch JWT is valid, lets do the postverify
    $tool_private_key = $row['lti13_privkey'];

    if ( $verifydata === null ) {

        // Save for oidc_verify
        $_SESSION['platform_public_key'] = $platform_public_key;
        $_SESSION['id_token'] = $id_token;
        $_SESSION['subject'] = $sub;
        // TODO: Move to user_id and not subject
        $_SESSION['user_id'] = $sub;

        $postjson = new \stdClass();
        $postjson->subject = "org.imsglobal.lti.postverify";
        $postjson->postverify = $postverify;
        $postjson->sub = $sub;
        $poststr = json_encode($postjson);
?>
<form method="POST" id="oidc_verify">
<input type="hidden" name="id_token" value="<?= htmlspecialchars($id_token) ?>">
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
        document.getElementById("postverify").value = e.data;
        document.getElementById("oidc_verify").submit();
    }
});
console.log('trophy sending org.imsglobal.lti.postverify');
parent.postMessage('<?= $poststr ?>', '<?= $origin ?>');
</script>
<?php
        return;
    } else {
        $verify_jwt = false;
        $verify_sub = false;
        error_log("Returned JWT $verifydata");
        $verify_jwt = LTI13::parse_jwt($verifydata);
        error_log("Platform public ".$platform_public_key);

        $e = LTI13::verifyPublicKey($verifydata, $platform_public_key, array($verify_jwt->header->alg));
        if ( $e !== true ) {
            LTIX::abort_with_error_log('Postverify validation fail key='.$issuer_key.' error='.$e->getMessage());
        }

        if ( isset($verify_jwt->body->sub) && is_string($verify_jwt->body->sub) ) {
            $verify_sub = $verify_jwt->body->sub;
        } else {
            error_log("Unable to parse postverify JWT ".$verifydata);
            LTIX::abort_with_error_log("Unable to parse postverify JWT");
        }


        if ( $verify_sub != $sub ) {
            error_log("Subject $sub does not match verified_subject of $verify_sub");
            LTIX::abort_with_error_log("Unable to verify subject - ".$sub);
        }
        // TODO: Do this better later
        $verified = true;
   }
}

// Reclaim the short-lived session
unset($_SESSION);
session_destroy();

if ( ! $verified ) {
    LTIX::abort_with_error_log("Unable to multi-verify subject - ".$sub);
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
