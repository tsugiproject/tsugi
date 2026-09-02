<?php

namespace Tsugi\Util;

/**
 * Expand and canonicalize Common Cartridge course-local URLs in HTML.
 *
 * At rest in the database, course-local URLs use the historical token
 * concatenated onto a relative path with no slash after the token:
 *
 *     $IMS-CC-FILEBASE$pages/week2
 *     $IMS-CC-FILEBASE$files/images/example.png
 *
 * That is the Common Cartridge spelling, not LTI. The implementation
 * guide's examples are `$TOKEN$images/icon.gif` (no slash between the
 * closing `$` and the path). The token is a flag for importers; the path
 * after it is relative to the resource base directory.
 *
 * @link https://www.imsglobal.org/spec/cc/v1p4/impl#referencing-web-content-from-embedded-text-in-another-resource
 *       CC 1.4 Implementation Guide §5.3.3 (example: `$1EdTech-CC-FILEBASE$images/icon.gif`)
 * @link https://www.imsglobal.org/spec/cc/v1p4/impl#referencing-cartridge-web-content-from-embedded-text-in-another-resource
 *       CC 1.4 Implementation Guide §5.5 (example: `$1EdTech-CC-FILEBASE$../images/icon.gif`)
 * @link https://www.imsglobal.org/cc/ccv1p2/imscc_profilev1p2-Implementation.html#toc-83
 *       CC 1.2 Implementation Guide §3.4.3.3 (same construction; older docs used `$IMS-CC-FILEBASE$`)
 *
 * Interactive use (browser render, rich-text editor) expands the token to
 * the current course base URL. Saving converts current-course URLs back to
 * the token. Extra slashes after the token or in the path are collapsed so
 * join never produces `//`. Export policy for `$1EdTech-CC-FILEBASE$` vs
 * `$IMS-CC-FILEBASE$` is out of scope here; storage stays on the historical
 * `$IMS-CC-FILEBASE$` form.
 *
 * Callers pass any first-path-segment allowlist (e.g. `pages`, `files`).
 * This class does not know about Tsugi controllers.
 */
class CCFileBase {

    /**
     * Historical Common Cartridge file-base token.
     *
     * Do not put a slash inside or after this string. Spec examples write
     * `$IMS-CC-FILEBASE$images/icon.gif`, not `$IMS-CC-FILEBASE$/images/icon.gif`.
     * A slash after the token is accepted on input and stripped.
     *
     * @see https://www.imsglobal.org/spec/cc/v1p4/impl#referencing-web-content-from-embedded-text-in-another-resource
     */
    const TOKEN = '$IMS-CC-FILEBASE$';

    /**
     * Token spellings recognized on input. The first entry is the storage form.
     */
    const TOKEN_ALIASES = array(
        '$IMS-CC-FILEBASE$',
        '$1EdTech-CC-FILEBASE$',
        '$IMS_CC_FILEBASE$',
    );

    /**
     * HTML attributes that carry a single URL.
     */
    const URL_ATTRIBUTES = array(
        'href',
        'src',
        'poster',
        'action',
        'formaction',
        'cite',
        'data',
        'data-oembed-url',
        'longdesc',
        'background',
    );

    /**
     * Build a course file-base URL from a request path prefix and site home.
     *
     * @param string $pathPrefix toolParent() value, e.g. '/courses/2' or '/py4e' or ''
     * @param string|null $absoluteHome $CFG->apphome or $CFG->wwwroot
     * @return string Course base with no trailing slash
     */
    public static function courseBaseUrl($pathPrefix, $absoluteHome = null) {
        $pathPrefix = is_string($pathPrefix) ? trim($pathPrefix) : '';
        $pathPrefix = rtrim($pathPrefix, '/');
        if ( $pathPrefix === '/' ) {
            $pathPrefix = '';
        }
        if ( $pathPrefix !== '' && preg_match('#^https?://#i', $pathPrefix) ) {
            return rtrim(self::collapseUrlPathSlashes($pathPrefix), '/');
        }
        if ( $pathPrefix !== '' && $pathPrefix[0] !== '/' ) {
            $pathPrefix = '/' . $pathPrefix;
        }
        $pathPrefix = self::collapseSlashRuns($pathPrefix);

        $absoluteHome = is_string($absoluteHome) ? trim($absoluteHome) : '';
        $origin = '';
        $homePath = '';
        if ( $absoluteHome !== '' && preg_match('#^https?://#i', $absoluteHome) ) {
            $parts = parse_url($absoluteHome);
            if ( is_array($parts) && ! empty($parts['host']) ) {
                $origin = strtolower($parts['scheme'] ?? 'https') . '://' . strtolower($parts['host']);
                if ( ! empty($parts['port']) ) {
                    $origin .= ':' . $parts['port'];
                }
                $homePath = isset($parts['path']) ? rtrim(self::collapseSlashRuns($parts['path']), '/') : '';
                if ( $homePath === '/' ) {
                    $homePath = '';
                }
            }
        } else if ( $absoluteHome !== '' && $absoluteHome !== '/' ) {
            $homePath = rtrim(self::collapseSlashRuns($absoluteHome), '/');
            if ( $homePath !== '' && $homePath[0] !== '/' ) {
                $homePath = '/' . $homePath;
            }
        }

        if ( $pathPrefix === '' ) {
            return $origin . $homePath;
        }
        if ( $homePath !== '' && $pathPrefix !== $homePath
            && ! str_starts_with($pathPrefix, $homePath.'/') ) {
            $pathPrefix = $homePath . $pathPrefix;
        }
        return $origin . $pathPrefix;
    }

    /**
     * Convert FILEBASE tokens and historical course-local URLs to the current course URL.
     *
     * @param string $html
     * @param string $courseBaseUrl
     * @param string[] $courseLocalPrefixes First path segments the caller treats as
     *        course-local (e.g. `pages`, `files`). Empty means: rewrite any
     *        path under a non-empty course base, but not origin-only URLs.
     * @return string
     */
    public static function expand($html, $courseBaseUrl, array $courseLocalPrefixes = array()) {
        return self::rewriteHtml($html, $courseBaseUrl, 'expand', $courseLocalPrefixes);
    }

    /**
     * Convert current-course URLs (and token aliases) to $IMS-CC-FILEBASE$...
     *
     * @param string $html
     * @param string $courseBaseUrl
     * @param string[] $courseLocalPrefixes
     * @return string
     */
    public static function canonicalize($html, $courseBaseUrl, array $courseLocalPrefixes = array()) {
        return self::rewriteHtml($html, $courseBaseUrl, 'canonicalize', $courseLocalPrefixes);
    }

    /**
     * @param string $html
     * @param string $courseBaseUrl
     * @param string $mode expand|canonicalize
     * @param string[] $courseLocalPrefixes
     * @return string
     */
    private static function rewriteHtml($html, $courseBaseUrl, $mode, array $courseLocalPrefixes = array()) {
        if ( ! is_string($html) || $html === '' ) {
            return $html;
        }
        $courseBaseUrl = self::courseBaseUrl($courseBaseUrl, null);
        if ( $courseBaseUrl === '' ) {
            return $html;
        }
        if ( $mode === 'expand' && ! self::containsFileBaseToken($html) ) {
            if ( ! preg_match('/\b(?:href|src|srcset|poster|action|formaction|cite|data-oembed-url|longdesc|background)\s*=/i', $html)
                && ! preg_match('/(?:^|\s)data\s*=/i', $html) ) {
                return $html;
            }
        }

        $wrapperId = 'tsugi-cc-filebase-root';
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $wrapped = '<?xml encoding="UTF-8"><div id="'.$wrapperId.'">'.$html.'</div>';
        $ok = $dom->loadHTML($wrapped, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ( ! $ok ) {
            return $html;
        }

        $changed = false;
        $xpath = new \DOMXPath($dom);
        foreach ( $xpath->query('//*[@*]') as $node ) {
            if ( ! $node instanceof \DOMElement ) {
                continue;
            }
            foreach ( iterator_to_array($node->attributes) as $attr ) {
                $name = strtolower($attr->name);
                $value = $attr->value;
                if ( $name === 'srcset' ) {
                    $new = self::rewriteSrcset($value, $courseBaseUrl, $mode, $courseLocalPrefixes);
                } else if ( in_array($name, self::URL_ATTRIBUTES, true) ) {
                    $new = self::rewriteUrl($value, $courseBaseUrl, $mode, $courseLocalPrefixes);
                } else {
                    continue;
                }
                if ( $new !== $value ) {
                    $node->setAttribute($attr->name, $new);
                    $changed = true;
                }
            }
        }

        if ( ! $changed ) {
            return $html;
        }

        $root = $dom->getElementById($wrapperId);
        if ( ! $root ) {
            return $html;
        }
        $out = '';
        foreach ( $root->childNodes as $child ) {
            $out .= $dom->saveHTML($child);
        }
        // libxml percent-encodes $ in URL attributes; storage uses the literal token.
        return str_replace(rawurlencode(self::TOKEN), self::TOKEN, $out);
    }

    /**
     * @param string $value
     * @param string $courseBaseUrl
     * @param string $mode
     * @param string[] $courseLocalPrefixes
     * @return string
     */
    private static function rewriteSrcset($value, $courseBaseUrl, $mode, array $courseLocalPrefixes = array()) {
        $parts = explode(',', $value);
        $out = array();
        foreach ( $parts as $part ) {
            $part = trim($part);
            if ( $part === '' ) {
                continue;
            }
            if ( preg_match('/^(.*?)(?:\s+([\d.]+x|\d+w))?$/i', $part, $m) ) {
                $url = trim($m[1]);
                $desc = isset($m[2]) ? $m[2] : '';
                $rewritten = self::rewriteUrl($url, $courseBaseUrl, $mode, $courseLocalPrefixes);
                $out[] = $desc !== '' ? ($rewritten.' '.$desc) : $rewritten;
            } else {
                $out[] = $part;
            }
        }
        return implode(', ', $out);
    }

    /**
     * @param string $url
     * @param string $courseBaseUrl
     * @param string $mode
     * @param string[] $courseLocalPrefixes
     * @return string
     */
    public static function rewriteUrl($url, $courseBaseUrl, $mode, array $courseLocalPrefixes = array()) {
        if ( ! is_string($url) ) {
            return $url;
        }
        $trimmed = trim($url);
        if ( $trimmed === '' ) {
            return $url;
        }
        $remainder = self::courseRemainder($trimmed, $courseBaseUrl, $courseLocalPrefixes);
        if ( $remainder === null ) {
            return $url;
        }
        $remainder = self::normalizeRemainder($remainder);
        if ( $mode === 'canonicalize' ) {
            return self::TOKEN . $remainder;
        }
        return self::joinCourseUrl($courseBaseUrl, $remainder);
    }

    /**
     * Remainder after the course prefix, or null if the URL is not course-local.
     *
     * Query string and fragment are kept on the remainder.
     *
     * @param string $url
     * @param string $courseBaseUrl
     * @param string[] $courseLocalPrefixes
     * @return string|null
     */
    public static function courseRemainder($url, $courseBaseUrl, array $courseLocalPrefixes = array()) {
        $url = trim($url);
        if ( $url === '' ) {
            return null;
        }
        if ( self::shouldSkipUrl($url) ) {
            return null;
        }

        $tokenRemainder = self::remainderFromToken($url);
        if ( $tokenRemainder !== null ) {
            return self::normalizeRemainder($tokenRemainder);
        }

        $courseBaseUrl = rtrim($courseBaseUrl, '/');
        $baseParts = self::splitUrl($courseBaseUrl);
        if ( $baseParts === null ) {
            return null;
        }

        $candidates = self::prefixCandidates($baseParts);
        foreach ( $candidates as $prefix ) {
            $remainder = self::remainderAfterPrefix($url, $prefix);
            if ( $remainder === null ) {
                continue;
            }
            if ( $remainder === '' ) {
                continue;
            }
            if ( self::pathHasDotDot($remainder) ) {
                return null;
            }
            $prefixPath = $prefix['path'] ?? '';
            if ( $courseLocalPrefixes ) {
                if ( ! self::remainderStartsWithLocalPrefix($remainder, $courseLocalPrefixes) ) {
                    continue;
                }
            } else if ( $prefixPath === '' ) {
                // Origin-only match is too broad unless the caller names local prefixes.
                continue;
            }
            return self::normalizeRemainder($remainder);
        }
        return null;
    }

    /**
     * @param string $url
     * @return bool
     */
    private static function shouldSkipUrl($url) {
        if ( $url[0] === '#' ) {
            return true;
        }
        $lower = strtolower($url);
        foreach ( array('mailto:', 'tel:', 'javascript:', 'data:', 'blob:', 'cid:', 'sms:') as $scheme ) {
            if ( str_starts_with($lower, $scheme) ) {
                return true;
            }
        }
        if ( str_contains($url, '/../') || str_starts_with($url, '../') || $url === '..' ) {
            return true;
        }
        return false;
    }

    /**
     * @param string $url
     * @return string|null Remainder after a FILEBASE token, or null
     */
    private static function remainderFromToken($url) {
        foreach ( self::TOKEN_ALIASES as $token ) {
            if ( str_starts_with($url, $token) ) {
                return substr($url, strlen($token));
            }
            $encoded = rawurlencode($token);
            if ( str_starts_with($url, $encoded) ) {
                return rawurldecode(substr($url, strlen($encoded)));
            }
        }
        return null;
    }

    /**
     * @param string $html
     * @return bool
     */
    private static function containsFileBaseToken($html) {
        foreach ( self::TOKEN_ALIASES as $token ) {
            if ( str_contains($html, $token) || str_contains($html, rawurlencode($token)) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $url
     * @return array{scheme:?string,host:?string,port:?int,path:string,query:?string,fragment:?string,original:string}|null
     */
    private static function splitUrl($url) {
        if ( str_starts_with($url, '//') ) {
            $parsed = parse_url('https:' . $url);
            if ( ! is_array($parsed) || empty($parsed['host']) ) {
                return null;
            }
            $parsed['scheme'] = '';
            $parsed['path'] = isset($parsed['path']) ? $parsed['path'] : '';
            $parsed['original'] = $url;
            return $parsed;
        }
        if ( $url !== '' && $url[0] === '/' ) {
            return array(
                'scheme' => null,
                'host' => null,
                'port' => null,
                'path' => $url,
                'query' => null,
                'fragment' => null,
                'original' => $url,
            );
        }
        $parsed = parse_url($url);
        if ( ! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host']) ) {
            return null;
        }
        $scheme = strtolower($parsed['scheme']);
        if ( $scheme !== 'http' && $scheme !== 'https' ) {
            return null;
        }
        $parsed['scheme'] = $scheme;
        $parsed['host'] = strtolower($parsed['host']);
        $parsed['path'] = isset($parsed['path']) ? $parsed['path'] : '';
        $parsed['original'] = $url;
        return $parsed;
    }

    /**
     * Prefixes to try when matching a URL to the current course, longest first.
     *
     * @param array $baseParts
     * @return array<int, array{scheme:?string,host:?string,port:?int,path:string}>
     */
    private static function prefixCandidates($baseParts) {
        $path = rtrim($baseParts['path'] ?? '', '/');
        $out = array();

        $out[] = array(
            'scheme' => $baseParts['scheme'] ?? null,
            'host' => $baseParts['host'] ?? null,
            'port' => $baseParts['port'] ?? null,
            'path' => $path,
        );

        if ( preg_match('#^(.*)/courses/(\d+)$#', $path, $m) ) {
            $out[] = array(
                'scheme' => $baseParts['scheme'] ?? null,
                'host' => $baseParts['host'] ?? null,
                'port' => $baseParts['port'] ?? null,
                'path' => $m[1] . '/course/' . $m[2],
            );
        } else if ( preg_match('#^/courses/(\d+)$#', $path, $m) ) {
            $out[] = array(
                'scheme' => $baseParts['scheme'] ?? null,
                'host' => $baseParts['host'] ?? null,
                'port' => $baseParts['port'] ?? null,
                'path' => '/course/' . $m[1],
            );
        }

        if ( $path !== '' && ($baseParts['host'] ?? null) ) {
            $out[] = array(
                'scheme' => $baseParts['scheme'] ?? null,
                'host' => $baseParts['host'] ?? null,
                'port' => $baseParts['port'] ?? null,
                'path' => '',
            );
        }

        return $out;
    }

    /**
     * @param string $url
     * @param array $prefix
     * @return string|null
     */
    private static function remainderAfterPrefix($url, $prefix) {
        $parts = self::splitUrl($url);
        if ( $parts === null ) {
            return null;
        }

        $urlPath = self::collapseSlashRuns($parts['path'] ?? '');
        $prefixPath = self::collapseSlashRuns($prefix['path'] ?? '');

        if ( ($parts['host'] ?? null) !== null ) {
            if ( ($prefix['host'] ?? null) === null ) {
                return null;
            }
            if ( strtolower($parts['host']) !== strtolower($prefix['host']) ) {
                return null;
            }
            if ( ! self::portsCompatible($parts['port'] ?? null, $prefix['port'] ?? null, $parts['scheme'] ?? null, $prefix['scheme'] ?? null) ) {
                return null;
            }
        } else if ( ($prefix['host'] ?? null) !== null && ! str_starts_with($url, '/') ) {
            return null;
        }

        $pathRemainder = self::stripPathPrefix($urlPath, $prefixPath);
        if ( $pathRemainder === null ) {
            return null;
        }

        $suffix = $pathRemainder;
        $query = self::queryFromUrl($url, $parts);
        $fragment = self::fragmentFromUrl($url, $parts);
        if ( $query !== null && $query !== '' ) {
            $suffix .= '?' . $query;
        }
        if ( $fragment !== null && $fragment !== '' ) {
            $suffix .= '#' . $fragment;
        }
        return $suffix;
    }

    /**
     * @param string $path
     * @param string $prefix
     * @return string|null Path after prefix, no leading slash; null if not under prefix
     */
    private static function stripPathPrefix($path, $prefix) {
        $path = $path === '' ? '/' : $path;
        $prefix = $prefix === '' ? '' : $prefix;
        if ( $prefix === '' ) {
            return $path === '/' ? '' : ltrim($path, '/');
        }
        if ( $path === $prefix ) {
            return '';
        }
        if ( str_starts_with($path, $prefix . '/') ) {
            return ltrim(substr($path, strlen($prefix)), '/');
        }
        return null;
    }

    /**
     * @param ?int $a
     * @param ?int $b
     * @param ?string $schemeA
     * @param ?string $schemeB
     * @return bool
     */
    private static function portsCompatible($a, $b, $schemeA, $schemeB) {
        $isDefault = function($port) {
            if ( ! $port ) {
                return true;
            }
            $port = (int) $port;
            return $port === 80 || $port === 443;
        };
        if ( $isDefault($a) && $isDefault($b) ) {
            return true;
        }
        $norm = function($port, $scheme) {
            if ( $port ) {
                return (int) $port;
            }
            if ( $scheme === 'https' ) {
                return 443;
            }
            if ( $scheme === 'http' ) {
                return 80;
            }
            return 0;
        };
        return $norm($a, $schemeA) === $norm($b, $schemeB);
    }

    /**
     * @param string $remainder
     * @param string[] $courseLocalPrefixes
     * @return bool
     */
    private static function remainderStartsWithLocalPrefix($remainder, array $courseLocalPrefixes) {
        $path = $remainder;
        $q = strpos($path, '?');
        if ( $q !== false ) {
            $path = substr($path, 0, $q);
        }
        $h = strpos($path, '#');
        if ( $h !== false ) {
            $path = substr($path, 0, $h);
        }
        $path = ltrim($path, '/');
        $seg = strtolower(explode('/', $path, 2)[0]);
        if ( $seg === '' ) {
            return false;
        }
        foreach ( $courseLocalPrefixes as $prefix ) {
            $want = strtolower(ltrim((string) $prefix, '/'));
            if ( $want !== '' && $seg === $want ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $remainder
     * @return bool
     */
    private static function pathHasDotDot($remainder) {
        $path = $remainder;
        $q = strpos($path, '?');
        if ( $q !== false ) {
            $path = substr($path, 0, $q);
        }
        $h = strpos($path, '#');
        if ( $h !== false ) {
            $path = substr($path, 0, $h);
        }
        foreach ( explode('/', $path) as $seg ) {
            if ( $seg === '..' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $url
     * @param array $parts
     * @return string|null
     */
    private static function queryFromUrl($url, $parts) {
        if ( isset($parts['query']) ) {
            return $parts['query'];
        }
        $q = strpos($url, '?');
        if ( $q === false ) {
            return null;
        }
        $rest = substr($url, $q + 1);
        $h = strpos($rest, '#');
        if ( $h !== false ) {
            $rest = substr($rest, 0, $h);
        }
        return $rest;
    }

    /**
     * @param string $url
     * @param array $parts
     * @return string|null
     */
    private static function fragmentFromUrl($url, $parts) {
        if ( isset($parts['fragment']) ) {
            return $parts['fragment'];
        }
        $h = strrpos($url, '#');
        if ( $h === false ) {
            return null;
        }
        return substr($url, $h + 1);
    }

    /**
     * @param string $courseBaseUrl
     * @param string $remainder
     * @return string
     */
    private static function joinCourseUrl($courseBaseUrl, $remainder) {
        $courseBaseUrl = rtrim(self::collapseUrlPathSlashes($courseBaseUrl), '/');
        $remainder = self::normalizeRemainder($remainder);
        if ( $remainder === '' ) {
            return $courseBaseUrl;
        }
        if ( $remainder[0] === '?' || $remainder[0] === '#' ) {
            return $courseBaseUrl . $remainder;
        }
        return $courseBaseUrl . '/' . $remainder;
    }

    /**
     * Collapse duplicate slashes in a path. Does not touch a URL scheme.
     *
     * @param string $path
     * @return string
     */
    private static function collapseSlashRuns($path) {
        if ( ! is_string($path) || $path === '' ) {
            return $path;
        }
        return preg_replace('#/+#', '/', $path);
    }

    /**
     * Collapse duplicate slashes in the path of an absolute or path-absolute URL.
     *
     * @param string $url
     * @return string
     */
    private static function collapseUrlPathSlashes($url) {
        if ( ! is_string($url) || $url === '' ) {
            return $url;
        }
        if ( preg_match('#^(https?://[^/]+)(/.*)?$#i', $url, $m) ) {
            $origin = $m[1];
            $rest = $m[2] ?? '';
            $hash = strpos($rest, '#');
            $fragment = '';
            if ( $hash !== false ) {
                $fragment = substr($rest, $hash);
                $rest = substr($rest, 0, $hash);
            }
            $q = strpos($rest, '?');
            $query = '';
            if ( $q !== false ) {
                $query = substr($rest, $q);
                $rest = substr($rest, 0, $q);
            }
            return $origin . self::collapseSlashRuns($rest) . $query . $fragment;
        }
        if ( $url[0] === '/' && ! str_starts_with($url, '//') ) {
            return self::collapseSlashRuns($url);
        }
        return $url;
    }

    /**
     * Remainder with no leading slash and no duplicate slashes in the path.
     * Query string and fragment are left intact (so http:// in a query stays).
     *
     * @param string $remainder
     * @return string
     */
    private static function normalizeRemainder($remainder) {
        if ( ! is_string($remainder) || $remainder === '' ) {
            return $remainder;
        }
        $fragment = '';
        $hash = strpos($remainder, '#');
        if ( $hash !== false ) {
            $fragment = substr($remainder, $hash);
            $remainder = substr($remainder, 0, $hash);
        }
        $query = '';
        $q = strpos($remainder, '?');
        if ( $q !== false ) {
            $query = substr($remainder, $q);
            $remainder = substr($remainder, 0, $q);
        }
        $remainder = ltrim($remainder, '/');
        $remainder = self::collapseSlashRuns($remainder);
        return $remainder . $query . $fragment;
    }
}
