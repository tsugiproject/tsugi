<?php

namespace Tsugi\Services\Lessons;

use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\GiftImporter;
use Tsugi\Services\Quiz1\ImportException;
use Tsugi\Services\Quiz1\Qti12Exporter;
use Tsugi\Services\Quiz1\Quiz1;
use Tsugi\Util\CC;
use Tsugi\Util\U;

/**
 * Legacy /cc/export helper: optionally turn GIFT LTI launches into QTI assessments.
 *
 * Used only by cc/export.php (file-based lessons.json). Settings / LessonsCartridge
 * native Quiz1 export is intentionally separate.
 *
 * Default is convert found GIFT quizzes to QTI. Keep-as-LTI is opt-out.
 * Conversion reads $CFG->giftquizzes and ignores .lock / other gift-tool access checks.
 */
class LessonsLegacyGift {

    /**
     * When false, gift LTI launches stay LTI even if GIFT text is on disk.
     *
     * @var bool
     */
    private $convertToQti = true;

    /**
     * Override for tests. Null means use $CFG->giftquizzes.
     *
     * @var string|null
     */
    private $giftquizzes;

    /**
     * @var list<string>
     */
    private $warnings = array();

    /**
     * @param bool $convertToQti Default true: convert found GIFT to QTI
     * @param string|null $giftquizzes
     */
    public function __construct($convertToQti = true, $giftquizzes = null) {
        $this->convertToQti = (bool) $convertToQti;
        $this->giftquizzes = is_string($giftquizzes) && $giftquizzes !== '' ? $giftquizzes : null;
    }

    /**
     * Convert found GIFT to QTI unless the caller explicitly keeps LTI.
     *
     * @param mixed $raw Query value, usually "qti" or "lti"
     * @return bool
     */
    public static function wantsGiftQti($raw) {
        if ( $raw === false || $raw === 0 ) {
            return false;
        }
        if ( ! is_string($raw) ) {
            return true;
        }
        $raw = strtolower(trim($raw));
        return $raw !== 'lti' && $raw !== 'no' && $raw !== '0';
    }

    /**
     * True when a launch URL is the old mod/gift tool or the in-tree tool/gift tool.
     *
     * @param mixed $launch
     * @return bool
     */
    public static function isGiftLaunch($launch) {
        if ( ! is_string($launch) || $launch === '' ) {
            return false;
        }
        $path = parse_url($launch, PHP_URL_PATH);
        if ( ! is_string($path) || $path === '' ) {
            $qpos = strpos($launch, '?');
            $path = $qpos === false ? $launch : substr($launch, 0, $qpos);
        }
        $path = strtolower(str_replace('\\', '/', $path));
        return (bool) preg_match('#(?:^|/)(?:mod|tool)/gift(?:/|$|\.php)#', $path);
    }

    /**
     * Quiz filename from launch ?quiz= or a custom quiz parameter.
     *
     * @param object|array $item
     * @return string|null
     */
    public static function quizNameFromItem($item) {
        $item = is_array($item) ? (object) $item : $item;
        if ( ! is_object($item) ) {
            return null;
        }
        $from_launch = self::quizNameFromLaunch(isset($item->launch) ? $item->launch : '');
        if ( $from_launch !== null ) {
            return $from_launch;
        }
        $custom = self::customMap($item);
        if ( isset($custom['quiz']) ) {
            return self::cleanQuizName($custom['quiz']);
        }
        return null;
    }

    /**
     * @param mixed $launch
     * @return string|null
     */
    public static function quizNameFromLaunch($launch) {
        if ( ! is_string($launch) || $launch === '' ) {
            return null;
        }
        $query = parse_url($launch, PHP_URL_QUERY);
        if ( ! is_string($query) || $query === '' ) {
            return null;
        }
        $params = array();
        parse_str($query, $params);
        if ( ! isset($params['quiz']) ) {
            return null;
        }
        return self::cleanQuizName($params['quiz']);
    }

    /**
     * Filenames in $CFG->giftquizzes (or $dir). Skips directories, dotfiles, and .lock.
     * Does not honor gift-tool lock/password.
     *
     * @param string|null $dir
     * @return list<string>
     */
    public static function listQuizFiles($dir = null) {
        $dir = self::giftquizzesDir($dir);
        if ( $dir === null ) {
            return array();
        }
        $names = @scandir($dir);
        if ( ! is_array($names) ) {
            return array();
        }
        $files = array();
        foreach ( $names as $file ) {
            if ( $file === '.' || $file === '..' || $file === '.lock' ) {
                continue;
            }
            if ( strpos($file, '.') === 0 ) {
                continue;
            }
            if ( is_dir($dir.'/'.$file) ) {
                continue;
            }
            $files[] = $file;
        }
        sort($files);
        return $files;
    }

    /**
     * Real path of a quiz file under giftquizzes, or null. No lock check.
     *
     * @param string $name
     * @param string|null $dir
     * @return string|null
     */
    public static function resolveGiftFile($name, $dir = null) {
        $name = self::cleanQuizName($name);
        if ( $name === null ) {
            return null;
        }
        $dir = self::giftquizzesDir($dir);
        if ( $dir === null ) {
            return null;
        }
        $files = self::listQuizFiles($dir);
        if ( ! in_array($name, $files, true) ) {
            return null;
        }
        $candidate = $dir.'/'.$name;
        $dirReal = realpath($dir);
        $fileReal = realpath($candidate);
        if ( $dirReal === false || $fileReal === false ) {
            return null;
        }
        if ( ! is_file($fileReal) || ! is_readable($fileReal) ) {
            return null;
        }
        $prefix = rtrim($dirReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if ( ! str_starts_with($fileReal, $prefix) ) {
            return null;
        }
        return $fileReal;
    }

    /**
     * GIFT text for a quiz name, or null.
     *
     * @param string $name
     * @param string|null $dir
     * @return string|null
     */
    public static function readGiftText($name, $dir = null) {
        $path = self::resolveGiftFile($name, $dir);
        if ( $path === null ) {
            return null;
        }
        $text = @file_get_contents($path);
        return is_string($text) && $text !== '' ? $text : null;
    }

    /**
     * Count LTI items that look like GIFT quizzes and which have convertible GIFT on disk.
     *
     * @param object $l Lessons
     * @param array|false $anchors
     * @param string|null $giftquizzes
     * @return array{scanned:int,gift:int,found:int,lti:int,paths:list<string>,warnings:list<string>,by_module:array<string,array{scanned:int,gift:int,found:int,lti:int}>}
     */
    public static function summarize($l, $anchors = false, $giftquizzes = null) {
        $gift = 0;
        $found = 0;
        $scanned = 0;
        $paths = array();
        $warnings = array();
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
            $mod_gift = 0;
            $mod_found = 0;
            foreach ( self::ltiItemsForModule($module) as $item ) {
                $scanned++;
                $mod_scanned++;
                if ( ! self::isGiftLaunch(isset($item->launch) ? $item->launch : '') ) {
                    continue;
                }
                $gift++;
                $mod_gift++;
                $converted = self::tryConvertItem($item, self::itemTitle($item, $module), $giftquizzes, $warnings);
                if ( $converted === null ) {
                    continue;
                }
                $found++;
                $mod_found++;
                $name = self::quizNameFromItem($item);
                if ( is_string($name) && ! in_array($name, $paths, true) ) {
                    $paths[] = $name;
                }
            }
            if ( $anchor !== '' ) {
                $by_module[$anchor] = array(
                    'scanned' => $mod_scanned,
                    'gift' => $mod_gift,
                    'found' => $mod_found,
                    'lti' => $mod_scanned - $mod_found,
                );
            }
        }
        sort($paths);
        return array(
            'scanned' => $scanned,
            'gift' => $gift,
            'found' => $found,
            'lti' => $scanned - $found,
            'paths' => $paths,
            'warnings' => $warnings,
            'by_module' => $by_module,
        );
    }

    /**
     * @return list<string>
     */
    public function warnings() {
        return $this->warnings;
    }

    /**
     * Add an LTI lesson item as QTI when conversion is on and GIFT is on disk;
     * otherwise keep it as an LTI outcome launch.
     *
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param \DOMNode $module_node
     * @param object $item
     * @param object $module
     * @param string|null $parentPath
     * @return string 'qti' or 'lti'
     */
    public function addToModule($zip, $cc_dom, $module_node, $item, $module, $parentPath = null) {
        global $CFG;
        $item = is_array($item) ? (object) $item : $item;
        $title = self::itemTitle($item, $module);
        if ( $this->convertToQti ) {
            $payload = self::tryConvertItem($item, $title, $this->giftquizzes, $this->warnings);
            if ( $payload !== null ) {
                $this->writeQti($zip, $cc_dom, $module_node, $title, $payload, $parentPath);
                return 'qti';
            }
        }
        $custom_arr = self::customMap($item);
        $launch = isset($item->launch) ? $item->launch : '';
        $endpoint = U::absolute_url(LessonsService::expandLink($launch));
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : null;
        if ( $resource_link_id ) {
            $endpoint = U::add_url_parm($endpoint, 'inherit', $resource_link_id);
        }
        $extensions = array('apphome' => isset($CFG->apphome) ? $CFG->apphome : '');
        $cc_dom->zip_add_lti_outcome_to_module($zip, $module_node, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parentPath);
        return 'lti';
    }

    /**
     * @param string|null $dir
     * @return string|null
     */
    public static function giftquizzesDir($dir = null) {
        global $CFG;
        if ( is_string($dir) && $dir !== '' ) {
            $root = $dir;
        } else if ( isset($CFG->giftquizzes) && is_string($CFG->giftquizzes) && $CFG->giftquizzes !== '' ) {
            $root = $CFG->giftquizzes;
        } else {
            return null;
        }
        $root = rtrim($root, '/\\');
        if ( $root === '' || ! is_dir($root) ) {
            return null;
        }
        return $root;
    }

    /**
     * @return array{scanned:int,gift:int,found:int,lti:int,paths:list<string>,warnings:list<string>,by_module:array<string,array{scanned:int,gift:int,found:int,lti:int}>}
     */
    private static function emptySummary() {
        return array(
            'scanned' => 0,
            'gift' => 0,
            'found' => 0,
            'lti' => 0,
            'paths' => array(),
            'warnings' => array(),
            'by_module' => array(),
        );
    }

    /**
     * @param object $module
     * @return list<object>
     */
    private static function ltiItemsForModule($module) {
        $items = array();
        if ( isset($module->items) && is_array($module->items) && count($module->items) > 0 ) {
            foreach ( $module->items as $item ) {
                $item = is_array($item) ? (object) $item : $item;
                $type = isset($item->type) ? $item->type : '';
                if ( $type !== 'lti' ) {
                    continue;
                }
                $items[] = $item;
            }
            return $items;
        }
        if ( isset($module->lti) && is_array($module->lti) ) {
            foreach ( $module->lti as $lti ) {
                $items[] = is_array($lti) ? (object) $lti : $lti;
            }
        }
        return $items;
    }

    /**
     * @param object $item
     * @param object $module
     * @return string
     */
    private static function itemTitle($item, $module) {
        $title = isset($item->title) ? $item->title : (isset($module->title) ? $module->title : 'Quiz');
        $title = is_string($title) ? $title : 'Quiz';
        if ( strpos($title, ':') === false ) {
            $title = 'Tool: '.$title;
        }
        return $title;
    }

    /**
     * @param object $item
     * @return array<string, string>
     */
    private static function customMap($item) {
        $out = array();
        if ( ! isset($item->custom) ) {
            return $out;
        }
        $custom = $item->custom;
        if ( is_object($custom) ) {
            $custom = (array) $custom;
        }
        if ( ! is_array($custom) ) {
            return $out;
        }
        foreach ( $custom as $key => $row ) {
            if ( is_string($key) && $key !== '' && ( is_string($row) || is_numeric($row) ) ) {
                $out[$key] = (string) $row;
                continue;
            }
            $row = is_array($row) ? (object) $row : $row;
            if ( ! is_object($row) || ! isset($row->key) || ! is_string($row->key) || $row->key === '' ) {
                continue;
            }
            if ( isset($row->value) && ( is_string($row->value) || is_numeric($row->value) ) ) {
                $out[$row->key] = (string) $row->value;
            } else if ( isset($row->json) ) {
                $out[$row->key] = json_encode($row->json);
            }
        }
        return $out;
    }

    /**
     * @param mixed $name
     * @return string|null
     */
    private static function cleanQuizName($name) {
        if ( ! is_string($name) && ! is_numeric($name) ) {
            return null;
        }
        $name = trim((string) $name);
        if ( $name === '' || str_contains($name, "\0") ) {
            return null;
        }
        $name = str_replace('\\', '/', $name);
        if ( str_contains($name, '/') || $name === '.' || $name === '..' || $name === '.lock' ) {
            return null;
        }
        if ( strpos($name, '.') === 0 ) {
            return null;
        }
        return $name;
    }

    /**
     * @param object $item
     * @param string $title
     * @param string|null $giftquizzes
     * @param list<string> $warnings
     * @return array{quiz:Quiz,xml:string,warnings:list<string>}|null
     */
    private static function tryConvertItem($item, $title, $giftquizzes, array &$warnings) {
        $launch = isset($item->launch) ? $item->launch : '';
        if ( ! self::isGiftLaunch($launch) ) {
            return null;
        }
        $name = self::quizNameFromItem($item);
        if ( $name === null ) {
            return null;
        }
        $text = self::readGiftText($name, $giftquizzes);
        if ( $text === null ) {
            return null;
        }
        try {
            list($quiz, $import_warnings) = GiftImporter::import($text);
        } catch ( ImportException $e ) {
            $warnings[] = $name.': '.$e->getMessage();
            return null;
        }
        foreach ( $import_warnings as $w ) {
            $warnings[] = $name.': '.$w;
        }
        $quiz->title = $title;
        self::assignExportIds($quiz, $name.(isset($item->resource_link_id) ? "\n".$item->resource_link_id : ''));
        try {
            Qti12Exporter::export($quiz);
        } catch ( ExportException $e ) {
            $warnings[] = $name.': '.$e->getMessage();
            return null;
        }
        return array(
            'quiz' => $quiz,
            'warnings' => $import_warnings,
            'name' => $name,
        );
    }

    /**
     * @param array{quiz:Quiz,name?:string} $payload
     */
    private function writeQti($zip, $cc_dom, $module_node, $title, array $payload, $parentPath) {
        $quiz = $payload['quiz'];
        $file = $cc_dom->add_qti_assessment($module_node, $title, $quiz->id, $parentPath);
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
    }

    /**
     * Qti12Exporter requires positive integer ids. File-based GIFT has none.
     */
    private static function assignExportIds(Quiz1 $quiz, $stableKey) {
        $crc = (int) sprintf('%u', crc32((string) $stableKey));
        $quiz_id = ($crc % 900000) + 100000;
        if ( $quiz_id < 1 ) {
            $quiz_id = 1;
        }
        $quiz->id = $quiz_id;
        $qseq = 1;
        foreach ( $quiz->questions as $question ) {
            $question->id = $quiz_id * 1000 + $qseq;
            $question->quiz_id = $quiz_id;
            $aseq = 1;
            foreach ( $question->answers as $answer ) {
                $answer->id = $question->id * 100 + $aseq;
                $aseq++;
            }
            $qseq++;
        }
    }
}
