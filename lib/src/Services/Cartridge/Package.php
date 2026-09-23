<?php

namespace Tsugi\Services\Cartridge;

use Tsugi\Util\CC;

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
     * @var list<array{identifier:string,title:string,description:?string,items:list<array<string,mixed>>}>
     */
    public $modules = array();

    /**
     * When set, importableResources() only returns these resource identifiers.
     *
     * @var array<string, true>|null
     */
    public $onlyResourceIds = null;

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
            $id = (string) ($row['identifier'] ?? '');
            if ( is_array($this->onlyResourceIds) && ! isset($this->onlyResourceIds[$id]) ) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Stable checkbox value for one organization module.
     *
     * @param array<string, mixed> $mod
     * @param int $index
     * @return string
     */
    public static function moduleSelectKey(array $mod, $index) {
        $id = trim((string) ($mod['identifier'] ?? ''));
        if ( $id !== '' ) {
            return $id;
        }
        return 'idx-'.(int) $index;
    }

    /**
     * Drop organization modules that were not selected and limit importable resources
     * to identifierrefs those modules still point at.
     *
     * @param list<string> $keys
     */
    public function restrictToModules(array $keys) {
        $wanted = array();
        foreach ( $keys as $key ) {
            $key = trim((string) $key);
            if ( $key !== '' ) {
                $wanted[$key] = true;
            }
        }
        if ( count($wanted) < 1 ) {
            return;
        }
        $kept = array();
        $refs = array();
        foreach ( $this->modules as $i => $mod ) {
            if ( ! is_array($mod) ) {
                continue;
            }
            $key = self::moduleSelectKey($mod, $i);
            if ( ! isset($wanted[$key]) ) {
                continue;
            }
            $kept[] = $mod;
            $items = isset($mod['items']) && is_array($mod['items']) ? $mod['items'] : array();
            foreach ( $items as $item ) {
                $ref = is_array($item) ? (string) ($item['identifierref'] ?? '') : '';
                if ( $ref !== '' ) {
                    $refs[$ref] = true;
                }
            }
        }
        $this->modules = $kept;
        $this->onlyResourceIds = $refs;
    }

    /**
     * Module list for the Settings import Select Content tab.
     *
     * @return list<array{key:string,title:string,counts:array{resources:int,files:int,pages:int,assignments:int,discussions:int,quizzes:int}}>
     */
    public function describeModules() {
        $byId = array();
        foreach ( $this->resources as $res ) {
            $id = (string) ($res['identifier'] ?? '');
            if ( $id !== '' ) {
                $byId[$id] = $res;
            }
        }
        $out = array();
        foreach ( $this->modules as $i => $mod ) {
            if ( ! is_array($mod) ) {
                continue;
            }
            $counts = array(
                'resources' => 0,
                'files' => 0,
                'pages' => 0,
                'assignments' => 0,
                'discussions' => 0,
                'quizzes' => 0,
            );
            $items = isset($mod['items']) && is_array($mod['items']) ? $mod['items'] : array();
            foreach ( $items as $item ) {
                if ( ! is_array($item) || ! empty($item['heading']) ) {
                    continue;
                }
                $ref = (string) ($item['identifierref'] ?? '');
                if ( $ref === '' || ! isset($byId[$ref]) ) {
                    continue;
                }
                $res = $byId[$ref];
                $kind = Fingerprint::localKind(
                    (string) ($res['type'] ?? ''),
                    (string) ($res['href'] ?? '')
                );
                if ( $kind === 'file' ) {
                    $counts['files']++;
                } else if ( $kind === 'page' ) {
                    $counts['pages']++;
                } else if ( $kind === 'quiz' ) {
                    $counts['quizzes']++;
                } else if ( $kind === 'lti_link' ) {
                    $counts['assignments']++;
                } else if ( $kind === 'discussion' ) {
                    $counts['discussions']++;
                } else {
                    $counts['resources']++;
                }
            }
            $out[] = array(
                'key' => self::moduleSelectKey($mod, $i),
                'title' => (string) ($mod['title'] ?? ''),
                'counts' => $counts,
            );
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
            $deps = array();
            foreach ( $el->childNodes as $child ) {
                if ( ! $child instanceof \DOMElement || $child->localName !== 'dependency' ) {
                    continue;
                }
                $dep = trim($child->getAttribute('identifierref'));
                if ( $dep !== '' ) {
                    $deps[] = $dep;
                }
            }
            $primary = count($files) ? $files[0] : '';
            $byId[$id] = array(
                'identifier' => $id,
                'type' => $type,
                'href' => $primary,
                'files' => $files,
                'dependencies' => $deps,
                'title' => '',
                'item_identifier' => '',
                'skipped' => self::shouldSkip($type, $primary),
            );
        }

        $byId = $this->preferNonCcQuizFiles($byId);
        $this->attachItems($dom, $byId);
        $this->resources = array_values($byId);
        $this->modules = $this->parseOrganization($dom);
        $this->attachUnreferencedResources();
    }

    /**
     * @return list<array{identifier:string,title:string,description:?string,items:list<array<string,mixed>>}>
     */
    private function parseOrganization(\DOMDocument $dom) {
        $org = null;
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === 'organization' ) {
                $org = $el;
                break;
            }
        }
        if ( ! $org instanceof \DOMElement || ! self::organizationHasItems($org) ) {
            throw new ImportException('This cartridge has an empty organization and cannot be imported.');
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
            $ids = CC::lomIdentifiersFromItem($el);
            $modules[] = array(
                'identifier' => trim($el->getAttribute('identifier')),
                'title' => $title,
                'description' => CC::lomDescriptionFromItem($el),
                'icon' => $ids[CC::LOM_CATALOG_ICON] ?? null,
                'items' => $items,
            );
        }
        if ( count($loose) ) {
            $modules[] = array(
                'identifier' => '',
                'title' => 'Imported',
                'description' => null,
                'items' => $loose,
            );
        }
        return $modules;
    }

    /**
     * @return array{identifier:string,identifierref:string,title:string,heading:bool,description:?string,icon:?string,href_source:?string,target:?string}
     */
    private static function orgItem(\DOMElement $el) {
        $ref = trim($el->getAttribute('identifierref'));
        $title = self::childTitle($el);
        $ids = CC::lomIdentifiersFromItem($el);
        return array(
            'identifier' => trim($el->getAttribute('identifier')),
            'identifierref' => $ref,
            'title' => $title,
            'heading' => ($ref === ''),
            'description' => CC::lomDescriptionFromItem($el),
            'icon' => $ids[CC::LOM_CATALOG_ICON] ?? null,
            'href_source' => $ids[CC::LOM_CATALOG_HREF_SOURCE] ?? null,
            'target' => CC::lessonTargetFromDocumentTarget($ids[CC::LOM_CATALOG_DOCUMENT_TARGET] ?? null),
        );
    }

    /**
     * True when some item in the organization points at a resource.
     * A placeholder such as Canvas LearningModules, with no identifierref, does not count.
     */
    private static function organizationHasItems(\DOMElement $org) {
        foreach ( $org->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement
                && $el->localName === 'item'
                && trim($el->getAttribute('identifierref')) !== '' ) {
                return true;
            }
        }
        return false;
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
        if ( str_starts_with($href, 'course_settings/') ) {
            return true;
        }
        // Item banks are associatedcontent resources whose href is a QTI file.
        // The quiz itself is the imsqti assessment; its questions may live in
        // a dependency's non_cc file (see preferNonCcQuizFiles).
        if ( str_starts_with($type, 'associatedcontent') ) {
            return true;
        }
        return false;
    }

    /**
     * Canvas New Quizzes leaves assessment_qti.xml empty and puts the items in
     * a dependency's non_cc_assessments file. Read that file instead.
     *
     * @param array<string, array<string, mixed>> $byId
     * @return array<string, array<string, mixed>>
     */
    private function preferNonCcQuizFiles(array $byId) {
        foreach ( $byId as $id => $row ) {
            if ( ! Fingerprint::isQti($row['type'] ?? '') ) {
                continue;
            }
            $deps = isset($row['dependencies']) && is_array($row['dependencies']) ? $row['dependencies'] : array();
            foreach ( $deps as $dep ) {
                if ( ! isset($byId[$dep]) ) {
                    continue;
                }
                $files = isset($byId[$dep]['files']) && is_array($byId[$dep]['files']) ? $byId[$dep]['files'] : array();
                foreach ( $files as $file ) {
                    if ( ! is_string($file) || ! Fingerprint::hrefIsQti($file) ) {
                        continue;
                    }
                    $byId[$id]['href'] = $file;
                    $own = isset($row['files']) && is_array($row['files']) ? $row['files'] : array();
                    if ( ! in_array($file, $own, true) ) {
                        $own[] = $file;
                    }
                    $byId[$id]['files'] = $own;
                    continue 3;
                }
            }
        }
        return $byId;
    }

    /**
     * Resources left out of a real organization still need a module so Select
     * Content can show them. An organization with no items is rejected earlier.
     */
    private function attachUnreferencedResources() {
        $refs = array();
        foreach ( $this->modules as $mod ) {
            $items = isset($mod['items']) && is_array($mod['items']) ? $mod['items'] : array();
            foreach ( $items as $item ) {
                $ref = is_array($item) ? (string) ($item['identifierref'] ?? '') : '';
                if ( $ref !== '' ) {
                    $refs[$ref] = true;
                }
            }
        }
        $orphans = array();
        foreach ( $this->resources as $res ) {
            if ( ! empty($res['skipped']) ) {
                continue;
            }
            $id = (string) ($res['identifier'] ?? '');
            if ( $id === '' || isset($refs[$id]) ) {
                continue;
            }
            $orphans[] = array(
                'identifier' => $id,
                'identifierref' => $id,
                'title' => '',
                'heading' => false,
                'description' => null,
                'icon' => null,
                'href_source' => null,
                'target' => null,
            );
        }
        if ( count($orphans) < 1 ) {
            return;
        }
        foreach ( $this->modules as $i => $mod ) {
            if ( (string) ($mod['identifier'] ?? '') === '' && (string) ($mod['title'] ?? '') === 'Imported' ) {
                $items = isset($mod['items']) && is_array($mod['items']) ? $mod['items'] : array();
                $this->modules[$i]['items'] = array_merge($items, $orphans);
                return;
            }
        }
        $this->modules[] = array(
            'identifier' => '',
            'title' => 'Imported',
            'description' => null,
            'items' => $orphans,
        );
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
