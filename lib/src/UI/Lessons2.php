<?php


namespace Tsugi\UI;

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\AesOpenSSL;
use \Tsugi\Grades\GradeUtil;

class Lessons2 {

    /**
     * All the lessons
     */
    public $lessons;

    /**
     * The individual module
     */
    public $module;

    /*
     ** The anchor of the module
     */
    public $anchor;

    /*
     ** The position of the module
     */
    public $position;

    /**
     * Index by resource_link
     */
    public $resource_links;

    /**
     * get a setting for the lesson
     */
    public function getSetting($key, $default=false) {
        if ( ! isset($this->lessons) ) return $default;
        if ( ! isset($this->lessons->settings) ) return $default;
        if ( ! isset($this->lessons->settings->{$key}) ) return $default;
        return $this->lessons->settings->{$key};
    }

    /**
     * emit the header material
     */
    public function header($buffer=false) {
        global $CFG;
        ob_start();
?>
<style>
    .card {
    display: inline-block;
    padding: 0.5em;
    margin: 12px;
    border: 1px solid black;
    height: 9em;
    overflow-y: hidden;
}
 .card div {
    height: 8em;
    overflow-y: hidden;
    text-overflow: ellipsis;
}

#loader {
      position: fixed;
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      background-color: white;
      margin: 0;
      z-index: 100;
}

.progress-badge {
    display: inline-block;
    margin-left: 0.3em;
    vertical-align: middle;
    font-size: 0.75em;
    line-height: 1;
}

.progress-badge-check {
    display: inline-block;
    background-color: #28a745;
    color: white;
    padding: 0.15em 0.4em;
    border-radius: 0.25em;
    font-weight: bold;
    font-size: 0.85em;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

/* Indent content lists that follow h2 headers */
/* Target ul elements that come after h2 in the same container */
h2 ~ ul.tsugi-lessons-content-list,
h2 + ul.tsugi-lessons-content-list {
    margin-left: 1.5em;
    padding-left: 0.5em;
}

/* Also target ul elements that are descendants of containers that have h2 */
div:has(> h2) ul.tsugi-lessons-content-list,
li:has(> h2) ul.tsugi-lessons-content-list {
    margin-left: 1.5em;
    padding-left: 0.5em;
}

.progress-badge-percent {
    display: inline-block;
    background-color: #007bff;
    color: white;
    padding: 0.15em 0.4em;
    border-radius: 0.25em;
    font-weight: bold;
    font-size: 0.85em;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

/* Remove list bullets and style icons for lesson items */
ul.tsugi-lessons-content-list {
    list-style: none;
    padding-left: 0;
}

ul.tsugi-lessons-content-list li {
    list-style: none;
    padding-left: 0;
}

ul.tsugi-lessons-content-list li i.fa {
    color: #666;
    width: 1.2em;
    text-align: center;
}

/* Style for colored item type icons */
.tsugi-item-type-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 3px;
    font-size: 14px;
    margin-right: 8px;
    vertical-align: middle;
    flex-shrink: 0;
}
</style>
<?php
        // See if there are any carousels in the lessons
        $carousel = false;
        foreach($this->lessons->modules as $module) {
            if ( isset($module->carousel) ) $carousel = true;
        }
        if ( $carousel ) {
?>
<link rel="stylesheet" href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css" type="text/css"/>
<?php
        }
        if ( isset($this->lessons->headers) && is_array($this->lessons->headers) ) {
            foreach($this->lessons->headers as $header) {
                $header = self::expandLink($header);
                echo($header);
                echo("\n");
            }
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    /*
     ** Load up the JSON from the file
     **/
    public function __construct($name='lessons.json', $anchor=null, $index=null)
    {
        global $CFG;

        $json_str = file_get_contents($name);
        $lessons = json_decode($json_str);
        $this->resource_links = array();

        if ( $lessons === null ) {
            echo("<pre>\n");
            echo("Problem parsing lessons.json: ");
            echo(json_last_error_msg());
            echo("\n");
            echo($json_str);
            die();
        }

        // Demand that every module have required elments
        foreach($lessons->modules as $module) {
            if ( !isset($module->title) ) {
                die_with_error_log('All modules in a lesson must have a title');
            }
            if ( !isset($module->anchor) ) {
                die_with_error_log('All modules must have an anchor: '.$module->title);
            }
        }

        // Demand that every module have required elments
        if ( isset($lessons->badges) ) foreach($lessons->badges as $badge) {
            if ( !isset($badge->title) ) {
                die_with_error_log('All badges in a lesson must have a title');
            }
            if ( !isset($badge->assignments) ) {
                die_with_error_log('All badges must have assignments: '.$badge->title);
            }
        }

        // Filter modules based on login
        if ( !isset($_SESSION['id']) ) {
            $filtered_modules = array();
            $filtered = false;
            foreach($lessons->modules as $module) {
	            if ( isset($module->login) && $module->login ) {
                    $filtered = true;
                    continue;
                }
                $filtered_modules[] = $module;
            }
            if ( $filtered ) $lessons->modules = $filtered_modules;
        }
        $this->lessons = $lessons;

        // Pretty up the data structure
        for($i=0;$i<count($this->lessons->modules);$i++) {
            if ( isset($this->lessons->modules[$i]->carousel) ) self::adjustArray($this->lessons->modules[$i]->carousel);
            if ( isset($this->lessons->modules[$i]->videos) ) self::adjustArray($this->lessons->modules[$i]->videos);
            if ( isset($this->lessons->modules[$i]->references) ) self::adjustArray($this->lessons->modules[$i]->references);
            if ( isset($this->lessons->modules[$i]->assignments) ) self::adjustArray($this->lessons->modules[$i]->assignments);
            if ( isset($this->lessons->modules[$i]->slides) ) self::adjustArray($this->lessons->modules[$i]->slides);
            if ( isset($this->lessons->modules[$i]->lti) ) self::adjustArray($this->lessons->modules[$i]->lti);
            if ( isset($this->lessons->modules[$i]->discussions) ) self::adjustArray($this->lessons->modules[$i]->discussions);

            // Non arrays
            if ( isset($this->lessons->modules[$i]->assignment) ) {
                if ( ! is_string($this->lessons->modules[$i]->assignment) ) die_with_error_log('Assignment must be a string: '.$module->title);
                self::absolute_url_ref($this->lessons->modules[$i]->assignment);
            }
            if ( isset($this->lessons->modules[$i]->solution) ) {
                if ( ! is_string($this->lessons->modules[$i]->solution) ) die_with_error_log('Solution must be a string: '.$module->title);
                self::absolute_url_ref($this->lessons->modules[$i]->solution);
            }
        }

        // Patch badges
        if ( isset($this->lessons->badges) ) for($i=0;$i<count($this->lessons->badges);$i++) {
            if ( ! isset($this->lessons->badges[$i]->threshold) ) {
                $this->lessons->badges[$i]->threshold = 1.0;
            }
        }

        // Make sure resource links are unique and remember them
        foreach($this->lessons->modules as $module) {
            // Items array takes precedence - if present, skip legacy arrays
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( !isset($item->type) ) continue;
                    if ( ($item->type == 'lti' || $item->type == 'discussion') && isset($item->resource_link_id) ) {
                        if (isset($this->resource_links[$item->resource_link_id]) ) {
                            die_with_error_log('Duplicate resource link in Lessons '. $item->resource_link_id);
                        }
                        $this->resource_links[$item->resource_link_id] = $module->anchor;
                    }
                }
            } else {
                // Process legacy lti array only if items is not present
                if ( isset($module->lti) ) {
                    $ltis = $module->lti;
                    if ( ! is_array($ltis) ) $ltis = array($ltis);
                    foreach($ltis as $lti) {
                        if ( ! isset($lti->title) ) {
                            die_with_error_log('Missing lti title in module:'. $module->title);
                        }
                        if ( ! isset($lti->resource_link_id) ) {
                            die_with_error_log('Missing resource link in Lessons '. $lti->title);
                        }
                        if (isset($this->resource_links[$lti->resource_link_id]) ) {
                            die_with_error_log('Duplicate resource link in Lessons '. $lti->resource_link_id);
                        }
                        $this->resource_links[$lti->resource_link_id] = $module->anchor;
                    }
                }
                // Process legacy discussions array only if items is not present
                if ( isset($module->discussions) ) {
                    $discussions = $module->discussions;
                    if ( ! is_array($discussions) ) $discussions = array($discussions);
                    foreach($discussions as $discussion) {
                        if ( ! isset($discussion->title) ) {
                            die_with_error_log('Missing discussion title in module:'. $module->title);
                        }
                        if ( ! isset($discussion->resource_link_id) ) {
                            die_with_error_log('Missing resource link in Lessons '. $discussion->title);
                        }
                        if (isset($this->resource_links[$discussion->resource_link_id]) ) {
                            die_with_error_log('Duplicate resource link in Lessons '. $discussion->resource_link_id);
                        }
                        $this->resource_links[$discussion->resource_link_id] = $module->anchor;
                    }
                }
            }
        }

        $anchor = isset($_GET['anchor']) ? $_GET['anchor'] : $anchor;
        $index = isset($_GET['index']) ? $_GET['index'] : $index;

        // Search for the selected anchor or index position
        $count = 0;
        $module = false;
        if ( $anchor || $index ) {
            foreach($lessons->modules as $mod) {
                $count++;
                if ( $anchor !== null && isset($mod->anchor) && $anchor != $mod->anchor ) continue;
                if ( $index !== null && $index != $count ) continue;
                if ( $anchor == null && isset($mod->anchor) ) $anchor = $mod->anchor;
                $this->module = $mod;
                $this->position = $count;
                if ( $mod->anchor ) $this->anchor = $mod->anchor;
                break; // Found the module, exit loop
            }
        }

        return true;
    }

    /**
     * Make non-array into an array and adjust paths
     */
    public static function adjustArray(&$entry) {
        global $CFG;
        if ( isset($entry) && !is_array($entry) ) {
            $entry = array($entry);
        }
        for($i=0; $i < count($entry); $i++ ) {
            if ( is_string($entry[$i]) ) self::absolute_url_ref($entry[$i]);
            if ( isset($entry[$i]->href) && is_string($entry[$i]->href) ) self::absolute_url_ref($entry[$i]->href);
            if ( isset($entry[$i]->launch) && is_string($entry[$i]->launch) ) self::absolute_url_ref($entry[$i]->launch);
        }
    }

    /**
     * Indicate we are in a single lesson
     */
    public function isSingle() {
        return ( $this->anchor !== null || $this->position !== null );
    }

    /**
     * Get a module associated with an anchor
     */
    public function getModuleByAnchor($anchor)
    {
        foreach($this->lessons->modules as $mod) {
            if ( $mod->anchor == $anchor) return $mod;
        }
        return null;
    }

    /**
     * Get an LTI or Discussion associated with a resource link ID
     */
    public function getLtiByRlid($resource_link_id)
    {
        if (isset($this->lessons->discussions) ) {
            foreach($this->lessons->discussions as $discussion) {
                if ( $discussion->resource_link_id == $resource_link_id) return $discussion;
            }
        }

        foreach($this->lessons->modules as $mod) {
            if ( isset($mod->lti) ) {
                foreach($mod->lti as $lti ) {
                    if ( $lti->resource_link_id == $resource_link_id) return $lti;
                }
            }
            if ( isset($mod->discussions) ) {
                foreach($mod->discussions as $discussion ) {
                    if ( $discussion->resource_link_id == $resource_link_id) return $discussion;
                }
            }
            // Scan items array for LTI and discussion items
            if ( isset($mod->items) && is_array($mod->items) ) {
                foreach($mod->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( isset($item_obj->type) && isset($item_obj->resource_link_id) ) {
                        if ( ($item_obj->type == 'lti' || $item_obj->type == 'discussion') 
                             && $item_obj->resource_link_id == $resource_link_id ) {
                            return $item_obj;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get a module associated with a resource link ID
     */
    public function getModuleByRlid($resource_link_id)
    {
        foreach($this->lessons->modules as $mod) {
            if ( isset($mod->lti) ) {
                foreach($mod->lti as $lti ) {
                    if ( $lti->resource_link_id == $resource_link_id) return $mod;
                }
            }
            if ( isset($mod->discussions) ) {
                foreach($mod->discussions as $discussion ) {
                    if ( $discussion->resource_link_id == $resource_link_id) return $mod;
                }
            }
            // Scan items array for LTI and discussion items
            if ( isset($mod->items) && is_array($mod->items) ) {
                foreach($mod->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( isset($item_obj->type) && isset($item_obj->resource_link_id) ) {
                        if ( ($item_obj->type == 'lti' || $item_obj->type == 'discussion') 
                             && $item_obj->resource_link_id == $resource_link_id ) {
                            return $mod;
                        }
                    }
                }
            }
        }
        return null;
    }

    /*
     ** render
     */
    public function render($buffer=false) {
        if ( $this->isSingle() ) {
            return $this->renderSingle($buffer);
        } else {
            return $this->renderAll($buffer);
        }
    }

    public static function absolute_url_ref(&$url) {
        $url = trim($url);
        $url = self::expandLink($url);
        $url = U::absolute_url($url);
    }

    /*
     * Do macro substitution on a link
     */
    public static function expandLink($url) {
        global $CFG;
        
        $search = array(
            "{apphome}",
            "{wwwroot}",
        );
        $replace = array(
            $CFG->apphome,
            $CFG->wwwroot,
        );
        $url = str_replace($search, $replace, $url);

        return $url;
    }

    /*
     * A Nostyle URL Link with title
     */
    public static function nostyleUrl($title, $url) {
        $url = self::expandLink($url);
        echo('<a href="'.$url.'" target="_blank" typeof="oer:SupportingMaterial">'.htmlentities($url)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
	    echo(' data-title="'.htmlentities($title).'" ');
	    echo('></div>');
        }
    }

    /*
     * A Nostyle URL Link with title as the href text
     */
    public static function nostyleLink($title, $url) {
        $url = self::expandLink($url);
        echo('<a href="'.$url.'" target="_blank" class="tsugi-lessons-link" typeof="oer:SupportingMaterial">'.htmlentities($title)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
	    echo(' data-title="'.htmlentities($title).'" ');
	    echo('></div>');
        }
    }


    /*
     * render a lesson
     */
    public function renderSingle($buffer=false) {
        global $CFG, $OUTPUT;
        ob_start();
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }
        $nostyle = isset($_SESSION['nostyle']);

        $module = $this->module;
        
        // Ensure module is set
        if ( !$module ) {
            echo('<p>Error: Module not found.</p>');
            $ob_output = ob_get_contents();
            ob_end_clean();
            if ( $buffer ) return $ob_output;
            echo($ob_output);
            return;
        }
        
        
	if ( $nostyle && isset($_SESSION['gc_count']) ) {
?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<?php
        }
            if ( $this->getSetting('prev-next') == "right" ) {
                echo('<div typeof="oer:Lesson" style="float:right; padding-left: 5px; vertical-align: text-top;"><ul class="pager">'."\n");
            } else if ( $this->getSetting('prev-next') == "left" ) {
                echo('<div typeof="oer:Lesson" style="float:left; padding-left: 5px; vertical-align: text-top;"><ul class="pager">'."\n");
            } else {
                echo('<div typeof="oer:Lesson" style="padding-left: 5px; vertical-align: text-top;"><ul class="pager">'."\n");
            }
            $disabled = ($this->position == 1) ? ' disabled' : '';
            $all = U::get_rest_parent();
            if ( $this->position == 1 ) {
                echo('<li class="previous disabled"><a href="#" onclick="return false;">&larr; '.__('Previous').'</a></li>'."\n");
            } else {
                $prev = $all . '/' . urlencode($this->lessons->modules[$this->position-2]->anchor);
                echo('<li class="previous"><a href="'.$prev.'">&larr; '.__('Previous').'</a></li>'."\n");
            }
            echo('<li><a href="'.$all.'">'.__('All').' ('.$this->position.' / '.count($this->lessons->modules).')</a></li>');
            if ( $this->position >= count($this->lessons->modules) ) {
                echo('<li class="next disabled"><a href="#" onclick="return false;">&rarr; '.__('Next').'</a></li>'."\n");
            } else {
                $next = $all . '/' . urlencode($this->lessons->modules[$this->position]->anchor);
                echo('<li class="next"><a href="'.$next.'">&rarr; '.__('Next').'</a></li>'."\n");
            }
            echo("</ul></div>\n");
            echo('<h1 property="oer:name" class="tsugi-lessons-module-title">'.$module->title."</h1>\n");
            $lessonurl = $CFG->apphome . U::get_rest_path();
            if ( $nostyle ) {
                self::nostyleUrl($module->title, $lessonurl);
                echo("<hr/>\n");
            }

            // Check if module uses items array (new format)
            // Direct access to items property - json_decode creates stdClass objects, arrays stay as arrays
            $has_items = false;
            $items_array = null;
            if ( isset($module->items) ) {
                $items_array = $module->items;
                // json_decode keeps JSON arrays as PHP arrays, so this should work
                if ( is_array($items_array) && count($items_array) > 0 ) {
                    $has_items = true;
                }
            }
            $has_legacy = isset($module->videos) || isset($module->lti) || isset($module->discussions) || 
                         isset($module->references) || isset($module->slides) || isset($module->assignment) || 
                         isset($module->solution) || isset($module->carousel);
            
            // Check debug flag
            $debug_conversion = $CFG->getExtension('lessons_debug_conversion', false);
            
            if ( $has_items ) {
                // New format: render items array
                if ( $debug_conversion && $has_legacy ) {
                    echo('<div style="border: 2px solid blue; padding: 10px; margin: 10px 0;"><h3 style="color: blue;">NEW FORMAT (items array):</h3>');
                }
                // Render description if present (aligned with title)
                if ( isset($module->description) ) {
                    if ( isset($module->image) ) {
                        echo('<img class="tsugi-module-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.self::expandLink($module->image).'">');
                    }
                    echo('<p property="oer:description" class="tsugi-lessons-module-description">'.$module->description."</p>\n");
                    if ( isset($module->image) ) {
                        echo('<br clear="all">');
                    }
                }
                
                // Render items in order - headers and text align with title, grouped items are indented
                $current_section = null;
                $in_list = false;
                foreach($items_array as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    // Skip malformed items (e.g., objects that should be arrays)
                    if ( isset($item_obj->type) && $item_obj->type == 'ltis' && isset($item_obj->items) && !is_array($item_obj->items) ) {
                        continue;
                    }
                    
                    $type = isset($item_obj->type) ? $item_obj->type : '';
                    
                    // Handle headers - render outside list structure (aligned with title)
                    if ($type == 'header') {
                        if ($in_list && $current_section) {
                            echo("</ul>\n");
                            $in_list = false;
                        }
                        $current_section = null;
                        $this->renderItem($item_obj, $module, $nostyle);
                        continue;
                    }
                    
                    // Handle text items - render outside list structure (aligned with title)
                    if ($type == 'text') {
                        if ($in_list && $current_section) {
                            echo("</ul>\n");
                            $in_list = false;
                            $current_section = null;
                        }
                        $this->renderItem($item_obj, $module, $nostyle);
                        continue;
                    }
                    
                    // Determine section type for grouping (these will be indented)
                    $section_type = null;
                    if (in_array($type, array('video'))) {
                        $section_type = 'videos';
                    } else if (in_array($type, array('reference'))) {
                        $section_type = 'references';
                    } else if (in_array($type, array('discussion'))) {
                        $section_type = 'discussions';
                    } else if (in_array($type, array('lti'))) {
                        $section_type = 'ltis';
                    } else if (in_array($type, array('slide'))) {
                        $section_type = 'slides';
                    } else if (in_array($type, array('assignment'))) {
                        $section_type = 'assignments';
                    } else if (in_array($type, array('solution'))) {
                        $section_type = 'solutions';
                    }
                    
                    // Start new list section if needed (indented under header)
                    if ($section_type && $section_type != $current_section) {
                        if ($in_list && $current_section) {
                            echo("</ul>\n");
                        }
                        $current_section = $section_type;
                        $in_list = true;
                        $class_map = array(
                            'videos' => 'tsugi-lessons-module-videos',
                            'references' => 'tsugi-lessons-module-references',
                            'discussions' => 'tsugi-lessons-module-discussions',
                            'ltis' => 'tsugi-lessons-module-ltis',
                            'slides' => 'tsugi-lessons-module-slides',
                            'assignments' => 'tsugi-lessons-module-assignments',
                            'solutions' => 'tsugi-lessons-module-solutions'
                        );
                        $ul_class_map = array(
                            'videos' => 'tsugi-lessons-module-videos-ul',
                            'references' => 'tsugi-lessons-module-references-ul',
                            'discussions' => 'tsugi-lessons-module-discussions-ul',
                            'ltis' => 'tsugi-lessons-module-ltis-ul',
                            'slides' => 'tsugi-lessons-module-slides-ul',
                            'assignments' => 'tsugi-lessons-module-assignments-ul',
                            'solutions' => 'tsugi-lessons-module-solutions-ul'
                        );
                        $typeof_map = array(
                            'videos' => 'oer:SupportingMaterial',
                            'references' => 'oer:SupportingMaterial',
                            'discussions' => 'oer:discussion',
                            'ltis' => 'oer:assessment',
                            'slides' => 'oer:SupportingMaterial',
                            'assignments' => 'oer:assessment',
                            'solutions' => 'oer:assessment'
                        );
                        echo('<ul typeof="'.$typeof_map[$section_type].'" class="'.$class_map[$section_type].' '.$ul_class_map[$section_type].' tsugi-lessons-content-list">'."\n");
                    } else if (!$section_type) {
                        // Non-grouped item (like chapters) - close list if open, render outside
                        if ($in_list && $current_section) {
                            echo("</ul>\n");
                            $in_list = false;
                            $current_section = null;
                        }
                    }
                    
                    $this->renderItem($item_obj, $module, $nostyle);
                }
                
                // Close any open list
                if ($in_list && $current_section) {
                    echo("</ul>\n");
                }
                
                if ( $debug_conversion && $has_legacy ) {
                    echo('</div>');
                    echo('<div style="border: 2px solid red; padding: 10px; margin: 10px 0;"><h3 style="color: red;">LEGACY FORMAT (old arrays):</h3>');
                }
                
                // If debug is off or no legacy format, return early (only render items)
                if ( !$debug_conversion || !$has_legacy ) {
                    if ( $nostyle ) {
                        $styleoff = U::get_rest_path() . '?nostyle=no';
                        echo('<p><a href="'.$styleoff.'">');
                        echo(__('Turn styling back on'));
                        echo("</a>\n");
                    }
                    
                    $ob_output = ob_get_contents();
                    ob_end_clean();
                    if ( $buffer ) return $ob_output;
                    echo($ob_output);
                    return;
                }
                // Otherwise continue to render legacy format below
            }

            // Legacy format: continue with existing rendering
            if ( isset($module->carousel) ) {
                $carousel = $module->carousel;
                $videotitle = __(self::getSetting('videos-title', 'Videos'));
                echo($nostyle ? $videotitle . ': <ul>' : '<ul class="bxslider">'."\n");
                foreach($carousel as $video ) {
                    echo('<li>');
                    if ( $nostyle ) {
                        echo(htmlentities($video->title)."<br/>");
                        $yurl = 'https://www.youtube.com/watch?v='.$video->youtube;
                        self::nostyleUrl($video->title, $yurl);
                    } else {
                        $OUTPUT->embedYouTube($video->youtube, $video->title);
                    }
                    echo('</li>');
                }
                echo("</ul>\n");
            }

            if ( isset($module->description) ) {
                if ( isset($module->image) ) {
                    echo('<img class="tsugi-module-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.self::expandLink($module->image).'">');
                }
                echo('<p property="oer:description" class="tsugi-lessons-module-description">'.$module->description."</p>\n");
                if ( isset($module->image) ) {
                    echo('<br clear="all">');
                }
            }

            echo("<ul>\n");

            if ( isset($module->videos) ) {
                $videos = $module->videos;
                $media_folder = $CFG->getExtension('media_folder', null);
                $media_base = $CFG->getExtension('media_base', null);
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-videos">');
                $videotitle = __(self::getSetting('videos-title', 'Videos'));
                echo("<p>");
                echo($videotitle);
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-videos-ul">'."\n");
                $lecno = 0;
                foreach($videos as $video ) {
                    $media_file = $video->media ?? null;
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-video">');
                    if ( is_string($media_file) && is_string($media_base) && is_string($media_folder) &&
                        file_exists($media_folder . '/' . $media_file) ) {
                        $media_path = $media_base . '/' . $media_file;
?>
<a href="<?= $media_path ?>" target="_blank"><?= htmlentities($video->title) ?></a>
<?php
                    } else {
                        $yurl = 'https://www.youtube.com/watch?v='.$video->youtube;
                        $lecno = $lecno + 1;
                        $navid = md5($lecno.$yurl);
                        // https://www.w3schools.com/howto/howto_js_fullscreen_overlay.asp
?>
<div id="<?= $navid ?>" class="w3schools-overlay">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <div class="youtube-player" data-id="<?= $video->youtube ?>"></div>
  </div>
</div>
<a href="#" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($video->title) ?></a>
<?php
                    }
                    echo("</li>\n");
                }
                echo("</ul></li>\n");
            }

            if ( isset($module->lectures) ) {
                $lectures = $module->lectures;
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lectures">');
                $lecturetitle = __(self::getSetting('lectures-title', 'Lectures'));
                echo("<p>");
                echo($lecturetitle);
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-lectures-ul">'."\n");
                $lecno = 1;
                foreach($lectures as $lecture ) {
                    $lecno = $lecno + 1;
                    if ( isset($lecture->youtube) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-youtube">');
                        $yurl = 'https://www.youtube.com/watch?v='.$lecture->youtube;
                        // self::nostyleLink($lecture->title, $yurl);
                        $navid = md5($lecno.$yurl);
                        // https://www.w3schools.com/howto/howto_js_fullscreen_overlay.asp
?>
<div id="<?= $navid ?>" class="w3schools-overlay">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <div class="youtube-player" data-id="<?= $lecture->youtube ?>"></div>
  </div>
</div>
<a href="#" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></a>
<?php
                        echo('</li>');
                    } else if ( isset($lecture->audio) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-audio">');
                        // self::nostyleLink($lecture->title, $lecture->audio);
                        $navid = md5($lecno.$lecture->audio);
?>
<div id="<?= $navid ?>" class="w3schools-overlay">
  <div class="w3schools-overlay-content" style="background-color: black;">
<h2><?= htmlentities($lecture->title) ?></h2>
  <audio controls preload='none' src="<?= self::expandLink($lecture->audio) ?>"></audio>
  </div>
</div>
<a href="#" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></a>
<?php
                        echo('</li>');
                    } else if ( isset($lecture->video) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-video">');
                        $yurl = 'https://www.youtube.com/watch?v='.$lecture->video;
                        // self::nostyleLink($lecture->title, $lecture->video);
                        $navid = md5($lecno.$lecture->video);
?>
<div id="<?= $navid ?>" class="w3schools-overlay">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <video controls style="width:95%;" preload="none" src="<?= self::expandLink($lecture->video) ?>"></video>
  </div>
</div>
<a href="#" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></a>
<?php
                        echo('</li>');
                    }
                }
                echo("</ul></li>\n");
            }

            if ( isset($module->slides) ) {
                $singular = 'slide';
                $plural = $singular.'s';
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-slides">');
                echo("<p>");
                $slidestitle = __(self::getSetting($plural.'title', ucfirst($plural)));
                echo(__($slidestitle));
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
                foreach($module->slides as $slide ) {
                    if ( is_string($slide) ) {
                        $slide_title = basename($slide);
                        $slide_href = $slide;
                    } else {
                        $slide_title = $slide->title ;
                        $slide_href = $slide->href ;
                    }
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$singular.'">');
                    echo('<span class="tsugi-lessons-module-'.$singular.'-icon"></span>');
                    echo('<span class="tsugi-lessons-module-'.$singular.'-link">');
                    self::nostyleLink($slide_title, $slide_href);
                    echo("</span>\n");
                    echo('</li>'."\n");
                }
                if ( count($module->slides) > 0 ) {
                    echo("</ul></li>\n");
                }
            }
            if ( isset($module->chapters) ) {
                echo('<li typeof="SupportingMaterial">'.__('Chapters').': '.$module->chapters.'</a></li>'."\n");
            }
            if ( isset($module->assignment) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">'.__('Assignment Specification').':');
                    self::nostyleUrl(__('Assignment Specification'), $module->assignment);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->assignment.'" target="_blank">'.__('Assignment Specification').'</a></li>'."\n");
                }
            }
            if ( isset($module->solution) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">'.__('Assignment Solution').':');
                    self::nostyleUrl(__('Assignment Solution'), $module->solution);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->solution.'" target="_blank">'.__('Assignment Solution').'</a></li>'."\n");
                }
            }

            // Reference like entries
            $lists = array("reference", "assignment");
            foreach($lists as $list) {
                $singular = $list;
                $plural = $list."s";
                if ( isset($module->{$plural}) ) {
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$plural.'">');
                    $list_title = __(self::getSetting($plural.'-title', ucfirst($plural)));
                    echo("<p>");
                    echo(__($list_title));
                    echo("</p>");
                    echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
                    foreach($module->{$plural} as $reference ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$singular.'">');
                        echo('<span class="tsugi-lessons-module-'.$singular.'-icon"></span>');
                        echo('<span class="tsugi-lessons-module-'.$singular.'-link">');
                        self::nostyleLink($reference->title, $reference->href);
                        echo("</span>\n");
                        echo('</li>'."\n");
                    }
                    echo("</ul></li>\n");
                }
            }

            // DISCUSSIONs not logged in
            if ( isset($CFG->tdiscus) && $CFG->tdiscus && isset($module->discussions) && ! isset($_SESSION['secret']) ) {
                $discussions = $module->discussions;
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussions">');
                echo(__('Discussions:'));
                echo('<ul class="tsugi-lessons-module-discussions-ul"> <!-- start of discussions -->'."\n");
                foreach($discussions as $discussion ) {
                    $resource_link_title = isset($discussion->title) ? $discussion->title : $module->title;
                    echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">'.htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
                    echo("\n</li>\n");
                }
                echo("</li></ul><!-- end of discussions -->\n");
            }

            // DISCUSSIONs logged in
            if ( isset($CFG->tdiscus) && $CFG->tdiscus && isset($module->discussions)
                && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
            {
                $discussions = $module->discussions;
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussions">');
                echo(__('Discussions:'));
                echo('<ul class="tsugi-lessons-module-discussions-ul"> <!-- start of discussions -->'."\n");
                $count = 0;
                foreach($discussions as $discussion ) {
                    $resource_link_title = isset($discussion->title) ? $discussion->title : $module->title;

                    if ( $nostyle ) {
                        echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">'.htmlentities($resource_link_title).' (Login Required) <br/>'."\n");
                        $discussionurl = U::add_url_parm($discussion->launch, 'inherit', $discussion->resource_link_id);
                        echo('<span style="color:green">'.htmlentities($discussionurl)."</span>\n");
                        if ( isset($_SESSION['gc_count']) ) {
                            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?rlid='.$discussion->resource_link_id);
                            echo('" title="Install Assignment in Classroom" target="iframe-frame"'."\n");
                            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
                            echo('<img height=16 width=16 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
                        }
                        echo("\n</li>\n");
                        continue;
                    }

                    $rest_path = U::rest_path();
                    $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $discussion->resource_link_id;
                    $title = isset($discussion->title) ? $discussion->title : "Discussion";
                    echo('<li class="tsugi-lessons-module-discussion"><a href="'.$launch_path.'">'.htmlentities($title).'</a></li>'."\n");
                    echo("\n</li>\n");
                }

                echo("</li></ul><!-- end of discussions -->\n");
            }

            // LTIs not logged in
            if ( isset($module->lti) && ! isset($_SESSION['secret']) ) {
                $ltis = $module->lti;
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-ltis">');
                echo(__('Tools:'));
                echo('<ul class="tsugi-lessons-module-ltis-ul"> <!-- start of ltis -->'."\n");
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">'.htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
                    echo("\n</li>\n");
                }
                echo("</li></ul><!-- end of ltis -->\n");
            }

            // LTIs logged in
            if ( isset($module->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
            {
                $ltis = $module->lti;
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-ltis">');
                echo(__('Tools:'));
                echo('<ul class="tsugi-lessons-module-ltis-ul"> <!-- start of ltis -->'."\n");
                $count = 0;
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;

                    if ( $nostyle ) {
                        echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">'.htmlentities($resource_link_title).' (Login Required) <br/>'."\n");
                        $ltiurl = U::add_url_parm($lti->launch, 'inherit', $lti->resource_link_id);
                        echo('<span style="color:green">'.htmlentities($ltiurl)."</span>\n");
                        if ( isset($_SESSION['gc_count']) ) {
                            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?rlid='.$lti->resource_link_id);
                            echo('" title="Install Assignment in Classroom" target="iframe-frame"'."\n");
                            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
                            echo('<img height=16 width=16 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
                        }
                        echo("\n</li>\n");
                        continue;
                    }

                    $rest_path = U::rest_path();
                    $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $lti->resource_link_id;
                    $title = isset($lti->title) ? $lti->title : "Autograder";
                    $target = isset($lti->target) ? $lti->target : false;

                    echo('<li class="tsugi-lessons-module-lti"><a');
                    if ( $target == "_blank" ) echo(' target="_blank" onclick="alert(\'Link will open in a new browser tab...\');" ');
                    echo(' href="'.$launch_path.'">'.htmlentities($title).'</a></li>'."\n");
                    echo("\n</li>\n");
                }

                echo("</li></ul><!-- end of ltis -->\n");
            }

        echo("</ul>\n");
        
        // Close legacy format div if it was opened (debug mode only)
        if ( $debug_conversion && $has_items && $has_legacy ) {
            echo('</div>');
        }

        if ( $nostyle ) {
            $styleoff = U::get_rest_path() . '?nostyle=no';
            echo('<p><a href="'.$styleoff.'">');
            echo(__('Turn styling back on'));
            echo("</a>\n");
        }

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    } // End of renderSingle

    public function renderAll($buffer=false)
    {
        ob_start();

         // Load all the Grades so far
         $allgrades = array();
         if ( isset($_SESSION['id']) && isset($_SESSION['context_id'])) {
             $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
             foreach($rows as $row) {
                 $allgrades[$row['resource_link_id']] = $row['grade'];
             }
         }


        echo('<div typeof="Course">'."\n");
        echo('<h1>'.$this->lessons->title."</h1>\n");
        echo('<p property="description">'.$this->lessons->description."</p>\n");
        echo('<div id="box">'."\n");
        $count = 0;

        foreach($this->lessons->modules as $module) {
        if ( isset($module->hidden) && $module->hidden ) continue;
	    if ( isset($module->login) && $module->login && !isset($_SESSION['id']) ) continue;

            $possible_points = 0;
            $actual_points = 0;
            $rlids = array();
            // Process items array first (takes precedence)
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( $item->type != 'lti' || !isset($item->resource_link_id) ) continue;
                    $possible_points += 1.0;
                    if ( isset($allgrades[$item->resource_link_id]) && is_numeric($allgrades[$item->resource_link_id]) ) {
                        $actual_points += $allgrades[$item->resource_link_id];
                    }
                    $rlids[] = $item->resource_link_id;
                }
            } else {
                // Process legacy lti array only if items is not present
                if ( isset($module->lti) ) {
                    $ltis = $module->lti;
                    if ( ! is_array($ltis) ) $ltis = array($ltis);
                    foreach($ltis as $lti) {
                        if ( isset($lti->resource_link_id) ) {
                            $possible_points += 1.0;
                            if ( isset($allgrades[$lti->resource_link_id]) && is_numeric($allgrades[$lti->resource_link_id]) ) {
                                $actual_points += $allgrades[$lti->resource_link_id];
                            }
                            $rlids[] = $lti->resource_link_id;
                        }
                    }
                }
            }
            $count++;
            // Calculate percent for icon coloring
            $percent = 0;
            if ( $possible_points > 0 ) {
                $percent = round(($actual_points / $possible_points)*100);
            }
            
            echo('<div class="card"><div>'."\n");
            $href = U::get_rest_path() . '/' . urlencode($module->anchor);
            if ( isset($module->icon) ) {
                $icon_color = '';
                if ( $percent == 100 ) {
                    $icon_color = 'color: #28a745;'; // Green for 100%
                } else if ( $percent > 0 && $percent < 100 ) {
                    $icon_color = 'color: #007bff;'; // Blue for 1-99%
                }
                echo('<i class="fa '.$module->icon.' fa-2x" aria-hidden="true" style="float: left; padding-right: 5px;'.$icon_color.'"></i>');
            }
            if ( isset($module->image) ) {
                echo('<img class="tsugi-all-modules-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.self::expandLink($module->image).'">');
            }
            echo('<a href="'.$href.'">'."\n");
            echo($count.': '.$module->title);
            if ( $possible_points > 0 ) {
                if ( $percent == 0 ) {
                    // No badge for 0%
                } else if ( $percent == 100 ) {
                    // Green badge with 100% for complete
                    echo('<span class="progress-badge progress-badge-check" title="Complete: 100%">100%</span>');
                } else {
                    // Blue badge with percentage for 1-99%
                    echo('<span class="progress-badge progress-badge-percent" title="Progress: '.$percent.'%">'.$percent.'%</span>');
                }
            }
            echo("<br clear=\"all\"/>\n");
            if ( isset($module->description) ) {
                $desc = $module->description;
                if ( strlen($desc) > 1000 ) $desc = substr($desc, 0, 1000);
                echo('<br/>'.$desc."\n");
            }
            echo("</a></div></div>\n");
            echo("<!--\n");
            print_r($allgrades);
            print_r($rlids);
            echo("\n-->\n");
        }
        echo('</div> <!-- box -->'."\n");
        echo('</div> <!-- typeof="Course" -->'."\n");
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public function renderAssignments($allgrades, $alldates, $buffer=false)
    {
        ob_start();
        echo('<h1>'.$this->lessons->title."</h1>\n");
        $displayname = U::get($_SESSION, 'displayname');
        $email = U::get($_SESSION, 'email');
        if ( is_string($displayname) || is_string($email) ) {
            $output = "";
            if ( is_string($displayname) ) $output .= $displayname;
            if ( is_string($displayname) && is_string($email) ) $output .= ' ';
            if ( is_string($email) ) $output .= $email;
            echo("<p>Grades for: ");
            echo(htmlentities($output));
            $sig = md5("42 ".$output);
            echo(" | ".substr($sig,0,5)."</p>\n");
        }
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        $count = 0;
        foreach($this->lessons->modules as $module) {
            $count++;
            // Items array takes precedence - check items first
            $has_assignments = false;
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( isset($item->type) && $item->type == 'lti' && isset($item->resource_link_id) ) {
                        $has_assignments = true;
                        break;
                    }
                }
            } else if ( isset($module->lti) ) {
                $has_assignments = true;
            }
            if ( !$has_assignments ) continue;
            
            echo('<tr><td class="info" colspan="3">'."\n");
            $href = U::get_rest_parent() . '/lessons/' . urlencode($module->anchor);
            echo('<a href="'.$href.'">'."\n");
            echo($module->title);
            echo("</td></tr>");
            
            // Process items array first (takes precedence)
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( !isset($item->type) || $item->type != 'lti' || !isset($item->resource_link_id) ) continue;
                    echo('<tr><td>');
                    if ( isset($allgrades[$item->resource_link_id]) ) {
                        if ( $allgrades[$item->resource_link_id] > 0.8 ) {
                            echo('<i class="fa fa-check-square-o text-success" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                        } else {
                            echo('<i class="fa fa-square-o text-warning" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                        }
                    } else {
                            echo('<i class="fa fa-square-o text-danger" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                    }
                    $item_title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment');
                    echo("</td><td>".$item_title."</td>\n");
                    if ( isset($allgrades[$item->resource_link_id]) ) {
                        $datestring = U::get($alldates, $item->resource_link_id, "");
                        if ( strlen($datestring) > 0 ) {
                            $datestring = " (".substr($datestring,0,10).")";
                        }
                        echo("<td>Score: ".(100*$allgrades[$item->resource_link_id]).$datestring."</td>");
                    } else {
                        echo("<td>&nbsp;</td>");
                    }

                    echo("</tr>\n");
                }
            } else {
                // Process legacy lti array only if items is not present
                if ( isset($module->lti) ) {
                    $ltis = $module->lti;
                    if ( ! is_array($ltis) ) $ltis = array($ltis);
                    foreach($ltis as $lti) {
                        if ( !isset($lti->resource_link_id) ) continue;
                        echo('<tr><td>');
                        if ( isset($allgrades[$lti->resource_link_id]) ) {
                            if ( $allgrades[$lti->resource_link_id] > 0.8 ) {
                                echo('<i class="fa fa-check-square-o text-success" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                            } else {
                                echo('<i class="fa fa-square-o text-warning" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                            }
                        } else {
                                echo('<i class="fa fa-square-o text-danger" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                        }
                        echo("</td><td>".$lti->title."</td>\n");
                        if ( isset($allgrades[$lti->resource_link_id]) ) {
                            $datestring = U::get($alldates, $lti->resource_link_id, "");
                            if ( strlen($datestring) > 0 ) {
                                $datestring = " (".substr($datestring,0,10).")";
                            }
                            echo("<td>Score: ".(100*$allgrades[$lti->resource_link_id]).$datestring."</td>");
                        } else {
                            echo("<td>&nbsp;</td>");
                        }

                        echo("</tr>\n");
                    }
                }
            }
        }
        echo('</tbody></table>'."\n");
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public static function makeUrlResource($type,$title,$url) {
        global $CFG;
       $RESOURCE_ICONS = array(
                'video' => 'fa-video-camera',
                'slides' => 'fa-file-powerpoint-o',
                'assignment' => 'fa-lock',
                'solution' => 'fa-unlock',
                'reference' => 'fa-external-link'
        );
        $retval = new \stdClass();
        $retval->type = $type;
        if ( isset($RESOURCE_ICONS[$type]) ) {
            $retval->icon = $RESOURCE_ICONS[$type];
        } else {
            $retval->icon = 'fa-external-link';
        }
        $retval->thumbnail = $CFG->fontawesome.'/png/'.str_replace('fa-','',$retval->icon).'.png';

        if ( strpos($title,':') !== false ) {
            $retval->title = $title;
        } else {
            $retval->title = ucwords($type) . ': ' . $title;
        }
        $retval->url = $url;
        return $retval;
    }

/* After PHP 5.6
    const RESOURCE_ICONS = array(
        'video' => 'fa-video-camera',
        'slides' => 'fa-file-powerpoint-o',
        'assignment' => 'fa-lock',
        'solution' => 'fa-unlock',
        'reference' => 'fa-external-link'
    );
*/

    public static function getUrlResources($module) {
        $resources = array();
        // Items array takes precedence - process items first
        if ( isset($module->items) ) {
            foreach($module->items as $item) {
                if ( !isset($item->type) ) continue;
                if ( $item->type == 'video' && isset($item->youtube) ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Video');
                    $resources[] = self::makeUrlResource('video', $title,
                        'https://www.youtube.com/watch?v='.urlencode($item->youtube));
                } else if ( $item->type == 'slide' && isset($item->href) ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : __('Slides').': '.$module->title);
                    $resources[] = self::makeUrlResource('slides', $title, $item->href);
                } else if ( $item->type == 'assignment' && isset($item->href) ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment Specification');
                    $resources[] = self::makeUrlResource('assignment', $title, $item->href);
                } else if ( $item->type == 'solution' && isset($item->href) ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment Solution');
                    $resources[] = self::makeUrlResource('solution', $title, $item->href);
                } else if ( $item->type == 'reference' && isset($item->href) ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Reference');
                    $resources[] = self::makeUrlResource('reference', $title, $item->href);
                }
            }
        } else {
            // Process legacy arrays only if items is not present
            if ( isset($module->carousel) ) {
                foreach($module->carousel as $carousel ) {
                    $resources[] = self::makeUrlResource('video',$carousel->title,
                        'https://www.youtube.com/watch?v='.urlencode($carousel->youtube));
                }
            }
            if ( isset($module->videos) ) {
                foreach($module->videos as $video ) {
                    $resources[] = self::makeUrlResource('video',$video->title,
                        'https://www.youtube.com/watch?v='.urlencode($video->youtube));
                }
            }
            if ( isset($module->slides) ) {
                $resources[] = self::makeUrlResource('slides',__('Slides').': '.$module->title, $module->slides);
            }
            if ( isset($module->assignment) ) {
                $resources[] = self::makeUrlResource('assignment','Assignment Specification', $module->assignment);
            }
            if ( isset($module->solution) ) {
                $resources[] = self::makeUrlResource('solution','Assignment Solution', $module->solution);
            }
            if ( isset($module->references) ) {
                foreach($module->references as $reference ) {
                    if ( !isset($reference->title) || ! isset($reference->href) ) continue;
                    $resources[] = self::makeUrlResource('reference',$reference->title, $reference->href);
                }
            }
        }
        return $resources;
    }

    public function renderBadges($allgrades, $buffer=false)
    {
        ob_start();
        global $CFG, $OUTPUT;
        echo('<h1>'.$this->lessons->title."</h1>\n");
        $display = '';
        $displayname = U::get($_SESSION, 'displayname');
        $email = U::get($_SESSION, 'email');
        if ( $displayname ) $display .=  $displayname;
        if ( $email ) {
            if ( U::strlen($display) > 0 ) {
                $display .= ' (';
                $display .= $email;
                $display .= ')';
            } else {
                $display = $email;
            }
        }
        if (U::strlen($display) > 0 ) {
            echo("<p>".__("Student:")." ".$display."</p>\n");
        }
        $awarded = array();
?>
<ul class="nav nav-tabs">
  <li class="active"><a href="#home" data-toggle="tab" aria-expanded="true">Progress</a></li>
  <li class=""><a href="#profile" data-toggle="tab" aria-expanded="false">Badges Awarded</a></li>
</ul>
<div id="myTabContent" class="tab-content">
  <div class="tab-pane fade active in" id="home">
<?php
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        foreach($this->lessons->badges as $badge) {
            $threshold = $badge->threshold;
            $count = 0;
            $total = 0;
            $scores = array();
            foreach($badge->assignments as $resource_link_id) {
                $score = 0;
                if ( isset($allgrades[$resource_link_id]) ) $score = 100*$allgrades[$resource_link_id];
                $scores[$resource_link_id] = $score;
                $total = $total + $score;
                $count = $count + 1;
            }
            $max = $count * 100;
            $progress = $max <= 0 ? 100 : intval(($total / $max)*100);
            $kind = 'danger';
            if ( $progress < 5 ) $progress = 5;
            if ( $progress > 5 ) $kind = 'warning';
            if ( $progress > 50 ) $kind = 'info';
            if ( $progress >= $threshold*100 ) {
                $kind = 'success';
                $awarded[] = $badge;
            }
            echo('<tr><td class="info">');
            if ( ! isset($CFG->badge_url) ) {
                echo('<i class="fa fa-certificate" aria-hidden="true" style="padding-right: 5px;"></i>');
            } else {
                $image = $CFG->badge_url . '/' . $badge->image;
                echo('<img src="'.$image.'" style="width: 4rem;"/> ');
            }
            echo($badge->title);
            echo('</td><td class="info" style="width: 30%; min-width: 200px;">');
            echo('<div class="progress">');
            echo('<div class="progress-bar progress-bar-'.$kind.'" style="width: '.$progress.'%"></div>');
            echo('</div>');
            echo("</td></tr>\n");
            foreach($badge->assignments as $resource_link_id) {
                $score = 0;
                if ( isset($allgrades[$resource_link_id]) ) $score = 100*$allgrades[$resource_link_id];
                $progress = intval($score*100);
                $kind = 'danger';
                if ( $progress < 5 ) $progress = 5;
                if ( $progress > 5 ) $kind = 'warning';
                if ( $progress > 50 ) $kind = 'info';
                if ( $progress >= 100 ) $kind = 'success';
                $module = $this->getModuleByRlid($resource_link_id);
                $lti = $this->getLtiByRlid($resource_link_id);

                echo('<tr><td>');

                $rest_path = U::rest_path();
                $href= "Missing ". $resource_link_id;
                if ($module != null )  $href = $rest_path->parent . '/lessons/' . urlencode($module->anchor);

                $badge_title = "Missing ". $resource_link_id;
                if ( $lti != null ) $badge_title = $lti->title;

                echo('<a href="'.$href.'">');
                echo('<i class="fa fa-square-o text-info" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                echo($badge_title."</a>\n");
                echo('</td><td style="width: 30%; min-width: 200px;">');
                echo('<a href="'.$href.'">');
                echo('<div class="progress">');
                echo('<div class="progress-bar progress-bar-'.$kind.'" style="width: '.$progress.'%"></div>');
                echo('</div>');
                echo('</a>');
                echo("</td></tr>\n");
            }
        }
        echo('</tbody></table>'."\n");
?>
  </div>
  <div class="tab-pane fade" id="profile">
<p></p>
<?php
    if ( count($awarded) < 1 ) {
        echo("<p>No badges have been awarded yet.</p>");
    } else if ( !isset($_SESSION['id']) || ! isset($_SESSION['context_id']) ) {
        echo("<p>You must be logged in to see your badges.</p>\n");
    } else {
        // Check badge configuration before attempting to encrypt
        if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ||
             !isset($CFG->badge_path) || empty($CFG->badge_path) ||
             !isset($CFG->badge_url) || empty($CFG->badge_url) ||
             !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
            echo("<div class=\"alert alert-warning\">\n");
            echo("<h3>Badge Configuration Required</h3>\n");
            echo("<p>The following badge configuration parameters are missing or not set in your <code>config.php</code>:</p>\n");
            echo("<ul>\n");
            if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ) {
                echo("<li><code>\$CFG->badge_encrypt_password</code></li>\n");
            }
            if ( !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
                echo("<li><code>\$CFG->badge_assert_salt</code></li>\n");
            }
            if ( !isset($CFG->badge_path) || empty($CFG->badge_path) ) {
                echo("<li><code>\$CFG->badge_path</code></li>\n");
            }
            if ( !isset($CFG->badge_url) || empty($CFG->badge_url) ) {
                echo("<li><code>\$CFG->badge_url</code></li>\n");
            }
            echo("</ul>\n");
            echo("<h4>How to Configure Badges</h4>\n");
            echo("<p>To enable badge functionality, add the following to your <code>config.php</code> file:</p>\n");
            echo("<pre>\n");
            echo("// Badge generation settings - once you set these values to something\n");
            echo("// other than false and start issuing badges - don't change these or\n");
            echo("// existing badge images that have been downloaded from the system\n");
            echo("// will be invalidated.\n");
            echo("\$CFG->badge_encrypt_password = \"somethinglongwithhex387438758974987\";\n");
            echo("\$CFG->badge_assert_salt = \"mediumlengthhexstring\";\n");
            echo("\n");
            echo("// This folder contains the badge images\n");
            echo("\$CFG->badge_path = \$CFG->dirroot . '/../bimages';\n");
            echo("\$CFG->badge_url = \$CFG->apphome . '/bimages';\n");
            echo("</pre>\n");
            echo("<p>For more information, see <code>config-dist.php</code> in your Tsugi installation.</p>\n");
            echo("</div>\n");
        } else {
            echo("<ul style=\"list-style: none;\">\n");
            foreach($awarded as $badge) {
                echo("<li><p>");
                $code = basename($badge->image,'.png');
                $decrypted = $_SESSION['id'].':'.$code.':'.$_SESSION['context_id'];
                $encrypted = bin2hex(AesOpenSSL::encrypt($decrypted, $CFG->badge_encrypt_password));
                echo('<a href="'.$CFG->wwwroot.'/assertions/'.$encrypted.'.html" target="_blank">');
                echo('<img src="'.$CFG->wwwroot.'/badges/images/'.$encrypted.'.png" width="90"></a>');
                echo($badge->title);
                echo("</p></li>\n");
            }
            echo("</ul>\n");
            echo("<p>These badges contain the official Open Badge metadata.  You can download the badge and\n");
            echo("put it on your own server, or add the badge to a \"badge packpack\".  You could validate the badge\n");
            echo("using <a href=\"http://www.dr-chuck.com/obi-sample/\" target=\"_blank\">A simple badge validator</a>.\n");
            echo("</p>\n");
        }
    }
?>
</div>
<?php
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public function renderDiscussions($buffer=false)
    {
        ob_start();
        global $CFG, $OUTPUT, $PDOX;

        // Flatten the discussions
        $discussions = array();
        if (isset($this->lessons->discussions) ) {
            foreach($this->lessons->discussions as $discussion) {
                $discussions [] = $discussion;
            }
        }

        foreach($this->lessons->modules as $module) {
            if ( isset($module->hidden) && $module->hidden ) continue;
            
            // Check if module uses items array (new format)
            $has_items = isset($module->items) && is_array($module->items) && count($module->items) > 0;
            
            if ( $has_items ) {
                // New format: scan items array for discussion items
                foreach($module->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( isset($item_obj->type) && $item_obj->type == 'discussion' ) {
                        $discussions [] = $item_obj;
                    }
                }
            } else {
                // Legacy format: scan discussions array
                if ( isset($module->discussions) && is_array($module->discussions) ) {
                    foreach($module->discussions as $discussion) {
                        $discussions [] = $discussion;
                    }
                }
            }
        }

        if ( count($discussions) < 1 || ! isset($CFG->tdiscus) || empty($CFG->tdiscus) ) {
            echo('<h1>'.__('Discussions not available')."</h1>\n");
            $ob_output = ob_get_contents();
            ob_end_clean();
            if ( $buffer ) return $ob_output;
            echo($ob_output);
            return;
        }

        echo('<h1>'.__('Discussions:').' '.$this->lessons->title."</h1>\n");

        // TODO: Perhaps the tdiscus service will get promoted to Tsugi
        // but for now we bypass the abstraction and go straight to the source...
        $rows_dict = array();
        if ( U::get($_SESSION,'context_id') > 0 ) {
            $rows = $PDOX->allRowsDie("SELECT L.link_key, L.link_sha256, count(L.link_sha256) AS thread_count,
                CONCAT(CONVERT_TZ(MAX(COALESCE(T.updated_at, T.created_at)), @@session.time_zone, '+00:00'), 'Z')
                AS modified_at
                FROM {$CFG->dbprefix}lti_link AS L
                JOIN {$CFG->dbprefix}tdiscus_thread AS T ON T.link_id = L.link_id
                WHERE L.context_id = :CID
                GROUP BY L.link_sha256
                ORDER BY L.link_sha256",
                array(':CID' => U::get($_SESSION,'context_id'))
            );
            $rows_dict = array();
            foreach($rows as $row) {
                $rows_dict[$row['link_key']] = $row;
            }
            // echo("<pre>\n");var_dump($rows_dict);echo("</pre>\n");
        }

        $launchable = U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email');

        echo('<ul class="tsugi-lessons-module-discussions-ul"> <!-- start of discussions -->'."\n");
        foreach($discussions as $discussion ) {
            $resource_link_title = $discussion->title;
            $rest_path = U::rest_path();
            $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $discussion->resource_link_id;
            $info = "";
            $row = U::get($rows_dict, $discussion->resource_link_id);
            if ( $row ) {
                $info = '<br/>'.$row['thread_count'].' '.__('threads'). ' - '.__('last post').
                    ' <time class="timeago" datetime="'.$row['modified_at'].'">'.$row['modified_at'].'</time>'.
                    "\n";
            }

            echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">'."\n");
            if ( $launchable ) {
                echo('<a href="'.$launch_path.'">'.htmlentities($discussion->title).'</a>'.$info."\n");
            } else {
                echo(htmlentities($resource_link_title).' ('.__('Login Required').')'.$info."\n");
            }
            echo("</li>\n");
        }
        echo("</ul><!-- end of discussions -->\n");


        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }
    public function footer($buffer=false)
    {
        global $CFG;
        ob_start();
        if ( $this->isSingle() ) {
// http://bxslider.com/examples/video
?>
<script>
$(document).ready(function() {
    $('.w3schools-overlay').on('click', function(event) {
        if ( event.target.id == event.currentTarget.id ) {
            // Sop our embedded YouTube Players
            labnolStopPlayers();
            // https://stackoverflow.com/questions/4071872/html5-video-force-abort-of-buffering
            // https://stackoverflow.com/a/34058996
            $('.w3schools-overlay audio, video').each(function (i,e) {
                    var tmp_src = this.src;
                    var playtime = this.currentTime;
                    this.src = '';
                    this.load();
                    this.src = tmp_src;
                    this.currentTime = playtime;

            });
            event.target.style.display = 'none';
        } else {
            event.stopPropagation();
        }
    })
});
</script>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/plugins/jquery.fitvids.js">
</script>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.js">
</script>
<script>
$(document).ready(function() {
    $('.bxslider').bxSlider({
        video: true,
        useCSS: false,
        adaptiveHeight: false,
        slideWidth: "350px",
        infiniteLoop: false,
        maxSlides: 2
    });
});
</script>
<?php
        } else { // isSingle()
// https://github.com/LinZap/jquery.waterfall
?>
<script type="text/javascript" src="<?= $CFG->staticroot ?>/js/waterfall-light.js"></script>
<script>
$(function(){
    $('#box').waterfall({refresh: 0})
});
</script>
<?php
        }
        if ( isset($this->lessons->footers) && is_array($this->lessons->footers) ) {
            foreach($this->lessons->footers as $footer) {
                $footer = self::expandLink($footer);
                echo($footer);
                echo("\n");
            }
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);

    } // end footer

    /**
     * Check if a setting value is in a resource in a Lesson
     *
     * This solves the problems that (a) most LMS systems do not handle
     * custom well for Common Cartridge Imports and (b) some systems
     * do not handle custom at all when links are installed via
     * ContentItem.  Canvas has this problem for sure and others might
     * as well.
     *
     * The solution is to add the resource link from the Lesson as a GET
     * parameter on the launchurl URL to be a fallback:
     *
     * https://../mod/zap/?inherit=assn03
     *
     * Say the tool has custom key of "exercise" that it wants a default
     * for when the tool has not yet been configured.  First we check
     * if the LMS sent us a custom parameter and use it if present.
     *
     * If not, load up the LTI launch for the resource link id (assn03)
     * in the above example and see if there is a custom parameter set
     * in that launch and assume it was passed to us.
     *
     * Sample call:
     *
     *     $assn = Settings::linkGet('exercise');
     *     if ( ! $assn || ! isset($assignments[$assn]) ) {
     *         $rlid = isset($_GET['inherit']) ? $_GET['inherit'] : false;
     *         if ( $rlid && isset($CFG->lessons) ) {
     *             $l = new Lessons($CFG->lessons);
     *             $assn = $l->getCustomWithInherit($rlid, 'exercise');
     *         } else {
     *             $assn = LTIX::ltiCustomGet('exercise');
     *         }
     *         Settings::linkSet('exercise', $assn);
     *     }
     *
     */
    public function getCustomWithInherit($key, $rlid=false) {
        global $CFG;

        $custom = LTIX::ltiCustomGet($key);
        if ( U::strlen($custom) > 0 ) return $custom;

        if ( $rlid === false ) return false;
        $lti = $this->getLtiByRlid($rlid);
        if ( isset($lti->custom) ) foreach($lti->custom as $custom ) {
            if (isset($custom->key) && isset($custom->value) && $custom->key == $key ) {
                return $custom->value;
            }
        }
        return false;
    }

    /**
     * Get icon class for an item type
     */
    private static function getItemTypeIcon($type) {
        $icons = array(
            'video' => 'fa-play-circle',
            'reference' => 'fa-external-link',
            'discussion' => 'fa-comments',
            'lti' => 'fa-puzzle-piece',
            'assignment' => 'fa-file-text',
            'slide' => 'fa-file-powerpoint-o',
            'solution' => 'fa-unlock',
            'text' => 'fa-file-text-o',
            'header' => 'fa-header'
        );
        return isset($icons[$type]) ? $icons[$type] : 'fa-circle';
    }

    /**
     * Get background color for an item type icon
     */
    private static function getItemTypeColor($type) {
        $colors = array(
            'video' => '#dc3545',
            'reference' => '#17a2b8',
            'discussion' => '#ffc107',
            'lti' => '#28a745',
            'assignment' => '#fd7e14',
            'slide' => '#6f42c1',
            'solution' => '#6c757d',
            'text' => '#6c757d',
            'header' => 'transparent'
        );
        return isset($colors[$type]) ? $colors[$type] : '#6c757d';
    }

    /**
     * Render an icon for an item type with styling
     */
    private static function renderItemIcon($type) {
        $icon = self::getItemTypeIcon($type);
        $color = self::getItemTypeColor($type);
        $iconColor = ($type === 'discussion') ? '#333' : 'white';
        echo('<span class="tsugi-item-type-icon tsugi-item-type-'.$type.'" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 3px; font-size: 14px; background-color: '.$color.'; margin-right: 8px; vertical-align: middle;">');
        echo('<i class="fa '.$icon.'" aria-hidden="true" style="color: '.$iconColor.';"></i>');
        echo('</span>');
    }

    /**
     * Render a single item from the items array
     */
    public function renderItem($item, $module, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        if ( !isset($item->type) ) {
            return; // Skip items without a type
        }
        
        $type = $item->type;
        
        switch($type) {
            case 'header':
                $this->renderItemHeader($item);
                break;
            case 'text':
                $this->renderItemText($item);
                break;
            case 'video':
                $this->renderItemVideo($item, $nostyle);
                break;
            case 'slide':
                $this->renderItemSlide($item, $nostyle);
                break;
            case 'reference':
                $this->renderItemReference($item, $nostyle);
                break;
            case 'discussion':
                $this->renderItemDiscussion($item, $module, $nostyle);
                break;
            case 'lti':
                $this->renderItemLti($item, $module, $nostyle);
                break;
            // Legacy plural types - convert to singular and re-render (backward compatibility)
            case 'videos':
            case 'references':
            case 'discussions':
            case 'ltis':
            case 'slides':
                // Convert plural to singular and render items
                $singular_type = rtrim($type, 's'); // Remove trailing 's'
                if (isset($item->items) && is_array($item->items)) {
                    foreach($item->items as $subitem) {
                        $subitem_obj = is_array($subitem) ? (object)$subitem : $subitem;
                        if (!isset($subitem_obj->type)) $subitem_obj->type = $singular_type;
                        $this->renderItem($subitem_obj, $module, $nostyle);
                    }
                } else if ($type == 'slides' && (isset($item->href) || isset($item->url))) {
                    // Handle single slide object (legacy format)
                    $item->type = 'slide';
                    $this->renderItem($item, $module, $nostyle);
                }
                break;
            case 'assignment':
                $this->renderItemAssignment($item, $nostyle);
                break;
            case 'solution':
                $this->renderItemSolution($item, $nostyle);
                break;
            case 'chapters':
                $this->renderItemChapters($item);
                break;
            case 'carousel':
                $this->renderItemCarousel($item, $nostyle);
                break;
            default:
                // Unknown type, skip
                break;
        }
    }

    /**
     * Render a header item
     */
    private function renderItemHeader($item) {
        $level = isset($item->level) ? intval($item->level) : 2;
        $text = isset($item->text) ? $item->text : (isset($item->title) ? $item->title : '');
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<h{$level}{$class}>".htmlentities($text)."</h{$level}>\n");
    }

    /**
     * Render a text item
     */
    private function renderItemText($item) {
        $text = isset($item->text) ? $item->text : (isset($item->content) ? $item->content : '');
        $tag = isset($item->tag) ? $item->tag : 'p';
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<{$tag}{$class}>".$text."</{$tag}>\n");
    }

    /**
     * Render a single video item
     */
    private function renderItemVideo($item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        $media_folder = $CFG->getExtension('media_folder', null);
        $media_base = $CFG->getExtension('media_base', null);
        $media_file = isset($item->media) ? $item->media : null;
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-video">');
        
        if ( is_string($media_file) && is_string($media_base) && is_string($media_folder) &&
            file_exists($media_folder . '/' . $media_file) ) {
            $media_path = $media_base . '/' . $media_file;
            echo('<a href="'.$media_path.'" target="_blank" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('video');
            echo(htmlentities($item->title).'</a>');
        } else {
            $youtube = isset($item->youtube) ? $item->youtube : '';
            if ( $youtube ) {
                $yurl = 'https://www.youtube.com/watch?v='.$youtube;
                static $lecno = 0;
                $lecno = $lecno + 1;
                $navid = md5($lecno.$yurl);
?>
<div id="<?= $navid ?>" class="w3schools-overlay">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <div class="youtube-player" data-id="<?= $youtube ?>"></div>
  </div>
</div>
<a href="#" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';" style="display: inline-flex; align-items: center;"><?php self::renderItemIcon('video'); ?><?= htmlentities($item->title) ?></a>
<?php
            } else {
                echo(htmlentities($item->title));
            }
        }
        echo("</li>\n");
    }

    /**
     * Render slides item (can be single slide or array)
     */
    private function renderItemSlides($item, $nostyle=false) {
        if (isset($item->href) || isset($item->url)) {
            // Single slide
            $this->renderItemSlide($item, $nostyle);
        } else if (isset($item->items) && is_array($item->items)) {
            // Multiple slides
            $singular = 'slide';
            $plural = 'slides';
            echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$plural.'">');
            echo("<p>");
            $slidestitle = isset($item->title) ? $item->title : __('Slides');
            echo(htmlentities($slidestitle));
            echo("</p>");
            echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
            foreach($item->items as $slide) {
                $slide_obj = is_array($slide) ? (object)$slide : $slide;
                $slide_obj->type = 'slide';
                $this->renderItemSlide($slide_obj, $nostyle);
            }
            echo("</ul></li>\n");
        }
    }

    /**
     * Render a single slide item
     */
    private function renderItemSlide($item, $nostyle=false) {
        $slide_title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : basename(isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '')));
        $slide_href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $slide_href = self::expandLink($slide_href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-slide">');
        echo('<span class="tsugi-lessons-module-slide-link">');
        echo('<a href="'.$slide_href.'" target="_blank" class="tsugi-lessons-link" typeof="oer:SupportingMaterial" style="display: inline-flex; align-items: center;">');
        self::renderItemIcon('slide');
        echo(htmlentities($slide_title)."</a>\n");
        echo("</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a reference item
     */
    private function renderItemReference($item, $nostyle=false) {
        $title = isset($item->title) ? $item->title : '';
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $href = self::expandLink($href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-reference">');
        echo('<span class="tsugi-lessons-module-reference-link">');
        echo('<a href="'.$href.'" target="_blank" class="tsugi-lessons-link" typeof="oer:SupportingMaterial" style="display: inline-flex; align-items: center;">');
        self::renderItemIcon('reference');
        echo(htmlentities($title)."</a>\n");
        echo("</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a discussion item
     */
    private function renderItemDiscussion($item, $module, $nostyle=false) {
        global $CFG;
        
        $resource_link_title = isset($item->title) ? $item->title : $module->title;
        $launch = isset($item->launch) ? $item->launch : '';
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : '';
        
        // Not logged in
        if ( ! isset($_SESSION['secret']) ) {
            echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">');
            self::renderItemIcon('discussion');
            echo(htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
            echo("\n</li>\n");
            return;
        }
        
        // Logged in
        if ( isset($CFG->tdiscus) && $CFG->tdiscus && 
            U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
            && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            if ( $nostyle ) {
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">');
                echo('<span style="display: inline-flex; align-items: center;">');
                self::renderItemIcon('discussion');
                echo(htmlentities($resource_link_title).' (Login Required)');
                echo('</span><br/>'."\n");
                $discussionurl = U::add_url_parm($launch, 'inherit', $resource_link_id);
                echo('<span style="color:green">'.htmlentities($discussionurl)."</span>\n");
                echo("\n</li>\n");
                return;
            }
            
            $rest_path = U::rest_path();
            $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $resource_link_id;
            echo('<li class="tsugi-lessons-module-discussion">');
            echo('<a href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('discussion');
            echo(htmlentities($resource_link_title).'</a></li>'."\n");
        }
    }

    /**
     * Render an LTI item
     */
    private function renderItemLti($item, $module, $nostyle=false) {
        global $CFG;
        
        $resource_link_title = isset($item->title) ? $item->title : $module->title;
        $launch = isset($item->launch) ? $item->launch : '';
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : '';
        $target = isset($item->target) ? $item->target : false;
        
        // Not logged in
        if ( ! isset($_SESSION['secret']) ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">');
            echo('<span style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('lti');
            echo(htmlentities($resource_link_title).' ('.__('Login Required').')');
            echo('</span><br/>'."\n");
            echo("\n</li>\n");
            return;
        }
        
        // Logged in
        if ( U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
            && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            if ( $nostyle ) {
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">');
                echo('<span style="display: inline-flex; align-items: center;">');
                self::renderItemIcon('lti');
                echo(htmlentities($resource_link_title).' (Login Required)');
                echo('</span><br/>'."\n");
                $ltiurl = U::add_url_parm($launch, 'inherit', $resource_link_id);
                echo('<span style="color:green">'.htmlentities($ltiurl)."</span>\n");
                echo("\n</li>\n");
                return;
            }
            
            $rest_path = U::rest_path();
            $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $resource_link_id;
            $title = isset($item->title) ? $item->title : "Autograder";
            
            echo('<li class="tsugi-lessons-module-lti">');
            echo('<a');
            if ( $target == "_blank" ) echo(' target="_blank" onclick="alert(\'Link will open in a new browser tab...\');" ');
            echo(' href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('lti');
            echo(htmlentities($title).'</a></li>'."\n");
        }
    }

    /**
     * Render an assignment item
     */
    private function renderItemAssignment($item, $nostyle=false) {
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = self::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            echo(__('Assignment Specification').':');
            self::nostyleUrl(__('Assignment Specification'), $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            echo('<a href="'.$url.'" target="_blank" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('assignment');
            echo(__('Assignment Specification').'</a></li>'."\n");
        }
    }

    /**
     * Render a solution item
     */
    private function renderItemSolution($item, $nostyle=false) {
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = self::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            echo(__('Assignment Solution').':');
            self::nostyleUrl(__('Assignment Solution'), $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            echo('<a href="'.$url.'" target="_blank" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon('solution');
            echo(__('Assignment Solution').'</a></li>'."\n");
        }
    }

    /**
     * Render chapters item
     */
    private function renderItemChapters($item) {
        $chapters = isset($item->text) ? $item->text : (isset($item->chapters) ? $item->chapters : '');
        echo('<li typeof="SupportingMaterial">'.__('Chapters').': '.htmlentities($chapters).'</li>'."\n");
    }

    /**
     * Render carousel item
     */
    private function renderItemCarousel($item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        if (!isset($item->items) || !is_array($item->items)) {
            return;
        }
        
        $videotitle = __(self::getSetting('videos-title', 'Videos'));
        echo($nostyle ? $videotitle . ': <ul>' : '<ul class="bxslider">'."\n");
        foreach($item->items as $video) {
            echo('<li>');
            if ( $nostyle ) {
                echo(htmlentities($video->title)."<br/>");
                $yurl = 'https://www.youtube.com/watch?v='.$video->youtube;
                self::nostyleUrl($video->title, $yurl);
            } else {
                $OUTPUT->embedYouTube($video->youtube, $video->title);
            }
            echo('</li>');
        }
        echo("</ul>\n");
    }

}
