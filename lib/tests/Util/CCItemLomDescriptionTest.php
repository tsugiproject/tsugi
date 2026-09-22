<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CanvasModuleMeta.php";

use Tsugi\Util\CC;

class CCItemLomDescriptionTest extends \PHPUnit\Framework\TestCase
{
    public function testPlainTextDescriptionOnModuleItem() {
        $cc = $this->cc12();
        $module = $cc->add_module('Django Models', '', 'This lesson introduces the Django ORM.');
        $this->assertSame('', $module->getAttribute('identifierref'));

        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);
        $this->assertStandardLomNamespace($xml);

        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame('Django Models', $this->directChildTitle($item));
        $this->assertSame(
            'This lesson introduces the Django ORM.',
            CC::lomDescriptionFromItem($item)
        );
        $names = array();
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement ) {
                $names[] = $child->localName;
            }
        }
        $this->assertSame(array('title', 'metadata'), $names);
        $this->assertStringContainsString(
            '<lomimscc:string language="en-US">This lesson introduces the Django ORM.</lomimscc:string>',
            $xml
        );
    }

    public function testNoDescriptionOmitsMetadata() {
        $cc = $this->cc12();
        $module = $cc->add_module('Django Models', '');
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);

        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame('Django Models', $this->directChildTitle($item));
        $this->assertNull(CC::lomDescriptionFromItem($item));
        $this->assertFalse($this->hasDirectMetadata($item));
        $this->assertFalse($cc->add_item_lom_description($module, ''));
        $this->assertFalse($cc->add_item_lom_description($module, null));
    }

    public function testSpecialCharactersAndUnicodeRoundTrip() {
        $desc = 'A & B < C > D "quotes" \'apostrophe\' café 😀';
        $cc = $this->cc12();
        $cc->add_module('Django Models', '', $desc);
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);

        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame($desc, CC::lomDescriptionFromItem($item));
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&lt;', $xml);
        $this->assertStringContainsString('&gt;', $xml);
        $this->assertStringContainsString('café', $xml);
        $this->assertStringContainsString('😀', $xml);
    }

    public function testHtmlDescriptionStaysEscapedText() {
        $desc = '<p>Hello <strong>world</strong></p>';
        $cc = $this->cc12();
        $cc->add_module('Django Models', '', $desc);
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);

        $this->assertStringContainsString('&lt;p&gt;Hello &lt;strong&gt;world&lt;/strong&gt;&lt;/p&gt;', $xml);
        $this->assertDoesNotMatchRegularExpression('/<lomimscc:string[^>]*>\s*<p>/', $xml);

        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame($desc, CC::lomDescriptionFromItem($item));
    }

    public function testMetadataDoesNotAlterChildHierarchy() {
        $cc = $this->cc12();
        $module = $cc->add_module('Django Models', '', 'This lesson introduces the Django ORM.');
        $cc->add_web_link($module, 'Docs', 'https://example.com/docs');
        $webId = $cc->last_identifier;
        $cc->add_header_item($module, 'Videos');
        $headerId = $cc->last_identifier;

        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);
        $item = $this->organizationItem($xml, $this->moduleIdentifier($xml, 'Django Models'));

        $this->assertSame('', $item->getAttribute('identifierref'));
        $this->assertMetadataAfterTitleBeforeItems($item);

        $kids = $this->directChildItems($item);
        $this->assertCount(2, $kids);
        $this->assertSame($webId, $kids[0]->getAttribute('identifier'));
        $this->assertSame($webId.'_R', $kids[0]->getAttribute('identifierref'));
        $this->assertSame('Docs', $this->directChildTitle($kids[0]));
        $this->assertSame($headerId, $kids[1]->getAttribute('identifier'));
        $this->assertSame('', $kids[1]->getAttribute('identifierref'));
        $this->assertSame('Videos', $this->directChildTitle($kids[1]));
        $this->assertNull(CC::lomDescriptionFromItem($kids[0]));
        $this->assertNull(CC::lomDescriptionFromItem($kids[1]));
    }

    public function testCc11DoesNotEmitItemMetadata() {
        $cc = new CC(CC::PROFILE_11);
        $cc->set_title('Course');
        $module = $cc->add_module('Django Models', '', 'This lesson introduces the Django ORM.');
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);

        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame('Django Models', $this->directChildTitle($item));
        $this->assertFalse($this->hasDirectMetadata($item));
        $this->assertNull(CC::lomDescriptionFromItem($item));
        $this->assertFalse($cc->add_item_lom_description($module, 'This lesson introduces the Django ORM.'));
        $this->assertStringNotContainsString('tsugi.org/xsd', $xml);
    }

    public function testSubModuleDescriptionUsesSharedHelper() {
        $cc = $this->cc12();
        $top = $cc->add_module('Modules (import)', '');
        $sub = $cc->add_sub_module($top, 'Django Models', 'Modules (import)', 'This lesson introduces the Django ORM.');
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);
        $this->assertFalse($this->hasDirectMetadata($this->organizationItem($xml, $top->getAttribute('identifier'))));
        $this->assertSame(
            'This lesson introduces the Django ORM.',
            CC::lomDescriptionFromItem($this->organizationItem($xml, $sub->getAttribute('identifier')))
        );
    }

    public function testOrganizationDescriptionHelper() {
        $this->assertNull(CC::organizationDescription(null));
        $this->assertNull(CC::organizationDescription(''));
        $this->assertNull(CC::organizationDescription(array()));
        $this->assertSame('Hi', CC::organizationDescription('Hi'));
        $this->assertSame('Hi', CC::organizationDescription((object) array('description' => 'Hi')));
        $this->assertSame('Hi', CC::organizationDescription(array('description' => 'Hi')));
        $this->assertNull(CC::organizationDescription((object) array('title' => 'X')));
    }

    public function testWindowTargetMapping() {
        $this->assertSame('_blank', CC::windowTargetFromLesson((object) array('target' => '_blank')));
        $this->assertSame('_blank', CC::windowTargetFromLesson((object) array('target' => 'window')));
        $this->assertSame('_self', CC::windowTargetFromLesson((object) array('target' => '_self')));
        $this->assertSame('_self', CC::windowTargetFromLesson((object) array('target' => 'iframe')));
        $this->assertSame('modal', CC::windowTargetFromLesson((object) array('target' => 'modal')));
        $this->assertNull(CC::windowTargetFromLesson((object) array('title' => 'Docs')));
        $this->assertNull(CC::windowTargetFromLesson((object) array('target' => '')));
        $this->assertSame('_blank', CC::lessonTargetFromWindowTarget('_blank'));
        $this->assertSame('_self', CC::lessonTargetFromWindowTarget('iframe'));
        $this->assertSame('modal', CC::lessonTargetFromWindowTarget('modal'));
        $this->assertNull(CC::lessonTargetFromWindowTarget(''));
        $this->assertTrue(CC::canvasNewTabForWindowTarget('_blank'));
        $this->assertFalse(CC::canvasNewTabForWindowTarget('_self'));
        $this->assertFalse(CC::canvasNewTabForWindowTarget('modal'));
        $this->assertTrue(CC::canvasNewTabForWindowTarget(null, true));
        $this->assertSame('window', CC::documentTargetFromLesson((object) array('target' => '_blank')));
        $this->assertSame('iframe', CC::documentTargetFromLesson((object) array('target' => '_self')));
        $this->assertSame('frame', CC::documentTargetFromLesson((object) array('target' => 'frame')));
        $this->assertSame('modal', CC::documentTargetFromLesson((object) array('target' => 'modal')));
        $this->assertSame('_blank', CC::lessonTargetFromDocumentTarget('window'));
        $this->assertSame('_self', CC::lessonTargetFromDocumentTarget('iframe'));
        $this->assertNull(CC::documentTargetFromLesson((object) array('title' => 'Docs')));
        $this->assertFalse(CC::canvasNewTabForWindowTarget(null, false));
    }

    public function testLomIdentifierCatalogsForIconAndHrefSource() {
        $this->assertSame('fa-star', CC::organizationIcon((object) array('icon' => 'fa-star')));
        $this->assertNull(CC::organizationIcon((object) array('icon' => 'javascript:alert(1)')));
        $this->assertSame('course', CC::organizationHrefSource(array('href_source' => 'course')));
        $this->assertSame('url', CC::organizationHrefSource((object) array('href_source' => 'url')));
        $this->assertNull(CC::organizationHrefSource((object) array('href_source' => 'other')));
        $this->assertSame(
            array(
                CC::LOM_CATALOG_ICON => 'fa-globe',
                CC::LOM_CATALOG_HREF_SOURCE => 'course',
                CC::LOM_CATALOG_DOCUMENT_TARGET => 'window',
            ),
            CC::lomIdentifiersFromLesson((object) array(
                'icon' => 'fa-globe',
                'href_source' => 'course',
                'target' => '_blank',
            ))
        );

        $cc = $this->cc12();
        $module = $cc->add_module('Week 1', '', (object) array(
            'description' => 'Installing Django.',
            'icon' => 'fa-globe',
        ));
        $xml = $cc->saveXML();
        $this->assertValidManifest($xml);
        $item = $this->organizationItem($xml, $cc->last_identifier);
        $this->assertSame('Installing Django.', CC::lomDescriptionFromItem($item));
        $ids = CC::lomIdentifiersFromItem($item);
        $this->assertSame('fa-globe', $ids[CC::LOM_CATALOG_ICON]);
        $this->assertStringContainsString('<lomimscc:catalog>tsugi.icon</lomimscc:catalog>', $xml);
        $this->assertStringContainsString('<lomimscc:entry>fa-globe</lomimscc:entry>', $xml);
        unset($module);
    }

    public function testThreeSiblingIdentifiersPassCc12LomManifestXsd() {
        $xsd = __DIR__.'/../fixtures/cc/ccv1p2_lommanifest_v1p0.xsd';
        $this->assertFileExists($xsd);

        $cc = $this->cc12();
        $cc->add_module('Week 1', '', (object) array(
            'description' => 'Installing Django.',
            'icon' => 'fa-globe',
            'href_source' => 'course',
            'target' => '_blank',
        ));
        $item = $this->organizationItem($cc->saveXML(), $cc->last_identifier);
        $ids = CC::lomIdentifiersFromItem($item);
        $this->assertCount(3, $ids);
        $this->assertSame('fa-globe', $ids[CC::LOM_CATALOG_ICON]);
        $this->assertSame('course', $ids[CC::LOM_CATALOG_HREF_SOURCE]);
        $this->assertSame('window', $ids[CC::LOM_CATALOG_DOCUMENT_TARGET]);

        $lom = $this->itemLom($item);
        $this->assertSame(3, $this->directIdentifierCount($lom));
        $this->assertTrue($this->lomValidatesAgainstCc12Xsd($lom, $xsd));

        $bad = new \DOMDocument();
        $bad->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>'
            .'<lom xmlns="'.CC::LOMIMSCC_NS.'"><general>'
            .'<structure><source>LOMv1.0</source><value>atomic</value></structure>'
            .'</general></lom>'
        );
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $ok = $bad->schemaValidate($xsd);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $this->assertFalse($ok, 'CC 1.2 lommanifest XSD must reject general.structure');
    }

    private function cc12() {
        $cc = new CC();
        $cc->set_title('Course');
        return $cc;
    }

    /**
     * @param string $xml
     */
    private function assertValidManifest($xml) {
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $dom = new \DOMDocument();
        $ok = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $this->assertTrue($ok, 'imsmanifest.xml must be well-formed XML');
        $this->assertSame(array(), $errors);
        $this->assertInstanceOf(\DOMElement::class, $dom->documentElement);
        $this->assertSame('manifest', $dom->documentElement->localName);
    }

    /**
     * @param string $xml
     */
    private function assertStandardLomNamespace($xml) {
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        $root = $dom->documentElement;
        $this->assertSame(CC::LOMIMSCC_NS, $root->lookupNamespaceURI('lomimscc'));
        $this->assertStringContainsString('xmlns:lomimscc="'.CC::LOMIMSCC_NS.'"', $xml);
        $this->assertStringNotContainsString('xmlns:tsugi', $xml);
        $this->assertDoesNotMatchRegularExpression('/xmlns:[a-z0-9]+="[^"]*tsugi[^"]*"/i', $xml);
    }

    /**
     * @param \DOMElement $item
     */
    private function assertMetadataAfterTitleBeforeItems(\DOMElement $item) {
        $names = array();
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement ) {
                $names[] = $child->localName;
            }
        }
        $this->assertSame('title', $names[0] ?? '');
        $this->assertSame('metadata', $names[1] ?? '');
        $this->assertContains('item', $names);
        $metaPos = array_search('metadata', $names, true);
        $firstItem = array_search('item', $names, true);
        $this->assertIsInt($metaPos);
        $this->assertIsInt($firstItem);
        $this->assertLessThan($firstItem, $metaPos);
    }

    /**
     * @param string $xml
     * @param string $identifier
     * @return \DOMElement
     */
    private function organizationItem($xml, $identifier) {
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === 'item'
                && $el->getAttribute('identifier') === $identifier ) {
                return $el;
            }
        }
        $this->fail('Missing organization item '.$identifier);
    }

    /**
     * @param string $xml
     * @param string $title
     * @return string
     */
    private function moduleIdentifier($xml, $title) {
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'item' ) {
                continue;
            }
            if ( $this->directChildTitle($el) === $title && $el->getAttribute('identifierref') === '' ) {
                return $el->getAttribute('identifier');
            }
        }
        $this->fail('Missing module item titled '.$title);
    }

    /**
     * @return string
     */
    private function directChildTitle(\DOMElement $item) {
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'title' ) {
                return $child->textContent;
            }
        }
        return '';
    }

    /**
     * @return list<\DOMElement>
     */
    private function directChildItems(\DOMElement $item) {
        $out = array();
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'item' ) {
                $out[] = $child;
            }
        }
        return $out;
    }

    /**
     * @return bool
     */
    private function hasDirectMetadata(\DOMElement $item) {
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'metadata' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \DOMElement
     */
    private function itemLom(\DOMElement $item) {
        foreach ( $item->childNodes as $child ) {
            if ( ! $child instanceof \DOMElement || $child->localName !== 'metadata' ) {
                continue;
            }
            foreach ( $child->childNodes as $lom ) {
                if ( $lom instanceof \DOMElement && $lom->localName === 'lom' ) {
                    return $lom;
                }
            }
        }
        $this->fail('Missing lomimscc:lom on organization item');
    }

    /**
     * @return int
     */
    private function directIdentifierCount(\DOMElement $lom) {
        $n = 0;
        foreach ( $lom->getElementsByTagNameNS(CC::LOMIMSCC_NS, 'identifier') as $el ) {
            if ( $el->parentNode instanceof \DOMElement && $el->parentNode->localName === 'general' ) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Official CC 1.2 lommanifest XSD:
     * http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lommanifest_v1p0.xsd
     *
     * @param string $xsd
     * @return bool
     */
    private function lomValidatesAgainstCc12Xsd(\DOMElement $lom, $xsd) {
        $doc = new \DOMDocument();
        $doc->appendChild($doc->importNode($lom, true));
        $prev = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $ok = $doc->schemaValidate($xsd);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ( ! $ok ) {
            $msgs = array();
            foreach ( $errors as $err ) {
                $msgs[] = trim($err->message);
            }
            $this->fail("CC 1.2 lommanifest XSD rejected item LOM:\n".implode("\n", $msgs));
        }
        return true;
    }
}
