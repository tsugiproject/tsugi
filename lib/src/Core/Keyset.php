<?php

namespace Tsugi\Core;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Google\JWT;

/**
 * Helper class for Using Tsugi's internal global outgoing keyset
 */

// TODO: Add a private key encryption regime like Sakai

class Keyset {

     public static $key_rotate_days = 30;
     public static $apc_ttl = 10*60;
     public static $apc_expire = 5*60;
     // public static $apc_expire = 0; // Debug only
     public static $verbose = false;  // Debug only

     // Auto populate and/or rotate the lti_keyset data
     public static function maintain() {
        global $PDOX, $CFG;

        $now = time();
        $apc_check = U::appCacheGet('keyset_last_check', 0);

        $privkey = U::appCacheGet('keyset_privkey', null);
        $kid = U::appCacheGet('keyset_kid', null);

        $delta = abs($now-$apc_check);
        if ( is_string($kid) && is_string($privkey) && $apc_check > 0 && $delta < self::$apc_expire ) {
            if ( self::$verbose ) $retval = "Keyset::maintain Last key rotation check seconds=".$delta;
            return true;
        }

        U::appCacheSet('keyset_last_check', $now, self::$apc_ttl);

        $sql = "SELECT *, NOW() as now FROM {$CFG->dbprefix}lti_keyset ORDER BY created_at DESC LIMIT 10";
        $rows = $PDOX->allRowsDie($sql);

        $days = -1;
        if ( count($rows) > 0 ) {
            $now = new \DateTime($rows[0]['now']);
            $create = new \DateTime($rows[0]['created_at']);
            $delta = $now->diff($create, true);
            $days = $delta->days;
        }

        if ( $days == -1 || $days >= self::$key_rotate_days) {
            error_log("Adding a row to lti_keyset days=".$days);
            // Returns those call by reference parms
            $success = \Tsugi\Util\LTI13::generatePKCS8Pair($publicKey, $privateKey);
            if ( is_string($success) ) return $success;

            $sql = "INSERT INTO {$CFG->dbprefix}lti_keyset (keyset_title, pubkey, privkey)
                VALUES (:title, :pubkey, :privkey)
            ";
            $values = array(
                ":title" => 'From Keyset:maintain()',
                ":pubkey" => $publicKey,
                ":privkey" => $privateKey,
            );
            $stmt = $PDOX->queryReturnError($sql, $values);

            if ( ! $stmt->success ) {
                $retval = "Keyset::maintain Unable to insert new key into keyset\n";
                if ( self::$verbose ) error_log($retval);
                return $retval;
            }

            // Reload our key
            $kid = LTIX::getKidForKey($publicKey);
            error_log("Keyset::maintain Key rotated days=".$days." new kid=".$kid);

            // Clean up very old records after six periods
            $days_to_wait = self::$key_rotate_days*6;
            if ( $days_to_wait < 5 ) $days_to_wait = 5;

            $stmt = $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lti_keyset WHERE
                 created_at < (CURDATE() - INTERVAL ".$days_to_wait." DAY);");

            if ( $stmt->rowCount() > 0 ) {
                error_log("KeySet::maintain table cleanup rows=".$stmt->rowCount());
            }

            return true;

        } else {
            if ( self::$verbose ) error_log("Keyset::maintain No key rotation necessary days=".$days);
            return true;
        }

     }

     // Get the private key and kid - call by reference
     public static function getSigning(&$privkey, &$kid) {
        global $PDOX, $CFG;

        // Make sure we have a key and it is recent
        $success = self::maintain();
        if ( is_string($success) ) return $success;

        $now = time();
        $last_load = U::appCacheGet('keyset_last_load', 0);
        $privkey = U::appCacheGet('keyset_privkey', null);
        $kid = U::appCacheGet('keyset_kid', null);

        // No more than once per expiration period
        $delta = abs($now-$last_load);
        if ( is_string($kid) && is_string($privkey) && $delta < self::$apc_expire ) {
            if ( self::$verbose ) error_log("Keyset::getSigning cache hit seconds=".$delta);
            return true;
        }

        $sql = "SELECT * FROM {$CFG->dbprefix}lti_keyset ORDER BY created_at DESC LIMIT 1";
        $row = $PDOX->rowDie($sql);

        $privkey = LTIX::decrypt_secret($row['privkey']);
        $pubkey = $row['pubkey'];
        $kid = LTIX::getKidForKey($pubkey);
        if ( self::$verbose ) error_log("Keyset::getSigning loaded key from database now=".$now." kid=".$kid);

        // Save for later
        if ( is_string($kid) && is_string($privkey)) {
            U::appCacheSet('keyset_last_load', $now, self::$apc_ttl);
            U::appCacheSet('keyset_privkey', $privkey, self::$apc_ttl);
            U::appCacheSet('keyset_kid', $kid, self::$apc_ttl);
            return true;
        } else {
            U::appCacheDelete('keyset_last_load');
            U::appCacheDelete('keyset_privkey');
            U::appCacheDelete('keyset_kid');
            return "Keyset::getSigning could not load key";
        }
    }

    public static function getCurrentKeys() {
        global $PDOX, $CFG;

        // Make sure we have a key and it is recent
        self::maintain();

        $stmt = $PDOX->queryDie(
        "SELECT pubkey FROM {$CFG->dbprefix}lti_keyset
            WHERE deleted = 0 AND pubkey IS NOT NULL AND privkey IS NOT NULL
            ORDER BY created_at DESC LIMIT 3"
        );
        if ( $stmt->success ) {
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $rows = array();
        }
        return $rows;
    }

    // $pubkey = "-----BEGIN PUBLIC KEY-----
    // MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvESXFmlzHz+nhZXTkjo2 9SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkm GliUIVg+rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDL SRIHFPv+kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2 MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe/13vhLOvRUCTN8h8z+SLORW abxoNIkzuAab0NtfO/Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh/vhG0vltSSIzHsZ8 pwIDAQAB
    // -----END PUBLIC KEY-----";

    // https://stackoverflow.com/questions/11681133/how-to-acquire-modulus-and-exponent-from-existing-rsa-public-key-in-php-openssl
    public static function build_jwk($pubkey) {

        $kid = LTIX::getKidForKey($pubkey);

	$data = openssl_pkey_get_public($pubkey);
	$data = openssl_pkey_get_details($data);

	$key = $data['key'];
	$modulus = $data['rsa']['n'];
	$exponent = $data['rsa']['e'];

        $components = array(
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'e' => JWT::urlsafeB64Encode($exponent),
                    'n' => JWT::urlsafeB64Encode($modulus),
                    'kid' => $kid,
                    'use' => 'sig',
        );

        return $components;
  }
}
