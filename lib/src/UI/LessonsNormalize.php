<?php

namespace Tsugi\UI;

use \Tsugi\Controllers\Files;

/**
 * Canonical Lessons item model and lossless legacy normalizer.
 *
 * Foundational types: heading, web_link, html_page, file, discussion, lti.
 * Lessons meaning stays on subtype (video, slides, assignment, quiz, ...).
 * Legacy lessons.json maps to web_link (plus subtype), never file or html_page.
 *
 * Normalization is in-memory only. It does not rewrite stored lessons.json.
 */
class LessonsNormalize {

    const TYPE_HEADING = 'heading';
    const TYPE_WEB_LINK = 'web_link';
    const TYPE_HTML_PAGE = 'html_page';
    const TYPE_FILE = 'file';
    const TYPE_DISCUSSION = 'discussion';
    const TYPE_LTI = 'lti';

    /** Built-in Tsugi discussion tool path (under $CFG->wwwroot). */
    const BUILTIN_DISCUSSION_LAUNCH = 'tsugi/tool/tdiscus';

    const SUBTYPE_SECTION = 'section';
    const SUBTYPE_REFERENCE = 'reference';
    const SUBTYPE_VIDEO = 'video';
    const SUBTYPE_SLIDES = 'slides';
    const SUBTYPE_ASSIGNMENT = 'assignment';
    const SUBTYPE_SOLUTION = 'solution';
    const SUBTYPE_DISCUSSION = 'discussion';
    const SUBTYPE_QUIZ = 'quiz';
    const SUBTYPE_AUTOGRADE = 'autograder';
    const SUBTYPE_PEER_GRADE = 'peer_grade';

    const FORMAT_VERSION = 2;

    /** When false, leave a missing discussion resource_link_id empty for a later unique fill. */
    private static $inventDiscussionRlid = true;

    /**
     * True when a decoded lessons document is Lessons JSON v2.
     *
     * @param mixed $doc
     * @return bool
     */
    public static function isV2Document($doc) {
        $arr = self::asArray($doc);
        return isset($arr['lessons_json_version']) && (int) $arr['lessons_json_version'] === self::FORMAT_VERSION;
    }

    /** @var list<string> */
    private static $foundationalTypes = array(
        self::TYPE_HEADING,
        self::TYPE_WEB_LINK,
        self::TYPE_HTML_PAGE,
        self::TYPE_FILE,
        self::TYPE_DISCUSSION,
        self::TYPE_LTI,
    );

    /** @var array<string, string> */
    private static $extensionMime = array(
        'pdf' => 'application/pdf',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'zip' => 'application/zip',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'm4v' => 'video/x-m4v',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'html' => 'text/html',
        'htm' => 'text/html',
        'md' => 'text/markdown',
        'txt' => 'text/plain',
        'json' => 'application/json',
        'php' => 'text/html',
    );

    /** @var list<string> */
    private static $itemKeyOrder = array(
        'type', 'subtype', 'title', 'description', 'text', 'level', 'class', 'tag',
        'href', 'url', 'launch', 'resource_link_id', 'target', 'result', 'custom',
        'sha256', 'filename', 'content_type', 'icon',
        'youtube', 'kaltura_id', 'media',
        'note', 'notes', 'TODO', 'todo', 'review', 'project', 'FCP', 'FCPX',
        'learning_objectives', 'items',
    );

    /** @var list<string> */
    private static $documentKeyOrder = array(
        'lessons_json_version', 'title', 'description', 'count', 'required_modules',
        'settings', 'headers', 'modules', 'badges', 'launches', 'discussions',
        'discussion_order',
    );

    /**
     * @param mixed $doc
     * @return array<string, mixed>
     */
    public static function normalizeDocument($doc) {
        if ( is_object($doc) ) {
            $doc = self::asArray($doc);
        }
        if ( ! is_array($doc) ) {
            return array();
        }
        $prev_invent = self::$inventDiscussionRlid;
        self::$inventDiscussionRlid = false;
        if ( isset($doc['modules']) && is_array($doc['modules']) ) {
            foreach ( $doc['modules'] as $i => $mod ) {
                if ( ! is_array($mod) ) {
                    continue;
                }
                if ( isset($mod['items']) && is_array($mod['items']) ) {
                    foreach ( $mod['items'] as $j => $item ) {
                        if ( is_array($item) ) {
                            $doc['modules'][$i]['items'][$j] = self::normalizeItem($item);
                        }
                    }
                }
            }
        }
        if ( isset($doc['launches']) && is_array($doc['launches']) ) {
            foreach ( $doc['launches'] as $i => $launch ) {
                if ( is_array($launch) ) {
                    if ( ! isset($launch['type']) ) {
                        $launch['type'] = 'lti';
                    }
                    $doc['launches'][$i] = self::normalizeItem($launch);
                }
            }
        }
        if ( isset($doc['discussions']) && is_array($doc['discussions']) ) {
            foreach ( $doc['discussions'] as $i => $discussion ) {
                if ( is_array($discussion) ) {
                    if ( ! isset($discussion['type']) ) {
                        $discussion['type'] = 'discussion';
                    }
                    $doc['discussions'][$i] = self::normalizeItem($discussion);
                }
            }
        }
        self::ensureDiscussionResourceLinkIds($doc);
        self::$inventDiscussionRlid = $prev_invent;
        return $doc;
    }

    /**
     * Normalize one lesson item. Unknown keys are copied through.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public static function normalizeItem(array $item) {
        $out = $item;
        $type = isset($out['type']) && is_string($out['type']) ? $out['type'] : '';

        if ( isset($out['items']) && is_array($out['items']) ) {
            foreach ( $out['items'] as $i => $child ) {
                if ( is_array($child) ) {
                    $out['items'][$i] = self::normalizeItem($child);
                }
            }
        }

        if ( $type === '' ) {
            return $out;
        }

        if ( in_array($type, self::$foundationalTypes, true) ) {
            return self::enrichCanonical($out);
        }

        switch ( $type ) {
            case 'header':
                $out['type'] = self::TYPE_HEADING;
                if ( ! self::nonEmptyString($out, 'title') && self::nonEmptyString($out, 'text') ) {
                    $out['title'] = $out['text'];
                    unset($out['text']);
                }
                break;
            case 'video':
                self::mapLegacyToWebLink($out, self::SUBTYPE_VIDEO);
                break;
            case 'slide':
            case 'slides':
            case 'lecture':
                if ( isset($out['items']) && is_array($out['items'])
                    && ! self::nonEmptyString($out, 'href') && ! self::nonEmptyString($out, 'url') ) {
                    return $out;
                }
                self::mapLegacyToWebLink($out, self::SUBTYPE_SLIDES);
                break;
            case 'reference':
                self::mapLegacyToWebLink($out, self::SUBTYPE_REFERENCE);
                break;
            case 'assignment':
                self::mapLegacyToWebLink($out, self::SUBTYPE_ASSIGNMENT);
                break;
            case 'solution':
                self::mapLegacyToWebLink($out, self::SUBTYPE_SOLUTION);
                break;
            case 'discussion':
                $out['type'] = self::TYPE_DISCUSSION;
                break;
            case 'lti':
                $out['type'] = self::TYPE_LTI;
                if ( ! self::nonEmptyString($out, 'subtype') ) {
                    $inferred = self::inferLtiSubtype($out);
                    if ( $inferred !== null ) {
                        $out['subtype'] = $inferred;
                    }
                }
                break;
            default:
                // text, carousel, chapters, and unknown types stay as-is
                return $out;
        }

        return self::enrichCanonical($out);
    }

    /**
     * @param mixed $item
     * @return object
     */
    public static function normalizeItemObject($item) {
        $arr = self::asArray($item);
        $norm = self::normalizeItem($arr);
        $obj = json_decode(json_encode($norm));
        return is_object($obj) ? $obj : (object) $norm;
    }

    /**
     * @param array<string, mixed> $doc
     * @return string
     */
    public static function serializeV2(array $doc) {
        $norm = self::normalizeDocument($doc);
        $norm['lessons_json_version'] = self::FORMAT_VERSION;
        $norm = self::orderDocument($norm);
        return json_encode($norm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public static function isHeading($item) {
        $type = self::typeOf($item);
        return $type === self::TYPE_HEADING || $type === 'header';
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public static function isDiscussion($item) {
        $type = self::typeOf($item);
        $subtype = self::subtypeOf($item);
        if ( $type === self::TYPE_DISCUSSION ) {
            return true;
        }
        return $type === self::TYPE_LTI && $subtype === self::SUBTYPE_DISCUSSION;
    }

    /**
     * True when launch is the built-in Tsugi discussion tool (tsugi/tool/tdiscus).
     * Other tdiscus paths (for example mod/tdiscus/) do not match.
     *
     * @param mixed $launch
     * @return bool
     */
    public static function isBuiltInDiscussionLaunch($launch) {
        if ( ! is_string($launch) ) {
            return false;
        }
        $path = strtolower(trim($launch));
        if ( $path === '' ) {
            return false;
        }
        $q = strpos($path, '?');
        if ( $q !== false ) {
            $path = substr($path, 0, $q);
        }
        $h = strpos($path, '#');
        if ( $h !== false ) {
            $path = substr($path, 0, $h);
        }
        $path = rtrim($path, '/');
        $needle = self::BUILTIN_DISCUSSION_LAUNCH;
        return $path === $needle || str_ends_with($path, '/'.$needle);
    }

    /**
     * Absolute URL of the built-in discussion tool ({wwwroot}/tool/tdiscus).
     *
     * @return string
     */
    public static function builtinDiscussionLaunchUrl() {
        global $CFG;
        $wwwroot = ( isset($CFG) && isset($CFG->wwwroot) && is_string($CFG->wwwroot) )
            ? rtrim($CFG->wwwroot, '/') : '';
        return $wwwroot . '/tool/tdiscus/';
    }

    /**
     * LTI endpoint for a lessons item. Type discussion always launches
     * {wwwroot}/tool/tdiscus; other items use their stored launch.
     *
     * @param mixed $item
     * @return string
     */
    public static function launchUrlForItem($item) {
        $type = self::typeOf($item);
        if ( $type === self::TYPE_DISCUSSION ) {
            return self::builtinDiscussionLaunchUrl();
        }
        $arr = self::asArray($item);
        return isset($arr['launch']) && is_string($arr['launch']) ? $arr['launch'] : '';
    }

    /**
     * LTI launch item, including discussion (resource_link lookups).
     *
     * @param mixed $item
     * @return bool
     */
    public static function isLtiLaunch($item) {
        $type = self::typeOf($item);
        return $type === self::TYPE_LTI || $type === self::TYPE_DISCUSSION;
    }

    /**
     * Graded LTI assignment rows (excludes discussion).
     *
     * @param mixed $item
     * @return bool
     */
    public static function isAssignmentLti($item) {
        if ( ! self::isLtiLaunch($item) || self::isDiscussion($item) ) {
            return false;
        }
        $arr = self::asArray($item);
        return isset($arr['resource_link_id']) && $arr['resource_link_id'] !== '' && $arr['resource_link_id'] !== null;
    }

    /**
     * Legacy presentation kind for icons, grouping, and getUrlResources.
     *
     * @param mixed $item
     * @return string
     */
    public static function presentationKind($item) {
        $arr = self::asArray($item);
        $type = self::typeOf($arr);
        $subtype = self::subtypeOf($arr);

        if ( $type === 'header' || $type === self::TYPE_HEADING ) {
            return 'header';
        }
        if ( $type === 'discussion' || $subtype === self::SUBTYPE_DISCUSSION ) {
            return 'discussion';
        }
        if ( $subtype === self::SUBTYPE_VIDEO || $type === 'video' ) {
            return 'video';
        }
        if ( $subtype === self::SUBTYPE_SLIDES || $type === 'slide' || $type === 'slides' || $type === 'lecture' ) {
            return 'slide';
        }
        if ( $subtype === self::SUBTYPE_ASSIGNMENT || $type === 'assignment' ) {
            return 'assignment';
        }
        if ( $subtype === self::SUBTYPE_SOLUTION || $type === 'solution' ) {
            return 'solution';
        }
        if ( $subtype === self::SUBTYPE_REFERENCE || $type === 'reference' ) {
            return 'reference';
        }
        if ( $subtype === self::SUBTYPE_QUIZ ) {
            return 'quiz';
        }
        if ( $subtype === self::SUBTYPE_AUTOGRADE ) {
            return 'autograder';
        }
        if ( $subtype === self::SUBTYPE_PEER_GRADE ) {
            return 'peer_grade';
        }
        if ( $type === self::TYPE_LTI || $type === 'lti' ) {
            return 'lti';
        }
        if ( $type === self::TYPE_WEB_LINK || $type === self::TYPE_HTML_PAGE || $type === self::TYPE_FILE ) {
            return $type;
        }
        return $type;
    }

    /**
     * Group key used by renderSingle list wrapping.
     *
     * @param mixed $item
     * @return string|null
     */
    public static function sectionGroup($item) {
        $kind = self::presentationKind($item);
        $map = array(
            'video' => 'videos',
            'reference' => 'references',
            'discussion' => 'discussions',
            'lti' => 'ltis',
            'quiz' => 'ltis',
            'autograder' => 'ltis',
            'peer_grade' => 'ltis',
            'slide' => 'slides',
            'assignment' => 'assignments',
            'solution' => 'solutions',
            self::TYPE_WEB_LINK => 'web_links',
            self::TYPE_FILE => 'files',
            self::TYPE_HTML_PAGE => 'html_pages',
        );
        return isset($map[$kind]) ? $map[$kind] : null;
    }

    /**
     * Icon resolution: explicit icon, subtype, content_type, then foundational type.
     *
     * @param mixed $item
     * @return string Type key or fa-* class
     */
    public static function iconKey($item) {
        $arr = self::asArray($item);
        if ( isset($arr['icon']) && is_string($arr['icon']) && $arr['icon'] !== '' ) {
            return $arr['icon'];
        }
        $kind = self::presentationKind($arr);
        $recognized = array(
            'video', 'slide', 'reference', 'assignment', 'solution',
            'discussion', 'lti', 'header', 'text', 'quiz', 'autograder', 'peer_grade',
        );
        $linkish = array('slide', 'reference', 'assignment', 'solution', self::TYPE_WEB_LINK, self::TYPE_FILE, self::TYPE_HTML_PAGE);
        $href = self::hrefOf($arr);
        $content_type = isset($arr['content_type']) && is_string($arr['content_type']) ? $arr['content_type'] : '';
        $is_pdf = ( $content_type === 'application/pdf' )
            || ( $href !== '' && (bool) preg_match('/\.pdf($|[?#])/i', $href) );
        if ( $is_pdf && in_array($kind, $linkish, true) ) {
            return 'pdf';
        }
        if ( in_array($kind, $recognized, true) ) {
            return $kind;
        }
        if ( $content_type === 'application/pdf' ) {
            return 'pdf';
        }
        $type = self::typeOf($arr);
        return $type !== '' ? $type : 'web_link';
    }

    /**
     * @param mixed $item
     * @return string
     */
    public static function typeOf($item) {
        $arr = self::asArray($item);
        return isset($arr['type']) && is_string($arr['type']) ? $arr['type'] : '';
    }

    /**
     * @param mixed $item
     * @return string
     */
    public static function subtypeOf($item) {
        $arr = self::asArray($item);
        return isset($arr['subtype']) && is_string($arr['subtype']) ? $arr['subtype'] : '';
    }

    /**
     * Infer Lessons subtype from an LTI launch path. Does not override an explicit subtype.
     *
     * @param array<string, mixed> $item
     * @return string|null
     */
    public static function inferLtiSubtype(array $item) {
        if ( self::nonEmptyString($item, 'subtype') ) {
            return $item['subtype'];
        }
        $launch = isset($item['launch']) && is_string($item['launch']) ? strtolower($item['launch']) : '';
        if ( $launch === '' ) {
            return null;
        }
        if ( strpos($launch, 'tdiscus') !== false ) {
            return self::SUBTYPE_DISCUSSION;
        }
        if ( strpos($launch, 'peer-grade') !== false || strpos($launch, 'peergrade') !== false ) {
            return self::SUBTYPE_PEER_GRADE;
        }
        if ( strpos($launch, '/gift') !== false || strpos($launch, 'mod/gift') !== false ) {
            return self::SUBTYPE_QUIZ;
        }
        if ( strpos($launch, 'autograder') !== false || strpos($launch, 'pythonauto') !== false ) {
            return self::SUBTYPE_AUTOGRADE;
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public static function asArray($value) {
        if ( is_array($value) ) {
            return $value;
        }
        if ( is_object($value) ) {
            $arr = json_decode(json_encode($value), true);
            return is_array($arr) ? $arr : array();
        }
        return array();
    }

    /**
     * Collect every scalar/array field under modules[].items[] (and nested items).
     *
     * @param array<string, mixed> $doc
     * @return array<int, array{path: string, key: string, value: mixed}>
     */
    public static function collectItemFields(array $doc) {
        $out = array();
        if ( ! isset($doc['modules']) || ! is_array($doc['modules']) ) {
            return $out;
        }
        foreach ( $doc['modules'] as $mi => $mod ) {
            if ( ! is_array($mod) || ! isset($mod['items']) || ! is_array($mod['items']) ) {
                continue;
            }
            self::collectItemFieldsWalk($mod['items'], 'modules.'.$mi.'.items', $out);
        }
        return $out;
    }

    /**
     * @param array<int, mixed> $items
     * @param array<int, array{path: string, key: string, value: mixed}> $out
     */
    private static function collectItemFieldsWalk(array $items, $prefix, array &$out) {
        foreach ( $items as $i => $item ) {
            if ( ! is_array($item) ) {
                continue;
            }
            $path = $prefix.'.'.$i;
            foreach ( $item as $key => $value ) {
                if ( $key === 'items' && is_array($value) ) {
                    self::collectItemFieldsWalk($value, $path.'.items', $out);
                    continue;
                }
                $out[] = array('path' => $path, 'key' => (string) $key, 'value' => $value);
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function enrichCanonical(array $item) {
        $type = isset($item['type']) ? $item['type'] : '';

        if ( $type === self::TYPE_HEADING ) {
            if ( ! self::nonEmptyString($item, 'title') && self::nonEmptyString($item, 'text') ) {
                $item['title'] = $item['text'];
                unset($item['text']);
            }
            return $item;
        }

        if ( $type === self::TYPE_LTI ) {
            $launch = isset($item['launch']) && is_string($item['launch']) ? $item['launch'] : '';
            if ( self::isBuiltInDiscussionLaunch($launch) ) {
                return self::canonicalizeDiscussion($item);
            }
            if ( ! self::nonEmptyString($item, 'subtype') ) {
                $inferred = self::inferLtiSubtype($item);
                if ( $inferred !== null ) {
                    $item['subtype'] = $inferred;
                }
            }
            return $item;
        }

        if ( $type === self::TYPE_DISCUSSION ) {
            return self::canonicalizeDiscussion($item);
        }

        if ( $type === self::TYPE_WEB_LINK || $type === self::TYPE_HTML_PAGE || $type === self::TYPE_FILE ) {
            self::promoteHref($item);
            self::applyFileIdentity($item);
            if ( $type === self::TYPE_WEB_LINK ) {
                self::ensureVideoHref($item);
            }
            if ( ! self::nonEmptyString($item, 'content_type') ) {
                $inferred = self::inferContentType($item);
                if ( $inferred !== null ) {
                    $item['content_type'] = $inferred;
                }
            }
        }

        return $item;
    }

    /**
     * Common Cartridge-shaped discussion: title, resource_link_id, optional description.
     * Drop the implied tsugi/tool/tdiscus launch and a redundant discussion subtype.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private static function canonicalizeDiscussion(array $item) {
        $item['type'] = self::TYPE_DISCUSSION;
        $launch = isset($item['launch']) && is_string($item['launch']) ? $item['launch'] : '';
        if ( $launch === '' || self::isBuiltInDiscussionLaunch($launch) ) {
            unset($item['launch']);
        }
        if ( isset($item['subtype']) && $item['subtype'] === self::SUBTYPE_DISCUSSION ) {
            unset($item['subtype']);
        }
        if ( isset($item['resource_link_id']) && is_string($item['resource_link_id']) ) {
            $item['resource_link_id'] = trim($item['resource_link_id']);
        }
        if ( ! self::nonEmptyString($item, 'resource_link_id') && self::$inventDiscussionRlid ) {
            $title = isset($item['title']) && is_string($item['title']) ? $item['title'] : '';
            $item['resource_link_id'] = self::discussionRlidBase($title);
        }
        return $item;
    }

    /**
     * Slug used when inventing a discussion resource_link_id from its title.
     *
     * @param mixed $title
     * @return string
     */
    public static function discussionRlidBase($title) {
        $slug = is_string($title) ? strtolower($title) : '';
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        if ( $slug === '' ) {
            $slug = 'topic';
        }
        if ( strlen($slug) > 40 ) {
            $slug = substr($slug, 0, 40);
            $slug = rtrim($slug, '_');
        }
        return 'discussion_' . $slug;
    }

    /**
     * Unused resource_link_id for a discussion title.
     *
     * @param mixed $title
     * @param array<string, true> $used
     * @return string
     */
    public static function allocateDiscussionRlid($title, array &$used) {
        $base = self::discussionRlidBase($title);
        $rlid = $base;
        $n = 2;
        while ( isset($used[$rlid]) ) {
            $rlid = $base . '_' . $n;
            $n++;
            if ( $n > 50 ) {
                $rlid = $base . '_' . bin2hex(random_bytes(3));
                break;
            }
        }
        $used[$rlid] = true;
        return $rlid;
    }

    /**
     * Fill missing discussion resource_link_id values and uniquify collisions.
     *
     * @param array<string, mixed> $doc
     */
    private static function ensureDiscussionResourceLinkIds(array &$doc) {
        $used = array();
        self::collectAllResourceLinkIds($doc, $used);

        $assign = function (&$item) use (&$used) {
            if ( ! is_array($item) || ! self::isDiscussion($item) ) {
                return;
            }
            $rlid = isset($item['resource_link_id']) && is_string($item['resource_link_id'])
                ? trim($item['resource_link_id']) : '';
            if ( $rlid === '' ) {
                $title = isset($item['title']) && is_string($item['title']) ? $item['title'] : '';
                $item['resource_link_id'] = self::allocateDiscussionRlid($title, $used);
            }
        };

        if ( isset($doc['discussions']) && is_array($doc['discussions']) ) {
            foreach ( $doc['discussions'] as $i => $discussion ) {
                $assign($doc['discussions'][$i]);
            }
        }
        if ( isset($doc['modules']) && is_array($doc['modules']) ) {
            foreach ( $doc['modules'] as $i => $mod ) {
                if ( ! is_array($mod) ) {
                    continue;
                }
                if ( isset($mod['items']) && is_array($mod['items']) ) {
                    foreach ( $mod['items'] as $j => $item ) {
                        $assign($doc['modules'][$i]['items'][$j]);
                    }
                }
                if ( isset($mod['discussions']) && is_array($mod['discussions']) ) {
                    foreach ( $mod['discussions'] as $j => $discussion ) {
                        $assign($doc['modules'][$i]['discussions'][$j]);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $doc
     * @param array<string, true> $used
     */
    private static function collectAllResourceLinkIds(array $doc, array &$used) {
        $take = function ($item) use (&$used) {
            if ( ! is_array($item) ) {
                return;
            }
            if ( isset($item['resource_link_id']) && is_string($item['resource_link_id'])
                    && trim($item['resource_link_id']) !== '' ) {
                $used[trim($item['resource_link_id'])] = true;
            }
        };
        if ( isset($doc['discussions']) && is_array($doc['discussions']) ) {
            foreach ( $doc['discussions'] as $discussion ) {
                $take($discussion);
            }
        }
        if ( isset($doc['launches']) && is_array($doc['launches']) ) {
            foreach ( $doc['launches'] as $launch ) {
                $take($launch);
            }
        }
        if ( isset($doc['modules']) && is_array($doc['modules']) ) {
            foreach ( $doc['modules'] as $mod ) {
                if ( ! is_array($mod) ) {
                    continue;
                }
                if ( isset($mod['lti']) && is_array($mod['lti']) ) {
                    foreach ( $mod['lti'] as $lti ) {
                        $take($lti);
                    }
                }
                if ( isset($mod['discussions']) && is_array($mod['discussions']) ) {
                    foreach ( $mod['discussions'] as $discussion ) {
                        $take($discussion);
                    }
                }
                if ( isset($mod['items']) && is_array($mod['items']) ) {
                    foreach ( $mod['items'] as $item ) {
                        $take($item);
                    }
                }
            }
        }
    }

    /**
     * Legacy lessons.json has no file or html_page items — only href/url
     * resources. Those become web_link plus a Lessons subtype. file and
     * html_page are created only by new Lessons JSON v2 authoring.
     *
     * @param array<string, mixed> $item
     * @param string $subtype
     */
    private static function mapLegacyToWebLink(array &$item, $subtype) {
        self::promoteHref($item);
        $item['type'] = self::TYPE_WEB_LINK;
        if ( ! self::nonEmptyString($item, 'subtype') ) {
            $item['subtype'] = $subtype;
        }
        if ( $subtype === self::SUBTYPE_VIDEO ) {
            self::ensureVideoHref($item);
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function promoteHref(array &$item) {
        if ( ! self::nonEmptyString($item, 'href') && self::nonEmptyString($item, 'url') ) {
            $item['href'] = $item['url'];
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function ensureVideoHref(array &$item) {
        if ( self::hrefOf($item) !== '' ) {
            return;
        }
        $subtype = self::subtypeOf($item);
        $type = self::typeOf($item);
        if ( $subtype !== self::SUBTYPE_VIDEO && $type !== 'video' && $type !== self::TYPE_WEB_LINK ) {
            return;
        }
        if ( isset($item['youtube']) && is_string($item['youtube']) && $item['youtube'] !== '' ) {
            $item['href'] = 'https://www.youtube.com/watch?v='.$item['youtube'];
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function applyFileIdentity(array &$item) {
        $href = self::hrefOf($item);
        $sha = self::sha256Of($item, $href);
        if ( $sha !== null ) {
            $item['sha256'] = $sha;
            if ( ! self::nonEmptyString($item, 'href') ) {
                $download = Files::downloadHrefForSha256($sha);
                if ( $download !== null ) {
                    $item['href'] = $download;
                }
            }
        }
        if ( isset($item['type']) && $item['type'] === self::TYPE_FILE
            && ! self::nonEmptyString($item, 'filename') ) {
            $filename = self::filenameFromHref($href);
            if ( $filename !== null ) {
                $item['filename'] = $filename;
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @return string|null
     */
    public static function inferContentType(array $item) {
        if ( self::nonEmptyString($item, 'content_type') ) {
            return $item['content_type'];
        }
        $filename = isset($item['filename']) && is_string($item['filename']) ? $item['filename'] : '';
        if ( $filename !== '' ) {
            $mime = self::mimeFromPath($filename);
            if ( $mime !== null ) {
                return $mime;
            }
        }
        $href = self::hrefOf($item);
        if ( $href !== '' ) {
            $mime = self::mimeFromPath($href);
            if ( $mime !== null ) {
                return $mime;
            }
        }
        $subtype = self::subtypeOf($item);
        if ( $subtype === self::SUBTYPE_VIDEO || isset($item['youtube']) || isset($item['kaltura_id']) ) {
            if ( isset($item['type']) && $item['type'] === self::TYPE_WEB_LINK ) {
                return 'text/html';
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $item
     * @return string
     */
    private static function hrefOf(array $item) {
        if ( isset($item['href']) && is_string($item['href']) ) {
            return $item['href'];
        }
        if ( isset($item['url']) && is_string($item['url']) ) {
            return $item['url'];
        }
        return '';
    }

    /**
     * @param array<string, mixed> $item
     * @return string|null
     */
    private static function sha256Of(array $item, $href) {
        if ( isset($item['sha256']) && Files::isSha256($item['sha256']) ) {
            return strtolower($item['sha256']);
        }
        $from_href = Files::sha256FromDownloadHref($href);
        return $from_href;
    }

    /**
     * @return string
     */
    private static function extensionOf($href) {
        if ( ! is_string($href) || $href === '' ) {
            return '';
        }
        $path = $href;
        $parsed = parse_url($href, PHP_URL_PATH);
        if ( is_string($parsed) && $parsed !== '' ) {
            $path = $parsed;
        }
        $path = preg_replace('/[?#].*$/', '', $path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return is_string($ext) ? $ext : '';
    }

    /**
     * @return string|null
     */
    private static function mimeFromPath($path) {
        $ext = self::extensionOf($path);
        if ( $ext === '' || ! isset(self::$extensionMime[$ext]) ) {
            return null;
        }
        return self::$extensionMime[$ext];
    }

    /**
     * Human filename from a retrieval URL. Never uses a SHA-256 path segment.
     *
     * @return string|null
     */
    private static function filenameFromHref($href) {
        if ( ! is_string($href) || $href === '' ) {
            return null;
        }
        if ( Files::sha256FromDownloadHref($href) !== null ) {
            return null;
        }
        $path = $href;
        $parsed = parse_url($href, PHP_URL_PATH);
        if ( is_string($parsed) && $parsed !== '' ) {
            $path = $parsed;
        }
        $path = preg_replace('/[?#].*$/', '', $path);
        $base = basename($path);
        if ( $base === '' || $base === '.' || $base === '/' ) {
            return null;
        }
        if ( strpos($base, '{') !== false ) {
            return null;
        }
        return $base;
    }

    /**
     * @param array<string, mixed> $item
     * @return bool
     */
    private static function nonEmptyString(array $item, $key) {
        return isset($item[$key]) && is_string($item[$key]) && $item[$key] !== '';
    }

    /**
     * @param array<string, mixed> $doc
     * @return array<string, mixed>
     */
    private static function orderDocument(array $doc) {
        if ( isset($doc['modules']) && is_array($doc['modules']) ) {
            foreach ( $doc['modules'] as $i => $mod ) {
                if ( ! is_array($mod) ) {
                    continue;
                }
                if ( isset($mod['items']) && is_array($mod['items']) ) {
                    foreach ( $mod['items'] as $j => $item ) {
                        if ( is_array($item) ) {
                            $doc['modules'][$i]['items'][$j] = self::orderKeys($item);
                        }
                    }
                }
                $doc['modules'][$i] = self::orderKeys($mod, array(
                    'title', 'anchor', 'icon', 'description', 'login', 'hidden', 'items',
                ));
            }
        }
        if ( isset($doc['launches']) && is_array($doc['launches']) ) {
            foreach ( $doc['launches'] as $i => $launch ) {
                if ( is_array($launch) ) {
                    $doc['launches'][$i] = self::orderKeys($launch);
                }
            }
        }
        if ( isset($doc['discussions']) && is_array($doc['discussions']) ) {
            foreach ( $doc['discussions'] as $i => $discussion ) {
                if ( is_array($discussion) ) {
                    $doc['discussions'][$i] = self::orderKeys($discussion);
                }
            }
        }
        return self::orderKeys($doc, self::$documentKeyOrder);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string>|null $preferred
     * @return array<string, mixed>
     */
    private static function orderKeys(array $data, $preferred = null) {
        if ( $preferred === null ) {
            $preferred = self::$itemKeyOrder;
        }
        $out = array();
        foreach ( $preferred as $key ) {
            if ( array_key_exists($key, $data) ) {
                $out[$key] = $data[$key];
            }
        }
        $rest = array();
        foreach ( $data as $key => $value ) {
            if ( ! array_key_exists($key, $out) ) {
                $rest[$key] = $value;
            }
        }
        ksort($rest);
        foreach ( $rest as $key => $value ) {
            $out[$key] = $value;
        }
        return $out;
    }
}
