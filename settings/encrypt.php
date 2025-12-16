<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
require_once("settings_util.php");
session_start();

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;
use \Tsugi\Crypt\AesOpenSSL;

if ( ! U::get($_SESSION,'id') ) {
    $_SESSION['login_return'] = $CFG->wwwroot . '/settings/encrypt';
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

LTIX::getConnection();

$encrypted_result = null;
$error_message = null;
$encrypt_value = '';
$encrypt_secret = '';

// Handle encryption (must be done BEFORE any output)
if (U::isKeyNotEmpty($_POST, "encrypt") && U::isKeyNotEmpty($_POST, "encrypt_value")) {
    $plaintext = U::get($_POST, "encrypt_value");
    $secret = U::get($_POST, "encrypt_secret", "");
    
    // Store form values for after redirect
    $encrypt_value = $plaintext;
    $encrypt_secret = $secret;
    
    if ( !empty($plaintext) ) {
        try {
            if ( !empty($secret) ) {
                // Use custom secret with AesOpenSSL - add AES:: prefix
                $encrypted_result = 'AES::' . AesOpenSSL::encrypt($plaintext, $secret);
            } else {
                // Use default LTIX encryption
                $encrypted_result = LTIX::encrypt_secret($plaintext);
            }
            // Store result in session for after redirect
            $_SESSION['encrypt_result'] = $encrypted_result;
            $_SESSION['encrypt_value'] = $encrypt_value;
            $_SESSION['encrypt_secret'] = $encrypt_secret;
        } catch (Exception $e) {
            $error_message = "Encryption error: " . htmlentities($e->getMessage());
            $_SESSION['encrypt_error'] = $error_message;
            $_SESSION['encrypt_value'] = $encrypt_value;
            $_SESSION['encrypt_secret'] = $encrypt_secret;
        }
    } else {
        $error_message = "Please enter a value to encrypt";
        $_SESSION['encrypt_error'] = $error_message;
        $_SESSION['encrypt_value'] = $encrypt_value;
        $_SESSION['encrypt_secret'] = $encrypt_secret;
    }
    
    // Always redirect after POST to avoid unhandled POST error
    header("Location: " . U::addSession($CFG->wwwroot . '/settings/encrypt'));
    return;
}

// Retrieve results from session (after redirect)
if ( isset($_SESSION['encrypt_result']) ) {
    $encrypted_result = $_SESSION['encrypt_result'];
    unset($_SESSION['encrypt_result']);
}
if ( isset($_SESSION['encrypt_error']) ) {
    $error_message = $_SESSION['encrypt_error'];
    unset($_SESSION['encrypt_error']);
}
if ( isset($_SESSION['encrypt_value']) ) {
    $encrypt_value = $_SESSION['encrypt_value'];
    unset($_SESSION['encrypt_value']);
}
if ( isset($_SESSION['encrypt_secret']) ) {
    $encrypt_secret = $_SESSION['encrypt_secret'];
    unset($_SESSION['encrypt_secret']);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Encrypt Strings</h1>
<p>Use this tool to encrypt strings using LTIX encryption methods. This tool only supports encryption - decryption is only available to administrators.</p>

<?php if ( $error_message ) { ?>
<div class="alert alert-danger"><?= $error_message ?></div>
<?php } ?>

<div class="form-group" style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
<h2>Encrypt String</h2>
<form method="POST" autocomplete="off">
<label for="encrypt_value">Enter plaintext to encrypt:</label><br/>
<textarea name="encrypt_value" id="encrypt_value" class="form-control" style="width: 100%; min-height: 100px; padding: 8px; font-family: monospace; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;" placeholder="Enter text to encrypt..."><?= htmlentities($encrypt_value) ?></textarea><br/>
<label for="encrypt_secret" style="margin-top: 10px;">Secret (optional - if provided, uses AesOpenSSL::encrypt):</label><br/>
<textarea name="encrypt_secret" id="encrypt_secret" class="form-control" style="width: 100%; min-height: 40px; max-height: 80px; padding: 8px; font-family: monospace; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; margin-top: 5px; resize: vertical;" autocomplete="off" placeholder="Leave empty to use default LTIX encryption"><?= htmlentities($encrypt_secret) ?></textarea><br/>
<input type="submit" name="encrypt" value="Encrypt" class="btn btn-primary" style="margin-top: 10px;">
</form>
<?php if ( $encrypted_result !== null ) { ?>
<div class="alert alert-success" style="margin-top: 15px; padding: 10px; background-color: #e8f5e9; border: 1px solid #4CAF50; border-radius: 3px;">
<div style="font-weight: bold; color: #2e7d32; margin-bottom: 5px;">Encrypted Result:</div>
<div style="font-family: monospace; word-break: break-all; white-space: pre-wrap;"><?= htmlentities($encrypted_result) ?></div>
</div>
<?php } ?>
</div>

<p>
<a href="<?= $CFG->wwwroot ?>/settings" class="btn btn-default">Back to Settings</a>
</p>

<?php
$OUTPUT->footer();

