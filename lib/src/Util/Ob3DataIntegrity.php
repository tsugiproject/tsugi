<?php

namespace Tsugi\Util;

use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * Data Integrity Proof for Open Badges 3.0 (eddsa-jcs-2022 cryptosuite)
 *
 * Implements W3C VC Data Integrity EdDSA Cryptosuites - eddsa-jcs-2022.
 * Note: The 1EdTech OB3 verifier at vc.1ed.tech only accepts eddsa-rdfc-2022 for
 * certification. This implementation uses eddsa-jcs-2022 (also in the W3C spec).
 * For 1EdTech certification, consider filing a request to support eddsa-jcs-2022.
 * @see https://www.w3.org/TR/vc-di-eddsa/
 */
class Ob3DataIntegrity {

    /** @var string eddsa-jcs-2022 cryptosuite */
    const CRYPTOSUITE = 'eddsa-jcs-2022';

    /**
     * Create a DataIntegrityProof for an OB3 credential (eddsa-jcs-2022)
     *
     * @param array $credential The credential document (without proof)
     * @param string $verificationMethodId URL of the verification method (e.g. issuer#key-0)
     * @param string $secretKey Ed25519 secret key bytes (64 bytes: 32 seed + 32 public)
     * @return array|null Proof object or null on failure
     */
    public static function createProof(array $credential, $verificationMethodId, $secretKey) {
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            error_log('Ob3DataIntegrity: Invalid secret key length, expected ' . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
            return null;
        }

        $created = gmdate('Y-m-d\TH:i:s\Z');
        $proofOptions = array(
            'type' => 'DataIntegrityProof',
            'cryptosuite' => self::CRYPTOSUITE,
            'created' => $created,
            'verificationMethod' => $verificationMethodId,
            'proofPurpose' => 'assertionMethod'
        );
        if (isset($credential['@context'])) {
            $proofOptions['@context'] = $credential['@context'];
        }

        // Proof config for hashing (clone without proofValue)
        $proofConfig = $proofOptions;

        $canonicalizator = JsonCanonicalizatorFactory::getInstance();

        // 1. Transformed data = JCS(canonicalize credential)
        $transformedData = $canonicalizator->canonicalize($credential);

        // 2. Canonical proof config = JCS(proofConfig)
        $canonicalProofConfig = $canonicalizator->canonicalize($proofConfig);

        // 3. hashData = SHA256(proofConfig) + SHA256(transformedData)
        $proofConfigHash = hash('sha256', $canonicalProofConfig, true);
        $transformedDocHash = hash('sha256', $transformedData, true);
        $hashData = $proofConfigHash . $transformedDocHash;

        // 4. Sign hashData with Ed25519
        $proofBytes = sodium_crypto_sign_detached($hashData, $secretKey);

        // 5. proofValue = base58btc(proofBytes)
        $proofValue = self::base58Encode($proofBytes);

        $proofOptions['proofValue'] = 'z' . $proofValue;
        return $proofOptions;
    }

    /**
     * Extract public key from libsodium secret key (last 32 bytes)
     *
     * @param string $secretKey 64-byte Ed25519 secret key from sodium
     * @return string 32-byte public key
     */
    public static function secretKeyToPublicKey($secretKey) {
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            return '';
        }
        return substr($secretKey, 32, 32);
    }

    /**
     * Encode Ed25519 public key as Multikey (base58btc with 0xed01 prefix)
     * Multikey format: 0xed01 (2 bytes) + public key (32 bytes) = 34 bytes
     *
     * @param string $publicKey 32-byte Ed25519 public key
     * @return string Multibase multikey string (z prefix + base58)
     */
    public static function publicKeyToMultibase($publicKey) {
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return '';
        }
        $multikeyBytes = "\xed\x01" . $publicKey;
        return 'z' . self::base58Encode($multikeyBytes);
    }

    /**
     * Base58 (Bitcoin alphabet) encode - for multibase "z" prefix
     * Alphabet: 123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz
     * Uses GMP or BCMath extension (at least one required for large byte strings).
     */
    private static function base58Encode($input) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $hex = bin2hex($input);
        $result = '';

        if (function_exists('gmp_init')) {
            $num = gmp_init($hex, 16);
            $zero = gmp_init(0);
            while (gmp_cmp($num, $zero) > 0) {
                list($num, $remainder) = gmp_div_qr($num, 58);
                $result = $alphabet[(int) gmp_strval($remainder)] . $result;
            }
        } elseif (function_exists('bcadd')) {
            $num = '0';
            for ($i = 0; $i < strlen($hex); $i++) {
                $num = bcadd(bcmul($num, '16', 0), (string) hexdec($hex[$i]), 0);
            }
            while (bccomp($num, '0', 0) > 0) {
                $remainder = (int) bcmod($num, '58');
                $num = bcdiv(bcsub($num, (string) $remainder, 0), '58', 0);
                $result = $alphabet[$remainder] . $result;
            }
        } else {
            throw new \RuntimeException(
                'OB3 base58 encoding requires PHP gmp or bcmath extension. ' .
                'Install one: apt-get install php-gmp or apt-get install php-bcmath'
            );
        }

        // Leading zeros become '1's in base58
        for ($i = 0; $i < strlen($input) && $input[$i] === "\x00"; $i++) {
            $result = $alphabet[0] . $result;
        }
        return $result;
    }

    /**
     * Generate a new Ed25519 key pair for OB3 signing
     * Returns [ 'secretKey' => string (64 bytes), 'publicKey' => string (32 bytes) ]
     *
     * @return array Key pair
     */
    public static function generateKeyPair() {
        $keypair = sodium_crypto_sign_keypair();
        return array(
            'secretKey' => sodium_crypto_sign_secretkey($keypair),
            'publicKey' => sodium_crypto_sign_publickey($keypair)
        );
    }
}
