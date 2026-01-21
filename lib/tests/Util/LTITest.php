<?php


require_once "src/Util/U.php";
require_once "src/Util/PS.php";
require_once "src/Util/LTI.php";
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

require_once "include/setup.php";

class LTITest extends \PHPUnit\Framework\TestCase
{
    public $parms;
    public $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parms = array(
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => '12345',
            'resource_link_title' => 'Installing Tsugi',
            'tool_consumer_info_product_family_code' => 'tsugi',
            'tool_consumer_info_version' => '1.1',
            'context_id' => '98765',
            'context_label' => 'Intro Tsugi',
            'context_title' => 'Intro Tsugi',
            'user_id' => 24,
            'lis_person_name_full' => 'Sally Student',
            'lis_person_contact_email_primary' => 'sally@example.com',
            'roles' => 'Learner'
        );
        $this->endpoint = 'http://localhost:8888/tsugi';
    }

    public function testIndent() {
        $retval = \Tsugi\Util\LTI::jsonIndent('{ "a": "b"; "c": "d"; }');
        $this->assertEquals($retval, '{
   "a": "b"; "c": "d"; 
}', 'JSON should be indented correctly');
    }

    function testSigning() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret',
                        'Finish Launch', 'http://www.wa4e.com', 'WA4E');
        $this->assertArrayHasKey('oauth_consumer_key', $signed, 'Signed parameters should contain oauth_consumer_key');
        $this->assertEquals($signed['oauth_consumer_key'], '12345', 'OAuth consumer key should match');
        $this->assertArrayHasKey('oauth_nonce', $signed, 'Signed parameters should contain oauth_nonce');
        $this->assertArrayHasKey('oauth_signature', $signed, 'Signed parameters should contain oauth_signature');
        $content = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, false /*debug */, '_pause');

        // Good secret
        $valid = \Tsugi\Util\LTI::verifyKeyAndSecret('12345','secret',$this->endpoint, $signed,'POST');
        $this->assertTrue($valid, 'Verification with correct secret should succeed');

        // Bad secret
        $valid = \Tsugi\Util\LTI::verifyKeyAndSecret('12345','xsecret',$this->endpoint, $signed,'POST');
        $this->assertEquals(count($valid), 2, "Expecting two entry array as the (expected) error return");
        $this->assertStringStartsWith('Invalid signature ours=', $valid[0], 'Error message should indicate invalid signature');
        $this->assertStringStartsWith('POST', $valid[1], 'Error array should contain HTTP method');
    }

}
