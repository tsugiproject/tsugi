<?php

/**
 * Test that validates Tsugi LTI.php POX message generation produces XML that is
 * semantically equivalent to the IMS LTI v1.1 Implementation Guide examples.
 *
 * Implementation Guide: https://www.imsglobal.org/specs/ltiv1p1/implementation-guide
 *
 * This test covers the Basic Outcomes Service examples from Section 6.1 of the
 * LTI v1.1 Implementation Guide. XML equivalence is verified by parsing both
 * spec and generated XML and comparing semantic content (structure + data),
 * not by string identity (whitespace, attribute order, etc. may differ).
 *
 * Ported from Sakai IMSLTIv1p1Test.java
 */

use Tsugi\Util\LTI;

class IMSLTIv1p1Test extends \PHPUnit\Framework\TestCase
{
    // ========================================================================
    // Canonical XML examples from the LTI v1.1 Implementation Guide Section 6.1
    // ========================================================================

    /** XML from Section 6.1.1 - replaceResultRequest */
    private const SPEC_XML_6_1_1_REPLACE_RESULT_REQUEST = '<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXRequestHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>999999123</imsx_messageIdentifier>
</imsx_POXRequestHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<replaceResultRequest>
<resultRecord>
<sourcedGUID>
<sourcedId>3124567</sourcedId>
</sourcedGUID>
<result>
<resultScore>
<language>en</language>
<textString>0.92</textString>
</resultScore>
</result>
</resultRecord>
</replaceResultRequest>
</imsx_POXBody>
</imsx_POXEnvelopeRequest>';

    /** XML from Section 6.1.1 - replaceResultResponse */
    private const SPEC_XML_6_1_1_REPLACE_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXResponseHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>4560</imsx_messageIdentifier>
<imsx_statusInfo>
<imsx_codeMajor>success</imsx_codeMajor>
<imsx_severity>status</imsx_severity>
<imsx_description>Score for 3124567 is now 0.92</imsx_description>
<imsx_messageRefIdentifier>999999123</imsx_messageRefIdentifier>
<imsx_operationRefIdentifier>replaceResult</imsx_operationRefIdentifier>
</imsx_statusInfo>
</imsx_POXResponseHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<replaceResultResponse/>
</imsx_POXBody>
</imsx_POXEnvelopeResponse>';

    /** XML from Section 6.1.2 - readResultRequest */
    private const SPEC_XML_6_1_2_READ_RESULT_REQUEST = '<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXRequestHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>999999123</imsx_messageIdentifier>
</imsx_POXRequestHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<readResultRequest>
<resultRecord>
<sourcedGUID>
<sourcedId>3124567</sourcedId>
</sourcedGUID>
</resultRecord>
</readResultRequest>
</imsx_POXBody>
</imsx_POXEnvelopeRequest>';

    /** XML from Section 6.1.2 - readResultResponse */
    private const SPEC_XML_6_1_2_READ_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXResponseHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>1313355158804</imsx_messageIdentifier>
<imsx_statusInfo>
<imsx_codeMajor>success</imsx_codeMajor>
<imsx_severity>status</imsx_severity>
<imsx_description>Result read</imsx_description>
<imsx_messageRefIdentifier>999999123</imsx_messageRefIdentifier>
<imsx_operationRefIdentifier>readResult</imsx_operationRefIdentifier>
</imsx_statusInfo>
</imsx_POXResponseHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<readResultResponse>
<result>
<resultScore>
<language>en</language>
<textString>0.91</textString>
</resultScore>
</result>
</readResultResponse>
</imsx_POXBody>
</imsx_POXEnvelopeResponse>';

    /** XML from Section 6.1.3 - deleteResultRequest */
    private const SPEC_XML_6_1_3_DELETE_RESULT_REQUEST = '<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXRequestHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>999999123</imsx_messageIdentifier>
</imsx_POXRequestHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<deleteResultRequest>
<resultRecord>
<sourcedGUID>
<sourcedId>3124567</sourcedId>
</sourcedGUID>
</resultRecord>
</deleteResultRequest>
</imsx_POXBody>
</imsx_POXEnvelopeRequest>';

    /** XML from Section 6.1.3 - deleteResultResponse */
    private const SPEC_XML_6_1_3_DELETE_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXResponseHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>4560</imsx_messageIdentifier>
<imsx_statusInfo>
<imsx_codeMajor>success</imsx_codeMajor>
<imsx_severity>status</imsx_severity>
<imsx_messageRefIdentifier>999999123</imsx_messageRefIdentifier>
<imsx_operationRefIdentifier>deleteResult</imsx_operationRefIdentifier>
</imsx_statusInfo>
</imsx_POXResponseHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<deleteResultResponse/>
</imsx_POXBody>
</imsx_POXEnvelopeResponse>';

    /**
     * Extract semantic content from POX Request XML for comparison.
     * Returns array with: version, messageId, operation, sourcedId, grade (if present), language (if present)
     */
    private function extractRequestSemantics(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        $result = [
            'version' => $this->getText($xpath, '//ns:imsx_version') ?: $this->getText($xpath, '//imsx_version'),
            'messageId' => $this->getText($xpath, '//ns:imsx_messageIdentifier') ?: $this->getText($xpath, '//imsx_messageIdentifier'),
            'sourcedId' => $this->getText($xpath, '//ns:sourcedId') ?: $this->getText($xpath, '//sourcedId'),
            'operation' => null,
            'grade' => null,
            'language' => null,
        ];

        if ($this->getText($xpath, '//ns:replaceResultRequest') || $this->getText($xpath, '//replaceResultRequest/ns:resultRecord')) {
            $result['operation'] = 'replaceResultRequest';
            $result['grade'] = $this->getText($xpath, '//ns:textString') ?: $this->getText($xpath, '//textString');
            $result['language'] = $this->getText($xpath, '//ns:language') ?: $this->getText($xpath, '//language');
        } elseif ($this->getText($xpath, '//ns:readResultRequest') || $this->getText($xpath, '//readResultRequest/ns:resultRecord')) {
            $result['operation'] = 'readResultRequest';
        } elseif ($this->getText($xpath, '//ns:deleteResultRequest') || $this->getText($xpath, '//deleteResultRequest/ns:resultRecord')) {
            $result['operation'] = 'deleteResultRequest';
        }

        return $result;
    }

    /**
     * Extract semantic content from POX Response XML for comparison.
     */
    private function extractResponseSemantics(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        $result = [
            'version' => $this->getText($xpath, '//ns:imsx_version') ?: $this->getText($xpath, '//imsx_version'),
            'messageId' => $this->getText($xpath, '//ns:imsx_messageIdentifier') ?: $this->getText($xpath, '//imsx_messageIdentifier'),
            'codeMajor' => $this->getText($xpath, '//ns:imsx_codeMajor') ?: $this->getText($xpath, '//imsx_codeMajor'),
            'severity' => $this->getText($xpath, '//ns:imsx_severity') ?: $this->getText($xpath, '//imsx_severity'),
            'description' => $this->getText($xpath, '//ns:imsx_description') ?: $this->getText($xpath, '//imsx_description'),
            'messageRefId' => $this->getText($xpath, '//ns:imsx_messageRefIdentifier') ?: $this->getText($xpath, '//imsx_messageRefIdentifier'),
            'operationRefId' => $this->getText($xpath, '//ns:imsx_operationRefIdentifier') ?: $this->getText($xpath, '//imsx_operationRefIdentifier'),
            'bodyOperation' => null,
            'grade' => null,
            'language' => null,
        ];

        if ($this->nodeExists($xpath, '//replaceResultResponse') || $this->nodeExists($xpath, '//ns:replaceResultResponse')) {
            $result['bodyOperation'] = 'replaceResultResponse';
        } elseif ($this->nodeExists($xpath, '//readResultResponse') || $this->nodeExists($xpath, '//ns:readResultResponse')) {
            $result['bodyOperation'] = 'readResultResponse';
            $result['grade'] = $this->getText($xpath, '//ns:textString') ?: $this->getText($xpath, '//textString');
            $result['language'] = $this->getText($xpath, '//ns:language') ?: $this->getText($xpath, '//language');
        } elseif ($this->nodeExists($xpath, '//deleteResultResponse') || $this->nodeExists($xpath, '//ns:deleteResultResponse')) {
            $result['bodyOperation'] = 'deleteResultResponse';
        }

        return $result;
    }

    private function getText(\DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes->length > 0 && $nodes->item(0)->firstChild) {
            return trim($nodes->item(0)->textContent);
        }
        return null;
    }

    private function nodeExists(\DOMXPath $xpath, string $query): bool
    {
        $nodes = $xpath->query($query);
        return $nodes->length > 0;
    }

    /**
     * Assert two POX Request XMLs are semantically equivalent.
     */
    private function assertRequestXmlEquivalent(string $expectedXml, string $actualXml, string $message = ''): void
    {
        $expected = $this->extractRequestSemantics($expectedXml);
        $actual = $this->extractRequestSemantics($actualXml);

        $this->assertSame($expected['version'], $actual['version'], $message . ' version');
        $this->assertSame($expected['sourcedId'], $actual['sourcedId'], $message . ' sourcedId');
        $this->assertSame($expected['operation'], $actual['operation'], $message . ' operation');

        if ($expected['grade'] !== null) {
            $this->assertSame($expected['grade'], $actual['grade'], $message . ' grade');
        }
        // Tsugi uses en-us, spec uses en - both are valid language codes for English
        if ($expected['language'] !== null && $actual['language'] !== null) {
            $this->assertTrue(
                $expected['language'] === $actual['language'] || $actual['language'] === 'en-us',
                $message . ' language (spec may use en, Tsugi uses en-us)'
            );
        }
    }

    /**
     * Assert two POX Response XMLs are semantically equivalent.
     */
    private function assertResponseXmlEquivalent(string $expectedXml, string $actualXml, string $message = ''): void
    {
        $expected = $this->extractResponseSemantics($expectedXml);
        $actual = $this->extractResponseSemantics($actualXml);

        $this->assertSame($expected['version'], $actual['version'], $message . ' version');
        $this->assertSame($expected['codeMajor'], $actual['codeMajor'], $message . ' codeMajor');
        $this->assertSame($expected['severity'], $actual['severity'], $message . ' severity');
        $this->assertSame($expected['description'], $actual['description'], $message . ' description');
        $this->assertSame($expected['messageRefId'], $actual['messageRefId'], $message . ' messageRefId');
        $this->assertSame($expected['operationRefId'], $actual['operationRefId'], $message . ' operationRefId');
        $this->assertSame($expected['bodyOperation'], $actual['bodyOperation'], $message . ' bodyOperation');

        if ($expected['grade'] !== null) {
            $this->assertSame($expected['grade'], $actual['grade'], $message . ' grade');
        }
    }

    /**
     * Assert XML is valid and parseable.
     */
    private function assertValidXml(string $xml): void
    {
        $doc = new \DOMDocument();
        $this->assertTrue(@$doc->loadXML($xml), 'XML should be valid and parseable');
    }

    // ========================================================================
    // Request generation tests - Tsugi getPOXRequest / getPOXGradeRequest
    // ========================================================================

    /**
     * Test replaceResultRequest - matches example from Section 6.1.1
     */
    public function testReplaceResultRequest(): void
    {
        $sourcedId = '3124567';
        $grade = '0.92';
        $messageId = '999999123';

        $template = LTI::getPOXGradeRequest();
        $generated = str_replace(
            ['SOURCEDID', 'GRADE', 'OPERATION', 'MESSAGE'],
            [$sourcedId, $grade, 'replaceResultRequest', $messageId],
            $template
        );

        $this->assertValidXml($generated);
        $this->assertRequestXmlEquivalent(
            self::SPEC_XML_6_1_1_REPLACE_RESULT_REQUEST,
            $generated,
            'replaceResultRequest should match Section 6.1.1 example'
        );
    }

    /**
     * Test readResultRequest - matches example from Section 6.1.2
     */
    public function testReadResultRequest(): void
    {
        $sourcedId = '3124567';
        $messageId = '999999123';

        $template = LTI::getPOXRequest();
        $generated = str_replace(
            ['SOURCEDID', 'OPERATION', 'MESSAGE'],
            [$sourcedId, 'readResultRequest', $messageId],
            $template
        );

        $this->assertValidXml($generated);
        $this->assertRequestXmlEquivalent(
            self::SPEC_XML_6_1_2_READ_RESULT_REQUEST,
            $generated,
            'readResultRequest should match Section 6.1.2 example'
        );
    }

    /**
     * Test deleteResultRequest - matches example from Section 6.1.3
     */
    public function testDeleteResultRequest(): void
    {
        $sourcedId = '3124567';
        $messageId = '999999123';

        $template = LTI::getPOXRequest();
        $generated = str_replace(
            ['SOURCEDID', 'OPERATION', 'MESSAGE'],
            [$sourcedId, 'deleteResultRequest', $messageId],
            $template
        );

        $this->assertValidXml($generated);
        $this->assertRequestXmlEquivalent(
            self::SPEC_XML_6_1_3_DELETE_RESULT_REQUEST,
            $generated,
            'deleteResultRequest should match Section 6.1.3 example'
        );
    }

    // ========================================================================
    // Response generation tests - Tsugi getPOXResponse
    // ========================================================================

    /**
     * Test replaceResultResponse - matches example from Section 6.1.1
     */
    public function testReplaceResultResponse(): void
    {
        $template = LTI::getPOXResponse();
        $generated = sprintf(
            $template,
            '4560',                                    // messageId
            'success',                                 // codeMajor
            'Score for 3124567 is now 0.92',           // description
            '999999123',                               // messageRefId
            'replaceResult',                           // operationRefId
            '<replaceResultResponse/>'                 // body
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_6_1_1_REPLACE_RESULT_RESPONSE,
            $generated,
            'replaceResultResponse should match Section 6.1.1 example'
        );
    }

    /**
     * Test readResultResponse - matches example from Section 6.1.2
     */
    public function testReadResultResponse(): void
    {
        $body = '<readResultResponse>
<result>
<resultScore>
<language>en</language>
<textString>0.91</textString>
</resultScore>
</result>
</readResultResponse>';

        $template = LTI::getPOXResponse();
        $generated = sprintf(
            $template,
            '1313355158804',   // messageId
            'success',        // codeMajor
            'Result read',    // description
            '999999123',      // messageRefId
            'readResult',     // operationRefId
            $body             // body
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_6_1_2_READ_RESULT_RESPONSE,
            $generated,
            'readResultResponse should match Section 6.1.2 example'
        );
    }

    /**
     * Test deleteResultResponse - matches example from Section 6.1.3
     */
    public function testDeleteResultResponse(): void
    {
        $template = LTI::getPOXResponse();
        $generated = sprintf(
            $template,
            '4560',                      // messageId
            'success',                   // codeMajor
            '',                          // description (empty for deleteResult per spec)
            '999999123',                 // messageRefId
            'deleteResult',              // operationRefId
            '<deleteResultResponse/>'    // body
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_6_1_3_DELETE_RESULT_RESPONSE,
            $generated,
            'deleteResultResponse should match Section 6.1.3 example'
        );
    }

    // ========================================================================
    // Parsing tests - Tsugi parseResponse
    // ========================================================================

    /**
     * Test parsing replaceResultRequest XML
     */
    public function testParseReplaceResultRequest(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_6_1_1_REPLACE_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('status', $parsed['imsx_severity']);
        $this->assertSame('Score for 3124567 is now 0.92', $parsed['imsx_description']);
        $this->assertSame('replaceResultResponse', $parsed['response']);
    }

    /**
     * Test parsing readResultResponse XML
     */
    public function testParseReadResultResponse(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_6_1_2_READ_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('Result read', $parsed['imsx_description']);
        $this->assertSame('readResultResponse', $parsed['response']);
        $this->assertSame('en', $parsed['language']);
        $this->assertSame('0.91', $parsed['textString']);
    }

    /**
     * Test parsing deleteResultResponse XML
     */
    public function testParseDeleteResultResponse(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_6_1_3_DELETE_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('deleteResultResponse', $parsed['response']);
    }
}
