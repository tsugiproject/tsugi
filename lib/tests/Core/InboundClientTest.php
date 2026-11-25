<?php

use Tsugi\Core\LTIX;

class InboundClientTest extends \PHPUnit\Framework\TestCase
{
    public function testCompleteClientReplacesTheWholeSet() {
        $row = LTIX::applyInboundClient(array(
            'key_id' => 3,
            'deploy_key' => 'deploy-kept',
            'lms_cache_kid' => 'key-kid',
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_oidc_auth' => 'https://key.example/oidc',
            'lms_keyset_url' => 'https://cli.example/keyset',
            'lms_token_url' => 'https://key.example/token',
            'lms_token_audience' => 'https://key.example/audience',
            'cli_issuer' => 'https://cli.example',
            'cli_client_id' => 'cli-client',
            'cli_oidc_auth_url' => 'https://cli.example/oidc',
            'cli_keyset_url' => 'https://cli.example/keyset',
            'cli_token_url' => 'https://cli.example/token',
            'cli_token_audience' => null,
        ));

        $this->assertSame('https://cli.example', $row['lms_issuer']);
        $this->assertSame('cli-client', $row['lms_client']);
        $this->assertSame('https://cli.example/oidc', $row['lms_oidc_auth']);
        $this->assertSame('https://cli.example/keyset', $row['lms_keyset_url']);
        $this->assertSame('https://cli.example/token', $row['lms_token_url']);
        $this->assertNull($row['lms_token_audience']);
        $this->assertSame('deploy-kept', $row['deploy_key']);
        $this->assertSame('key-kid', $row['lms_cache_kid']);
        $this->assertArrayNotHasKey('cli_issuer', $row);
        $this->assertArrayNotHasKey('cli_token_url', $row);
    }

    public function testIncompleteClientLeavesTheKeyAlone() {
        $row = LTIX::applyInboundClient(array(
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_token_url' => 'https://key.example/token',
            'lms_token_audience' => 'https://key.example/audience',
            'cli_issuer' => 'https://cli.example',
            'cli_client_id' => 'cli-client',
            'cli_oidc_auth_url' => '',
            'cli_keyset_url' => 'https://cli.example/keyset',
            'cli_token_url' => 'https://cli.example/token',
        ));

        $this->assertSame('https://key.example', $row['lms_issuer']);
        $this->assertSame('key-client', $row['lms_client']);
        $this->assertSame('https://key.example/token', $row['lms_token_url']);
        $this->assertSame('https://key.example/audience', $row['lms_token_audience']);
        $this->assertArrayNotHasKey('cli_issuer', $row);
    }

    public function testDifferentKeysetUrlDropsTheCachedKid() {
        $row = LTIX::applyInboundClient(array(
            'deploy_key' => 'deploy-kept',
            'lms_cache_keyset' => '{"keys":[]}',
            'lms_cache_pubkey' => 'cached-key',
            'lms_cache_kid' => 'key-kid',
            'lms_issuer' => 'https://key.example',
            'lms_client' => 'key-client',
            'lms_oidc_auth' => 'https://key.example/oidc',
            'lms_keyset_url' => 'https://key.example/keyset',
            'lms_token_url' => 'https://key.example/token',
            'cli_issuer' => 'https://cli.example',
            'cli_client_id' => 'cli-client',
            'cli_oidc_auth_url' => 'https://cli.example/oidc',
            'cli_keyset_url' => 'https://cli.example/keyset',
            'cli_token_url' => 'https://cli.example/token',
            'cli_token_audience' => null,
        ));

        $this->assertNull($row['lms_cache_keyset']);
        $this->assertNull($row['lms_cache_pubkey']);
        $this->assertNull($row['lms_cache_kid']);
        $this->assertSame('https://cli.example/keyset', $row['lms_keyset_url']);
    }

    public function testSameKeysetUrlKeepsTheCache() {
        $row = LTIX::applyInboundClient(array(
            'lms_cache_keyset' => '{"keys":[]}',
            'lms_cache_pubkey' => 'cached-key',
            'lms_cache_kid' => 'key-kid',
            'lms_keyset_url' => 'https://cli.example/keyset',
            'cli_issuer' => 'https://cli.example',
            'cli_client_id' => 'cli-client',
            'cli_oidc_auth_url' => 'https://cli.example/oidc',
            'cli_keyset_url' => 'https://cli.example/keyset',
            'cli_token_url' => 'https://cli.example/token',
            'cli_token_audience' => 'https://cli.example/aud',
        ));

        $this->assertSame('{"keys":[]}', $row['lms_cache_keyset']);
        $this->assertSame('cached-key', $row['lms_cache_pubkey']);
        $this->assertSame('key-kid', $row['lms_cache_kid']);
        $this->assertSame('https://cli.example/aud', $row['lms_token_audience']);
    }

    public function testRecordedKeysetUrlMismatchDropsTheCache() {
        $row = LTIX::applyInboundClient(array(
            'lms_cache_keyset' => '{"keys":[],"tsugi_keyset_url":"https://old.example/keyset"}',
            'lms_cache_pubkey' => 'cached-key',
            'lms_cache_kid' => 'old-kid',
            'lms_keyset_url' => 'https://cli.example/keyset',
            'cli_issuer' => 'https://cli.example',
            'cli_client_id' => 'cli-client',
            'cli_oidc_auth_url' => 'https://cli.example/oidc',
            'cli_keyset_url' => 'https://cli.example/keyset',
            'cli_token_url' => 'https://cli.example/token',
            'cli_token_audience' => null,
        ));

        $this->assertNull($row['lms_cache_keyset']);
        $this->assertNull($row['lms_cache_pubkey']);
        $this->assertNull($row['lms_cache_kid']);
    }
}
