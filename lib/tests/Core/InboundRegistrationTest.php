<?php

use Tsugi\Core\LTIX;

class InboundRegistrationTest extends \PHPUnit\Framework\TestCase
{
    public function testCompleteRegistrationReplacesTheWholeSet() {
        $row = LTIX::applyInboundRegistration(array(
            'key_id' => 3,
            'deploy_key' => 'deploy-kept',
            'lms_cache_kid' => 'key-kid',
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_oidc_auth' => 'https://key.example/oidc',
            'lms_keyset_url' => 'https://reg.example/keyset',
            'lms_token_url' => 'https://key.example/token',
            'lms_token_audience' => 'https://key.example/audience',
            'reg_issuer' => 'https://reg.example',
            'reg_client_id' => 'reg-client',
            'reg_oidc_auth_url' => 'https://reg.example/oidc',
            'reg_keyset_url' => 'https://reg.example/keyset',
            'reg_token_url' => 'https://reg.example/token',
            'reg_token_audience' => null,
        ));

        $this->assertSame('https://reg.example', $row['lms_issuer']);
        $this->assertSame('reg-client', $row['lms_client']);
        $this->assertSame('https://reg.example/oidc', $row['lms_oidc_auth']);
        $this->assertSame('https://reg.example/keyset', $row['lms_keyset_url']);
        $this->assertSame('https://reg.example/token', $row['lms_token_url']);
        $this->assertNull($row['lms_token_audience']);
        $this->assertSame('deploy-kept', $row['deploy_key']);
        $this->assertSame('key-kid', $row['lms_cache_kid']);
        $this->assertArrayNotHasKey('reg_issuer', $row);
        $this->assertArrayNotHasKey('reg_token_url', $row);
        $this->assertArrayNotHasKey('reg_token_audience', $row);
    }

    public function testDifferentKeysetUrlDropsTheCachedKid() {
        $row = LTIX::applyInboundRegistration(array(
            'deploy_key' => 'deploy-kept',
            'lms_cache_keyset' => '{"keys":[]}',
            'lms_cache_pubkey' => 'cached-key',
            'lms_cache_kid' => 'key-kid',
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_oidc_auth' => 'https://key.example/oidc',
            'lms_keyset_url' => 'https://key.example/keyset',
            'lms_token_url' => 'https://key.example/token',
            'lms_token_audience' => 'https://key.example/audience',
            'reg_issuer' => 'https://reg.example',
            'reg_client_id' => 'reg-client',
            'reg_oidc_auth_url' => 'https://reg.example/oidc',
            'reg_keyset_url' => 'https://reg.example/keyset',
            'reg_token_url' => 'https://reg.example/token',
            'reg_token_audience' => null,
        ));

        $this->assertNull($row['lms_cache_keyset']);
        $this->assertNull($row['lms_cache_pubkey']);
        $this->assertNull($row['lms_cache_kid']);
        $this->assertSame('https://reg.example/keyset', $row['lms_keyset_url']);
        $this->assertSame('deploy-kept', $row['deploy_key']);
    }

    public function testIncompleteRegistrationLeavesTheKey() {
        $row = LTIX::applyInboundRegistration(array(
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_oidc_auth' => 'https://key.example/oidc',
            'lms_keyset_url' => 'https://key.example/keyset',
            'lms_token_url' => 'https://key.example/token',
            'lms_token_audience' => 'https://key.example/audience',
            'reg_issuer' => 'https://reg.example',
            'reg_client_id' => 'reg-client',
            'reg_oidc_auth_url' => 'https://reg.example/oidc',
            'reg_keyset_url' => 'https://reg.example/keyset',
            'reg_token_url' => '',
            'reg_token_audience' => 'https://reg.example/audience',
        ));

        $this->assertSame('https://key.example', $row['lms_issuer']);
        $this->assertSame('key-client', $row['lms_client']);
        $this->assertSame('https://key.example/oidc', $row['lms_oidc_auth']);
        $this->assertSame('https://key.example/keyset', $row['lms_keyset_url']);
        $this->assertSame('https://key.example/token', $row['lms_token_url']);
        $this->assertSame('https://key.example/audience', $row['lms_token_audience']);
        $this->assertArrayNotHasKey('reg_issuer', $row);
        $this->assertArrayNotHasKey('reg_token_audience', $row);
    }

    public function testLoadAllDataUsesTheRegistrationWhenTheKeyFieldsDiffer() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for registration fixtures.');
        }
        $p = $this->prefix();
        if ( ! LTIX::ltiRegistrationTableExists($p) || ! $pdo->columnExists('lti_registration_id', $p.'lti_key') ) {
            $pdo->rollBack();
            $this->markTestSkipped('lti_registration is not installed.');
        }
        try {
            $stamp = 'reg'.bin2hex(random_bytes(6));
            $issuer = 'http://'.$stamp.'.example';
            $client = $stamp.'-client';
            $pdo->queryDie(
                "INSERT INTO {$p}lti_registration
                    (issuer, client_id, oidc_auth_url, keyset_url, token_url, token_audience, created_at)
                 VALUES (:ISS, :CLIENT, :OIDC, :KEYSET, :TOKEN, NULL, NOW())",
                array(
                    ':ISS' => $issuer,
                    ':CLIENT' => $client,
                    ':OIDC' => 'http://'.$stamp.'.example/oidc',
                    ':KEYSET' => 'http://'.$stamp.'.example/keyset',
                    ':TOKEN' => 'http://'.$stamp.'.example/token',
                )
            );
            $registrationId = (int) $pdo->lastInsertId();
            $keyIssuer = 'http://key-'.$stamp.'.example';
            $pdo->queryDie(
                "INSERT INTO {$p}lti_key
                    (key_key, key_sha256, deploy_key, lms_issuer, lms_issuer_sha256, lms_client,
                     lms_oidc_auth, lms_keyset_url, lms_token_url, lms_token_audience, lms_cache_kid,
                     lti_registration_id, created_at, updated_at)
                 VALUES (:K, :SHA, :DEP, :ISS, :ISHA, :CLIENT, :OIDC, :KEYSET, :TOKEN, :AUD, :KID, :RID, NOW(), NOW())",
                array(
                    ':K' => $stamp,
                    ':SHA' => hash('sha256', $stamp),
                    ':DEP' => 'deploy-kept',
                    ':ISS' => $keyIssuer,
                    ':ISHA' => hash('sha256', $keyIssuer),
                    ':CLIENT' => $stamp.'-key-client',
                    ':OIDC' => 'http://key.example/oidc',
                    ':KEYSET' => 'http://key.example/keyset',
                    ':TOKEN' => 'http://key.example/token',
                    ':AUD' => 'http://key.example/audience',
                    ':KID' => 'key-kid',
                    ':RID' => $registrationId,
                )
            );
            $keyId = (int) $pdo->lastInsertId();

            $loaded = LTIX::loadAllData($p, false, array(
                'issuer_key' => $issuer,
                'issuer_sha256' => hash('sha256', $issuer),
                'issuer_client' => $client,
                'nonce' => 'nonce-'.$stamp,
                'context_id' => 'context-'.$stamp,
                'link_id' => 'link-'.$stamp,
                'user_id' => 'user-'.$stamp,
            ));

            $this->assertIsArray($loaded);
            $this->assertSame($keyId, (int) $loaded['key_id']);
            $this->assertSame($issuer, $loaded['lms_issuer']);
            $this->assertSame($client, $loaded['lms_client']);
            $this->assertSame('http://'.$stamp.'.example/oidc', $loaded['lms_oidc_auth']);
            $this->assertSame('http://'.$stamp.'.example/keyset', $loaded['lms_keyset_url']);
            $this->assertSame('http://'.$stamp.'.example/token', $loaded['lms_token_url']);
            $this->assertNull($loaded['lms_token_audience']);
            $this->assertSame($issuer, $loaded['issuer_key']);
            $this->assertSame($client, $loaded['issuer_client']);
            $this->assertSame('http://'.$stamp.'.example/token', $loaded['lti13_token_url']);
            $this->assertSame('deploy-kept', $loaded['deploy_key']);
            $this->assertNull($loaded['lms_cache_kid']);
            $this->assertArrayNotHasKey('reg_token_url', $loaded);

            $stored = $pdo->rowDie(
                "SELECT lms_issuer, lms_client, lms_token_url, lms_token_audience
                 FROM {$p}lti_key WHERE key_id = :KID",
                array(':KID' => $keyId)
            );
            $this->assertSame($keyIssuer, $stored['lms_issuer']);
            $this->assertSame($stamp.'-key-client', $stored['lms_client']);
            $this->assertSame('http://key.example/token', $stored['lms_token_url']);
            $this->assertSame('http://key.example/audience', $stored['lms_token_audience']);
        } finally {
            $pdo->rollBack();
        }
    }

    public function testLookupUsesRegistrationIdentityOnlyWhenTheRegistrationIsComplete() {
        $pdo = $this->beginFixtureTransaction();
        if ( $pdo === null ) {
            $this->markTestSkipped('Database not available for registration fixtures.');
        }
        $p = $this->prefix();
        if ( ! LTIX::ltiRegistrationTableExists($p) || ! $pdo->columnExists('lti_registration_id', $p.'lti_key') ) {
            $pdo->rollBack();
            $this->markTestSkipped('lti_registration is not installed.');
        }
        try {
            $stamp = 'gap'.bin2hex(random_bytes(6));
            $issuer = 'http://'.$stamp.'.example';
            $client = $stamp.'-client';
            $pdo->queryDie(
                "INSERT INTO {$p}lti_registration
                    (issuer, client_id, oidc_auth_url, keyset_url, token_url, created_at)
                 VALUES (:ISS, :CLIENT, :OIDC, :KEYSET, NULL, NOW())",
                array(
                    ':ISS' => $issuer,
                    ':CLIENT' => $client,
                    ':OIDC' => 'http://'.$stamp.'.example/oidc',
                    ':KEYSET' => 'http://'.$stamp.'.example/keyset',
                )
            );
            $registrationId = (int) $pdo->lastInsertId();
            $keyClient = $stamp.'-key-client';
            $pdo->queryDie(
                "INSERT INTO {$p}lti_key
                    (key_key, key_sha256, lms_issuer, lms_issuer_sha256, lms_client, lms_token_url,
                     lti_registration_id, created_at, updated_at)
                 VALUES (:K, :SHA, :ISS, :ISHA, :CLIENT, :TOKEN, :RID, NOW(), NOW())",
                array(
                    ':K' => $stamp,
                    ':SHA' => hash('sha256', $stamp),
                    ':ISS' => 'http://key-'.$stamp.'.example',
                    ':ISHA' => hash('sha256', 'http://key-'.$stamp.'.example'),
                    ':CLIENT' => $keyClient,
                    ':TOKEN' => 'http://key.example/token',
                    ':RID' => $registrationId,
                )
            );
            $incompleteKeyId = (int) $pdo->lastInsertId();
            $byRegistration = LTIX::loadAllData($p, false, $this->launchPost($issuer, $client));
            $this->assertTrue(! is_array($byRegistration) || (int) $byRegistration['key_id'] !== $incompleteKeyId);

            $pdo->queryDie(
                "UPDATE {$p}lti_registration
                 SET token_url = :TOKEN, updated_at = NOW()
                 WHERE lti_registration_id = :RID",
                array(
                    ':TOKEN' => 'http://'.$stamp.'.example/token',
                    ':RID' => $registrationId,
                )
            );
            $byOldKey = LTIX::loadAllData($p, false, $this->launchPost('http://key-'.$stamp.'.example', $keyClient));
            $this->assertTrue(! is_array($byOldKey) || (int) $byOldKey['key_id'] !== $incompleteKeyId);

            $byNewRegistration = LTIX::loadAllData($p, false, $this->launchPost($issuer, $client));
            $this->assertIsArray($byNewRegistration);
            $this->assertSame($incompleteKeyId, (int) $byNewRegistration['key_id']);
            $this->assertSame('http://'.$stamp.'.example/token', $byNewRegistration['lms_token_url']);
        } finally {
            $pdo->rollBack();
        }
    }

    private function launchPost($issuer, $client) {
        $stamp = bin2hex(random_bytes(4));
        return array(
            'issuer_key' => $issuer,
            'issuer_sha256' => hash('sha256', $issuer),
            'issuer_client' => $client,
            'nonce' => 'nonce-'.$stamp,
            'context_id' => 'context-'.$stamp,
            'link_id' => 'link-'.$stamp,
            'user_id' => 'user-'.$stamp,
        );
    }

    private function beginFixtureTransaction() {
        global $CFG, $PDOX;
        $config = dirname(__DIR__, 3).'/config.php';
        if ( ! is_file($config) ) {
            return null;
        }
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            require_once $config;
        }
        try {
            if ( ! defined('PDO_WILL_CATCH') ) define('PDO_WILL_CATCH', true);
            $PDOX = LTIX::getConnection();
            if ( ! is_object($PDOX) ) {
                return null;
            }
            $PDOX->beginTransaction();
        } catch ( \Throwable $ex ) {
            return null;
        }
        return $PDOX;
    }

    private function prefix() {
        global $CFG;
        return $CFG->dbprefix;
    }
}
