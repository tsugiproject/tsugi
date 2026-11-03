<?php

namespace Tsugi\Services\Lessons;

use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Util\CC;
use Tsugi\Util\U;

/**
 * File-based lessons.json Common Cartridge zip.
 *
 * This is the zip cc/export.php writes. Course copy uses it for $CFG->lessons.
 * Manifest courses use LessonsCartridge instead. Do not merge the two writers.
 */
class LessonsLegacyExport {

    /**
     * Write imsmanifest.xml and resource files into an open ZipArchive.
     *
     * @param object $l Lessons
     * @param \ZipArchive $zip
     * @param array{tsugi_lms?:string,topic?:string,anchors?:array|false,thick?:bool,gift_qti?:bool,title?:string,cli?:bool} $options
     */
    public static function writeZip($l, $zip, array $options = array()) {
        global $CFG;

        $tsugi_lms = LessonsCartridge::exportFlavor(isset($options['tsugi_lms']) ? $options['tsugi_lms'] : '');
        $topic = LessonsCartridge::exportTopicMode(isset($options['topic']) ? $options['topic'] : false);
        $anchors = isset($options['anchors']) ? $options['anchors'] : false;
        if ( ! is_array($anchors) || count($anchors) < 1 ) {
            $anchors = false;
        }
        $thick = ! empty($options['thick']);
        $convert_qti = ! empty($options['gift_qti']);
        $cli = ! empty($options['cli']);
        $title = isset($options['title']) && is_string($options['title']) ? $options['title'] : '';
        if ( $title === '' ) {
            $course = isset($l->lessons->title) && is_string($l->lessons->title) ? $l->lessons->title : 'Course';
            $title = $course.' import';
        }

        $cc_dom = new CC(CC::profileForFlavor($tsugi_lms));
        if ( ! LessonsCartridge::wantsCanvasExtensions($tsugi_lms) ) {
            $cc_dom->disable_canvas_extensions();
        }
        if ( LessonsCartridge::usesCanvasCartridge($tsugi_lms) ) {
            $cc_dom->canvas_quiz_wrapper = true;
        }
        $cc_dom->set_title($title);
        $local_files = new LessonsLegacyFiles($thick);
        $gift_qti = new LessonsLegacyGift($convert_qti);
        $top_module = false;
        if ( $tsugi_lms === 'sakai' ) {
            $top_module = $cc_dom->add_module('Modules (import)', '');
        }

        $modules = isset($l->lessons->modules) && is_array($l->lessons->modules) ? $l->lessons->modules : array();
        foreach ( $modules as $module ) {
            if ( $cli ) {
                echo("title=$module->title\n");
            }
            if ( $anchors && ! in_array($module->anchor, $anchors) ) {
                continue;
            }
            if ( $top_module ) {
                $parent_path = 'Modules (import)';
                $sub_module = $cc_dom->add_sub_module(
                    $top_module,
                    $module->title,
                    $parent_path,
                    $module
                );
            } else {
                $sub_module = $cc_dom->add_module($module->title, '', $module);
            }

            if ( isset($module->items) && is_array($module->items) && count($module->items) > 0 ) {
                foreach ( $module->items as $item ) {
                    $item_obj = is_array($item) ? (object) $item : $item;
                    self::processItem($item_obj, $module, $sub_module, $zip, $cc_dom, $topic, $local_files, $gift_qti);
                }
                continue;
            }

            $parent_path_legacy = self::modulePath($sub_module, $cc_dom);

            if ( isset($module->videos) ) {
                foreach ( $module->videos as $video ) {
                    $v = is_object($video) ? clone $video : (object) $video;
                    if ( ! isset($v->type) ) {
                        $v->type = 'video';
                    }
                    LessonsCartridge::addVideoItem($zip, $cc_dom, $sub_module, $v, $parent_path_legacy);
                }
            }

            if ( isset($module->slides) && is_string($module->slides) ) {
                $slide_href = LessonsService::expandLink($module->slides);
                $url = U::absolute_url($slide_href);
                $slide_title = 'Slides: '.$module->title;
                $local_files->addToModule($zip, $cc_dom, $sub_module, $slide_title, $url, $parent_path_legacy);
            }

            if ( isset($module->slides) && is_array($module->slides) ) {
                foreach ( $module->slides as $slide ) {
                    if ( is_string($slide) ) {
                        $slide_title = basename($slide);
                        $slide_href = $slide;
                    } else {
                        $slide_title = $slide->title;
                        $slide_href = $slide->href;
                    }
                    $slide_href = LessonsService::expandLink($slide_href);
                    $url = U::absolute_url($slide_href);
                    $slide_label = 'Slides: '.$slide_title;
                    $local_files->addToModule($zip, $cc_dom, $sub_module, $slide_label, $url, $parent_path_legacy);
                }
            }

            if ( isset($module->assignment) ) {
                $href = LessonsService::expandLink($module->assignment);
                $url = U::absolute_url($href);
                $assign_title = 'Assignment: '.$module->title;
                $local_files->addToModule($zip, $cc_dom, $sub_module, $assign_title, $url, $parent_path_legacy);
            }

            if ( isset($module->solution) ) {
                $href = LessonsService::expandLink($module->solution);
                $url = U::absolute_url($href);
                $solution_title = 'Solution: '.$module->title;
                $local_files->addToModule($zip, $cc_dom, $sub_module, $solution_title, $url, $parent_path_legacy);
            }

            if ( isset($module->references) ) {
                foreach ( $module->references as $reference ) {
                    $ref_title = 'Reference: '.$reference->title;
                    $href = LessonsService::expandLink($reference->href);
                    $url = U::absolute_url($href);
                    $local_files->addToModule($zip, $cc_dom, $sub_module, $ref_title, $url, $parent_path_legacy);
                }
            }

            if ( isset($module->lti) ) {
                foreach ( $module->lti as $lti ) {
                    $gift_qti->addToModule($zip, $cc_dom, $sub_module, $lti, $module, $parent_path_legacy);
                }
            }

            if ( isset($module->discussions) && $topic != 'none' ) {
                foreach ( $module->discussions as $discussion ) {
                    $disc_title = isset($discussion->title) ? $discussion->title : $module->title;
                    $text = isset($discussion->description) ? $discussion->description : $module->description;

                    if ( $topic == 'lms' || ! isset($CFG->tdiscus) ) {
                        $cc_dom->zip_add_topic_to_module(
                            $zip, $sub_module, $disc_title, $text, $parent_path_legacy,
                            self::exportIdentifier(isset($discussion->resource_link_id) ? $discussion->resource_link_id : null)
                        );
                        continue;
                    }

                    $disc_title = __('Discussion:').' '.$disc_title;
                    $custom_arr = array();
                    if ( isset($discussion->custom) ) {
                        foreach ( $discussion->custom as $custom ) {
                            if ( isset($custom->value) ) {
                                $custom_arr[$custom->key] = $custom->value;
                            }
                            if ( isset($custom->json) ) {
                                $custom_arr[$custom->key] = json_encode($custom->json);
                            }
                        }
                    }

                    $endpoint = U::absolute_url($CFG->tdiscus);
                    $endpoint = U::add_url_parm($endpoint, 'inherit', $discussion->resource_link_id);
                    $extensions = array('apphome' => $CFG->apphome);
                    $resource_link_id = isset($discussion->resource_link_id) ? $discussion->resource_link_id : null;
                    $identifier = self::exportIdentifier($resource_link_id);

                    if ( $topic == 'lti_grade' ) {
                        $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $disc_title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parent_path_legacy, $identifier);
                    } else {
                        $cc_dom->zip_add_lti_to_module($zip, $sub_module, $disc_title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parent_path_legacy, $identifier);
                    }
                }
            }
        }

        $cc_dom->zip_add_canvas_module_meta($zip);
        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());

        if ( $cli ) {
            foreach ( $gift_qti->warnings() as $w ) {
                echo('  warning: '.$w."\n");
            }
        }
    }

    /**
     * @param object $item_obj
     * @param object $module
     * @param \DOMNode $sub_module
     * @param \ZipArchive $zip
     * @param CC $cc_dom
     * @param string|false $topic
     */
    private static function processItem($item_obj, $module, $sub_module, $zip, $cc_dom, $topic, LessonsLegacyFiles $local_files, LessonsLegacyGift $gift_qti) {
        global $CFG;
        $type = isset($item_obj->type) ? $item_obj->type : '';
        $kind = LessonsNormalize::presentationKind($item_obj);

        $parentPath = self::modulePath($sub_module, $cc_dom);

        if ( $type == 'text' ) {
            return;
        }

        if ( LessonsCartridge::addHeadingItem($cc_dom, $sub_module, $item_obj, $parentPath) ) {
            return;
        }

        if ( LessonsCartridge::addVideoItem($zip, $cc_dom, $sub_module, $item_obj, $parentPath) ) {
            return;
        }

        if ( $type == 'slide' || $kind == 'slide' ) {
            $slide_title = isset($item_obj->title) ? $item_obj->title : basename(isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : ''));
            $slide_href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
            $slide_href = LessonsService::expandLink($slide_href);
            $url = U::absolute_url($slide_href);
            $title = 'Slides: '.$slide_title;
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath, $item_obj);
            return;
        }

        if ( $type == 'reference' || $kind == 'reference' ) {
            $title = isset($item_obj->title) ? $item_obj->title : $module->title;
            $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
            $href = LessonsService::expandLink($href);
            $url = U::absolute_url($href);
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath, $item_obj);
            return;
        }

        if ( $type == 'assignment' || $kind == 'assignment' ) {
            $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
            $href = LessonsService::expandLink($href);
            $url = U::absolute_url($href);
            $title = 'Assignment: '.$module->title;
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath, $item_obj);
            return;
        }

        if ( $type == 'solution' || $kind == 'solution' ) {
            $href = isset($item_obj->href) ? $item_obj->href : (isset($item_obj->url) ? $item_obj->url : '');
            $href = LessonsService::expandLink($href);
            $url = U::absolute_url($href);
            $title = 'Solution: '.$module->title;
            $local_files->addToModule($zip, $cc_dom, $sub_module, $title, $url, $parentPath, $item_obj);
            return;
        }

        if ( $type == 'lti' ) {
            $gift_qti->addToModule($zip, $cc_dom, $sub_module, $item_obj, $module, $parentPath);
            return;
        }

        if ( $type == 'discussion' && $topic != 'none' ) {
            $title = isset($item_obj->title) ? $item_obj->title : $module->title;
            $text = isset($item_obj->description) ? $item_obj->description : $module->description;

            if ( $topic == 'lms' || ! isset($CFG->tdiscus) ) {
                $cc_dom->zip_add_topic_to_module(
                    $zip, $sub_module, $title, $text, $parentPath,
                    self::exportIdentifier(isset($item_obj->resource_link_id) ? $item_obj->resource_link_id : null)
                );
                return;
            }

            $title = __('Discussion:').' '.$title;
            $custom_arr = array();
            if ( isset($item_obj->custom) ) {
                foreach ( $item_obj->custom as $custom ) {
                    if ( isset($custom->value) ) {
                        $custom_arr[$custom->key] = $custom->value;
                    }
                    if ( isset($custom->json) ) {
                        $custom_arr[$custom->key] = json_encode($custom->json);
                    }
                }
            }

            $endpoint = U::absolute_url($CFG->tdiscus);
            $endpoint = U::add_url_parm($endpoint, 'inherit', $item_obj->resource_link_id);
            $extensions = array('apphome' => $CFG->apphome);
            $resource_link_id = isset($item_obj->resource_link_id) ? $item_obj->resource_link_id : null;
            $identifier = self::exportIdentifier($resource_link_id);

            if ( $topic == 'lti_grade' ) {
                $cc_dom->zip_add_lti_outcome_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parentPath, $identifier);
            } else {
                $cc_dom->zip_add_lti_to_module($zip, $sub_module, $title, $endpoint, $custom_arr, $extensions, $resource_link_id, $parentPath, $identifier);
            }
        }
    }

    /**
     * @param CC $cc_dom
     * @return string
     */
    private static function modulePath($module_node, $cc_dom) {
        $reflection = new \ReflectionClass($cc_dom);
        $property = $reflection->getProperty('modulePaths');
        $modulePaths = $property->getValue($cc_dom);
        $moduleHash = spl_object_hash($module_node);
        return isset($modulePaths[$moduleHash]) ? $modulePaths[$moduleHash] : '';
    }

    /**
     * Cartridge item identifier for a lesson resource link id.
     * ":" is written as _colon_. Null when the lesson has no link id.
     *
     * @param mixed $resource_link_id
     * @return string|null
     */
    private static function exportIdentifier($resource_link_id) {
        $identifier = Quiz1Repository::cartridgeIdentifierForLinkKey($resource_link_id);
        return $identifier !== '' ? $identifier : null;
    }
}
