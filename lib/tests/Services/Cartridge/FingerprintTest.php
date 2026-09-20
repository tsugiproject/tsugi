<?php

use Tsugi\Services\Cartridge\Fingerprint;

class CartridgeFingerprintTest extends \PHPUnit\Framework\TestCase
{
    public function testWebLinkIgnoresXmlWhitespaceAndTitle() {
        $a = '<?xml version="1.0"?><webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2"><title>A</title><url href="https://www.dr-chuck.com/"/></webLink>';
        $b = '<?xml version="1.0"?>
<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1">
  <title>Different</title>
  <url href="https://www.dr-chuck.com/" windowTarget="_blank"/>
</webLink>';
        $this->assertSame('url|https://www.dr-chuck.com/|target|', Fingerprint::webLinkKey($a));
        $this->assertSame('url|https://www.dr-chuck.com/|target|_blank', Fingerprint::webLinkKey($b));
        $this->assertSame(
            Fingerprint::hashString('url|https://www.dr-chuck.com/|target|'),
            Fingerprint::hashString(Fingerprint::webLinkKey($a))
        );
    }

    public function testLtiUsesLaunchAndSortedCustomNotTitle() {
        $xml = '<?xml version="1.0"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
  xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
  xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0">
  <blti:title>Ignore me</blti:title>
  <blti:custom>
    <lticm:property name="b">2</lticm:property>
    <lticm:property name="a">1</lticm:property>
  </blti:custom>
  <blti:secure_launch_url>https://example.com/tool</blti:secure_launch_url>
</cartridge_basiclti_link>';
        $this->assertSame(
            'launch|https://example.com/tool|custom|a=1|custom|b=2',
            Fingerprint::ltiKey($xml)
        );
    }

    public function testTopicNormalizesWhitespace() {
        $xml = '<?xml version="1.0"?><topic xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imsdt_v1p2"><title>Hi</title><text>Say   hello.</text></topic>';
        $this->assertSame('title|Hi|text|Say hello.', Fingerprint::topicKey($xml));
    }

    public function testLocalKind() {
        $this->assertSame('page', Fingerprint::localKind('webcontent', 'wiki_content/welcome.html'));
        $this->assertSame('page', Fingerprint::localKind('webcontent', 'web_resources/pages/welcome.html'));
        $this->assertSame('file', Fingerprint::localKind('webcontent', 'web_resources/reading.txt'));
        $this->assertSame('quiz', Fingerprint::localKind('imsqti_xmlv1p2/imscc_xmlv1p2/assessment', 'qti.xml'));
        $this->assertSame('web_link', Fingerprint::localKind('imswl_xmlv1p2', 'wl.xml'));
    }
}
