<?php

namespace Tsugi\Services\Lessons;

use Tsugi\Util\CC;
use Tsugi\Util\U;

/**
 * Legacy /cc/export helper: turn same-server public file URLs into cartridge bytes.
 *
 * Used only by cc/export.php (file-based lessons.json). Settings / LessonsCartridge
 * already packages Files-tool blobs and is intentionally separate.
 */
class LessonsLegacyFiles {

    /**
     * Office / document extensions that should travel in the zip as webcontent.
     * HTML, PHP, and extensionless course pages stay web links.
     *
     * @var list<string>
     */
    const FILE_EXTENSIONS = array(
        'pdf',
        'ppt', 'pptx', 'pps', 'ppsx', 'pot', 'potx',
        'doc', 'docx',
        'xls', 'xlsx',
        'odt', 'odp', 'ods',
        'zip', 'epub', 'rtf', 'txt', 'csv',
        'key',
    );

    /**
     * Item kinds whose URLs are considered for packing as files.
     *
     * @var list<string>
     */
    const FILEISH_KINDS = array('slide', 'reference', 'assignment', 'solution');

    /**
     * When false (thin cartridge), same-server files stay web links.
     *
     * @var bool
     */
    private $embedFiles = false;

    /**
     * realpath => array{identifierref:string,listings:int}
     *
     * @var array<string, array{identifierref:string,listings:int}>
     */
    private $seen = array();

    /**
     * @param bool $embedFiles Thick cartridge includes file bytes
     */
    public function __construct($embedFiles = false) {
        $this->embedFiles = (bool) $embedFiles;
    }

    /**
     * Thick cartridge includes file contents. Default / unknown is thin.
     *
     * @param mixed $raw Query value, usually "thin" or "thick"
     * @return bool
     */
    public static function wantsThickCartridge($raw) {
        if ( ! is_string($raw) ) {
            return false;
        }
        return strtolower(trim($raw)) === 'thick';
    }

    /**
     * Count slide/reference/assignment/solution URLs that will become files vs stay links.
     * Does not read file bytes.
     *
     * @param object $l Lessons
     * @param array|false $anchors
     * @return array{scanned:int,files:int,listings:int,links:int,paths:list<string>,by_module:array<string,array{scanned:int,files:int,links:int}>}
     */
    public static function summarize($l, $anchors = false) {
        $files = array();
        $scanned = 0;
        $links = 0;
        $listings = 0;
        $by_module = array();
        if ( ! isset($l->lessons->modules) || ! is_array($l->lessons->modules) ) {
            return self::emptySummary();
        }
        foreach ( $l->lessons->modules as $module ) {
            $anchor = isset($module->anchor) ? (string) $module->anchor : '';
            if ( is_array($anchors) && count($anchors) > 0 && $anchor !== '' && ! in_array($anchor, $anchors, true) ) {
                continue;
            }
            $mod_scanned = 0;
            $mod_files = 0;
            $mod_links = 0;
            $seen_in_module = array();
            foreach ( self::urlsForModule($module) as $url ) {
                $scanned++;
                $mod_scanned++;
                $mapped = self::mapIfEmbeddable($url);
                if ( $mapped === null ) {
                    $links++;
                    $mod_links++;
                    continue;
                }
                $key = $mapped['realpath'];
                if ( isset($files[$key]) ) {
                    $listings++;
                    if ( isset($seen_in_module[$key]) ) {
                        continue;
                    }
                    $seen_in_module[$key] = true;
                    $mod_files++;
                    continue;
                }
                $files[$key] = $mapped['path'];
                $seen_in_module[$key] = true;
                $mod_files++;
            }
            if ( $anchor !== '' ) {
                $by_module[$anchor] = array(
                    'scanned' => $mod_scanned,
                    'files' => $mod_files,
                    'links' => $mod_links,
                );
            }
        }
        $paths = array_values($files);
        sort($paths);
        return array(
            'scanned' => $scanned,
            'files' => count($files),
            'listings' => $listings,
            'links' => $links,
            'paths' => $paths,
            'by_module' => $by_module,
        );
    }

    /**
     * Add a lessons URL as webcontent when it is a same-server public file;
     * otherwise keep it as an IMS web link.
     *
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param \DOMNode $module
     * @param string $title
     * @param string $url Absolute URL after expandLink / absolute_url
     * @param string|null $parentPath
     * @param mixed $lesson Optional lesson/item for windowTarget and LOM description
     * @return string 'file', 'listing', or 'url'
     */
    public function addToModule($zip, $cc_dom, $module, $title, $url, $parentPath=null, $lesson=null) {
        if ( ! $this->embedFiles ) {
            $cc_dom->zip_add_url_to_module($zip, $module, $title, $url, $parentPath, true, $lesson);
            return 'url';
        }
        $payload = self::payloadForUrl($url);
        if ( $payload === null ) {
            $cc_dom->zip_add_url_to_module($zip, $module, $title, $url, $parentPath, true, $lesson);
            return 'url';
        }
        $key = $payload['realpath'];
        if ( isset($this->seen[$key]) ) {
            $this->seen[$key]['listings']++;
            $cc_dom->zip_add_file_listing_to_module(
                $module,
                $title,
                $payload['sha256'],
                $this->seen[$key]['listings'],
                $this->seen[$key]['identifierref']
            );
            return 'listing';
        }
        $cc_dom->zip_add_file_to_module(
            $zip,
            $module,
            $title,
            $payload['path'],
            $payload['bytes'],
            $parentPath,
            $payload['sha256']
        );
        $this->seen[$key] = array(
            'identifierref' => $cc_dom->last_identifierref,
            'listings' => 1,
        );
        return 'file';
    }

    /**
     * Local file bytes for a public same-server URL, or null to leave as a link.
     *
     * @param string $url
     * @return array{bytes:string,path:string,filename:string,sha256:string,realpath:string}|null
     */
    public static function payloadForUrl($url) {
        $mapped = self::mapIfEmbeddable($url);
        if ( $mapped === null ) {
            return null;
        }
        $bytes = @file_get_contents($mapped['realpath']);
        if ( ! is_string($bytes) ) {
            return null;
        }
        return array(
            'bytes' => $bytes,
            'path' => $mapped['path'],
            'filename' => $mapped['filename'],
            'sha256' => hash('sha256', $bytes),
            'realpath' => $mapped['realpath'],
        );
    }

    /**
     * Path looks like a downloadable document (pdf, pptx, …), not a site page.
     *
     * @param string $url
     * @return bool
     */
    public static function looksLikeFile($url) {
        if ( ! is_string($url) || $url === '' ) {
            return false;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ( ! is_string($path) || $path === '' ) {
            $path = $url;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $ext !== '' && in_array($ext, self::FILE_EXTENSIONS, true);
    }

    /**
     * Host (and port) matches $CFG->apphome or $CFG->wwwroot.
     *
     * @param string $url
     * @return bool
     */
    public static function isSameServer($url) {
        foreach ( self::serverBases() as $base ) {
            if ( self::urlIsUnderBase($url, $base['url']) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array{scanned:int,files:int,listings:int,links:int,paths:list<string>,by_module:array<string,array{scanned:int,files:int,links:int}>}
     */
    private static function emptySummary() {
        return array(
            'scanned' => 0,
            'files' => 0,
            'listings' => 0,
            'links' => 0,
            'paths' => array(),
            'by_module' => array(),
        );
    }

    /**
     * @param object $module
     * @return list<string>
     */
    private static function urlsForModule($module) {
        $urls = array();
        if ( isset($module->items) && is_array($module->items) && count($module->items) > 0 ) {
            foreach ( $module->items as $item ) {
                $item = is_array($item) ? (object) $item : $item;
                $type = isset($item->type) ? $item->type : '';
                $kind = LessonsNormalize::presentationKind($item);
                if ( ! self::isFileishKind($type, $kind) ) {
                    continue;
                }
                $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
                if ( ! is_string($href) || $href === '' ) {
                    continue;
                }
                $urls[] = U::absolute_url(LessonsService::expandLink($href));
            }
            return $urls;
        }
        if ( isset($module->slides) && is_string($module->slides) && $module->slides !== '' ) {
            $urls[] = U::absolute_url(LessonsService::expandLink($module->slides));
        }
        if ( isset($module->slides) && is_array($module->slides) ) {
            foreach ( $module->slides as $slide ) {
                if ( is_string($slide) ) {
                    $href = $slide;
                } else if ( is_object($slide) && isset($slide->href) ) {
                    $href = $slide->href;
                } else {
                    continue;
                }
                if ( ! is_string($href) || $href === '' ) {
                    continue;
                }
                $urls[] = U::absolute_url(LessonsService::expandLink($href));
            }
        }
        if ( isset($module->assignment) && is_string($module->assignment) && $module->assignment !== '' ) {
            $urls[] = U::absolute_url(LessonsService::expandLink($module->assignment));
        }
        if ( isset($module->solution) && is_string($module->solution) && $module->solution !== '' ) {
            $urls[] = U::absolute_url(LessonsService::expandLink($module->solution));
        }
        if ( isset($module->references) && is_array($module->references) ) {
            foreach ( $module->references as $reference ) {
                if ( ! is_object($reference) || ! isset($reference->href) || ! is_string($reference->href) ) {
                    continue;
                }
                $urls[] = U::absolute_url(LessonsService::expandLink($reference->href));
            }
        }
        return $urls;
    }

    /**
     * @param mixed $type
     * @param string $kind
     * @return bool
     */
    private static function isFileishKind($type, $kind) {
        if ( in_array($kind, self::FILEISH_KINDS, true) ) {
            return true;
        }
        return is_string($type) && in_array($type, self::FILEISH_KINDS, true);
    }

    /**
     * @param string $url
     * @return array{realpath:string,path:string,filename:string}|null
     */
    private static function mapIfEmbeddable($url) {
        if ( ! self::looksLikeFile($url) || ! self::isSameServer($url) ) {
            return null;
        }
        return self::mapUrlToLocalFile($url);
    }

    /**
     * @param string $url
     * @return array{realpath:string,path:string,filename:string}|null
     */
    private static function mapUrlToLocalFile($url) {
        foreach ( self::serverBases() as $base ) {
            if ( ! self::urlIsUnderBase($url, $base['url']) ) {
                continue;
            }
            $rel = self::relativePathUnderBase($url, $base['url']);
            if ( $rel === null || $rel === '' ) {
                continue;
            }
            $candidate = $base['root'].'/'.$rel;
            $real = self::safeFileUnderRoot($candidate, $base['root']);
            if ( $real === null ) {
                continue;
            }
            return array(
                'realpath' => $real,
                'path' => $rel,
                'filename' => basename($rel),
            );
        }
        return null;
    }

    /**
     * apphome maps onto the parent of tsugi (course files). wwwroot maps onto dirroot.
     * Longer URL prefixes first so /course/tsugi/… is not treated as an apphome path.
     *
     * @return list<array{url:string,root:string}>
     */
    private static function serverBases() {
        global $CFG;
        $dirroot = isset($CFG->dirroot) && is_string($CFG->dirroot) ? rtrim($CFG->dirroot, '/\\') : '';
        if ( $dirroot === '' ) {
            return array();
        }
        $wwwroot = isset($CFG->wwwroot) && is_string($CFG->wwwroot) ? rtrim($CFG->wwwroot, '/') : '';
        $apphome = isset($CFG->apphome) && is_string($CFG->apphome) && trim($CFG->apphome) !== ''
            ? rtrim($CFG->apphome, '/')
            : $wwwroot;
        $bases = array();
        if ( $wwwroot !== '' ) {
            $bases[] = array('url' => $wwwroot, 'root' => $dirroot);
        }
        $appRoot = dirname($dirroot);
        if ( $apphome !== '' && $appRoot !== '' && $appRoot !== '.' && $appRoot !== '/' ) {
            $bases[] = array('url' => $apphome, 'root' => $appRoot);
        }
        usort($bases, function ($a, $b) {
            $la = strlen((string) parse_url($a['url'], PHP_URL_PATH));
            $lb = strlen((string) parse_url($b['url'], PHP_URL_PATH));
            return $lb <=> $la;
        });
        return $bases;
    }

    /**
     * @param string $url
     * @param string $base
     * @return bool
     */
    private static function urlIsUnderBase($url, $base) {
        $u = parse_url($url);
        $b = parse_url($base);
        if ( ! is_array($u) || ! is_array($b) ) {
            return false;
        }
        if ( ! isset($u['host'], $b['host']) ) {
            return false;
        }
        if ( strtolower((string) $u['host']) !== strtolower((string) $b['host']) ) {
            return false;
        }
        if ( self::urlPort($u) !== self::urlPort($b) ) {
            return false;
        }
        $bpath = isset($b['path']) ? rtrim((string) $b['path'], '/') : '';
        $upath = isset($u['path']) ? (string) $u['path'] : '/';
        if ( $bpath === '' ) {
            return true;
        }
        return $upath === $bpath || str_starts_with($upath, $bpath.'/');
    }

    /**
     * @param string $url
     * @param string $base
     * @return string|null
     */
    private static function relativePathUnderBase($url, $base) {
        $u = parse_url($url);
        $b = parse_url($base);
        if ( ! is_array($u) || ! is_array($b) ) {
            return null;
        }
        $bpath = isset($b['path']) ? rtrim((string) $b['path'], '/') : '';
        $upath = isset($u['path']) ? (string) $u['path'] : '';
        $upath = rawurldecode($upath);
        if ( str_contains($upath, "\0") ) {
            return null;
        }
        if ( $bpath === '' ) {
            $rel = ltrim($upath, '/');
        } else if ( $upath === $bpath ) {
            $rel = '';
        } else if ( str_starts_with($upath, $bpath.'/') ) {
            $rel = substr($upath, strlen($bpath) + 1);
        } else {
            return null;
        }
        $rel = str_replace('\\', '/', $rel);
        $parts = array();
        foreach ( explode('/', $rel) as $seg ) {
            if ( $seg === '' || $seg === '.' ) {
                continue;
            }
            if ( $seg === '..' ) {
                return null;
            }
            $parts[] = $seg;
        }
        return implode('/', $parts);
    }

    /**
     * @param array<string, mixed> $parts parse_url() result
     * @return int
     */
    private static function urlPort(array $parts) {
        if ( isset($parts['port']) ) {
            return (int) $parts['port'];
        }
        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : 'http';
        return $scheme === 'https' ? 443 : 80;
    }

    /**
     * @param string $candidate
     * @param string $root
     * @return string|null
     */
    private static function safeFileUnderRoot($candidate, $root) {
        $rootReal = realpath($root);
        $fileReal = realpath($candidate);
        if ( $rootReal === false || $fileReal === false ) {
            return null;
        }
        if ( ! is_file($fileReal) || ! is_readable($fileReal) ) {
            return null;
        }
        $prefix = rtrim($rootReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if ( ! str_starts_with($fileReal, $prefix) ) {
            return null;
        }
        return $fileReal;
    }
}
