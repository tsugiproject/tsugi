<?php

require_once "src/Util/U.php";
require_once "src/Util/PS.php";
require_once "src/Util/LTI.php";
require_once "src/Util/LTI13.php";
require_once "src/Util/KVS.php";
require_once "src/Core/I18N.php";
require_once "src/OAuth/OAuthDataStore.php";
require_once "src/OAuth/TrivialOAuthDataStore.php";
require_once "src/OAuth/OAuthUtil.php";
require_once "src/OAuth/OAuthRequest.php";
require_once "src/OAuth/OAuthConsumer.php";
require_once "src/OAuth/OAuthServer.php";
require_once "src/OAuth/TrivialOAuthDataStore.php";
require_once "src/OAuth/OAuthSignatureMethod.php";
require_once "src/OAuth/OAuthSignatureMethod_HMAC_SHA1.php";
require_once "src/OAuth/OAuthSignatureMethod_HMAC_SHA256.php";
require_once "src/OAuth/OAuthException.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Blob/BlobUtil.php";

$dirroot = dirname(__FILE__).'/../../';
$wwwroot = 'http://localhost:8888';
$CFG = new \Tsugi\Config\ConfigInfo($dirroot, $wwwroot);

$toppath = dirname(__FILE__).'/../..';
require_once $toppath.'/vendor/firebase/php-jwt/src/JWT.php';
require_once $toppath.'/vendor/firebase/php-jwt/src/JWK.php';
require_once $toppath.'/vendor/firebase/php-jwt/src/Key.php';

require_once "include/setup.php";

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

class LTI13Test extends \PHPUnit\Framework\TestCase
{
    public $test_jwt_str = <<< EOF
        {
            "nonce": "172we8671fd8z",
            "iat": 1551290796,
            "exp": 1551290856,
            "iss": "https://lmsvendor.com",
            "aud": "PM48OJSfGDTAzAo",
            "sub": "3",
            "https://purl.imsglobal.org/spec/lti/claim/deployment_id": "689302",
            "https://purl.imsglobal.org/spec/lti/claim/lti1p1": {
                "user_id": "34212",
                "oauth_consumer_key": "179248902",
                "oauth_consumer_key_sign": "lWd54kFo5qU7xshAna6v8BwoBm6tmUjc6GTax6+12ps="
            }
        }
EOF
;

    public $raw_jwt = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c";

    public function testBasics() {
        $lj = json_decode($this->test_jwt_str);

        $base = \Tsugi\Util\LTI13::getLTI11TransitionBase($lj);
        $expected_base ="179248902&689302&https://lmsvendor.com&PM48OJSfGDTAzAo&1551290856&172we8671fd8z";
        $this->assertEquals($base, $expected_base);

        $secret = "my-lti11-secret";
        $key = "179248902";
        $signature = \Tsugi\Util\LTI13::signLTI11Transition($lj, $secret);
        $this->assertEquals($signature, "lWd54kFo5qU7xshAna6v8BwoBm6tmUjc6GTax6+12ps=");

        $lj->{\Tsugi\Util\LTI13::LTI11_TRANSITION_CLAIM}->oauth_consumer_key_sign = $signature;

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, $key, $secret);
        $this->assertTrue($check);

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, $key, "badsecret");
        $this->assertFalse($check);

        $check = \Tsugi\Util\LTI13::checkLTI11Transition($lj, "badkey", $secret);
        $this->assertEquals($check, 'LTI1.1 Transition key mis-match tsugi key=badkey');


    }

    // https://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim
    /*
        sign=base64(hmac_sha256(utf8bytes('179248902&689302&https://lmsvendor.com&PM48OJSfGDTAzAo&1551290856&172we8671fd8z'), utf8bytes('my-lti11-secret')))

     */

    public function testJWT() {
        $required_fields = false;
        $retval = \Tsugi\Util\LTI13::parse_jwt($this->raw_jwt, $required_fields);
        $this->assertTrue(is_object($retval));
        $this->assertEquals($retval->header->alg, "HS256");
        $this->assertEquals($retval->body->name, "John Doe");
        $this->assertEquals($retval->extra["name"], "John Doe");
    }
/*
  ["header"]=>
  object(stdClass)#394 (2) {
    ["alg"]=>
    string(5) "HS256"
    ["typ"]=>
    string(3) "JWT"
  }
  ["body"]=>
  object(stdClass)#395 (3) {
    ["sub"]=>
    string(10) "1234567890"
    ["name"]=>
    string(8) "John Doe"
    ["iat"]=>
    int(1516239022)
  }
  ["extra"]=>
  array(3) {
    ["sub"]=>
    string(10) "1234567890"
    ["name"]=>
    string(8) "John Doe"
    ["iat"]=>
    int(1516239022)
  }
}
 */

    // https://tools.ietf.org/html/rfc7517#appendix-A.1
    public function testJWK() {
        $json_str = trim( <<< EOF
     {"keys":
       [
         {"kty":"RSA",
          "n": "0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csFCur-kEgU8awapJzKnqDKgw",
          "e":"AQAB",
          "alg":"RS256",
          "kid":"2011-04-29"}
       ]
     }
EOF
);
        $json = json_decode($json_str, true);
        $this->assertTrue(is_array($json));
        $this->assertTrue(is_array($json['keys']));
        $key_set = JWK::parseKeySet($json);
        $this->assertTrue(is_array($key_set));
        $this->assertEquals(count($key_set), 1);
        $this->assertTrue(isset($key_set["2011-04-29"]));

        $pub_expected = trim( <<< EOF
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0vx7agoebGcQSuuPiLJX
ZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tS
oc/BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ/2W+5JsGY4Hc5n9yBXArwl93lqt
7/RN5w6Cf0h4QyQ5v+65YGjQR0/FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0
zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt+bFTWhAI4vMQFh6WeZu0f
M4lFd2NcRwr3XPksINHaQ+G/xBniIqbw0Ls1jF44+csFCur+kEgU8awapJzKnqDK
gwIDAQAB
-----END PUBLIC KEY-----
EOF
);
        $pub_key = \Tsugi\Util\LTI13::extractKeyFromKeySet($json_str, '2011-04-29');
        $this->assertEquals(trim($pub_key), $pub_expected);
        $pub_key = \Tsugi\Util\LTI13::extractKeyFromKeySet($json_str, 'bob');
        $this->assertNull($pub_key);
        $pub_key = \Tsugi\Util\LTI13::extractKeyFromKeySet('{{{{{', 'bob');
        $this->assertNull($pub_key);
        $pub_key = \Tsugi\Util\LTI13::extractKeyFromKeySet('{"bob": "bob"}', 'bob');
        $this->assertNull($pub_key);
        $pub_key = \Tsugi\Util\LTI13::extractKeyFromKeySet('{"keys": [{"kty":"RSA"} ]}', 'bob');
        $this->assertNull($pub_key);
    }


}
