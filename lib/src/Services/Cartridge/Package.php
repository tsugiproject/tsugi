<?php

namespace Tsugi\Services\Cartridge;

/**
 * Open a Common Cartridge zip and index imsmanifest.xml (CC 1.1 or 1.2).
 *
 * Does not extract to disk. File hrefs are resolved relative to the manifest
 * directory (some LMS zips nest the cartridge in a folder).
 */
class Package {

    /** Refuse a zip that lists more files than this (stat only). */
    const MAX_ZIP_ENTRIES = 2048;

    /** Refuse a zip whose declared uncompressed sizes sum past this. */
    const MAX_ZIP_UNCOMPRESSED = 64 * 1024 * 1024;

    /** Refuse to read one zip member larger than this. */
    const MAX_ZIP_ENTRY = 16 * 1024 * 1024;

    /** @var \ZipArchive|null */
    public $zip = null;

    /** @var string Path used to open the zip (for zip_sha256). */
    public $path = '';

    /** @var string Directory prefix of imsmanifest.xml inside the zip */
    public $base = '';

    /** @var string */
    public $identifier = '';

    /** @var string */
    public $title = '';

    /** @var string */
    public $schemaversion = '';

    /** @var list<array<string, mixed>> */
    public $resources = array();

    /**
     * Organization modules (title + items) for Lessons.
     *
     * @var list<array{identifier:string,title:string,items:list<array<string,mixed>>}>
     */
    public $modules = array();

    /**
     * @param string $path
     * @return self
     */
    public static function open($path) {
        if ( ! is_string($path) || $path === '' || ! is_readable($path) ) {
            throw new ImportException('Cartridge zip is not readable.');
        }
        $zip = new \ZipArchive();
        $opened = $zip->open($path);
        if ( $opened !== true ) {
            throw new ImportException('Not a readable ZIP (code '.$opened.').');
        }
        $pkg = new self();
        $pkg->path = $path;
        $pkg->zip = $zip;
        try {
            $pkg->assertZipBudget();
            $manifestName = $pkg->findManifestName();
            $pkg->base = self::dirName($manifestName);
            $xml = $pkg->readMember($manifestName);
            $pkg->parseManifest($xml);
        } catch ( \Throwable $e ) {
            $pkg->close();
            if ( $e instanceof ImportException ) {
                throw $e;
            }
            throw new ImportException($e->getMessage(), 0, $e);
        }
        return $pkg;
    }

    /**
     * @param string $id
     * @return array<string, mixed>|null
     */
    public function resourceById($id) {
        foreach ( $this->resources as $row ) {
            if ( (string) ($row['identifier'] ?? '') === (string) $id ) {
                return $row;
            }
        }
        return null;
    }

    public function close() {
        if ( $this->zip instanceof \ZipArchive ) {
            $this->zip->close();
        }
        $this->zip = null;
    }

    /**
     * Resources the importer should fingerprint (not Canvas course_settings extras).
     *
     * @return list<array<string, mixed>>
     */
    public function importableResources() {
        $out = array();
        foreach ( $this->resources as $row ) {
            if ( ! empty($row['skipped']) ) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @param string $href Manifest file href
     * @return string
     */
    public function readHref($href) {
        return $this->readMember($this->resolveHref($href));
    }

    /**
     * @param string $name Zip member path
     * @return string
     */
    public function readMember($name) {
        if ( ! $this->zip instanceof \ZipArchive ) {
            throw new ImportException('Cartridge zip is closed.');
        }
        $candidates = self::nameCandidates($name);
        $stat = false;
        $use = null;
        foreach ( $candidates as $candidate ) {
            $stat = $this->zip->statName($candidate);
            if ( $stat !== false ) {
                $use = $candidate;
                break;
            }
        }
        if ( $use === null || $stat === false ) {
            throw new ImportException('Missing zip member: '.$name);
        }
        $size = isset($stat['size']) ? (int) $stat['size'] : 0;
        if ( $size > self::MAX_ZIP_ENTRY ) {
            throw new ImportException('Zip member too large: '.$use);
        }
        $bytes = $this->zip->getFromName($use, self::MAX_ZIP_ENTRY);
        if ( $bytes === false ) {
            throw new ImportException('Cannot read zip member: '.$use);
        }
        return $bytes;
    }

    /**
     * @param string $href
     * @return string
     */
    public function resolveHref($href) {
        $href = str_replace('\\', '/', (string) $href);
        $href = str_replace("\0", '', $href);
        $href = ltrim($href, '/');
        $full = $this->base !== '' ? $this->base.'/'.$href : $href;
        $parts = array();
        foreach ( explode('/', $full) as $part ) {
            if ( $part === '' || $part === '.' ) {
                continue;
            }
            if ( $part === '..' ) {
                throw new ImportException('Illegal zip path: '.$href);
            }
            $parts[] = $part;
        }
        return implode('/', $parts);
    }

    private function assertZipBudget() {
        $n = $this->zip->numFiles;
        if ( $n > self::MAX_ZIP_ENTRIES ) {
            throw new ImportException('Zip lists too many files.');
        }
        $sum = 0;
        for ( $i = 0; $i < $n; $i++ ) {
            $stat = $this->zip->statIndex($i);
            if ( $stat === false ) {
                continue;
            }
            $sum += isset($stat['size']) ? (int) $stat['size'] : 0;
            if ( $sum > self::MAX_ZIP_UNCOMPRESSED ) {
                throw new ImportException('Zip uncompressed size is too large.');
            }
        }
    }

    /**
     * @return string
     */
    private function findManifestName() {
        $found = array();
        $n = $this->zip->numFiles;
        for ( $i = 0; $i < $n; $i++ ) {
            $name = $this->zip->getNameIndex($i);
            if ( ! is_string($name) || $name === '' ) {
                continue;
            }
            $norm = str_replace('\\', '/', $name);
            if ( $norm === 'imsmanifest.xml' || str_ends_with($norm, '/imsmanifest.xml') ) {
                $found[] = $norm;
            }
        }
        if ( count($found) < 1 ) {
            throw new ImportException('imsmanifest.xml missing.');
        }
        usort($found, function ($a, $b) {
            return strlen($a) - strlen($b);
        });
        return $found[0];
    }

    /**
     * @param string $xml
     */
    private function parseManifest($xml) {
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML($xml);
        $errs = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ( ! $ok ) {
            $msg = 'imsmanifest.xml is not well formed.';
            if ( count($errs) ) {
                $msg .= ' '.trim($errs[0]->message);
            }
            throw new ImportException($msg);
        }

        $root = $dom->documentElement;
        if ( $root instanceof \DOMElement ) {
            $this->identifier = trim($root->getAttribute('identifier'));
        }
        $this->schemaversion = self::firstLocalText($dom, 'schemaversion');
        $this->title = self::manifestTitle($dom);

        $byId = array();
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'resource' ) {
                continue;
            }
            $id = trim($el->getAttribute('identifier'));
            if ( $id === '' ) {
                continue;
            }
            $type = trim($el->getAttribute('type'));
            $files = array();
            $href = trim($el->getAttribute('href'));
            if ( $href !== '' ) {
                $files[] = $href;
            }
            foreach ( $el->childNodes as $child ) {
                if ( ! $child instanceof \DOMElement || $child->localName !== 'file' ) {
                    continue;
                }
                $fh = trim($child->getAttribute('href'));
                if ( $fh !== '' && ! in_array($fh, $files, true) ) {
                    $files[] = $fh;
                }
            }
            $primary = count($files) ? $files[0] : '';
            $byId[$id] = array(
                'identifier' => $id,
                'type' => $type,
                'href' => $primary,
                'files' => $files,
                'title' => '',
                'item_identifier' => '',
                'skipped' => self::shouldSkip($type, $primary),
            );
        }

        $this->attachItems($dom, $byId);
        $this->resources = array_values($byId);
        $this->modules = $this->parseOrganization($dom);
    }

    /**
     * @return list<array{identifier:string,title:string,items:list<array<string,mixed>>}>
     */
    private function parseOrganization(\DOMDocument $dom) {
        $org = null;
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === 'organization' ) {
                $org = $el;
                break;
            }
        }
        if ( ! $org instanceof \DOMElement ) {
            return array();
        }
        $top = self::childItems($org);
        if ( count($top) === 1 && count(self::childItems($top[0])) > 0 ) {
            $sources = self::childItems($top[0]);
        } else {
            $sources = $top;
        }
        $modules = array();
        $loose = array();
        foreach ( $sources as $el ) {
            $kids = self::childItems($el);
            if ( count($kids) < 1 ) {
                $loose[] = self::orgItem($el);
                continue;
            }
            $title = self::childTitle($el);
            if ( $title === '' ) {
                $title = 'Imported';
            }
            $items = array();
            foreach ( $kids as $kid ) {
                $items[] = self::orgItem($kid);
            }
            $modules[] = array(
                'identifier' => trim($el->getAttribute('identifier')),
                'title' => $title,
                'items' => $items,
            );
        }
        if ( count($loose) ) {
            $modules[] = array(
                'identifier' => '',
                'title' => 'Imported',
                'items' => $loose,
            );
        }
        return $modules;
    }

    /**
     * @return array{identifier:string,identifierref:string,title:string,heading:bool}
     */
    private static function orgItem(\DOMElement $el) {
        $ref = trim($el->getAttribute('identifierref'));
        $title = self::childTitle($el);
        return array(
            'identifier' => trim($el->getAttribute('identifier')),
            'identifierref' => $ref,
            'title' => $title,
            'heading' => ($ref === ''),
        );
    }

    /**
     * @return list<\DOMElement>
     */
    private static function childItems(\DOMElement $el) {
        $out = array();
        foreach ( $el->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'item' ) {
                $out[] = $child;
            }
        }
        return $out;
    }

    /**
     * @param array<string, array<string, mixed>> $byId
     */
    private function attachItems(\DOMDocument $dom, array &$byId) {
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'item' ) {
                continue;
            }
            $ref = trim($el->getAttribute('identifierref'));
            if ( $ref === '' || ! isset($byId[$ref]) ) {
                continue;
            }
            $itemId = trim($el->getAttribute('identifier'));
            $title = self::childTitle($el);
            if ( $byId[$ref]['item_identifier'] === '' && $itemId !== '' ) {
                $byId[$ref]['item_identifier'] = $itemId;
            }
            if ( $byId[$ref]['title'] === '' && $title !== '' ) {
                $byId[$ref]['title'] = $title;
            }
        }
    }

    /**
     * @param string $type
     * @param string $href
     * @return bool
     */
    public static function shouldSkip($type, $href) {
        $type = strtolower((string) $type);
        $href = str_replace('\\', '/', strtolower((string) $href));
        if ( str_starts_with($type, 'associatedcontent') ) {
            return true;
        }
        if ( str_starts_with($href, 'course_settings/') ) {
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @return list<string>
     */
    private static function nameCandidates($name) {
        $name = str_replace('\\', '/', (string) $name);
        $out = array($name);
        $decoded = rawurldecode($name);
        if ( $decoded !== $name ) {
            $out[] = $decoded;
        }
        return $out;
    }

    /**
     * @param string $path
     * @return string
     */
    private static function dirName($path) {
        $path = str_replace('\\', '/', $path);
        $slash = strrpos($path, '/');
        if ( $slash === false ) {
            return '';
        }
        return substr($path, 0, $slash);
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
    private static function manifestTitle(\DOMDocument $dom) {
        $metadata = null;
        if ( $dom->documentElement instanceof \DOMElement ) {
            foreach ( $dom->documentElement->childNodes as $child ) {
                if ( $child instanceof \DOMElement && $child->localName === 'metadata' ) {
                    $metadata = $child;
                    break;
                }
            }
        }
        if ( ! $metadata instanceof \DOMElement ) {
            return '';
        }
        foreach ( $metadata->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'title' ) {
                continue;
            }
            foreach ( $el->childNodes as $child ) {
                if ( $child instanceof \DOMElement && $child->localName === 'string' ) {
                    $t = trim($child->textContent);
                    if ( $t !== '' ) {
                        return $t;
                    }
                }
            }
            $t = trim($el->textContent);
            if ( $t !== '' ) {
                return $t;
            }
        }
        return '';
    }

    /**
     * @return string
     */
    private static function childTitle(\DOMElement $item) {
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'title' ) {
                return trim($child->textContent);
            }
        }
        return '';
    }
}
