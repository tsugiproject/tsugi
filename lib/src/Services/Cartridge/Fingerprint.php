<?php

namespace Tsugi\Services\Cartridge;

/**
 * Canonical content hash for one cartridge resource.
 *
 * Files hash as bytes. Links / LTI / topics hash a stable extract so XML
 * whitespace and titles do not change identity.
 */
class Fingerprint {

    /**
     * @param array<string, mixed> $resource
     * @return string sha256 hex
     */
    public static function resource(Package $pkg, array $resource) {
        $type = isset($resource['type']) ? (string) $resource['type'] : '';
        $href = isset($resource['href']) ? (string) $resource['href'] : '';
        if ( $href === '' && ! empty($resource['files'][0]) ) {
            $href = (string) $resource['files'][0];
        }
        if ( $href === '' ) {
            throw new ImportException('Resource has no file href.');
        }
        $xml = null;
        if ( self::needsXml($type) ) {
            $xml = $pkg->readHref($href);
        }

        if ( self::isWebLink($type) ) {
            return self::hashString(self::webLinkKey($xml));
        }
        if ( self::isLti($type) ) {
            return self::hashString(self::ltiKey($xml));
        }
        if ( self::isTopic($type) ) {
            return self::hashString(self::topicKey($xml));
        }
        if ( self::isQti($type) ) {
            return self::hashString($xml);
        }
        return self::hashString($pkg->readHref($href));
    }

    public static function isWebLink($type) {
        return str_contains(strtolower((string) $type), 'imswl_xml');
    }

    public static function isLti($type) {
        return str_contains(strtolower((string) $type), 'imsbasiclti');
    }

    public static function isTopic($type) {
        return str_contains(strtolower((string) $type), 'imsdt_xml');
    }

    public static function isQti($type) {
        return str_contains(strtolower((string) $type), 'imsqti');
    }

    public static function isWebContent($type) {
        return strtolower((string) $type) === 'webcontent';
    }

    /**
     * @param string $type
     * @param string $href
     * @return string
     */
    public static function localKind($type, $href) {
        if ( self::isQti($type) ) {
            return 'quiz';
        }
        if ( self::isLti($type) ) {
            return 'lti_link';
        }
        if ( self::isWebLink($type) ) {
            return 'web_link';
        }
        if ( self::isTopic($type) ) {
            return 'discussion';
        }
        if ( self::isWebContent($type) ) {
            $path = str_replace('\\', '/', strtolower((string) $href));
            if ( str_contains($path, 'wiki_content/') || str_contains($path, '/pages/') ) {
                return 'page';
            }
            return 'file';
        }
        return 'resource';
    }

    /**
     * @param string $xml
     * @return string
     */
    public static function webLinkKey($xml) {
        return 'url|'.self::webLinkHref($xml).'|target|'.self::webLinkWindowTarget($xml);
    }

    /**
     * @param string $xml
     * @return string
     */
    public static function webLinkHref($xml) {
        return self::firstAttr(self::xmlDom($xml, 'web link'), 'url', 'href');
    }

    /**
     * @param string $xml
     * @return string
     */
    public static function webLinkWindowTarget($xml) {
        return self::firstAttr(self::xmlDom($xml, 'web link'), 'url', 'windowTarget');
    }

    /**
     * @param string $xml
     * @return string
     */
    public static function ltiKey($xml) {
        $dom = self::xmlDom($xml, 'LTI link');
        $launch = self::firstLocalText($dom, 'launch_url');
        if ( $launch === '' ) {
            $launch = self::firstLocalText($dom, 'secure_launch_url');
        }
        $custom = array();
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'property' ) {
                continue;
            }
            $parent = $el->parentNode;
            if ( ! $parent instanceof \DOMElement || $parent->localName !== 'custom' ) {
                continue;
            }
            $name = trim($el->getAttribute('name'));
            if ( $name === '' ) {
                continue;
            }
            $custom[$name] = trim($el->textContent);
        }
        ksort($custom);
        $parts = array('launch|'.$launch);
        foreach ( $custom as $name => $value ) {
            $parts[] = 'custom|'.$name.'='.$value;
        }
        return implode('|', $parts);
    }

    /**
     * @param string $xml
     * @return string
     */
    public static function topicKey($xml) {
        $dom = self::xmlDom($xml, 'topic');
        $title = self::firstLocalText($dom, 'title');
        $text = self::firstLocalText($dom, 'text');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? $text;
        return 'title|'.$title.'|text|'.$text;
    }

    /**
     * @param string $bytes
     * @return string
     */
    public static function hashString($bytes) {
        return hash('sha256', (string) $bytes);
    }

    private static function needsXml($type) {
        return self::isWebLink($type) || self::isLti($type) || self::isTopic($type) || self::isQti($type);
    }

    /**
     * @param string $xml
     * @param string $what
     * @return \DOMDocument
     */
    private static function xmlDom($xml, $what) {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML((string) $xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ( ! $ok ) {
            throw new ImportException('Could not parse '.$what.' XML.');
        }
        return $dom;
    }

    /**
     * @return string
     */
    private static function firstLocalText(\DOMDocument $dom, $localName) {
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === $localName ) {
                return trim($el->textContent);
            }
        }
        return '';
    }

    /**
     * @return string
     */
    private static function firstAttr(\DOMDocument $dom, $localName, $attr) {
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === $localName ) {
                return trim($el->getAttribute($attr));
            }
        }
        return '';
    }
}
