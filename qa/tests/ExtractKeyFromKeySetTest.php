<?php

use Tsugi\Util\LTI13;

/**
 * Platform key sets. Brightspace omits alg on RSA keys; php-jwt 7 needs a default.
 */
final class ExtractKeyFromKeySetTest extends \PHPUnit\Framework\TestCase
{
    private const N = '0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csFCur-kEgU8awapJzKnqDKgw';

    private const PEM = <<<'EOF'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0vx7agoebGcQSuuPiLJX
ZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tS
oc/BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ/2W+5JsGY4Hc5n9yBXArwl93lqt
7/RN5w6Cf0h4QyQ5v+65YGjQR0/FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0
zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt+bFTWhAI4vMQFh6WeZu0f
M4lFd2NcRwr3XPksINHaQ+G/xBniIqbw0Ls1jF44+csFCur+kEgU8awapJzKnqDK
gwIDAQAB
-----END PUBLIC KEY-----
EOF;

    public function testRsaKeyWithAlg(): void
    {
        $pem = LTI13::extractKeyFromKeySet($this->keySet($this->rsaKey(true)), '2011-04-29');
        $this->assertSame(self::PEM, trim($pem));
    }

    public function testRsaKeyWithoutAlgUsesRs256(): void
    {
        $pem = LTI13::extractKeyFromKeySet($this->keySet($this->rsaKey(false)), '2011-04-29');
        $this->assertSame(self::PEM, trim($pem));
    }

    public function testReturnsTheKeyMatchingKid(): void
    {
        $withAlg = $this->generatedRsa('kid-with-alg', true);
        $withoutAlg = $this->generatedRsa('kid-without-alg', false);
        $json = json_encode(array('keys' => array($withAlg['jwk'], $withoutAlg['jwk'])));

        $this->assertSame($withAlg['pem'], trim(LTI13::extractKeyFromKeySet($json, 'kid-with-alg')));
        $this->assertSame($withoutAlg['pem'], trim(LTI13::extractKeyFromKeySet($json, 'kid-without-alg')));
        $this->assertNull(LTI13::extractKeyFromKeySet($json, 'missing'));
    }

    public function testMissingAlgorithmWithoutDefaultReturnsNull(): void
    {
        $pem = LTI13::extractKeyFromKeySet($this->keySet($this->rsaKey(false)), '2011-04-29', null);
        $this->assertNull($pem);
    }

    public function testMalformedKeySetReturnsNull(): void
    {
        $this->assertNull(LTI13::extractKeyFromKeySet('{{{{{', '2011-04-29'));
        $this->assertNull(LTI13::extractKeyFromKeySet('{"bob":"bob"}', '2011-04-29'));
        $this->assertNull(LTI13::extractKeyFromKeySet('{"keys":[]}', '2011-04-29'));
        $this->assertNull(LTI13::extractKeyFromKeySet('{"keys":[{"kty":"RSA"}]}', '2011-04-29'));
        $this->assertNull(LTI13::extractKeyFromKeySet(
            '{"keys":[{"kid":"bad","kty":"RSA","n":[],"e":"AQAB"}]}',
            'bad'
        ));
    }

    private function rsaKey(bool $withAlg): array
    {
        $key = array(
            'kty' => 'RSA',
            'n' => self::N,
            'e' => 'AQAB',
            'kid' => '2011-04-29',
        );
        if ($withAlg) {
            $key['alg'] = 'RS256';
        }
        return $key;
    }

    private function keySet(array $key): string
    {
        return json_encode(array('keys' => array($key)));
    }

    /**
     * @return array{jwk: array<string, string>, pem: string}
     */
    private function generatedRsa(string $kid, bool $withAlg): array
    {
        $key = openssl_pkey_new(array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
        $this->assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        $this->assertIsArray($details);
        $jwk = array(
            'kty' => 'RSA',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
            'kid' => $kid,
        );
        if ($withAlg) {
            $jwk['alg'] = 'RS256';
        }
        return array('jwk' => $jwk, 'pem' => trim($details['key']));
    }
}
