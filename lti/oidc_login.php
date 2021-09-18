<?php

// https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
//
use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\AesCtr;

require_once "../config.php";

// We will switch these defaults in the future...
$postverify_enabled = isset($CFG->postverify) ? $CFG->postverify : false;
$postmessage_enabled = isset($CFG->postmessage) ? $CFG->postmessage : false;

// target_link_uri and lti_message_hint are not required by Tsugi
$login_hint = U::get($_REQUEST, 'login_hint');
$iss = U::get($_REQUEST, 'iss');
$issuer_guid = U::get($_REQUEST, 'guid');
$lti_storage_target = U::get($_REQUEST, 'web_message_target');
$lti_storage_target = U::get($_REQUEST, 'ims_web_message_target', $lti_storage_target);
$lti_storage_target = U::get($_REQUEST, 'lti_storage_target', $lti_storage_target);
$put_data_supported = is_string($lti_storage_target) && strlen($lti_storage_target) > 0;
if ( $lti_storage_target == "_parent" ) $lti_storage_target = null;

// TODO: Try to get rid of this
$put_data_supported = true; // For Now

// Allow a format where the parameter is the primary key of the lti_key row
$key_id = null;
if ( is_numeric($issuer_guid) ) $key_id = intval($issuer_guid);

// echo("<pre>\n");var_dump($_REQUEST); LTIX::abort_with_error_log();

if ( ! $login_hint ) {
    LTIX::abort_with_error_log('Missing login_hint');
}

if ( ! $iss ) {
    LTIX::abort_with_error_log('Missing iss');
}

$PDOX = \Tsugi\Core\LTIX::getConnection();

$key_sha256 = LTI13::extract_issuer_key_string($iss);

// TODO: This is a mess :(
error_log("key_id=$key_id issuer_giud=$issuer_guid iss=".$iss." sha256=".$key_sha256);
if ( $key_id ) {
    $sql = "SELECT key_id,
        lms_issuer, lms_client, lms_oidc_auth, lms_keyset_url,
        lms_token_url, lms_token_audience, lms_cache_keyset, lms_cache_pubkey, lms_cache_kid,
        K.issuer_id AS issuer_id,
        issuer_client, lti13_oidc_auth, issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey
        FROM {$CFG->dbprefix}lti_key AS K
        LEFT JOIN {$CFG->dbprefix}lti_issuer AS I ON
                K.issuer_id = I.issuer_id AND I.issuer_sha256 = :SHA
            WHERE K.key_id = :KID";
    $row = $PDOX->rowDie($sql, array(":KID" => $key_id, ":SHA" => $key_sha256));
    if ( ! is_array($row) || count($row) < 1 ) {
        LTIX::abort_with_error_log('Login could not find issuer '.htmlentities($iss)." for key=".$key_id);
        return;
    }

    // Move issuer data from key to global if needed
    if ( $row['issuer_id'] < 1 ) {
        $row['issuer_client'] = $row['lms_client'];
        $row['lti13_oidc_auth'] = $row['lms_oidc_auth'];
        $row['issuer_key'] = $row['lms_issuer'];
        $row['lti13_kid'] = $row['lms_cache_kid'];
        $row['lti13_keyset_url'] = $row['lms_keyset_url'];
        $row['lti13_keyset'] = $row['lms_cache_keyset'];
        $row['lti13_platform_pubkey'] = $row['lms_cache_pubkey'];
    }

} else {
    if ( $issuer_guid ) {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_guid = :issuer_guid AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256, ":issuer_guid" => $issuer_guid);
    } else {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256);
    }

    $row = $PDOX->rowDie(
        "SELECT NULL as key_id, issuer_id, issuer_client, lti13_oidc_auth,
        issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey
        FROM {$CFG->dbprefix}lti_issuer $query_where",
        $query_where_params);
}

if ( ! is_array($row) || count($row) < 1 ) {
    LTIX::abort_with_error_log('Login could not find issuer '.htmlentities($iss)." issuer_guid=".$issuer_guid);
}
$client_id = trim($row['issuer_client']);
$redirect = trim($row['lti13_oidc_auth']);

$issuer_key = $row['issuer_key'];
$platform_public_key = $row['lti13_platform_pubkey'];
$our_kid = $row['lti13_kid'];
$our_keyset_url = $row['lti13_keyset'];
$our_keyset = $row['lti13_keyset'];

$signature = \Tsugi\Core\LTIX::getBrowserSignature();

$payload = array();
$payload['signature'] = $signature;
$payload['time'] = time();
// Someday we might do something clever with this...
if ( U::get($_REQUEST,'target_link_uri') ) {
    $payload['target_link_uri'] = $_REQUEST['target_link_uri'];
}

$state = JWT::encode($payload, $CFG->cookiesecret, 'HS256');

// Make a short-lived session
$sid = substr("log-".md5($state), 0, 20);
error_log(" =============== oidc_login ===================== $sid");
session_id($sid);
session_start();
$_SESSION['state'] = $state;
$_SESSION['issuer_id'] = $row['issuer_id'];
$_SESSION['key_id'] = $row['key_id'];
$_SESSION['issuer_key'] = $issuer_key;
$_SESSION['platform_public_key'] = $platform_public_key;
$_SESSION['lti_storage_target'] = $lti_storage_target;
$_SESSION['our_kid'] = $our_kid;
$_SESSION['our_keyset_url'] = $our_keyset_url;
$_SESSION['our_keyset'] = $our_keyset;
$_SESSION['lti13_oidc_auth'] = trim($row['lti13_oidc_auth']);
$session_password = uniqid();
$_SESSION['password'] = $session_password;

$_SESSION['put_data_supported'] = $put_data_supported;

$redirect = U::add_url_parm($redirect, "scope", "openid");
$redirect = U::add_url_parm($redirect, "response_type", "id_token");
$redirect = U::add_url_parm($redirect, "response_mode", "form_post");
$redirect = U::add_url_parm($redirect, "prompt", "none");
$redirect = U::add_url_parm($redirect, "nonce", uniqid());

// client_id - Required, per OIDC spec, the tool’s client id for this issuer.
$redirect = U::add_url_parm($redirect, "client_id", $client_id);
$redirect = U::add_url_parm($redirect, "login_hint", $login_hint);
if ( U::get($_REQUEST,'lti_message_hint') ) {
    $redirect = U::add_url_parm($redirect, "lti_message_hint", $_REQUEST['lti_message_hint']);
}
$redirect = U::add_url_parm($redirect, "redirect_uri", $CFG->wwwroot . '/lti/oidc_launch');
$redirect = U::add_url_parm($redirect, "state", $state);

error_log("oidc_login redirect: ".$redirect);

// Store it in a session cookie - likely won't work inside iframes on future browsers
setcookie("TSUGI_STATE", $state);

// We use postmessage if we have explicit notification from the LMS or if we always enable it through config
if ( $put_data_supported || $postmessage_enabled ) {
    // Fall through to below
} else {
    // Simple fallback
?>
<script>
    let TSUGI_REDIRECT = <?= json_encode($redirect, JSON_UNESCAPED_SLASHES) ?>;
    console.log("Redirecting to "+TSUGI_REDIRECT);
    window.location.href = TSUGI_REDIRECT;
</script>
<?php
    return;
}
// Send our data using the postMessage approach
$post_frame = (is_string($lti_storage_target)) ? ('.frames["'.$lti_storage_target.'"]') : '';
$state_key = 'state_'.md5($state.$session_password);
?>
<script src="<?= $CFG->staticroot ?>/js/tsugiscripts_head.js"></script>
<script>

let TSUGI_REDIRECT = <?= json_encode($redirect, JSON_UNESCAPED_SLASHES) ?>;

// Adapted from https://github.com/MartinLenord/simple-lti-1p3/blob/cookie-shim/src/web/login_initiation.php
if ( inIframe() ) {
    let return_url = new URL(<?= json_encode($redirect, JSON_UNESCAPED_SLASHES); ?>);
    let send_data = {
        subject: 'org.imsglobal.lti.put_data',
        message_id: Math.random(),
        key: "<?= $state_key ?>",
        value: "<?= $state ?>",
    };

    let state_set = false;
    let message_window = (window.opener || window.parent<?= $post_frame ?>);

    // Listen for the response from the platform
    window.addEventListener("message", function(event) {
        console.log(window.location.origin + " Got post message from " + event.origin);
        console.log(JSON.stringify(event.data, null, '    '));

        // Origin MUST be the same as the registered oauth return url origin
        if (event.origin !== return_url.origin) {
            console.log('invalid origin');
            return;
        }

        // Check state matches the one sent to the platform
        if (event.data.subject !== 'org.imsglobal.lti.put_data.response' ) {
            console.log('invalid response');
            return;
        }

        state_set = true;

        window.location.href=TSUGI_REDIRECT;;

    }, false);

    console.log(window.location.origin + " Sending post message to " + return_url.origin);
    console.log(JSON.stringify(send_data, null, '    '));
    message_window.postMessage(send_data, return_url.origin);


    setTimeout(() => { if (!state_set) { console.log('no response from platform'); window.location.href=TSUGI_REDIRECT;} }, 2000);
} else {
    console.log("Redirecting to "+TSUGI_REDIRECT);
    window.location.href = TSUGI_REDIRECT;
}
</script>

