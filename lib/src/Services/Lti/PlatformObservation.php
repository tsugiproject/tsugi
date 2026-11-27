<?php

namespace Tsugi\Services\Lti;

use Tsugi\Core\LTIX;
use Tsugi\Services\Settings\DynamicRegistration;
use Tsugi\Util\LTI13;

/**
 * Observational platform metadata from a registration or a validated launch.
 *
 * A row is a snapshot. lti_key.platform_id moves forward. registration_platform_id
 * does not. Launches do not move the client's pointers. Nothing here chooses a
 * key, a deployment, or a tenant. A failure is logged and swallowed.
 */
class PlatformObservation {

    /**
     * Compared scalars. Endpoint URLs, deployment ids, and launch claims
     * outside tool_platform are not in this list.
     *
     * @return list<string>
     */
    public static function fields() {
        return array(
            'platform_guid',
            'platform_name',
            'platform_description',
            'platform_url',
            'platform_family',
            'platform_version',
            'platform_contact_email',
        );
    }

    /**
     * @return array<string, null>
     */
    public static function blank() {
        $row = array();
        foreach ( self::fields() as $field ) {
            $row[$field] = null;
        }
        return $row;
    }

    /**
     * tool_platform claim. Unknown claim fields are ignored.
     *
     * @param mixed $claim
     * @return array<string, string|null>
     */
    public static function scalarsFromToolPlatform($claim) {
        $claim = self::asArray($claim);
        $row = self::blank();
        if ( ! is_array($claim) ) return $row;
        $row['platform_guid'] = self::toPrintableAscii(self::value($claim, 'guid'), 255);
        $row['platform_name'] = self::text(self::value($claim, 'name'), 4000);
        $row['platform_description'] = self::text(self::value($claim, 'description'), 4000);
        $row['platform_url'] = self::text(self::value($claim, 'url'), 2000);
        $row['platform_family'] = self::toPrintableAscii(self::value($claim, 'product_family_code'), 64);
        $row['platform_version'] = self::toPrintableAscii(self::value($claim, 'version'), 64);
        $row['platform_contact_email'] = self::toPrintableAscii(self::value($claim, 'contact_email'), 255);
        return $row;
    }

    /**
     * Scalars from the platform's OpenID configuration.
     *
     * The registration response is stored in JSON and is not a source of
     * these scalars. Its client_name and contacts describe this tool.
     *
     * @param mixed $openid
     * @return array<string, string|null>
     */
    public static function scalarsFromRegistration($openid) {
        $row = self::blank();
        $block = self::platformBlock($openid);
        if ( ! is_array($block) ) return $row;
        $guid = self::value($block, 'guid');
        if ( $guid === null ) $guid = self::value($block, 'platform_guid');
        $row['platform_guid'] = self::toPrintableAscii($guid, 255);
        $row['platform_name'] = self::text(self::value($block, 'name'), 4000);
        $row['platform_description'] = self::text(self::value($block, 'description'), 4000);
        $row['platform_url'] = self::text(self::value($block, 'url'), 2000);
        $row['platform_family'] = self::toPrintableAscii(self::value($block, 'product_family_code'), 64);
        $row['platform_version'] = self::toPrintableAscii(self::value($block, 'version'), 64);
        $row['platform_contact_email'] = self::toPrintableAscii(self::value($block, 'contact_email'), 255);
        return $row;
    }

    /**
     * Non-empty observed values replace. Missing values leave the current ones.
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $observed
     * @return array<string, string|null>
     */
    public static function merge(array $current, array $observed) {
        $out = self::blank();
        foreach ( self::fields() as $field ) {
            $next = $observed[$field] ?? null;
            if ( is_string($next) && $next !== '' ) {
                $out[$field] = $next;
                continue;
            }
            $kept = $current[$field] ?? null;
            $out[$field] = (is_string($kept) && $kept !== '') ? $kept : null;
        }
        return $out;
    }

    /**
     * True when applying $observed onto $current changes no compared scalar.
     * A missing observed value is not a change.
     *
     * @param array<string, mixed> $current
     * @param array<string, mixed> $observed
     */
    public static function same(array $current, array $observed) {
        $merged = self::merge($current, $observed);
        foreach ( self::fields() as $field ) {
            $a = $current[$field] ?? null;
            $b = $merged[$field] ?? null;
            if ( ! is_string($a) || $a === '' ) $a = null;
            if ( ! is_string($b) || $b === '' ) $b = null;
            if ( $a !== $b ) return false;
        }
        return true;
    }

    /**
     * @param mixed $openid
     * @param mixed $response
     */
    public static function registrationJson($openid, $response) {
        $doc = array(
            'openid_configuration' => self::scrub($openid),
            'registration_response' => self::scrub($response),
        );
        return self::encode($doc);
    }

    /**
     * The tool_platform claim and the issuer. Not the rest of the JWT.
     *
     * @param mixed $claim
     * @param mixed $issuer
     */
    public static function launchJson($claim, $issuer) {
        $doc = array();
        $platform = self::scrub($claim);
        if ( is_array($platform) && count($platform) > 0 ) {
            $doc['tool_platform'] = $platform;
        }
        $iss = self::text($issuer, 2000);
        if ( $iss !== null ) $doc['issuer'] = $iss;
        if ( count($doc) === 0 ) return null;
        return self::encode($doc);
    }

    /**
     * Drop credential fields before the document is stored.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function scrub($value) {
        if ( is_object($value) ) $value = get_object_vars($value);
        if ( ! is_array($value) ) return $value;
        $out = array();
        foreach ( $value as $key => $item ) {
            if ( is_string($key) && self::secretKey($key) ) continue;
            $out[$key] = self::scrub($item);
        }
        return $out;
    }

    /**
     * Columns selected by LTIX::loadAllData(). No JSON.
     */
    public static function launchSelectSql($prefix, $keyAlias) {
        if ( ! self::safePrefix($prefix) || ! self::safeAlias($keyAlias) ) return '';
        return $keyAlias.'.platform_id AS key_platform_id, '
            .'plat.platform_guid AS plat_platform_guid, '
            .'plat.platform_name AS plat_platform_name, '
            .'plat.platform_description AS plat_platform_description, '
            .'plat.platform_url AS plat_platform_url, '
            .'plat.platform_family AS plat_platform_family, '
            .'plat.platform_version AS plat_platform_version, '
            .'plat.platform_contact_email AS plat_platform_contact_email';
    }

    /**
     * LEFT JOIN for the key's current platform row. One row, primary key.
     */
    public static function launchJoinSql($prefix, $keyAlias) {
        if ( ! self::safePrefix($prefix) || ! self::safeAlias($keyAlias) ) return '';
        return "LEFT JOIN {$prefix}lti_platform AS plat ON plat.platform_id = {$keyAlias}.platform_id";
    }

    /**
     * True when this install can store an observation.
     *
     * @param string $prefix
     */
    public static function ready($prefix) {
        static $seen = array();
        if ( array_key_exists($prefix, $seen) ) return $seen[$prefix];
        try {
            $PDOX = LTIX::getConnection();
            $key = $prefix.'lti_key';
            $seen[$prefix] = $PDOX->metadata($prefix.'lti_platform') !== false
                && $PDOX->columnExists('platform_id', $key)
                && $PDOX->columnExists('registration_platform_id', $key)
                && $PDOX->columnExists('platform_guid', $key);
        } catch ( \Throwable $e ) {
            $seen[$prefix] = false;
        }
        return $seen[$prefix];
    }

    /**
     * After the launch JWT is valid. The current snapshot is already on $row
     * from loadAllData(). This does not run its own SELECT.
     *
     * @param array<string, mixed> $row
     * @param mixed $body Parsed JWT body
     */
    public static function observeLaunch(array $row, $body) {
        try {
            global $CFG;
            if ( ! isset($CFG) || ! self::ready($CFG->dbprefix) ) return;
            if ( ! array_key_exists('key_platform_id', $row) ) {
                error_log('Platform observation: launch row has no key_platform_id. Skipped.');
                return;
            }
            $key_id = (int) ($row['key_id'] ?? 0);
            if ( $key_id < 1 ) return;
            $claim = null;
            if ( is_object($body) && isset($body->{LTI13::TOOL_PLATFORM_CLAIM}) ) {
                $claim = $body->{LTI13::TOOL_PLATFORM_CLAIM};
            } elseif ( is_array($body) && isset($body[LTI13::TOOL_PLATFORM_CLAIM]) ) {
                $claim = $body[LTI13::TOOL_PLATFORM_CLAIM];
            }
            $observed = self::scalarsFromToolPlatform($claim);
            $current = self::scalarsFromLaunchRow($row);
            if ( self::same($current, $observed) ) return;
            $merged = self::merge($current, $observed);
            $issuer = is_object($body) ? ($body->iss ?? null) : (is_array($body) ? ($body['iss'] ?? null) : null);
            $id = self::insert('launch', $merged, self::launchJson($claim, $issuer), null);
            if ( $id < 1 ) return;
            self::advanceKey($key_id, $id, self::idOrNull($row['key_platform_id']), $merged['platform_guid']);
        } catch ( \Throwable $e ) {
            error_log('Platform observation: '.$e->getMessage());
        }
    }

    /**
     * The key's registration just stored its issuer and client id.
     *
     * Returns the new snapshot id when one was inserted, the id a new client
     * should share, and the guid from this registration document.
     *
     * @param mixed $openid
     * @param mixed $response
     * @return array{inserted_id:int|null, share_id:int|null, guid:string|null}|null
     */
    public static function recordKeyRegistration($keyId, $openid, $response) {
        try {
            global $CFG;
            $key_id = (int) $keyId;
            if ( $key_id < 1 || ! isset($CFG) || ! self::ready($CFG->dbprefix) ) return null;
            $existing = self::keySnapshot($key_id);
            if ( ! is_array($existing) ) return null;
            $observed = self::scalarsFromRegistration($openid);
            $current = self::scalarsFromLaunchRow($existing);
            $provenance = self::idOrNull($existing['key_registration_platform_id'] ?? null);
            $inserted = null;
            if ( $provenance === null || ! self::same($current, $observed) ) {
                $merged = self::merge($current, $observed);
                $inserted = self::insert('registration', $merged, null, self::registrationJson($openid, $response));
                if ( $inserted < 1 ) $inserted = null;
                if ( $inserted !== null ) {
                    if ( $provenance === null ) self::keepProvenance($key_id, $inserted);
                    self::advanceKey(
                        $key_id,
                        $inserted,
                        self::idOrNull($existing['key_platform_id'] ?? null),
                        $merged['platform_guid']
                    );
                }
            }
            $share = $inserted !== null ? $inserted : $provenance;
            return array(
                'inserted_id' => $inserted,
                'share_id' => $share,
                'guid' => $observed['platform_guid'],
            );
        } catch ( \Throwable $e ) {
            error_log('Platform observation: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Point a client this registration just inserted at the shared snapshot.
     * An existing client is not updated.
     *
     * @param array{inserted_id?:int|null, share_id?:int|null, guid?:string|null}|null $record
     */
    public static function attachNewClient($clientId, $record) {
        try {
            global $CFG, $PDOX;
            $client_id = (int) $clientId;
            if ( $client_id < 1 || ! is_array($record) || ! isset($CFG) || ! isset($PDOX) || ! $PDOX ) return;
            $share = self::idOrNull($record['share_id'] ?? null);
            if ( $share === null || ! self::ready($CFG->dbprefix) ) return;
            if ( ! $PDOX->columnExists('platform_id', $CFG->dbprefix.'lti_client') ) return;
            if ( ! $PDOX->columnExists('registration_platform_id', $CFG->dbprefix.'lti_client') ) return;
            $guid = $record['guid'] ?? null;
            if ( ! is_string($guid) || $guid === '' ) $guid = null;
            $q = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_client
                    SET platform_id = :pid,
                        registration_platform_id = :reg,
                        platform_guid = COALESCE(platform_guid, :guid)
                    WHERE lti_client_id = :id
                      AND platform_id IS NULL
                      AND registration_platform_id IS NULL",
                array(':pid' => $share, ':reg' => $share, ':guid' => $guid, ':id' => $client_id)
            );
            if ( ! $q->success ) {
                error_log('Platform observation: could not attach client '.$client_id.'. '.$q->errorImplode);
            }
        } catch ( \Throwable $e ) {
            error_log('Platform observation: '.$e->getMessage());
        }
    }

    /**
     * Copy the client's platform guid onto the key once, while the key's is null.
     *
     * @param mixed $keyId
     * @param mixed $clientId
     */
    public static function copyGuidFromClient($keyId, $clientId) {
        try {
            global $CFG, $PDOX;
            $key_id = (int) $keyId;
            $client_id = (int) $clientId;
            if ( $key_id < 1 || $client_id < 1 || ! isset($CFG) || ! isset($PDOX) || ! $PDOX ) return;
            if ( ! self::ready($CFG->dbprefix) ) return;
            if ( ! $PDOX->columnExists('platform_guid', $CFG->dbprefix.'lti_client') ) return;
            $q = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_key AS k
                    INNER JOIN {$CFG->dbprefix}lti_client AS c ON c.lti_client_id = k.lti_client_id
                    SET k.platform_guid = c.platform_guid
                    WHERE k.key_id = :id
                      AND k.lti_client_id = :client
                      AND k.platform_guid IS NULL
                      AND c.platform_guid IS NOT NULL",
                array(':id' => $key_id, ':client' => $client_id)
            );
            if ( ! $q->success ) {
                error_log('Platform observation: could not copy platform guid onto key '.$key_id.'. '.$q->errorImplode);
            }
        } catch ( \Throwable $e ) {
            error_log('Platform observation: '.$e->getMessage());
        }
    }

    /**
     * After dynamic registration has created or linked a client.
     *
     * @param array<string, mixed> $stored
     * @param array{inserted_id?:int|null, share_id?:int|null, guid?:string|null}|null $record
     */
    public static function finishRegistrationLink($keyId, array $stored, $record) {
        $client_id = (int) ($stored['id'] ?? 0);
        if ( $client_id < 1 ) return;
        if ( ! empty($stored['created']) ) self::attachNewClient($client_id, $record);
        if ( ! empty($stored['linked']) ) self::copyGuidFromClient($keyId, $client_id);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string|null>
     */
    private static function scalarsFromLaunchRow(array $row) {
        return array(
            'platform_guid' => self::toPrintableAscii($row['plat_platform_guid'] ?? null, 255),
            'platform_name' => self::text($row['plat_platform_name'] ?? null, 4000),
            'platform_description' => self::text($row['plat_platform_description'] ?? null, 4000),
            'platform_url' => self::text($row['plat_platform_url'] ?? null, 2000),
            'platform_family' => self::toPrintableAscii($row['plat_platform_family'] ?? null, 64),
            'platform_version' => self::toPrintableAscii($row['plat_platform_version'] ?? null, 64),
            'platform_contact_email' => self::toPrintableAscii($row['plat_platform_contact_email'] ?? null, 255),
        );
    }

    /**
     * @param mixed $openid
     * @return array<string, mixed>|null
     */
    private static function platformBlock($openid) {
        $key = 'https://purl.imsglobal.org/spec/lti-platform-configuration';
        $block = null;
        if ( is_object($openid) && isset($openid->{$key}) ) $block = $openid->{$key};
        if ( is_array($openid) && isset($openid[$key]) ) $block = $openid[$key];
        $block = self::asArray($block);
        return is_array($block) ? $block : null;
    }

    /**
     * @param array<string, mixed> $claim
     * @param mixed $value
     * @return mixed
     */
    private static function value(array $claim, $key) {
        if ( ! array_key_exists($key, $claim) ) return null;
        return $claim[$key];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private static function asArray($value) {
        if ( is_object($value) ) $value = get_object_vars($value);
        return is_array($value) ? $value : null;
    }

    /**
     * Printable ASCII for an ascii column.
     *
     * A value that is already printable ASCII is trimmed and kept.
     * Unicode is folded first (é to e, ß to ss). What is still not
     * printable ASCII is removed. Nothing left, or a result past $max, is null.
     *
     * @param mixed $value
     * @param int $max
     * @return string|null
     */
    public static function toPrintableAscii($value, $max) {
        if ( is_int($value) || is_float($value) ) $value = (string) $value;
        if ( ! is_string($value) ) return null;
        $value = trim($value);
        if ( $value === '' ) return null;
        if ( preg_match('/[^\x20-\x7E]/', $value) === 1 ) {
            $value = self::foldToAscii($value);
            $value = preg_replace('/[^\x20-\x7E]+/', ' ', $value);
            if ( ! is_string($value) ) return null;
            $value = preg_replace('/ {2,}/', ' ', $value);
            if ( ! is_string($value) ) return null;
            $value = trim($value);
        }
        if ( $value === '' || strlen($value) > $max ) return null;
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    private static function foldToAscii($value) {
        static $transliterator = false;
        if ( $transliterator === false ) {
            $transliterator = null;
            if ( class_exists(\Transliterator::class) ) {
                $created = \Transliterator::create('Any-Latin; Latin-ASCII');
                if ( $created instanceof \Transliterator ) $transliterator = $created;
            }
        }
        if ( $transliterator instanceof \Transliterator ) {
            $folded = $transliterator->transliterate($value);
            if ( is_string($folded) ) return $folded;
        }
        if ( class_exists(\Normalizer::class) ) {
            $decomposed = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if ( is_string($decomposed) ) {
                $stripped = preg_replace('/\p{M}/u', '', $decomposed);
                if ( is_string($stripped) ) return $stripped;
            }
        }
        return $value;
    }

    /**
     * @param mixed $value
     */
    private static function text($value, $max) {
        if ( is_int($value) || is_float($value) ) $value = (string) $value;
        if ( ! is_string($value) ) return null;
        $value = trim($value);
        if ( $value === '' ) return null;
        if ( function_exists('mb_strlen') && mb_strlen($value) > $max ) return null;
        if ( ! function_exists('mb_strlen') && strlen($value) > $max ) return null;
        return $value;
    }

    /**
     * @param mixed $id
     */
    private static function idOrNull($id) {
        if ( $id === null || $id === '' ) return null;
        $id = (int) $id;
        return $id > 0 ? $id : null;
    }

    private static function secretKey($key) {
        $key = strtolower($key);
        return in_array($key, array(
            'registration_access_token',
            'client_secret',
            'registration_token',
            'unlock_code',
            'authorization',
            'password',
            'secret',
        ), true);
    }

    /**
     * @param mixed $doc
     */
    private static function encode($doc) {
        $json = json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ( ! is_string($json) || $json === '' || $json === 'null' ) return null;
        $clean = DynamicRegistration::redactSecrets($json);
        return $clean === '' ? null : $clean;
    }

    private static function safePrefix($prefix) {
        return is_string($prefix) && preg_match('/^[A-Za-z0-9_]*$/', $prefix) === 1;
    }

    private static function safeAlias($alias) {
        return is_string($alias) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias) === 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function keySnapshot($keyId) {
        global $CFG, $PDOX;
        if ( ! isset($PDOX) || ! $PDOX || ! isset($CFG) ) return null;
        $p = $CFG->dbprefix;
        $q = $PDOX->queryReturnError(
            "SELECT k.platform_id AS key_platform_id,
                    k.registration_platform_id AS key_registration_platform_id,
                    p.platform_guid AS plat_platform_guid,
                    p.platform_name AS plat_platform_name,
                    p.platform_description AS plat_platform_description,
                    p.platform_url AS plat_platform_url,
                    p.platform_family AS plat_platform_family,
                    p.platform_version AS plat_platform_version,
                    p.platform_contact_email AS plat_platform_contact_email
                FROM {$p}lti_key AS k
                LEFT JOIN {$p}lti_platform AS p ON p.platform_id = k.platform_id
                WHERE k.key_id = :id",
            array(':id' => $keyId)
        );
        if ( ! $q->success ) return null;
        $row = $q->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, string|null> $scalars
     */
    private static function insert($source, array $scalars, $platformJson, $registrationJson) {
        global $CFG, $PDOX;
        if ( $source !== 'launch' && $source !== 'registration' ) return 0;
        if ( ! isset($PDOX) || ! $PDOX || ! isset($CFG) ) return 0;
        $q = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_platform
                (platform_guid, platform_name, platform_description, platform_url,
                    platform_family, platform_version, platform_contact_email,
                    platform_json, registration_json, source, created_at)
            VALUES
                (:guid, :name, :description, :url, :family, :version, :contact,
                    :platform_json, :registration_json, :source, NOW())",
            array(
                ':guid' => $scalars['platform_guid'],
                ':name' => $scalars['platform_name'],
                ':description' => $scalars['platform_description'],
                ':url' => $scalars['platform_url'],
                ':family' => $scalars['platform_family'],
                ':version' => $scalars['platform_version'],
                ':contact' => $scalars['platform_contact_email'],
                ':platform_json' => $platformJson,
                ':registration_json' => $registrationJson,
                ':source' => $source,
            )
        );
        if ( ! $q->success ) {
            error_log('Platform observation: insert failed. '.$q->errorImplode);
            return 0;
        }
        return (int) $PDOX->lastInsertId();
    }

    private static function keepProvenance($keyId, $platformId) {
        global $CFG, $PDOX;
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_key
                SET registration_platform_id = :pid
                WHERE key_id = :id AND registration_platform_id IS NULL",
            array(':pid' => $platformId, ':id' => $keyId)
        );
        if ( ! $q->success ) {
            error_log('Platform observation: could not keep registration provenance for key '.$keyId.'. '.$q->errorImplode);
        }
    }

    /**
     * Move platform_id only while it still points at the row we read.
     * A null guid does not clear platform_guid.
     *
     * @param int $oldId
     * @param string|null $guid
     */
    private static function advanceKey($keyId, $platformId, $oldId, $guid) {
        global $CFG, $PDOX;
        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_key
                SET platform_id = :new,
                    platform_guid = COALESCE(:guid, platform_guid)
                WHERE key_id = :id
                  AND platform_id <=> :old",
            array(
                ':new' => $platformId,
                ':guid' => $guid,
                ':id' => $keyId,
                ':old' => $oldId,
            )
        );
        if ( ! $q->success ) {
            error_log('Platform observation: could not advance key '.$keyId.'. '.$q->errorImplode);
        }
    }
}
