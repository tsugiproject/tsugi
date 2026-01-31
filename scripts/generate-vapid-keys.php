<?php
/**
 * VAPID Key Generator (CLI Only)
 * 
 * Run this script from command line to generate VAPID keys for push notifications:
 *   php tsugi/scripts/generate-vapid-keys.php
 * 
 * Then add the output keys to tsugi_settings.php
 */

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("Error: This script can only be run from the command line.\n");
}

// Check if OpenSSL is available
if (!extension_loaded('openssl')) {
    die("Error: OpenSSL extension is required. Install it to generate VAPID keys.\n");
}

// Generate EC key pair for VAPID
$config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_EC,
    "curve_name" => "prime256v1"
);

$keyPair = openssl_pkey_new($config);

if (!$keyPair) {
    die("Error: Failed to generate key pair. " . openssl_error_string() . "\n");
}

// Export private key
openssl_pkey_export($keyPair, $privateKeyPem);

// Get public key details
$publicKeyDetails = openssl_pkey_get_details($keyPair);
$publicKeyPem = $publicKeyDetails['key'];

// Extract the public key point (x, y coordinates) from the PEM
// VAPID uses uncompressed point format: 04 + x + y (65 bytes total)
preg_match('/-----BEGIN PUBLIC KEY-----(.*?)-----END PUBLIC KEY-----/s', $publicKeyPem, $matches);
$publicKeyDer = base64_decode($matches[1]);

// Parse the DER to get the raw public key point
// This is a simplified approach - for production, use a proper library
$publicKeyRaw = substr($publicKeyDer, -65); // Last 65 bytes contain the point

// Convert to base64url (URL-safe base64)
$publicKeyBase64 = rtrim(strtr(base64_encode($publicKeyRaw), '+/', '-_'), '=');

// For private key, we need to extract it from the PEM
preg_match('/-----BEGIN EC PRIVATE KEY-----(.*?)-----END EC PRIVATE KEY-----/s', $privateKeyPem, $matches);
$privateKeyDer = base64_decode($matches[1]);

// Extract the private key (32 bytes)
// This is simplified - proper extraction requires parsing ASN.1 structure
// For now, we'll use a workaround with OpenSSL
$privateKeyResource = openssl_pkey_get_private($privateKeyPem);
$privateKeyDetails = openssl_pkey_get_details($privateKeyResource);
$privateKeyRaw = $privateKeyDetails['ec']['d']; // 'd' is the private key value

// Convert to base64url
$privateKeyBase64 = rtrim(strtr(base64_encode($privateKeyRaw), '+/', '-_'), '=');

echo "\n";
echo "========================================\n";
echo "VAPID Keys Generated Successfully!\n";
echo "========================================\n\n";
echo "Add these to tsugi_settings.php:\n\n";
echo "\$CFG->vapid_public_key = '" . $publicKeyBase64 . "';\n";
echo "\$CFG->vapid_private_key = '" . $privateKeyBase64 . "';\n";
echo "\$CFG->vapid_subject = 'mailto:drchuck@learnxp.com';\n\n";
echo "========================================\n";
echo "Public Key (for reference):\n";
echo $publicKeyBase64 . "\n\n";
echo "Private Key (KEEP SECRET - never commit to git):\n";
echo $privateKeyBase64 . "\n";
echo "========================================\n\n";
