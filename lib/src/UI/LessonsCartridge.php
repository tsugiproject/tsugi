<?php

namespace Tsugi\UI;

use Tsugi\Util\CC;
use Tsugi\Util\CCFileBase;
use Tsugi\Util\CCIdentifier;
use Tsugi\Util\U;
use Tsugi\Controllers\Files;
use Tsugi\Controllers\Pages;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\Qti12Exporter;
use Tsugi\Services\Quiz1\QuizRepository;

/**
 * Common Cartridge export from an in-memory Lessons document (v2 items).
 *
 * Used by course Settings export. Does not rewrite stored lessons JSON.
 *
 * Canvas's cartridge (wiki_content, canvas_export.txt, module_meta, FILEBASE,
 * $WIKI_REFERENCE$) is the interchange format. The Canvas and Tsugi flavors
 * emit that same zip so Canvas import works today and Tsugi import can later
 * consume a cartridge Canvas itself exported. Do not add Tsugi-only zip
 * shapes to those flavors. Generic (CC 1.2) stays spec CC 1.2. Generic
 * (CC 1.1) and Moodle are the same Generic content as Common Cartridge 1.1.
 */
class LessonsCartridge {

    /**
     * sha256 / "path:{course path}" => zip path (web_resources/...).
     *
     * @var array<string, string>
     */
    private static $exportFileZipPaths = array();

    /**
     * sha256 => array{zipPath:string,identifierref:string,listings:int}
     *
     * @var array<string, array{zipPath:string,identifierref:string,listings:int}>
     */
    private static $exportFileResources = array();

    /**
     * Wiki pages deferred until files are in the zip so FILEBASE links can use paths.
     *
     * @var list<array{html:string,title:string,logical_key:string,sub_module:mixed}>
     */
    private static $pendingWikiPages = array();

    /**
     * Counts for the export form.
     *
     * @return array{modules:int,resources:int,assignments:int,discussions:int,quizzes:int,files:int,pages:int}
     */
    public static function summarize($l) {
        $modules = 0;
        $resources = 0;
        $assignments = 0;
        $discussions = 0;
        $quizzes = 0;
        $files = 0;
        $pages = 0;
        if ( ! isset($l->lessons->modules) || ! is_array($l->lessons->modules) ) {
            return array(
                'modules' => 0,
                'resources' => 0,
                'assignments' => 0,
                'discussions' => 0,
                'quizzes' => 0,
                'files' => 0,
                'pages' => 0,
            );
        }
        foreach ( $l->lessons->modules as $module ) {
            $modules++;
            $c = self::moduleCounts($module);
            $resources += $c['resources'];
            $assignments += $c['assignments'];
            $discussions += $c['discussions'];
            $quizzes += $c['quizzes'];
            $files += $c['files'];
            $pages += $c['pages'];
        }
        return array(
            'modules' => $modules,
            'resources' => $resources,
            'assignments' => $assignments,
            'discussions' => $discussions,
            'quizzes' => $quizzes,
            'files' => $files,
            'pages' => $pages,
        );
    }

    /**
     * @return array{resources:int,assignments:int,discussions:int,quizzes:int,files:int,pages:int}
     */
    public static function moduleCounts($module) {
        $resources = 0;
        $assignments = 0;
        $discussions = 0;
        $quizzes = 0;
        $files = 0;
        $pages = 0;
        foreach ( self::itemsForModule($module) as $item ) {
            self::countItem($item, $resources, $assignments, $discussions, $quizzes, $files, $pages);
        }
        return array(
            'resources' => $resources,
            'assignments' => $assignments,
            'discussions' => $discussions,
            'quizzes' => $quizzes,
            'files' => $files,
            'pages' => $pages,
        );
    }

    /**
     * Count one item and its nested items, matching writeZip()/processChildren().
     */
    private static function countItem($item, &$resources, &$assignments, &$discussions, &$quizzes, &$files, &$pages) {
        $item = is_array($item) ? (object) $item : $item;
        $kind = LessonsNormalize::presentationKind($item);
        if ( $kind !== 'header' && ! LessonsNormalize::isHeading($item) ) {
            if ( LessonsNormalize::isNativeQuiz($item) ) {
                $quizzes++;
            } else if ( LessonsNormalize::isDiscussion($item) ) {
                $discussions++;
            } else if ( self::isAssignmentLtiKind($kind) ) {
                $assignments++;
            } else if ( LessonsNormalize::typeOf($item) === LessonsNormalize::TYPE_FILE ) {
                $files++;
            } else if ( LessonsNormalize::typeOf($item) === LessonsNormalize::TYPE_HTML_PAGE ) {
                $pages++;
            } else if ( self::itemHasExportUrl($item, $kind) ) {
                $resources++;
            }
        }
        if ( isset($item->items) && is_array($item->items) ) {
            foreach ( $item->items as $child ) {
                self::countItem($child, $resources, $assignments, $discussions, $quizzes, $files, $pages);
            }
        }
    }

    /**
     * Write imsmanifest.xml and resource files into an open ZipArchive.
     *
     * @param object $l Lessons
     * @param \ZipArchive $zip
     * @param array{tsugi_lms?:string,topic?:string,anchors?:array|false,context_id?:int,load_quiz?:callable,load_file?:callable,load_page?:callable} $options
     */
    public static function writeZip($l, $zip, array $options = array()) {
        global $CFG;

        $tsugi_lms = self::exportFlavor(isset($options['tsugi_lms']) ? $options['tsugi_lms'] : '');
        $options['tsugi_lms'] = $tsugi_lms;
        $topic = self::exportTopicMode(isset($options['topic']) ? $options['topic'] : false);
        $options['topic'] = $topic;
        $anchors = isset($options['anchors']) ? $options['anchors'] : false;

        $title = isset($l->lessons->title) ? $l->lessons->title : '';
        if ( ! is_string($title) || trim($title) === '' ) {
            $title = isset($CFG->context_title) ? $CFG->context_title : 'Course';
        }

        $cc_dom = new CC(CC::profileForFlavor($tsugi_lms));
        // Generic 1.2 / Generic 1.1 / Moodle are spec CC. Canvas/Tsugi emit Canvas's interchange format.
        // Sakai still gets module_meta. Pages must not flip Generic onto wiki.
        if ( ! self::wantsCanvasExtensions($tsugi_lms) ) {
            $cc_dom->disable_canvas_extensions();
        }
        if ( self::usesCanvasCartridge($tsugi_lms) ) {
            $cc_dom->canvas_quiz_wrapper = true;
        }
        $cc_dom->set_title($title.' import');
        self::$exportFileZipPaths = array();
        self::$exportFileResources = array();
        self::$pendingWikiPages = array();
        $top_module = false;
        if ( $tsugi_lms === 'sakai' ) {
            $top_module = $cc_dom->add_module('Modules (import)', '');
        }

        foreach ( $l->lessons->modules as $module ) {
            if ( is_array($anchors) && count($anchors) > 0 ) {
                $anchor = isset($module->anchor) ? (string) $module->anchor : '';
                if ( $anchor === '' || ! in_array($anchor, $anchors, true) ) {
                    continue;
                }
            }
            if ( $top_module ) {
                $sub_module = $cc_dom->add_sub_module(
                    $top_module,
                    $module->title,
                    'Modules (import)',
                    $module
                );
            } else {
                $sub_module = $cc_dom->add_module($module->title, '', $module);
            }
            foreach ( self::itemsForModule($module) as $item ) {
                self::processItem($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            }
        }

        self::flushPendingWikiPages($zip, $cc_dom, $options);
        self::$exportFileZipPaths = array();
        self::$exportFileResources = array();
        self::$pendingWikiPages = array();

        if ( $cc_dom->canvas_extensions ) {
            $cc_dom->zip_add_canvas_module_meta($zip);
        }
        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
    }

    /**
     * Canvas course_settings / assignment wrappers are LMS extras, not CC 1.2.
     * Generic Setup export omits them. Legacy /cc/export is unchanged.
     */
    public static function wantsCanvasExtensions($tsugi_lms) {
        return self::usesCanvasCartridge($tsugi_lms) || $tsugi_lms === 'sakai';
    }

    /**
     * Canvas and Tsugi write Canvas's interchange cartridge (same zip contents).
     * Future Tsugi import should accept this format from Tsugi or from Canvas.
     *
     * @param string $tsugi_lms
     * @return bool
     */
    public static function usesCanvasCartridge($tsugi_lms) {
        return $tsugi_lms === 'canvas' || $tsugi_lms === 'tsugi';
    }

    /**
     * How discussions are written into the cartridge.
     *
     * Default is native CC discussion (LMS topic), not an LTI link.
     *
     * @param mixed $raw
     * @return string none|lti|lms|lti_grade
     */
    public static function exportTopicMode($raw) {
        if ( ! is_string($raw) ) {
            return 'lms';
        }
        $raw = strtolower(trim($raw));
        if ( $raw === 'none' || $raw === 'lti' || $raw === 'lti_grade' || $raw === 'lms' ) {
            return $raw;
        }
        return 'lms';
    }

    /**
     * Setup flavor used in the download filename: generic, generic11, moodle, canvas, tsugi, or sakai.
     */
    public static function exportFlavor($tsugi_lms) {
        $lms = is_string($tsugi_lms) ? strtolower(trim($tsugi_lms)) : '';
        if ( $lms === 'canvas' || $lms === 'sakai' || $lms === 'tsugi' || $lms === 'moodle' || $lms === 'generic11' ) {
            return $lms;
        }
        return 'generic';
    }

    /**
     * LMS flavor labels for export dropdowns, including the CC version each flavor writes.
     *
     * @return array<string, string> flavor => label
     */
    public static function exportFlavorLabels() {
        return array(
            'generic' => 'Generic (CC 1.2)',
            'canvas' => 'Canvas (CC 1.2)',
            'sakai' => 'Sakai (CC 1.2)',
            'tsugi' => 'Tsugi (CC 1.2)',
            'generic11' => 'Generic (CC 1.1)',
            'moodle' => 'Moodle (CC 1.1)',
        );
    }

    /**
     * Download basename for the .imscc file, including the export flavor.
     */
    public static function downloadName($l, $tsugi_lms = 'generic') {
        global $CFG;
        $title = isset($l->lessons->title) && is_string($l->lessons->title) ? $l->lessons->title : '';
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $title);
        $slug = trim($slug, '-');
        if ( $slug === '' ) {
            $service = isset($CFG->servicename) ? strtolower((string) $CFG->servicename) : 'course';
            $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $service);
            $slug = trim($slug, '-');
        }
        if ( $slug === '' ) {
            $slug = 'course';
        }
        return $slug.'_'.self::exportFlavor($tsugi_lms).'.imscc';
    }

    /**
     * Emit a heading (or legacy header) as a CC item with no resource.
     *
     * Used by Settings export and legacy /cc/export. Canvas stores these as
     * ContextModuleSubHeader in module_meta.
     *
     * @param CC $cc_dom
     * @param mixed $sub_module
     * @param mixed $item
     * @param string|null $parentPath
     * @return bool true when the item is a heading (even if an empty title skips emit)
     */
    public static function addHeadingItem($cc_dom, $sub_module, $item, $parentPath = null) {
        $item = is_array($item) ? (object) $item : $item;
        if ( ! LessonsNormalize::isHeading($item) ) {
            return false;
        }
        $header_text = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : '');
        if ( is_string($header_text) && $header_text !== '' ) {
            $cc_dom->add_header_item($sub_module, $header_text, $parentPath, $item);
        }
        return true;
    }

    /**
     * Emit a video as a CC web link, preferring Kaltura when configured.
     *
     * Same preference as Lessons rendering: kaltura_embed + kaltura_id, else
     * a YouTube watch URL.
     *
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param mixed $sub_module
     * @param mixed $item
     * @param string|null $parentPath
     * @return bool true when the item is a video
     */
    public static function addVideoItem($zip, $cc_dom, $sub_module, $item, $parentPath = null) {
        $item = is_array($item) ? (object) $item : $item;
        if ( LessonsNormalize::presentationKind($item) !== 'video' ) {
            return false;
        }
        $title = __('Video:').' '.(isset($item->title) ? $item->title : '');
        $kaltura_url = Lessons::kalturaEmbedUrl($item);
        if ( $kaltura_url ) {
            // new_tab=false => Canvas ExternalUrl launches inline in an iframe
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $kaltura_url, $parentPath, false, $item);
            return true;
        }
        if ( ! empty($item->youtube) ) {
            $url = U::youtubeWatchUrl($item->youtube);
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url, $parentPath, true, $item);
        }
        return true;
    }

    /**
     * @return list<object>
     */
    private static function itemsForModule($module) {
        if ( ! isset($module->items) || ! is_array($module->items) ) {
            return array();
        }
        $out = array();
        foreach ( $module->items as $item ) {
            $out[] = is_array($item) ? (object) $item : $item;
        }
        return $out;
    }

    private static function isAssignmentLtiKind($kind) {
        return in_array($kind, array('lti', 'quiz', 'autograder', 'peer_grade'), true);
    }

    private static function itemHasExportUrl($item, $kind) {
        if ( $kind === 'video' ) {
            return Lessons::videoUrlForItem($item) !== null
                || ( isset($item->youtube) && is_string($item->youtube) && $item->youtube !== '' );
        }
        return self::itemHref($item) !== '';
    }

    private static function processItem($item, $module, $sub_module, $zip, $cc_dom, $topic, array $options) {
        global $CFG;

        $item = is_array($item) ? (object) $item : $item;
        $type = LessonsNormalize::typeOf($item);
        $kind = LessonsNormalize::presentationKind($item);

        if ( $type === 'text' ) {
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( self::addHeadingItem($cc_dom, $sub_module, $item) ) {
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( self::addVideoItem($zip, $cc_dom, $sub_module, $item) ) {
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( LessonsNormalize::isNativeQuiz($item) ) {
            self::processNativeQuiz($item, $module, $sub_module, $zip, $cc_dom, $options);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( LessonsNormalize::isDiscussion($item) ) {
            self::processDiscussion($item, $module, $sub_module, $zip, $cc_dom, $topic);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( self::isAssignmentLtiKind($kind) || $type === LessonsNormalize::TYPE_LTI ) {
            self::processLti($item, $module, $sub_module, $zip, $cc_dom);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( $type === LessonsNormalize::TYPE_FILE ) {
            self::processFile($item, $module, $sub_module, $zip, $cc_dom, $options);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        if ( $type === LessonsNormalize::TYPE_HTML_PAGE ) {
            self::processHtmlPage($item, $module, $sub_module, $zip, $cc_dom, $options);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
            return;
        }

        $url = self::itemHref($item);
        if ( $url !== '' ) {
            $title = self::urlItemTitle($item, $module, $kind);
            $new_tab = true;
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url, null, $new_tab, $item);
        }

        self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, $options);
    }

    /**
     * Package a Lessons file item as IMS CC webcontent (bytes in the zip).
     */
    private static function processFile($item, $module, $sub_module, $zip, $cc_dom, array $options) {
        $filename = isset($item->filename) && is_string($item->filename) && $item->filename !== ''
            ? $item->filename
            : 'file.bin';
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : $filename;
        $payload = self::loadFilePayload($item, $options);
        if ( $payload === null || ! isset($payload['bytes']) || ! is_string($payload['bytes']) ) {
            throw new ExportException(
                'Lesson file could not be loaded for the cartridge (title: '.$title.').'
            );
        }
        if ( isset($payload['filename']) && is_string($payload['filename']) && $payload['filename'] !== '' ) {
            $filename = $payload['filename'];
        }
        $path = $filename;
        if ( isset($payload['path']) && is_string($payload['path']) && $payload['path'] !== '' ) {
            $path = $payload['path'];
        } else if ( isset($item->path) && is_string($item->path) && $item->path !== '' ) {
            $path = $item->path;
        }
        $sha = self::fileSha256($item);
        if ( $sha !== null && isset(self::$exportFileResources[$sha]) ) {
            $info = self::$exportFileResources[$sha];
            $info['listings']++;
            self::$exportFileResources[$sha] = $info;
            $cc_dom->zip_add_file_listing_to_module(
                $sub_module,
                $title,
                $sha,
                $info['listings'],
                $info['identifierref']
            );
            $cc_dom->add_last_item_lom($item);
            self::rememberExportFilePath($sha, $path, $info['zipPath']);
            return;
        }
        $zipPath = $cc_dom->zip_add_file_to_module($zip, $sub_module, $title, $path, $payload['bytes'], null, $sha);
        $cc_dom->add_last_item_lom($item);
        self::rememberExportFilePath($sha, $path, $zipPath);
        if ( $sha !== null ) {
            self::$exportFileResources[$sha] = array(
                'zipPath' => $zipPath,
                'identifierref' => $cc_dom->last_identifierref,
                'listings' => 1,
            );
        }
    }

    /**
     * Package a Lessons html_page as IMS CC webcontent.
     * Canvas/Sakai: wiki_content. Generic: web_resources/pages/*.html.
     * Legacy html_page items with only an href (no page identity) stay web links.
     */
    private static function processHtmlPage($item, $module, $sub_module, $zip, $cc_dom, array $options) {
        $pageId = isset($item->page_id) ? $item->page_id : 0;
        $logicalKey = isset($item->logical_key) && is_string($item->logical_key) ? trim($item->logical_key) : '';
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : ($logicalKey !== '' ? $logicalKey : 'Page');
        $hasIdentity = (is_numeric($pageId) && (int) $pageId > 0) || $logicalKey !== '';
        if ( ! $hasIdentity ) {
            $url = self::itemHref($item);
            if ( $url !== '' ) {
                $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url, null, false, $item);
            }
            return;
        }
        $payload = self::loadPagePayload($item, $options);
        if ( $payload === null ) {
            throw new ExportException(
                'Lesson page could not be loaded for the cartridge (title: '.$title.').'
            );
        }
        if ( isset($payload['title']) && is_string($payload['title']) && $payload['title'] !== '' ) {
            $title = $payload['title'];
        }
        if ( isset($payload['logical_key']) && is_string($payload['logical_key']) && $payload['logical_key'] !== '' ) {
            $logicalKey = $payload['logical_key'];
        }
        if ( isset($payload['html']) && is_string($payload['html']) && $payload['html'] !== '' ) {
            $html = $payload['html'];
        } else {
            $body = isset($payload['body']) && is_string($payload['body']) ? $payload['body'] : '';
            $html = Pages::cartridgeDocument($title, $body);
        }
        if ( $logicalKey === '' ) {
            $logicalKey = 'page';
        }
        self::$pendingWikiPages[] = array(
            'html' => $html,
            'title' => $title,
            'logical_key' => $logicalKey,
            'sub_module' => $sub_module,
            'lesson' => $item,
        );
    }

    /**
     * @param string|null $sha
     * @param string $path
     * @param string $zipPath
     */
    private static function rememberExportFilePath($sha, $path, $zipPath) {
        if ( is_string($zipPath) && $zipPath !== '' ) {
            if ( is_string($sha) && $sha !== '' ) {
                self::$exportFileZipPaths[strtolower($sha)] = $zipPath;
            }
            if ( is_string($path) && $path !== '' ) {
                self::$exportFileZipPaths['path:'.$path] = $zipPath;
                $decoded = rawurldecode(str_replace('+', ' ', $path));
                if ( $decoded !== '' && $decoded !== $path ) {
                    self::$exportFileZipPaths['path:'.$decoded] = $zipPath;
                }
            }
        }
    }

    /**
     * Canvas and Sakai (for now) package pages as wiki_content.
     * Generic packages them as ordinary web_resources files.
     *
     * @param array<string, mixed> $options
     * @return bool
     */
    private static function usesCanvasWikiPages(array $options) {
        $lms = self::exportFlavor(isset($options['tsugi_lms']) ? $options['tsugi_lms'] : '');
        return self::wantsCanvasExtensions($lms);
    }

    /**
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param array<string, mixed> $options
     */
    private static function flushPendingWikiPages($zip, $cc_dom, array $options) {
        if ( self::usesCanvasWikiPages($options) ) {
            self::flushCanvasWikiPages($zip, $cc_dom, $options);
        } else {
            self::flushGenericPages($zip, $cc_dom, $options);
        }
        self::$pendingWikiPages = array();
    }

    /**
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param array<string, mixed> $options
     */
    private static function flushCanvasWikiPages($zip, $cc_dom, array $options) {
        $seen = array();
        $wikiRefs = array();
        foreach ( self::$pendingWikiPages as $pending ) {
            $key = is_string($pending['logical_key']) ? trim($pending['logical_key']) : '';
            if ( $key !== '' && isset($wikiRefs[$key]) ) {
                $seen[$key] = isset($seen[$key]) ? $seen[$key] + 1 : 2;
                $cc_dom->zip_add_wiki_listing_to_module(
                    $pending['sub_module'],
                    $pending['title'],
                    $key,
                    $seen[$key],
                    $wikiRefs[$key]
                );
                $cc_dom->add_last_item_lom($pending['lesson'] ?? null);
                continue;
            }
            $html = self::rewriteCartridgeFileLinks($pending['html'], $options, true);
            $cc_dom->zip_add_wiki_page_to_module(
                $zip,
                $pending['sub_module'],
                $pending['title'],
                $pending['logical_key'],
                $html
            );
            $cc_dom->add_last_item_lom($pending['lesson'] ?? null);
            if ( $key !== '' ) {
                $seen[$key] = 1;
                $wikiRefs[$key] = $cc_dom->last_identifierref;
            }
        }
    }

    /**
     * Generic CC: pages are HTML files under web_resources/pages/.
     *
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param array<string, mixed> $options
     */
    private static function flushGenericPages($zip, $cc_dom, array $options) {
        $pageZipPaths = array();
        foreach ( self::$pendingWikiPages as $pending ) {
            $key = self::pendingPageKey($pending);
            if ( ! isset($pageZipPaths[$key]) ) {
                $pageZipPaths[$key] = $cc_dom->reserveWebResourcePath('pages/'.$key.'.html');
            }
        }
        $seen = array();
        $pageRefs = array();
        foreach ( self::$pendingWikiPages as $pending ) {
            $key = self::pendingPageKey($pending);
            if ( isset($pageRefs[$key]) ) {
                $seen[$key] = isset($seen[$key]) ? $seen[$key] + 1 : 2;
                $cc_dom->zip_add_file_listing_to_module(
                    $pending['sub_module'],
                    $pending['title'],
                    $key,
                    $seen[$key],
                    $pageRefs[$key]
                );
                $cc_dom->add_last_item_lom($pending['lesson'] ?? null);
                continue;
            }
            $html = self::rewriteCartridgeFileLinks($pending['html'], $options, false, $pageZipPaths);
            $cc_dom->zip_add_file_at_path_to_module(
                $zip,
                $pending['sub_module'],
                $pending['title'],
                $pageZipPaths[$key],
                $html,
                $key
            );
            $cc_dom->add_last_item_lom($pending['lesson'] ?? null);
            $seen[$key] = 1;
            $pageRefs[$key] = $cc_dom->last_identifierref;
        }
    }

    /**
     * @param array{logical_key?:string} $pending
     * @return string
     */
    private static function pendingPageKey(array $pending) {
        $key = isset($pending['logical_key']) && is_string($pending['logical_key'])
            ? trim($pending['logical_key']) : '';
        return $key !== '' ? $key : 'page';
    }

    /**
     * Point page HTML links at $IMS-CC-FILEBASE$ (and Canvas $WIKI_REFERENCE$ when needed).
     *
     * @param string $html
     * @param array<string, mixed> $options
     * @param bool $canvasWiki
     * @param array<string, string> $pageZipPaths logical_key => zip path
     * @return string
     */
    private static function rewriteCartridgeFileLinks($html, array $options, $canvasWiki, array $pageZipPaths = array()) {
        if ( ! is_string($html) || $html === '' ) {
            return $html;
        }
        $context_id = isset($options['context_id']) ? (int) $options['context_id'] : 0;
        return (string) preg_replace_callback(
            '/\b(href|src)\s*=\s*(["\'])([^"\']+)\2/i',
            function ($m) use ($context_id, $canvasWiki, $pageZipPaths) {
                $url = html_entity_decode($m[3], ENT_QUOTES, 'UTF-8');
                $next = $canvasWiki
                    ? self::cartridgeWikiHref($url)
                    : self::genericPageHref($url, $pageZipPaths);
                if ( $next === $url ) {
                    $next = self::cartridgeFileHref($url, $context_id, $canvasWiki);
                }
                if ( $next === $url ) {
                    return $m[0];
                }
                return $m[1].'='.$m[2].htmlspecialchars($next, ENT_QUOTES, 'UTF-8').$m[2];
            },
            $html
        );
    }

    /**
     * Canvas wiki import resolves $WIKI_REFERENCE$/pages/{migration_id}.
     * Tsugi stores page links as $IMS-CC-FILEBASE$pages/{logical_key}; leaving
     * that in wiki HTML makes Canvas treat it as a missing course file.
     *
     * @param string $url
     * @return string
     */
    private static function cartridgeWikiHref($url) {
        if ( str_starts_with($url, '$WIKI_REFERENCE$')
            || str_starts_with($url, '$CANVAS_OBJECT_REFERENCE$')
            || str_starts_with($url, '$CANVAS_COURSE_REFERENCE$') ) {
            return $url;
        }
        $key = self::coursePageKeyFromHref($url);
        if ( $key === null ) {
            return $url;
        }
        return '$WIKI_REFERENCE$/pages/'.CCIdentifier::wikiMigrationId($key);
    }

    /**
     * Generic CC has no Pages tool: page links are FILEBASE to web_resources/pages/{key}.html.
     *
     * @param string $url
     * @param array<string, string> $pageZipPaths
     * @return string
     */
    private static function genericPageHref($url, array $pageZipPaths) {
        $key = self::coursePageKeyFromHref($url);
        if ( $key === null || ! isset($pageZipPaths[$key]) ) {
            return $url;
        }
        return self::fileBaseHref($pageZipPaths[$key], false);
    }

    /**
     * Page logical_key from a stored href (FILEBASE, /pages/…, or a live course URL).
     *
     * @param string $url
     * @return string|null
     */
    private static function coursePageKeyFromHref($url) {
        $remainder = $url;
        foreach ( CCFileBase::TOKEN_ALIASES as $token ) {
            if ( str_starts_with($remainder, $token) ) {
                $remainder = substr($remainder, strlen($token));
                break;
            }
        }
        if ( preg_match('#^https?://#i', $remainder) ) {
            $parts = parse_url($remainder);
            $remainder = isset($parts['path']) && is_string($parts['path']) ? $parts['path'] : '';
        }
        $remainder = ltrim($remainder, '/');
        if ( preg_match('#^courses/\d+/(.*)$#', $remainder, $m) ) {
            $remainder = $m[1];
        }
        $q = strpos($remainder, '?');
        if ( $q !== false ) {
            $remainder = substr($remainder, 0, $q);
        }
        if ( ! str_starts_with($remainder, 'pages/') ) {
            return null;
        }
        $key = rawurldecode(str_replace('+', ' ', substr($remainder, strlen('pages/'))));
        $key = trim($key, '/');
        if ( $key === '' ) {
            return null;
        }
        $slash = strpos($key, '/');
        if ( $slash !== false ) {
            $key = substr($key, 0, $slash);
        }
        return $key !== '' ? $key : null;
    }

    /**
     * @param string $url
     * @param int $context_id
     * @param bool $canvasWiki Canvas FILEBASE strips web_resources/; generic keeps it
     * @return string
     */
    private static function cartridgeFileHref($url, $context_id, $canvasWiki=true) {
        $sha = Files::sha256FromDownloadHref($url);
        if ( $sha && isset(self::$exportFileZipPaths[$sha]) ) {
            return self::fileBaseHref(self::$exportFileZipPaths[$sha], $canvasWiki);
        }
        $path = $sha ? Files::pathForSha256($sha, $context_id) : self::courseFilePathFromHref($url);
        if ( ! is_string($path) || $path === '' ) {
            return $url;
        }
        $zipPath = self::lookupExportZipPath($path);
        if ( $zipPath !== null ) {
            return self::fileBaseHref($zipPath, $canvasWiki);
        }
        return self::fileBaseHref('web_resources/'.$path, $canvasWiki);
    }

    /**
     * Course folder/name from a stored page href (FILEBASE, /files/…, or a live course URL).
     *
     * @param string $url
     * @return string|null
     */
    private static function courseFilePathFromHref($url) {
        $remainder = $url;
        foreach ( CCFileBase::TOKEN_ALIASES as $token ) {
            if ( str_starts_with($remainder, $token) ) {
                $remainder = substr($remainder, strlen($token));
                break;
            }
        }
        if ( preg_match('#^https?://#i', $remainder) ) {
            $parts = parse_url($remainder);
            $remainder = isset($parts['path']) && is_string($parts['path']) ? $parts['path'] : '';
        }
        $remainder = ltrim($remainder, '/');
        if ( preg_match('#^courses/\d+/(.*)$#', $remainder, $m) ) {
            $remainder = $m[1];
        }
        $q = strpos($remainder, '?');
        if ( $q !== false ) {
            $remainder = substr($remainder, 0, $q);
        }
        if ( str_starts_with($remainder, 'web_resources/') ) {
            $remainder = substr($remainder, strlen('web_resources/'));
        } else if ( str_starts_with($remainder, 'files/') && ! str_starts_with($remainder, 'files/download/') ) {
            $remainder = substr($remainder, strlen('files/'));
        } else {
            return null;
        }
        $remainder = rawurldecode(str_replace('+', ' ', $remainder));
        $path = Files::normalizeFilePath($remainder);
        return $path;
    }

    /**
     * @param string $path
     * @return string|null
     */
    private static function lookupExportZipPath($path) {
        $candidates = array($path, rawurldecode($path));
        $slash = strrpos($path, '/');
        if ( $slash !== false ) {
            $candidates[] = substr($path, $slash + 1);
        }
        foreach ( $candidates as $candidate ) {
            $candidate = Files::normalizeFilePath($candidate);
            if ( $candidate && isset(self::$exportFileZipPaths['path:'.$candidate]) ) {
                return self::$exportFileZipPaths['path:'.$candidate];
            }
        }
        return null;
    }

    /**
     * Canvas rewrites $IMS-CC-FILEBASE$/{path} against files imported from
     * web_resources/. The token must be followed by a slash, and the path must
     * match the zip path with web_resources/ stripped (spaces/commas intact).
     * Percent-encoding that path makes Canvas report missing wiki links.
     *
     * @param string $zipPath
     * @param bool $stripWebResources Canvas maps FILEBASE onto files imported from web_resources/
     * @return string
     */
    private static function fileBaseHref($zipPath, $stripWebResources) {
        $rel = ltrim(str_replace('\\', '/', (string) $zipPath), '/');
        if ( $stripWebResources && str_starts_with($rel, 'web_resources/') ) {
            $rel = substr($rel, strlen('web_resources/'));
        }
        $rel = ltrim($rel, '/');
        if ( $rel === '' ) {
            return CCFileBase::TOKEN.'/';
        }
        return CCFileBase::TOKEN.'/'.$rel;
    }

    /**
     * @param array{context_id?:int,load_page?:callable} $options
     * @return array{title?:string,logical_key?:string,body?:string,html?:string}|null
     */
    private static function loadPagePayload($item, array $options) {
        if ( isset($options['load_page']) && is_callable($options['load_page']) ) {
            $loaded = call_user_func($options['load_page'], $item);
            return is_array($loaded) ? $loaded : null;
        }
        $pageId = isset($item->page_id) ? $item->page_id : 0;
        $logicalKey = isset($item->logical_key) && is_string($item->logical_key) ? $item->logical_key : '';
        $context_id = isset($options['context_id']) ? (int) $options['context_id'] : U::currentContextId();
        return Pages::readExportPayload($pageId, $logicalKey, $context_id);
    }

    /**
     * @param array{context_id?:int,load_file?:callable} $options
     * @return array{bytes:string,filename?:string,content_type?:string}|null
     */
    private static function loadFilePayload($item, array $options) {
        if ( isset($options['load_file']) && is_callable($options['load_file']) ) {
            $loaded = call_user_func($options['load_file'], $item);
            return is_array($loaded) ? $loaded : null;
        }
        $sha = self::fileSha256($item);
        if ( $sha === null ) {
            return null;
        }
        $context_id = isset($options['context_id']) ? (int) $options['context_id'] : U::currentContextId();
        return Files::readExportPayload($sha, $context_id);
    }

    /**
     * @return string|null
     */
    private static function fileSha256($item) {
        if ( isset($item->sha256) && is_string($item->sha256) && Files::isSha256($item->sha256) ) {
            return strtolower($item->sha256);
        }
        $href = isset($item->href) && is_string($item->href) ? $item->href : '';
        return Files::sha256FromDownloadHref($href);
    }

    private static function processChildren($item, $module, $sub_module, $zip, $cc_dom, $topic, array $options) {
        if ( ! isset($item->items) || ! is_array($item->items) ) {
            return;
        }
        foreach ( $item->items as $child ) {
            self::processItem($child, $module, $sub_module, $zip, $cc_dom, $topic, $options);
        }
    }

    /**
     * Export a native Quiz1 item as IMS CC QTI 1.2.1. Only quizzes referenced
     * in lessons are included. Missing or invalid quizzes fail the export.
     */
    private static function processNativeQuiz($item, $module, $sub_module, $zip, $cc_dom, array $options) {
        $quiz_id = LessonsNormalize::quizIdOf($item);
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : (isset($module->title) ? $module->title : __('Quiz'));
        if ( $quiz_id < 1 ) {
            throw new ExportException(
                'A lesson quiz is missing quiz_id (title: '.$title.'). Pick a Quiz1 quiz in Lessons authoring.'
            );
        }
        $quiz = self::loadQuiz($quiz_id, $options);
        if ( $quiz === null ) {
            throw new ExportException(
                'Lesson references quiz_id '.$quiz_id.' ('.$title.') which was not found in this course.'
            );
        }
        if ( is_string($quiz->title) && $quiz->title !== '' && ( ! isset($item->title) || $item->title === '' ) ) {
            $title = $quiz->title;
        }
        $file = $cc_dom->add_qti_assessment($sub_module, $title, $quiz_id);
        $export_opts = array(
            'schema_location' => $cc_dom->qtiSchemaLocation(),
        );
        if ( $cc_dom->canvas_quiz_wrapper ) {
            $export_opts['pattern_match_as_fib'] = true;
            $export_opts['canvas_item_metadata'] = true;
            $export_opts['assessment_ident'] = $cc_dom->last_identifier;
        }
        $xml = Qti12Exporter::export($quiz, $export_opts);
        $cc_dom->zip_finish_qti_assessment($zip, $file, $title, $xml, $quiz);
        $cc_dom->add_last_item_lom($item);
    }

    /**
     * @param array{context_id?:int,load_quiz?:callable} $options
     * @return \Tsugi\Services\Quiz1\Quiz|null
     */
    private static function loadQuiz($quiz_id, array $options) {
        if ( isset($options['load_quiz']) && is_callable($options['load_quiz']) ) {
            return call_user_func($options['load_quiz'], $quiz_id);
        }
        $context_id = isset($options['context_id']) ? (int) $options['context_id'] : U::currentContextId();
        if ( $context_id < 1 ) {
            return null;
        }
        return QuizRepository::load($quiz_id, $context_id);
    }

    private static function processDiscussion($item, $module, $sub_module, $zip, $cc_dom, $topic) {
        global $CFG;
        $topic = self::exportTopicMode($topic);
        if ( $topic === 'none' ) {
            return;
        }
        $title = isset($item->title) && $item->title !== '' ? $item->title : $module->title;
        $text = isset($item->description) ? $item->description : (isset($module->description) ? $module->description : '');

        if ( $topic === 'lms' ) {
            $cc_dom->zip_add_topic_to_module($zip, $sub_module, $title, $text);
            $cc_dom->add_last_item_lom($item);
            return;
        }

        $title = __('Discussion:').' '.$title;
        $endpoint = U::absolute_url(LessonsNormalize::launchUrlForItem($item));
        if ( isset($item->resource_link_id) && $item->resource_link_id !== '' && $item->resource_link_id !== null ) {
            $endpoint = U::add_url_parm($endpoint, 'inherit', $item->resource_link_id);
        }
        $extensions = array('apphome' => $CFG->apphome);
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : null;
        $custom_arr = self::customArray($item);
        if ( $topic === 'lti_grade' ) {
            $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id);
        } else {
            $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id);
        }
        $cc_dom->add_last_item_lom($item);
    }

    private static function processLti($item, $module, $sub_module, $zip, $cc_dom) {
        global $CFG;
        $title = isset($item->title) && $item->title !== '' ? $item->title : $module->title;
        if ( strpos($title, ':') === false ) {
            $title = 'Tool: '.$title;
        }
        $endpoint = LessonsNormalize::launchUrlForItem($item);
        $endpoint = U::absolute_url(Lessons::expandLink($endpoint));
        if ( isset($item->resource_link_id) && $item->resource_link_id !== '' && $item->resource_link_id !== null ) {
            $endpoint = U::add_url_parm($endpoint, 'inherit', $item->resource_link_id);
        }
        $extensions = array('apphome' => $CFG->apphome);
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : null;
        $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, self::customArray($item), $extensions, $resource_link_id);
        $cc_dom->add_last_item_lom($item);
    }

    private static function urlItemTitle($item, $module, $kind) {
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : $module->title;
        if ( $kind === 'slide' ) {
            return 'Slides: '.$title;
        }
        if ( $kind === 'assignment' ) {
            return 'Assignment: '.$title;
        }
        if ( $kind === 'solution' ) {
            return 'Solution: '.$title;
        }
        return $title;
    }

    private static function itemHref($item) {
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        if ( ! is_string($href) || $href === '' ) {
            return '';
        }
        return U::absolute_url(Lessons::expandLink($href));
    }

    /**
     * @return array<string, string>
     */
    private static function customArray($item) {
        $custom_arr = array();
        if ( ! isset($item->custom) ) {
            return $custom_arr;
        }
        foreach ( $item->custom as $custom ) {
            if ( isset($custom->value) ) {
                $custom_arr[$custom->key] = $custom->value;
            }
            if ( isset($custom->json) ) {
                $custom_arr[$custom->key] = json_encode($custom->json);
            }
        }
        return $custom_arr;
    }
}
