<?php

namespace Tsugi\UI;

use Tsugi\Util\CC;
use Tsugi\Util\U;

/**
 * Common Cartridge export from an in-memory Lessons document (v2 items).
 *
 * Used by Setup export. Does not rewrite stored lessons JSON.
 */
class LessonsCartridge {

    /**
     * Counts for the export form.
     *
     * @return array{modules:int,resources:int,assignments:int,discussions:int}
     */
    public static function summarize($l) {
        $modules = 0;
        $resources = 0;
        $assignments = 0;
        $discussions = 0;
        if ( ! isset($l->lessons->modules) || ! is_array($l->lessons->modules) ) {
            return array(
                'modules' => 0,
                'resources' => 0,
                'assignments' => 0,
                'discussions' => 0,
            );
        }
        foreach ( $l->lessons->modules as $module ) {
            $modules++;
            $c = self::moduleCounts($module);
            $resources += $c['resources'];
            $assignments += $c['assignments'];
            $discussions += $c['discussions'];
        }
        return array(
            'modules' => $modules,
            'resources' => $resources,
            'assignments' => $assignments,
            'discussions' => $discussions,
        );
    }

    /**
     * @return array{resources:int,assignments:int,discussions:int}
     */
    public static function moduleCounts($module) {
        $resources = 0;
        $assignments = 0;
        $discussions = 0;
        foreach ( self::itemsForModule($module) as $item ) {
            $kind = LessonsNormalize::presentationKind($item);
            if ( $kind === 'header' || LessonsNormalize::isHeading($item) ) {
                continue;
            }
            if ( LessonsNormalize::isDiscussion($item) ) {
                $discussions++;
                continue;
            }
            if ( self::isAssignmentLtiKind($kind) ) {
                $assignments++;
                continue;
            }
            if ( self::itemHasExportUrl($item, $kind) ) {
                $resources++;
            }
        }
        return array(
            'resources' => $resources,
            'assignments' => $assignments,
            'discussions' => $discussions,
        );
    }

    /**
     * Write imsmanifest.xml and resource files into an open ZipArchive.
     *
     * @param object $l Lessons
     * @param \ZipArchive $zip
     * @param array{tsugi_lms?:string,topic?:string,youtube?:string|false,anchors?:array|false} $options
     */
    public static function writeZip($l, $zip, array $options = array()) {
        global $CFG;

        $tsugi_lms = isset($options['tsugi_lms']) ? $options['tsugi_lms'] : '';
        $topic = isset($options['topic']) ? $options['topic'] : false;
        $youtube = isset($options['youtube']) ? $options['youtube'] : false;
        if ( $youtube === 'no' ) {
            $youtube = false;
        }
        $anchors = isset($options['anchors']) ? $options['anchors'] : false;

        $title = isset($l->lessons->title) ? $l->lessons->title : '';
        if ( ! is_string($title) || trim($title) === '' ) {
            $title = isset($CFG->context_title) ? $CFG->context_title : 'Course';
        }

        $cc_dom = new CC();
        $cc_dom->set_title($title.' import');
        $top_module = false;
        if ( $tsugi_lms === 'sakai' ) {
            $top_module = $cc_dom->add_module('Modules (import)', '');
        }

        foreach ( $l->lessons->modules as $module ) {
            if ( is_array($anchors) && count($anchors) > 0 && ! in_array($module->anchor, $anchors) ) {
                continue;
            }
            if ( $top_module ) {
                $sub_module = $cc_dom->add_sub_module($top_module, $module->title, 'Modules (import)');
            } else {
                $sub_module = $cc_dom->add_module($module->title, '');
            }
            foreach ( self::itemsForModule($module) as $item ) {
                self::processItem($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            }
        }

        $cc_dom->zip_add_canvas_module_meta($zip);
        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
    }

    /**
     * Download basename for the .imscc file.
     */
    public static function downloadName($l) {
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
        return $slug.'_export.imscc';
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

    private static function processItem($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic) {
        global $CFG;

        $item = is_array($item) ? (object) $item : $item;
        $type = LessonsNormalize::typeOf($item);
        $kind = LessonsNormalize::presentationKind($item);

        if ( $type === 'text' ) {
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            return;
        }

        if ( LessonsNormalize::isHeading($item) ) {
            $header_text = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : '');
            if ( is_string($header_text) && $header_text !== '' ) {
                $cc_dom->add_header_item($sub_module, $header_text);
            }
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            return;
        }

        if ( $kind === 'video' ) {
            self::processVideo($item, $sub_module, $zip, $cc_dom, $youtube);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            return;
        }

        if ( LessonsNormalize::isDiscussion($item) ) {
            self::processDiscussion($item, $module, $sub_module, $zip, $cc_dom, $topic);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            return;
        }

        if ( self::isAssignmentLtiKind($kind) || $type === LessonsNormalize::TYPE_LTI ) {
            self::processLti($item, $module, $sub_module, $zip, $cc_dom);
            self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
            return;
        }

        $url = self::itemHref($item);
        if ( $url !== '' ) {
            $title = self::urlItemTitle($item, $module, $kind);
            $new_tab = true;
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url, null, $new_tab);
        }

        self::processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
    }

    private static function processChildren($item, $module, $sub_module, $zip, $cc_dom, $youtube, $topic) {
        if ( ! isset($item->items) || ! is_array($item->items) ) {
            return;
        }
        foreach ( $item->items as $child ) {
            self::processItem($child, $module, $sub_module, $zip, $cc_dom, $youtube, $topic);
        }
    }

    private static function processVideo($item, $sub_module, $zip, $cc_dom, $youtube) {
        global $CFG;
        $title = __('Video:').' '.(isset($item->title) ? $item->title : '');
        $kaltura_url = Lessons::kalturaEmbedUrl($item);
        if ( $kaltura_url ) {
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $kaltura_url, null, false);
            return;
        }
        if ( $youtube && isset($CFG->youtube_url) && ! empty($item->youtube) ) {
            $endpoint = U::absolute_url($CFG->youtube_url);
            $endpoint = U::add_url_parm($endpoint, 'v', $item->youtube);
            $extensions = array('apphome' => $CFG->apphome);
            $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : null;
            if ( $youtube === 'track_grade' ) {
                $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, array(), $extensions, $resource_link_id);
            } else {
                $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, array(), $extensions, $resource_link_id);
            }
            return;
        }
        if ( ! empty($item->youtube) ) {
            $url = U::youtubeWatchUrl($item->youtube);
            $cc_dom->zip_add_url_to_module($zip, $sub_module, $title, $url);
        }
    }

    private static function processDiscussion($item, $module, $sub_module, $zip, $cc_dom, $topic) {
        global $CFG;
        if ( $topic === 'none' ) {
            return;
        }
        $title = isset($item->title) && $item->title !== '' ? $item->title : $module->title;
        $text = isset($item->description) ? $item->description : (isset($module->description) ? $module->description : '');

        if ( $topic === 'lms' ) {
            $cc_dom->zip_add_topic_to_module($zip, $sub_module, $title, $text);
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
