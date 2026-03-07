<?php


require_once "src/Util/U.php";
require_once "src/Util/PS.php";
require_once "src/Util/LTI.php";
require_once "src/Util/LTIConstants.php";
require_once "src/Util/KVS.php";
require_once "src/Util/Mimeparse.php";
require_once "src/Core/I18N.php";
require_once "src/OAuth/OAuthDataStore.php";
require_once "src/OAuth/TrivialOAuthDataStore.php";
require_once "src/OAuth/OAuthUtil.php";
require_once "src/OAuth/OAuthRequest.php";
require_once "src/OAuth/OAuthConsumer.php";
require_once "src/OAuth/OAuthServer.php";
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
            'lti_version' => 'LTI-1p0',
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

    // ========== isRequest / isRequestCheck / isValidMessageType / isValidVersion ==========

    /**
     * @dataProvider isValidMessageTypeProvider
     */
    public function testIsValidMessageType($messageType, $expected) {
        $this->assertSame($expected, \Tsugi\Util\LTI::isValidMessageType($messageType));
    }

    public static function isValidMessageTypeProvider() {
        return [
            'basic-lti-launch-request' => ['basic-lti-launch-request', true],
            'ContentItemSelection' => ['ContentItemSelection', true],
            'ContentItemSelectionRequest' => ['ContentItemSelectionRequest', true],
            'invalid empty' => ['', false],
            'invalid random' => ['foo', false],
            'invalid case' => ['Basic-LTI-Launch-Request', false],
        ];
    }

    /**
     * @dataProvider isValidVersionProvider
     */
    public function testIsValidVersion($version, $expected) {
        $this->assertSame($expected, \Tsugi\Util\LTI::isValidVersion($version));
    }

    public static function isValidVersionProvider() {
        return [
            'LTI-1p0' => ['LTI-1p0', true],
            'LTI-2p0' => ['LTI-2p0', true],
            'invalid LTI-1p1' => ['LTI-1p1', false],
            'invalid empty' => ['', false],
            'invalid random' => ['LTI-3p0', false],
        ];
    }

    /**
     * @dataProvider isRequestProvider
     */
    public function testIsRequest($requestData, $expected) {
        $this->assertSame($expected, \Tsugi\Util\LTI::isRequest($requestData));
    }

    public static function isRequestProvider() {
        return [
            'valid launch' => [
                ['lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-1p0'],
                true
            ],
            'valid ContentItemSelection' => [
                ['lti_message_type' => 'ContentItemSelection', 'lti_version' => 'LTI-1p0'],
                true
            ],
            'valid LTI-2p0' => [
                ['lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-2p0'],
                true
            ],
            'missing message type' => [
                ['lti_version' => 'LTI-1p0'],
                false
            ],
            'missing version' => [
                ['lti_message_type' => 'basic-lti-launch-request'],
                false
            ],
            'invalid version' => [
                ['lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-3p0'],
                false
            ],
            'invalid message type' => [
                ['lti_message_type' => 'foo', 'lti_version' => 'LTI-1p0'],
                false
            ],
            'empty array' => [[], false],
        ];
    }

    /**
     * @dataProvider isRequestCheckProvider
     */
    public function testIsRequestCheck($requestData, $expected) {
        $result = \Tsugi\Util\LTI::isRequestCheck($requestData);
        if (is_bool($expected)) {
            $this->assertSame($expected, $result);
        } else {
            $this->assertIsString($result);
            $this->assertStringContainsString($expected, $result);
        }
    }

    public static function isRequestCheckProvider() {
        return [
            'valid launch' => [
                ['lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-1p0'],
                true
            ],
            'missing message type' => [['lti_version' => 'LTI-1p0'], false],
            'missing version' => [['lti_message_type' => 'basic-lti-launch-request'], false],
            'invalid version returns error string' => [
                ['lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-3p0'],
                'Invalid LTI version'
            ],
            'invalid message type returns error string' => [
                ['lti_message_type' => 'foo', 'lti_version' => 'LTI-1p0'],
                'Invalid message type'
            ],
        ];
    }

    // ========== mapCustomName / addCustom ==========

    /**
     * @dataProvider mapCustomNameProvider
     */
    public function testMapCustomName($input, $expected) {
        $this->assertSame($expected, \Tsugi\Util\LTI::mapCustomName($input));
    }

    public static function mapCustomNameProvider() {
        return [
            'Review:Chapter per LTI 1.1 spec' => ['Review:Chapter', 'review_chapter'],
            'lowercase' => ['abc', 'abc'],
            'uppercase' => ['ABC', 'abc'],
            'numbers' => ['a1b2', 'a1b2'],
            'hyphen' => ['a-b', 'a_b'],
            'space' => ['a b', 'a_b'],
            'multiple special' => ['a:b-c d', 'a_b_c_d'],
            'empty' => ['', ''],
        ];
    }

    public function testAddCustomArray() {
        $parms = [];
        \Tsugi\Util\LTI::addCustom($parms, ['Review:Chapter' => '1.2.56', 'foo' => 'bar']);
        $this->assertArrayHasKey('custom_review_chapter', $parms);
        $this->assertSame('1.2.56', $parms['custom_review_chapter']);
        $this->assertArrayHasKey('custom_foo', $parms);
        $this->assertSame('bar', $parms['custom_foo']);
    }

    public function testAddCustomNewlineString() {
        $parms = [];
        \Tsugi\Util\LTI::addCustom($parms, "Review:Chapter=1.2.56\nfoo=bar");
        $this->assertArrayHasKey('custom_review_chapter', $parms);
        $this->assertSame('1.2.56', $parms['custom_review_chapter']);
        $this->assertArrayHasKey('custom_foo', $parms);
        $this->assertSame('bar', $parms['custom_foo']);
    }

    public function testAddCustomSkipsEmptyValue() {
        $parms = [];
        \Tsugi\Util\LTI::addCustom($parms, "key1=val1\nkey2=\nkey3=val3");
        $this->assertArrayHasKey('custom_key1', $parms);
        $this->assertArrayNotHasKey('custom_key2', $parms);
        $this->assertArrayHasKey('custom_key3', $parms);
    }

    public function testAddCustomSkipsMalformedLines() {
        $parms = [];
        \Tsugi\Util\LTI::addCustom($parms, "=novalue\nnokey\nkey=val");
        $this->assertArrayHasKey('custom_key', $parms);
        $this->assertSame('val', $parms['custom_key']);
    }

    // ========== get_string ==========

    public function testGetString() {
        $this->assertSame('toggle_debug_data', \Tsugi\Util\LTI::get_string('toggle_debug_data', 'basiclti'));
        $this->assertSame('foo', \Tsugi\Util\LTI::get_string('foo', 'bar'));
    }

    // ========== verifyKeyAndSecret ==========

    public function testVerifyKeyAndSecretMissingKey() {
        $result = \Tsugi\Util\LTI::verifyKeyAndSecret('', 'secret');
        $this->assertIsArray($result);
        $this->assertSame('Missing key or secret', $result[0]);
    }

    public function testVerifyKeyAndSecretMissingSecret() {
        $result = \Tsugi\Util\LTI::verifyKeyAndSecret('key', '');
        $this->assertIsArray($result);
        $this->assertSame('Missing key or secret', $result[0]);
    }

    public function testVerifyKeyAndSecretValid() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret');
        $result = \Tsugi\Util\LTI::verifyKeyAndSecret('12345', 'secret', $this->endpoint, $signed, 'POST');
        $this->assertTrue($result);
    }

    public function testVerifyKeyAndSecretInvalid() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret');
        $result = \Tsugi\Util\LTI::verifyKeyAndSecret('12345', 'wrong', $this->endpoint, $signed, 'POST');
        $this->assertIsArray($result);
        $this->assertStringStartsWith('Invalid signature', $result[0]);
        $this->assertStringStartsWith('POST', $result[1]);
    }

    // ========== signParameters ==========

    public function testSignParametersAddsDefaults() {
        $parms = ['resource_link_id' => '123'];
        $signed = \Tsugi\Util\LTI::signParameters($parms, $this->endpoint, 'POST', 'key', 'secret');
        $this->assertArrayHasKey('lti_version', $signed);
        $this->assertSame('LTI-1p0', $signed['lti_version']);
        $this->assertArrayHasKey('lti_message_type', $signed);
        $this->assertSame('basic-lti-launch-request', $signed['lti_message_type']);
        $this->assertArrayHasKey('oauth_callback', $signed);
        $this->assertSame('about:blank', $signed['oauth_callback']);
        $this->assertArrayHasKey('ext_lti_element_id', $signed);
        $this->assertStringStartsWith('tsugi_element_id_', $signed['ext_lti_element_id']);
    }

    public function testSignParametersWithOrgIdAndDesc() {
        $parms = ['resource_link_id' => '123'];
        $signed = \Tsugi\Util\LTI::signParameters($parms, $this->endpoint, 'POST', 'key', 'secret', false, 'org123', 'Org Desc');
        $this->assertSame('org123', $signed['tool_consumer_instance_guid']);
        $this->assertSame('Org Desc', $signed['tool_consumer_instance_description']);
    }

    public function testSignParametersWithSubmitText() {
        $parms = ['resource_link_id' => '123'];
        $signed = \Tsugi\Util\LTI::signParameters($parms, $this->endpoint, 'POST', 'key', 'secret', 'Launch');
        $this->assertSame('Launch', $signed['ext_submit']);
    }

    public function testSignParametersHMAC_SHA256() {
        $parms = ['resource_link_id' => '123', 'oauth_signature_method' => 'HMAC-SHA256'];
        $signed = \Tsugi\Util\LTI::signParameters($parms, $this->endpoint, 'POST', 'key', 'secret');
        $this->assertArrayHasKey('oauth_signature', $signed);
        $valid = \Tsugi\Util\LTI::verifyKeyAndSecret('key', 'secret', $this->endpoint, $signed, 'POST');
        $this->assertTrue($valid);
    }

    // ========== getPOXRequest / getPOXGradeRequest / getPOXResponse ==========

    public function testGetPOXRequestStructure() {
        $xml = \Tsugi\Util\LTI::getPOXRequest();
        $this->assertStringStartsWith('<?xml', $xml);
        $this->assertStringContainsString('imsx_POXEnvelopeRequest', $xml);
        $this->assertStringContainsString('SOURCEDID', $xml);
        $this->assertStringContainsString('OPERATION', $xml);
        $this->assertStringContainsString('MESSAGE', $xml);
        $this->assertStringContainsString('imsx_POXBody', $xml);
    }

    public function testGetPOXGradeRequestStructure() {
        $xml = \Tsugi\Util\LTI::getPOXGradeRequest();
        $this->assertStringStartsWith('<?xml', $xml);
        $this->assertStringContainsString('imsx_POXEnvelopeRequest', $xml);
        $this->assertStringContainsString('SOURCEDID', $xml);
        $this->assertStringContainsString('GRADE', $xml);
        $this->assertStringContainsString('OPERATION', $xml);
        $this->assertStringContainsString('MESSAGE', $xml);
        $this->assertStringContainsString('resultScore', $xml);
        $this->assertStringContainsString('textString', $xml);
    }

    public function testGetPOXResponseStructure() {
        $template = \Tsugi\Util\LTI::getPOXResponse();
        $this->assertStringStartsWith('<?xml', $template);
        $this->assertStringContainsString('imsx_POXEnvelopeResponse', $template);
        $this->assertStringContainsString('%s', $template);
        $this->assertGreaterThanOrEqual(5, substr_count($template, '%s'), 'Template should have placeholders for sprintf');
    }

    public function testGetPOXResponseSprintf() {
        $template = \Tsugi\Util\LTI::getPOXResponse();
        $placeholders = substr_count($template, '%s');
        $result = sprintf($template, 'msg1', 'success', 'OK', 'ref1', 'op1', '<replaceResultResponse/>');
        $this->assertStringContainsString('msg1', $result);
        $this->assertStringContainsString('success', $result);
        $this->assertStringContainsString('OK', $result);
        $this->assertStringContainsString('<replaceResultResponse/>', $result);
    }

    // ========== parseResponse ==========

    public function testParseResponseSuccess() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>msg123</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>success</imsx_codeMajor>
                <imsx_severity>status</imsx_severity>
                <imsx_description>Score read successfully</imsx_description>
                <imsx_messageRefIdentifier>op123</imsx_messageRefIdentifier>
                <imsx_operationRefIdentifier>read</imsx_operationRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <readResultResponse>
            <result>
                <resultScore>
                    <language>en-us</language>
                    <textString>0.85</textString>
                </resultScore>
            </result>
        </readResultResponse>
    </imsx_POXBody>
</imsx_POXEnvelopeResponse>';
        $retval = \Tsugi\Util\LTI::parseResponse($response);
        $this->assertSame('success', $retval['imsx_codeMajor']);
        $this->assertSame('status', $retval['imsx_severity']);
        $this->assertSame('Score read successfully', $retval['imsx_description']);
        $this->assertSame('readResultResponse', $retval['response']);
        $this->assertSame('en-us', $retval['language']);
        $this->assertSame('0.85', $retval['textString']);
    }

    public function testParseResponseFailure() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>msg123</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>failure</imsx_codeMajor>
                <imsx_severity>error</imsx_severity>
                <imsx_description>Invalid sourcedid</imsx_description>
                <imsx_messageRefIdentifier>op123</imsx_messageRefIdentifier>
                <imsx_operationRefIdentifier>read</imsx_operationRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <readResultResponse/>
    </imsx_POXBody>
</imsx_POXEnvelopeResponse>';
        $retval = \Tsugi\Util\LTI::parseResponse($response);
        $this->assertSame('failure', $retval['imsx_codeMajor']);
        $this->assertSame('Invalid sourcedid', $retval['imsx_description']);
    }

    public function testParseResponseReplaceResult() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
    <imsx_POXHeader>
        <imsx_POXResponseHeaderInfo>
            <imsx_version>V1.0</imsx_version>
            <imsx_messageIdentifier>msg123</imsx_messageIdentifier>
            <imsx_statusInfo>
                <imsx_codeMajor>success</imsx_codeMajor>
                <imsx_severity>status</imsx_severity>
                <imsx_description>Grade stored</imsx_description>
                <imsx_messageRefIdentifier>op123</imsx_messageRefIdentifier>
                <imsx_operationRefIdentifier>replace</imsx_operationRefIdentifier>
            </imsx_statusInfo>
        </imsx_POXResponseHeaderInfo>
    </imsx_POXHeader>
    <imsx_POXBody>
        <replaceResultResponse/>
    </imsx_POXBody>
</imsx_POXEnvelopeResponse>';
        $retval = \Tsugi\Util\LTI::parseResponse($response);
        $this->assertSame('success', $retval['imsx_codeMajor']);
        $this->assertSame('replaceResultResponse', $retval['response']);
    }

    public function testParseResponseInvalidXmlThrows() {
        $this->expectException(\Exception::class);
        \Tsugi\Util\LTI::parseResponse('not valid xml');
        // Exception message may be "Unable to parse XML" (wrapped) or "String could not be parsed as XML" (SimpleXML)
    }

    // ========== parseContextMembershipsResponse ==========

    public function testParseContextMembershipsResponse() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<message_response>
    <statusinfo>
        <codemajor>Success</codemajor>
    </statusinfo>
    <members>
        <member>
            <user_id>user123</user_id>
            <person_name_given>Jane</person_name_given>
            <person_name_family>Doe</person_name_family>
            <role>Learner</role>
            <roles>http://purl.imsglobal.org/vocab/lis/v2/membership#Learner</roles>
            <person_contact_email_primary>jane@example.com</person_contact_email_primary>
            <person_name_full>Jane Doe</person_name_full>
            <lis_result_sourcedid>sourcedid123</lis_result_sourcedid>
            <person_sourcedid>person123</person_sourcedid>
        </member>
    </members>
</message_response>';
        $result = \Tsugi\Util\LTI::parseContextMembershipsResponse($response);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('user123', $result[0]['user_id']);
        $this->assertSame('Jane', $result[0]['person_name_given']);
        $this->assertSame('Doe', $result[0]['person_name_family']);
        $this->assertSame('Learner', $result[0]['role']);
        $this->assertSame('jane@example.com', $result[0]['user_email']);
        $this->assertSame('Jane Doe', $result[0]['user_name']);
        $this->assertSame('sourcedid123', $result[0]['lis_result_sourcedid']);
        $this->assertIsArray($result[0]['groups']);
    }

    public function testParseContextMembershipsResponseUsesPersonSourcedidWhenLisResultEmpty() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<message_response>
    <statusinfo>
        <codemajor>Success</codemajor>
    </statusinfo>
    <members>
        <member>
            <user_id>user123</user_id>
            <person_name_given>Jane</person_name_given>
            <person_name_family>Doe</person_name_family>
            <role>Learner</role>
            <roles>Learner</roles>
            <person_contact_email_primary>jane@example.com</person_contact_email_primary>
            <person_name_full>Jane Doe</person_name_full>
            <lis_result_sourcedid></lis_result_sourcedid>
            <person_sourcedid>person123</person_sourcedid>
        </member>
    </members>
</message_response>';
        $result = \Tsugi\Util\LTI::parseContextMembershipsResponse($response);
        $this->assertSame('person123', $result[0]['lis_result_sourcedid']);
    }

    public function testParseContextMembershipsResponseNonSuccessReturnsFalse() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<message_response>
    <statusinfo>
        <codemajor>Failure</codemajor>
    </statusinfo>
</message_response>';
        $result = \Tsugi\Util\LTI::parseContextMembershipsResponse($response);
        $this->assertFalse($result);
    }

    public function testParseContextMembershipsResponseWithGroups() {
        $response = '<?xml version="1.0" encoding="UTF-8"?>
<message_response>
    <statusinfo>
        <codemajor>Success</codemajor>
    </statusinfo>
    <members>
        <member>
            <user_id>user1</user_id>
            <person_name_given>Alice</person_name_given>
            <person_name_family>Smith</person_name_family>
            <role>Learner</role>
            <roles>Learner</roles>
            <person_contact_email_primary>alice@example.com</person_contact_email_primary>
            <person_name_full>Alice Smith</person_name_full>
            <lis_result_sourcedid>sid1</lis_result_sourcedid>
            <person_sourcedid>ps1</person_sourcedid>
            <groups>
                <group>
                    <id>group1</id>
                    <title>Section A</title>
                </group>
                <group>
                    <id>group2</id>
                    <title>Section B</title>
                </group>
            </groups>
        </member>
    </members>
</message_response>';
        $result = \Tsugi\Util\LTI::parseContextMembershipsResponse($response);
        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]['groups']);
        $this->assertSame('group1', $result[0]['groups'][0]['id']);
        $this->assertSame('Section A', $result[0]['groups'][0]['title']);
        $this->assertSame('group2', $result[0]['groups'][1]['id']);
        $this->assertSame('Section B', $result[0]['groups'][1]['title']);
    }

    // ========== getResultJSON / getLtiLinkJSON ==========

    public function testGetResultJSON() {
        $result = \Tsugi\Util\LTI::getResultJSON(0.85, 'Good work!');
        $this->assertSame('http://purl.imsglobal.org/ctx/lis/v2/Result', $result['@context']);
        $this->assertSame('Result', $result['@type']);
        $this->assertSame('Good work!', $result['comment']);
        $this->assertSame('decimal', $result['resultScore']['@type']);
        $this->assertSame(0.85, $result['resultScore']['@value']);
    }

    public function testGetResultJSONIntegerGrade() {
        $result = \Tsugi\Util\LTI::getResultJSON(100, '');
        $this->assertSame(100, $result['resultScore']['@value']);
    }

    public function testGetLtiLinkJSONDefaults() {
        $result = \Tsugi\Util\LTI::getLtiLinkJSON('https://example.com/launch');
        $this->assertSame('https://example.com/launch', $result->{'@graph'}[0]->url);
        $this->assertSame('A cool tool hosted in the Tsugi environment.', $result->{'@graph'}[0]->{'title'});
        $this->assertSame('application/vnd.ims.lti.v1.ltilink', $result->{'@graph'}[0]->mediaType);
        $this->assertObjectHasProperty('placementAdvice', $result->{'@graph'}[0]);
        $this->assertSame('window', $result->{'@graph'}[0]->placementAdvice->presentationDocumentTarget);
    }

    public function testGetLtiLinkJSONWithAllParams() {
        $result = \Tsugi\Util\LTI::getLtiLinkJSON(
            'https://example.com/launch',
            'My Tool',
            'Description text',
            'https://example.com/icon.png',
            'fa-rocket',
            ['key' => 'value']
        );
        $this->assertSame('https://example.com/launch', $result->{'@graph'}[0]->url);
        $this->assertSame('My Tool', $result->{'@graph'}[0]->{'title'});
        $this->assertSame('Description text', $result->{'@graph'}[0]->{'text'});
        $this->assertSame('https://example.com/icon.png', $result->{'@graph'}[0]->icon->{'@id'});
        $this->assertSame('fa-rocket', $result->{'@graph'}[0]->icon->fa_icon);
        $this->assertSame(['key' => 'value'], (array)$result->{'@graph'}[0]->custom);
    }

    // ========== compareBaseStrings ==========

    public function testCompareBaseStringsIdentical() {
        $result = \Tsugi\Util\LTI::compareBaseStrings('identical', 'identical');
        $this->assertTrue($result);
    }

    public function testCompareBaseStringsDifferent() {
        $result = \Tsugi\Util\LTI::compareBaseStrings('abc', 'abd');
        $this->assertIsString($result);
        $this->assertStringContainsString('->c<-', $result);
        $this->assertStringContainsString('->d<-', $result);
        $this->assertStringContainsString('------------', $result);
    }

    public function testCompareBaseStringsFirstTruncated() {
        $result = \Tsugi\Util\LTI::compareBaseStrings('longer', 'short');
        $this->assertIsString($result);
        $this->assertStringContainsString('truncated', $result);
    }

    public function testCompareBaseStringsSecondTruncated() {
        $result = \Tsugi\Util\LTI::compareBaseStrings('short', 'longer');
        $this->assertIsString($result);
        $this->assertStringContainsString('truncated', $result);
    }

    // ========== jsonIndent ==========

    public function testJsonIndent() {
        $retval = \Tsugi\Util\LTI::jsonIndent('{ "a": "b"; "c": "d"; }');
        $this->assertEquals($retval, '{
   "a": "b"; "c": "d"; 
}', 'JSON should be indented correctly');
    }

    public function testJsonIndentArray() {
        $json = '["a","b","c"]';
        $result = \Tsugi\Util\LTI::jsonIndent($json);
        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('  ', $result);
    }

    public function testJsonIndentNested() {
        $json = '{"outer":{"inner":"value"}}';
        $result = \Tsugi\Util\LTI::jsonIndent($json);
        $this->assertStringContainsString('outer', $result);
        $this->assertStringContainsString('inner', $result);
        $this->assertStringContainsString('value', $result);
    }

    // ========== ltiLinkUrl ==========

    public function testLtiLinkUrlMissingReturnUrl() {
        $postdata = ['accept_media_types' => 'application/vnd.ims.lti.v1.ltilink'];
        $this->assertFalse(\Tsugi\Util\LTI::ltiLinkUrl($postdata));
    }

    public function testLtiLinkUrlMissingAcceptMediaTypes() {
        $postdata = ['content_item_return_url' => 'https://example.com/return'];
        $this->assertFalse(\Tsugi\Util\LTI::ltiLinkUrl($postdata));
    }

    public function testLtiLinkUrlLtilinkNotAccepted() {
        $postdata = [
            'content_item_return_url' => 'https://example.com/return',
            'accept_media_types' => 'image/*,text/html'
        ];
        $this->assertFalse(\Tsugi\Util\LTI::ltiLinkUrl($postdata));
    }

    public function testLtiLinkUrlLtilinkAccepted() {
        $postdata = [
            'content_item_return_url' => 'https://example.com/return',
            'accept_media_types' => 'application/vnd.ims.lti.v1.ltilink,image/*'
        ];
        $this->assertSame('https://example.com/return', \Tsugi\Util\LTI::ltiLinkUrl($postdata));
    }

    // ========== getLastOAuthBodyBaseString / getLastOAuthBodyHashInfo ==========

    public function testGetLastOAuthBodyBaseString() {
        \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', 'key', 'secret');
        $base = \Tsugi\Util\LTI::getLastOAuthBodyBaseString();
        $this->assertIsString($base);
        $this->assertStringStartsWith('POST&', $base);
        $this->assertStringContainsString('localhost', $base);
    }

    public function testGetLastOAuthBodyHashInfo() {
        $info = \Tsugi\Util\LTI::getLastOAuthBodyHashInfo();
        $this->assertTrue($info === null || is_string($info));
    }

    // ========== postLaunchHTML ==========

    public function testPostLaunchHTMLStructure() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret', 'Launch', false, false, '_pause');
        $html = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, false, '_pause');
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('action="'.$this->endpoint.'"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('encType="application/x-www-form-urlencoded"', $html);
        $this->assertStringContainsString('oauth_consumer_key', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function testPostLaunchHTMLWithTargetBlank() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret', 'Launch', false, false, '_blank');
        $html = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, false, '_blank');
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testPostLaunchHTMLWithIframe() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret', 'Launch', false, false, 'width="800" height="600"');
        $html = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, false, 'width="800" height="600"');
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('lti_frameResize', $html);
    }

    public function testPostLaunchHTMLWithDebug() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret', 'Launch', false, false, '_pause');
        $html = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, true, '_pause');
        $this->assertStringContainsString('basicltiDebugToggle', $html);
        $this->assertStringContainsString('basiclti_endpoint', $html);
        $this->assertStringContainsString('basiclti_parameters', $html);
        $this->assertStringContainsString('basiclti_base_string', $html);
    }

    public function testPostLaunchHTMLWithEndform() {
        $signed = \Tsugi\Util\LTI::signParameters($this->parms, $this->endpoint, 'POST', '12345', 'secret', 'Launch', false, false, '_pause');
        $html = \Tsugi\Util\LTI::postLaunchHTML($signed, $this->endpoint, false, '_pause', '<div class="extra">extra content</div>');
        $this->assertStringContainsString('<div class="extra">extra content</div>', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    // ========== getPOXGrade / sendPOXGrade (validation only - no network) ==========

    public function testGetPOXGradeMissingSourcedid() {
        $result = \Tsugi\Util\LTI::getPOXGrade('', 'https://example.com/service', 'key', 'secret');
        $this->assertSame('Missing sourcedid', $result);
    }

    public function testGetPOXGradeMissingService() {
        $result = \Tsugi\Util\LTI::getPOXGrade('sourcedid123', '', 'key', 'secret');
        $this->assertSame('Missing service', $result);
    }

    public function testSendPOXGradeMissingSourcedid() {
        $result = \Tsugi\Util\LTI::sendPOXGrade(0.85, '', 'https://example.com/service', 'key', 'secret');
        $this->assertSame('Missing sourcedid', $result);
    }

    public function testSendPOXGradeMissingService() {
        $result = \Tsugi\Util\LTI::sendPOXGrade(0.85, 'sourcedid123', '', 'key', 'secret');
        $this->assertSame('Missing service', $result);
    }

    // ========== bodyRequest ==========
    // Note: bodyRequest delegates to Net::doBody. Full network tests are in integration.
    // We only verify the method exists and accepts parameters (no network call).

    // ========== Original integration tests ==========

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
