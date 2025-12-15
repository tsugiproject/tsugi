<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\AesOpenSSL;
use \Tsugi\Crypt\AesCtr;

$encrypted_result = null;
$decrypted_result = null;
$error_message = null;

// Handle encryption
if (U::isKeyNotEmpty($_POST, "encrypt") && U::isKeyNotEmpty($_POST, "encrypt_value")) {
    $plaintext = U::get($_POST, "encrypt_value");
    $secret = U::get($_POST, "encrypt_secret", "");
    
    if ( !empty($plaintext) ) {
        try {
            if ( !empty($secret) ) {
                // Use custom secret with AesOpenSSL - add AES:: prefix
                $encrypted_result = 'AES::' . AesOpenSSL::encrypt($plaintext, $secret);
            } else {
                // Use default LTIX encryption
                $encrypted_result = LTIX::encrypt_secret($plaintext);
            }
        } catch (Exception $e) {
            $error_message = "Encryption error: " . htmlentities($e->getMessage());
        }
    } else {
        $error_message = "Please enter a value to encrypt";
    }
}

// Handle decryption
if (U::isKeyNotEmpty($_POST, "decrypt") && U::isKeyNotEmpty($_POST, "decrypt_value")) {
    $ciphertext = U::get($_POST, "decrypt_value");
    $secret = U::get($_POST, "decrypt_secret", "");
    
    if ( !empty($ciphertext) ) {
        try {
            if ( !empty($secret) ) {
                // Remove AES:: prefix if present before decrypting
                $ciphertext_to_decrypt = $ciphertext;
                if ( substr($ciphertext_to_decrypt, 0, 5) === 'AES::' ) {
                    $ciphertext_to_decrypt = substr($ciphertext_to_decrypt, 5);
                }
                // Use custom secret - try both methods and compare
                $decrypted_ctr = AesCtr::decrypt($ciphertext_to_decrypt, $secret, 256);
                $decrypted_openssl = AesOpenSSL::decrypt($ciphertext_to_decrypt, $secret);
                
                // Compare results if both succeeded
                if ( $decrypted_ctr !== null && $decrypted_ctr !== false && 
                     $decrypted_openssl !== null && $decrypted_openssl !== false ) {
                    if ( $decrypted_ctr !== $decrypted_openssl ) {
                        $error_message = "Decryption mismatch: AesCtr and AesOpenSSL produced different results. " .
                                        "AesCtr result: " . htmlentities(substr($decrypted_ctr, 0, 50)) . 
                                        (strlen($decrypted_ctr) > 50 ? "..." : "") . 
                                        " | AesOpenSSL result: " . htmlentities(substr($decrypted_openssl, 0, 50)) . 
                                        (strlen($decrypted_openssl) > 50 ? "..." : "");
                        // Still show one result (prefer AesCtr as originally specified)
                        $decrypted_result = $decrypted_ctr;
                    } else {
                        // Results match
                        $decrypted_result = $decrypted_ctr;
                    }
                } else if ( $decrypted_ctr !== null && $decrypted_ctr !== false ) {
                    // Only AesCtr succeeded
                    $decrypted_result = $decrypted_ctr;
                } else if ( $decrypted_openssl !== null && $decrypted_openssl !== false ) {
                    // Only AesOpenSSL succeeded
                    $decrypted_result = $decrypted_openssl;
                } else {
                    // Both failed
                    $error_message = "Decryption failed: Both AesCtr::decrypt and AesOpenSSL::decrypt failed to decrypt the ciphertext.";
                }
            } else {
                // Use default LTIX decryption
                $decrypted_result = LTIX::decrypt_secret($ciphertext);
            }
        } catch (Exception $e) {
            $error_message = "Decryption error: " . htmlentities($e->getMessage());
        }
    } else {
        $error_message = "Please enter a value to decrypt";
    }
}

?>
<html>
<head>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
}
h1 {
    color: #333;
}
.form-section {
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}
.form-section h2 {
    margin-top: 0;
    color: #555;
}
textarea {
    width: 100%;
    min-height: 100px;
    padding: 8px;
    font-family: monospace;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box;
}
textarea.secret-field {
    width: 100%;
    min-height: 40px;
    max-height: 80px;
    padding: 8px;
    font-family: monospace;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box;
    margin-top: 5px;
    resize: vertical;
}
input[type="submit"] {
    padding: 8px 16px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 14px;
    margin-top: 10px;
}
input[type="submit"]:hover {
    background-color: #45a049;
}
.result {
    margin-top: 15px;
    padding: 10px;
    background-color: #e8f5e9;
    border: 1px solid #4CAF50;
    border-radius: 3px;
}
.result-label {
    font-weight: bold;
    color: #2e7d32;
    margin-bottom: 5px;
}
.result-value {
    font-family: monospace;
    word-break: break-all;
    white-space: pre-wrap;
}
.error {
    margin-top: 15px;
    padding: 10px;
    background-color: #ffebee;
    border: 1px solid #f44336;
    border-radius: 3px;
    color: #c62828;
}
</style>
</head>
<body>
<h1>Encrypt/Decrypt Strings</h1>
<p>Use this tool to encrypt and decrypt strings using LTIX encryption methods.</p>

<?php if ( $error_message ) { ?>
<div class="error"><?= $error_message ?></div>
<?php } ?>

<div class="form-section">
<h2>Encrypt String</h2>
<form method="POST" autocomplete="off">
<label for="encrypt_value">Enter plaintext to encrypt:</label><br/>
<textarea name="encrypt_value" id="encrypt_value" placeholder="Enter text to encrypt..."><?= htmlentities(U::get($_POST, "encrypt_value", "")) ?></textarea><br/>
<label for="encrypt_secret">Secret (optional - if provided, uses AesOpenSSL::encrypt):</label><br/>
<textarea name="encrypt_secret" id="encrypt_secret" class="secret-field" autocomplete="off" placeholder="Leave empty to use default LTIX encryption"><?= htmlentities(U::get($_POST, "encrypt_secret", "")) ?></textarea><br/>
<input type="submit" name="encrypt" value="Encrypt">
</form>
<?php if ( $encrypted_result !== null ) { ?>
<div class="result">
<div class="result-label">Encrypted Result:</div>
<div class="result-value"><?= htmlentities($encrypted_result) ?></div>
</div>
<?php } ?>
</div>

<div class="form-section">
<h2>Decrypt String</h2>
<form method="POST" autocomplete="off">
<label for="decrypt_value">Enter encrypted string to decrypt:</label><br/>
<textarea name="decrypt_value" id="decrypt_value" placeholder="Enter encrypted text (e.g., AES::...)..."><?= htmlentities(U::get($_POST, "decrypt_value", "")) ?></textarea><br/>
<label for="decrypt_secret">Secret (optional - if provided, uses AesCtr::decrypt):</label><br/>
<textarea name="decrypt_secret" id="decrypt_secret" class="secret-field" autocomplete="off" placeholder="Leave empty to use default LTIX decryption"><?= htmlentities(U::get($_POST, "decrypt_secret", "")) ?></textarea><br/>
<input type="submit" name="decrypt" value="Decrypt">
</form>
<?php if ( $decrypted_result !== null ) { ?>
<div class="result">
<div class="result-label">Decrypted Result:</div>
<div class="result-value"><?= htmlentities($decrypted_result) ?></div>
</div>
<?php } ?>
</div>

</body>
</html>

