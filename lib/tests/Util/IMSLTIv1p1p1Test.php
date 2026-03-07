<?php

/**
 * Test that validates Tsugi LTI.php POX message generation produces XML that is
 * semantically equivalent to the IMS LTI 1.1 Basic Outcomes specification examples.
 *
 * Specification: https://www.imsglobal.org/spec/lti-bo/v1p1
 * Implementation Guide: https://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide
 *
 * This test covers Figures 3-8 from the LTI 1.1 Basic Outcomes spec. The v1.1.1 spec
 * examples include empty <imsx_codeMinor/> elements in response XML. Tsugi correctly
 * omits codeMinor (semantically equivalent: empty = "no minor code"). Our semantic
 * comparison ignores imsx_codeMinor for equivalence.
 *
 * Ported from Sakai IMSLTIv1p1p1Test.java
 */

use Tsugi\Util\LTI;

class IMSLTIv1p1p1Test extends \PHPUnit\Framework\TestCase
{
    // ========================================================================
    // Canonical XML examples from the LTI v1.1.1 Basic Outcomes spec (Figures 3-8)
    // Note: v1.1.1 spec examples include empty <imsx_codeMinor/> elements
    // ========================================================================

    /** XML from Figure 3 / Section 6.1.1 - replaceResultRequest */
    private const SPEC_XML_FIG3_REPLACE_RESULT_REQUEST = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
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

    /** XML from Figure 4 / Section 6.1.1 - replaceResultResponse (includes imsx_codeMinor) */
    private const SPEC_XML_FIG4_REPLACE_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
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
<imsx_codeMinor/>
</imsx_statusInfo>
</imsx_POXResponseHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<replaceResultResponse />
</imsx_POXBody>
</imsx_POXEnvelopeResponse>';

    /** XML from Figure 5 / Section 6.1.2 - readResultRequest */
    private const SPEC_XML_FIG5_READ_RESULT_REQUEST = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
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

    /** XML from Figure 6 / Section 6.1.2 - readResultResponse (includes imsx_codeMinor) */
    private const SPEC_XML_FIG6_READ_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
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
<imsx_codeMinor/>
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

    /** XML from Figure 7 / Section 6.1.3 - deleteResultRequest */
    private const SPEC_XML_FIG7_DELETE_RESULT_REQUEST = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeRequest xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
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

    /** XML from Figure 8 / Section 6.1.3 - deleteResultResponse (includes imsx_codeMinor, no description) */
    private const SPEC_XML_FIG8_DELETE_RESULT_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
<imsx_POXHeader>
<imsx_POXResponseHeaderInfo>
<imsx_version>V1.0</imsx_version>
<imsx_messageIdentifier>4560</imsx_messageIdentifier>
<imsx_statusInfo>
<imsx_codeMajor>success</imsx_codeMajor>
<imsx_severity>status</imsx_severity>
<imsx_messageRefIdentifier>999999123</imsx_messageRefIdentifier>
<imsx_operationRefIdentifier>deleteResult</imsx_operationRefIdentifier>
<imsx_codeMinor/>
</imsx_statusInfo>
</imsx_POXResponseHeaderInfo>
</imsx_POXHeader>
<imsx_POXBody>
<deleteResultResponse />
</imsx_POXBody>
</imsx_POXEnvelopeResponse>';

    /**
     * Extract semantic content from POX Request XML for comparison.
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
     * Ignores imsx_codeMinor (empty in v1.1.1 spec = omitted in Tsugi = semantically equivalent).
     */
    private function extractResponseSemantics(string $xml): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        $desc = $this->getText($xpath, '//ns:imsx_description') ?: $this->getText($xpath, '//imsx_description');

        $result = [
            'version' => $this->getText($xpath, '//ns:imsx_version') ?: $this->getText($xpath, '//imsx_version'),
            'messageId' => $this->getText($xpath, '//ns:imsx_messageIdentifier') ?: $this->getText($xpath, '//imsx_messageIdentifier'),
            'codeMajor' => $this->getText($xpath, '//ns:imsx_codeMajor') ?: $this->getText($xpath, '//imsx_codeMajor'),
            'severity' => $this->getText($xpath, '//ns:imsx_severity') ?: $this->getText($xpath, '//imsx_severity'),
            'description' => $desc === '' ? null : $desc,
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
        if ($nodes->length > 0) {
            $text = trim($nodes->item(0)->textContent ?? '');
            return $text === '' ? null : $text;
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
        if ($expected['language'] !== null && $actual['language'] !== null) {
            $this->assertTrue(
                $expected['language'] === $actual['language'] || $actual['language'] === 'en-us',
                $message . ' language (spec may use en, Tsugi uses en-us)'
            );
        }
    }

    /**
     * Assert two POX Response XMLs are semantically equivalent.
     * Treats null and '' description as equivalent (Figure 8 has no description).
     */
    private function assertResponseXmlEquivalent(string $expectedXml, string $actualXml, string $message = ''): void
    {
        $expected = $this->extractResponseSemantics($expectedXml);
        $actual = $this->extractResponseSemantics($actualXml);

        $this->assertSame($expected['version'], $actual['version'], $message . ' version');
        $this->assertSame($expected['codeMajor'], $actual['codeMajor'], $message . ' codeMajor');
        $this->assertSame($expected['severity'], $actual['severity'], $message . ' severity');
        $this->assertTrue(
            $expected['description'] === $actual['description'] ||
            ($expected['description'] === null && ($actual['description'] === null || $actual['description'] === '')),
            $message . ' description (null/empty equivalent for deleteResultResponse)'
        );
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
     * Test replaceResultRequest - matches Figure 3 from spec
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
            self::SPEC_XML_FIG3_REPLACE_RESULT_REQUEST,
            $generated,
            'replaceResultRequest should match Figure 3'
        );
    }

    /**
     * Test readResultRequest - matches Figure 5 from spec
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
            self::SPEC_XML_FIG5_READ_RESULT_REQUEST,
            $generated,
            'readResultRequest should match Figure 5'
        );
    }

    /**
     * Test deleteResultRequest - matches Figure 7 from spec
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
            self::SPEC_XML_FIG7_DELETE_RESULT_REQUEST,
            $generated,
            'deleteResultRequest should match Figure 7'
        );
    }

    // ========================================================================
    // Response generation tests - Tsugi getPOXResponse
    // ========================================================================

    /**
     * Test replaceResultResponse - matches Figure 4 from spec
     */
    public function testReplaceResultResponse(): void
    {
        $template = LTI::getPOXResponse();
        $generated = sprintf(
            $template,
            '4560',
            'success',
            'Score for 3124567 is now 0.92',
            '999999123',
            'replaceResult',
            '<replaceResultResponse/>'
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_FIG4_REPLACE_RESULT_RESPONSE,
            $generated,
            'replaceResultResponse should match Figure 4'
        );
    }

    /**
     * Test readResultResponse - matches Figure 6 from spec
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
            '1313355158804',
            'success',
            'Result read',
            '999999123',
            'readResult',
            $body
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_FIG6_READ_RESULT_RESPONSE,
            $generated,
            'readResultResponse should match Figure 6'
        );
    }

    /**
     * Test deleteResultResponse - matches Figure 8 from spec (no description field)
     */
    public function testDeleteResultResponse(): void
    {
        $template = LTI::getPOXResponse();
        $generated = sprintf(
            $template,
            '4560',
            'success',
            '',
            '999999123',
            'deleteResult',
            '<deleteResultResponse/>'
        );

        $this->assertValidXml($generated);
        $this->assertResponseXmlEquivalent(
            self::SPEC_XML_FIG8_DELETE_RESULT_RESPONSE,
            $generated,
            'deleteResultResponse should match Figure 8'
        );
    }

    // ========================================================================
    // Parsing tests - Tsugi parseResponse (handles imsx_codeMinor in v1.1.1 spec)
    // ========================================================================

    /**
     * Test parsing replaceResultResponse XML with imsx_codeMinor (Figure 4)
     */
    public function testParseReplaceResultResponse(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_FIG4_REPLACE_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('status', $parsed['imsx_severity']);
        $this->assertSame('Score for 3124567 is now 0.92', $parsed['imsx_description']);
        $this->assertSame('replaceResultResponse', $parsed['response']);
    }

    /**
     * Test parsing readResultResponse XML with imsx_codeMinor (Figure 6)
     */
    public function testParseReadResultResponse(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_FIG6_READ_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('Result read', $parsed['imsx_description']);
        $this->assertSame('readResultResponse', $parsed['response']);
        $this->assertSame('en', $parsed['language']);
        $this->assertSame('0.91', $parsed['textString']);
    }

    /**
     * Test parsing deleteResultResponse XML with imsx_codeMinor, no description (Figure 8)
     */
    public function testParseDeleteResultResponse(): void
    {
        $parsed = LTI::parseResponse(self::SPEC_XML_FIG8_DELETE_RESULT_RESPONSE);

        $this->assertSame('success', $parsed['imsx_codeMajor']);
        $this->assertSame('deleteResultResponse', $parsed['response']);
        // Figure 8 has no imsx_description - parseResponse may return empty string or not set
        $this->assertTrue(
            !isset($parsed['imsx_description']) || $parsed['imsx_description'] === '',
            'Description should be absent or empty for deleteResultResponse'
        );
    }
}
