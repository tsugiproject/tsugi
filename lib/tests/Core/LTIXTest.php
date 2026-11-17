<?php

require_once "src/Core/LTIX.php";
require_once "src/Util/PDOX.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Core\LTIX;
use \Firebase\JWT\JWT;

class LTIXTest extends \PHPUnit\Framework\TestCase
{
    public function testSecretEncrypt() {
        global $CFG;
        $CFG = $CFG ?? new \Tsugi\Config\ConfigInfo("dir", "www");
        $CFG->cookiesecret = "hockey";
        $zap = LTIX::encrypt_secret("apereo");
        $this->assertTrue(strpos($zap,"AES::") === 0 );
        $zot = LTIX::decrypt_secret($zap);
        $this->assertEquals($zot,"apereo");
    }

    public function testCSRF() {
        $this->assertEquals(LTIX::ltiParameter('bob', 'sam'), 'sam');
    }

    /**
     * Test session access via $_SESSION (replaces former wrapped_session tests)
     */
    public function testSessionAccess() {
        $level_before = ob_get_level();
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['x'] = 'y';
        $this->assertEquals('y', $_SESSION['x'] ?? 'sam');
        unset($_SESSION['x']);
        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['a'] = 'b';
        $_SESSION['a'] = 'c';
        $this->assertEquals('c', $_SESSION['a'] ?? 'sam');
        $_SESSION = [];
        $this->assertEquals('sam', $_SESSION['a'] ?? 'sam');

        while (ob_get_level() > $level_before) {
            ob_end_clean();
        }
    }

    public function testExtractJwtAudStringAndArray() {
        $stringAud = $this->extractIssuerClient('client-from-canvas');
        $this->assertSame('client-from-canvas', $stringAud);

        $arrayAud = $this->extractIssuerClient(array('client-from-schoology'));
        $this->assertSame('client-from-schoology', $arrayAud);
    }

    public function testExtractJwtMultipleAudRequiresAzp() {
        $post = $this->idTokenPost(array('client-a', 'client-b'));
        $result = LTIX::extractJWT(LTIX::NONE, $post);
        $this->assertIsString($result);
        $this->assertStringContainsString('client_id', $result);

        $withAzp = $this->idTokenPost(array('client-a', 'client-b'), 'client-b');
        $result = LTIX::extractJWT(LTIX::NONE, $withAzp);
        $this->assertIsArray($result);
        $this->assertSame('client-b', $result['issuer_client']);
    }

    private function extractIssuerClient($aud) {
        $result = LTIX::extractJWT(LTIX::NONE, $this->idTokenPost($aud));
        $this->assertIsArray($result);
        return $result['issuer_client'];
    }

    private function idTokenPost($aud, $azp = null) {
        $body = array(
            'iss' => 'https://schoology.schoology.com',
            'aud' => $aud,
            'nonce' => 'nonce-1',
            'exp' => time() + 60,
            'sub' => 'user-1',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => 'deploy-1',
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/roles' => array('http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'),
        );
        if ( $azp !== null ) $body['azp'] = $azp;
        $header = JWT::urlsafeB64Encode('{"alg":"none","typ":"JWT"}');
        $payload = JWT::urlsafeB64Encode(json_encode($body));
        return array('id_token' => $header.'.'.$payload.'.sig');
    }
}
