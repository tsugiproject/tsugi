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

error_log("iss=".$iss." sha256=".$key_sha256);
if ( $key_id ) {
     $sql = "SELECT issuer_client, lti13_oidc_auth,
         issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey, lti13_privkey
         FROM {$CFG->dbprefix}lti_issuer AS I
            JOIN {$CFG->dbprefix}lti_key AS K ON
                K.issuer_id = I.issuer_id
            WHERE K.key_id = :KID AND I.issuer_sha256 = :SHA";
    $row = $PDOX->rowDie($sql, array(":KID" => $key_id, ":SHA" => $key_sha256));
} else {
    if ( $issuer_guid ) {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_guid = :issuer_guid AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256, ":issuer_guid" => $issuer_guid);
    } else {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256);
    }

    $row = $PDOX->rowDie(
        "SELECT issuer_client, lti13_oidc_auth,
        issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey, lti13_privkey
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
$tool_private_key = $row['lti13_privkey'];

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
$_SESSION['issuer_key'] = $issuer_key;
$_SESSION['platform_public_key'] = $platform_public_key;

$_SESSION['our_kid'] = $our_kid;
$_SESSION['our_keyset_url'] = $our_keyset_url;
$_SESSION['our_keyset'] = $our_keyset;
$_SESSION['lti13_oidc_auth'] = trim($row['lti13_oidc_auth']);
$_SESSION['password'] = uniqid();

$encr = AesCtr::encrypt($tool_private_key, $CFG->cookiesecret, 256) ;
$_SESSION['tool_private_key_encr'] = $encr;

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

if ( ! $postmessage_enabled ) {
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
        key: "state",
        value: "<?= $state ?>",
    };

    let state_set = false;
    //
    // TODO: Ask Martin about this extra complexity in this if test ...
    // let message_window = (window.opener || window.parent)<?= isset($_REQUEST['ims_web_message_target']) ? '.frames["'.$_REQUEST['ims_web_message_target'].'"]' : ''; ?>;
    let message_window = (window.opener || window.parent);

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


    setTimeout(() => { if (!state_set) { console.log('no response from platform'); window.location.href=TSUGI_REDIRECT;} }, 1000);
} else {
    console.log("Redirecting to "+TSUGI_REDIRECT);
    window.location.href = TSUGI_REDIRECT;
}
</script>

