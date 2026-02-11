<?php
/**
 * Generate Ed25519 key pair for OB3 badge signing (DataIntegrityProof)
 *
 * Run from CLI: php scripts/badge_ob3_keygen.php
 * Or from browser when logged in as admin (requires $CFG->adminpw)
 * Note: scripts/ folder typically denies web access via .htaccess.
 *
 * Add the output to your config.php to enable OB3 proof signing.
 */

$is_cli = (php_sapi_name() === 'cli');

// Check for required extensions before loading config (gmp or bcmath needed for base58 encoding)
if (!function_exists('gmp_init') && !function_exists('bcadd')) {
    $msg = "OB3 keygen requires the PHP gmp or bcmath extension.\n" .
           "Install one: apt-get install php-gmp  OR  apt-get install php-bcmath";
    if ($is_cli) {
        echo "Error: $msg\n";
        exit(1);
    }
    // Web: show error page
    define('COOKIE_SESSION', true);
    require_once dirname(__DIR__) . '/config.php';
    require_once $CFG->dirroot . '/admin/admin_util.php';
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    echo "<div class='container'><div class='alert alert-danger'><strong>Missing PHP extension:</strong> $msg</div></div>";
    $OUTPUT->footer();
    exit(1);
}

define('COOKIE_SESSION', true);
require_once dirname(__DIR__) . '/config.php';

use \Tsugi\Util\Ob3DataIntegrity;

if (!$is_cli) {
    require_once $CFG->dirroot . '/admin/admin_util.php';
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    $OUTPUT->flashMessage();
}

try {
    $keypair = Ob3DataIntegrity::generateKeyPair();
    $secretKeyB64 = base64_encode($keypair['secretKey']);
    $publicKeyMultibase = Ob3DataIntegrity::publicKeyToMultibase($keypair['publicKey']);
} catch (\Throwable $e) {
    $msg = $e->getMessage();
    if ($is_cli) {
        echo "Error: $msg\n";
        if (strpos($msg, 'gmp') !== false || strpos($msg, 'bcmath') !== false) {
            echo "\nInstall one: apt-get install php-gmp  OR  apt-get install php-bcmath\n";
        }
        exit(1);
    }
    echo "<div class='container'><div class='alert alert-danger'><strong>Key generation failed:</strong> " . htmlspecialchars($msg) . "</div></div>";
    $OUTPUT->footer();
    exit(1);
}

$issuerUrl = $CFG->wwwroot . '/assertions/issuer.json';
$verificationMethodId = $issuerUrl . '#key-0';

$configBlock = <<<EOT
// OB3 DataIntegrityProof signing (add to config.php for OB3 certification)
// Generated: {date}
\$CFG->badge_ob3_secret_key = "{secretKey}";
\$CFG->badge_ob3_verification_method_id = "{vmId}";

EOT;
$configBlock = str_replace(
    ['{date}', '{secretKey}', '{vmId}'],
    [gmdate('Y-m-d H:i:s') . ' UTC', $secretKeyB64, $verificationMethodId],
    $configBlock
);

if ($is_cli) {
    echo "Add these lines to your config.php:\n\n";
    echo $configBlock;
    echo "\nPublic key (derived from secret key—no config needed; published automatically in issuer profile):\n";
    echo $publicKeyMultibase . "\n";
    echo "\n⚠️  Keep badge_ob3_secret_key secret! Never commit it to version control.\n";
    exit(0);
}

echo "<div class='container'><h2>OB3 Signing Key Generator</h2>";
echo "<p>Add these lines to your <code>config.php</code> to enable OB3 DataIntegrityProof signing:</p>";
echo "<pre style='background:#f5f5f5; padding:15px; overflow-x:auto;'>";
echo htmlspecialchars($configBlock);
echo "</pre>";
echo "<p><strong>Verification method ID:</strong> <code>" . htmlspecialchars($verificationMethodId) . "</code></p>";
echo "<p><strong>Public key</strong> (derived from secret key—no config needed; published automatically in issuer profile): <code>" . htmlspecialchars($publicKeyMultibase) . "</code></p>";
echo "<p class='alert alert-warning'><strong>⚠️ Security:</strong> Keep <code>badge_ob3_secret_key</code> secret! Never commit it to version control.</p>";
echo "</div>";

if (!$is_cli) {
    $OUTPUT->footer();
}
