<?php

namespace Tsugi\UI;

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\AesCtr;


class Lessons {

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
     * emit the header material
     */
    public static function header($buffer=false) {
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
</style>
<link rel="stylesheet" href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css" type="text/css"/>
<?php
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
            if ( isset($this->lessons->modules[$i]->videos) ) self::adjustArray($this->lessons->modules[$i]->videos);
            if ( isset($this->lessons->modules[$i]->references) ) self::adjustArray($this->lessons->modules[$i]->references);
            if ( isset($this->lessons->modules[$i]->lti) ) self::adjustArray($this->lessons->modules[$i]->lti);

            // Non arrays
            if ( isset($this->lessons->modules[$i]->slides) ) {
                U::absolute_url_ref($this->lessons->modules[$i]->slides);
            }
            if ( isset($this->lessons->modules[$i]->assignment) ) {
                U::absolute_url_ref($this->lessons->modules[$i]->assignment);
            }
            if ( isset($this->lessons->modules[$i]->solution) ) {
                U::absolute_url_ref($this->lessons->modules[$i]->solution);
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
                if ( $anchor == null && isset($module->anchor) ) $anchor = $module->anchor;
                $this->module = $mod;
                $this->position = $count;
                if ( $mod->anchor ) $this->anchor = $mod->anchor;
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
            if ( is_string($entry[$i]) ) U::absolute_url_ref($entry[$i]);
            if ( isset($entry[$i]->href) && is_string($entry[$i]->href) ) U::absolute_url_ref($entry[$i]->href);
            if ( isset($entry[$i]->launch) && is_string($entry[$i]->launch) ) U::absolute_url_ref($entry[$i]->launch);
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
     * Get an LTI associated with a resource link ID
     */
    public function getLtiByRlid($resource_link_id)
    {
        foreach($this->lessons->modules as $mod) {
            if ( ! isset($mod->lti) ) continue;
            foreach($mod->lti as $lti ) {
                if ( $lti->resource_link_id == $resource_link_id) return $lti;
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
            if ( ! isset($mod->lti) ) continue;
            foreach($mod->lti as $lti ) {
                if ( $lti->resource_link_id == $resource_link_id) return $mod;
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

    /*
     * A Nostyle URL Link with title
     */
    public static function nostyleUrl($title, $url) {
        echo('<a href="'.$url.'" target="_blank" typeof="oer:SupportingMaterial">'.htmlentities($url)."</a>\n");
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

	if ( $nostyle && isset($_SESSION['gc_count']) ) {
?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<?php
        }
            echo('<div typeof="oer:Lesson" style="float:right; padding-left: 5px; vertical-align: text-top;"><ul class="pager">'."\n");
            $disabled = ($this->position == 1) ? ' disabled' : '';
            $all = U::get_rest_parent();
            if ( $this->position == 1 ) {
                echo('<li class="previous disabled"><a href="#" onclick="return false;">&larr; Previous</a></li>'."\n");
            } else {
                $prev = $all . '/' . urlencode($this->lessons->modules[$this->position-2]->anchor);
                echo('<li class="previous"><a href="'.$prev.'">&larr; Previous</a></li>'."\n");
            }
            echo('<li><a href="'.$all.'">All ('.$this->position.' / '.count($this->lessons->modules).')</a></li>');
            if ( $this->position >= count($this->lessons->modules) ) {
                echo('<li class="next disabled"><a href="#" onclick="return false;">&rarr; Next</a></li>'."\n");
            } else {
                $next = $all . '/' . urlencode($this->lessons->modules[$this->position]->anchor);
                echo('<li class="next"><a href="'.$next.'">&rarr; Next</a></li>'."\n");
            }
            echo("</ul></div>\n");
            echo('<h1 property="oer:name">'.$module->title."</h1>\n");
            $lessonurl = $CFG->apphome . U::get_rest_path();
            if ( $nostyle ) {
                self::nostyleUrl($module->title, $lessonurl);
                echo("<hr/>\n");
            }

            if ( isset($module->videos) ) {
                $videos = $module->videos;
                echo($nostyle ? 'Videos: <ul>' : '<ul class="bxslider">'."\n");
                foreach($videos as $video ) {
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
                echo('<p property="oer:description">'.$module->description."</p>\n");
            }

            echo("<ul>\n");
            if ( isset($module->slides) ) {
                if ( $nostyle ) {
                    echo('<li>Slides: ');
                    self::nostyleUrl(__('Slides'), $module->slides);
                    echo('</li>'."\n");
                } else {
                    echo('<li><a href="'.$module->slides.'" typeof="oer:SupportingMaterial" target="_blank">Slides</a></li>'."\n");
                }
            }
            if ( isset($module->chapters) ) {
                echo('<li typeof="SupportingMaterial">Chapters: '.$module->chapters.'</a></li>'."\n");
            }
            if ( isset($module->assignment) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">Assignment Specification:');
                    self::nostyleUrl(__('Assignment Specification'), $module->assignment);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->assignment.'" target="_blank">Assignment Specification</a></li>'."\n");
                }
            }
            if ( isset($module->solution) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">Assignment Solution:');
                    self::nostyleUrl(__('Assignment Solution'), $module->solution);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->solution.'" target="_blank">Assignment Solution</a></li>'."\n");
                }
            }
            if ( isset($module->references) ) {
                if ( count($module->references) > 0 ) {
                    echo('<li typeof="oer:SupportingMaterial">References:<ul>'."\n");
                }
                foreach($module->references as $reference ) {
                    if ( $nostyle ) {
                        echo('<li typeof="oer:SupportingMaterial">');
                        echo(htmlentities($reference->title).' ');
                        self::nostyleUrl($reference->title, $reference->href);
                        echo('</li>'."\n");
                    } else {
                        echo('<li typeof="oer:SupportingMaterial"><a href="'.$reference->href.'" target="_blank">'.
                            $reference->title."</a></li>\n");
                    }
                }
                if ( count($module->references) > 0 ) {
                    echo("</ul></li>\n");
                }
            }

            // LTIs not logged in
            if ( isset($module->lti) && ! isset($_SESSION['secret']) ) {
                $ltis = $module->lti;
                if ( count($ltis) > 1 ) echo('<li typeof="oer:assessment">Tools:<ul> <!-- start of ltis -->'."\n");
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    if ( $nostyle ) {
                        echo('<li typeof="oer:assessment">'.htmlentities($resource_link_title).' (LTI Required) <br/>'."\n");
                        $ltiurl = U::add_url_parm($lti->launch, 'inherit', $lti->resource_link_id);
                        echo('<span style="color:green">'.htmlentities($ltiurl)."</span>\n");
                        echo("\n</li>\n");
                        continue;
                    }
                    echo('<li typeof="oer:assessment">'.htmlentities($resource_link_title).' (Login Required)</li>'."\n");
                }
                if ( count($ltis) > 1 ) echo("</li></ul><!-- end of ltis -->\n");
            }

            // LTIs logged in
            if ( isset($module->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
            {
                $ltis = $module->lti;

                if ( count($ltis) > 1 ) echo("<li>Tools:<ul> <!-- start of ltis -->\n");
                $count = 0;
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    if ( $nostyle ) {
                        echo('<li typeof="oer:assessment">'.htmlentities($resource_link_title).' (LTI Required) <br/>'."\n");
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
                    echo('<li><a href="'.$launch_path.'">'.htmlentities($title).'</a></li>'."\n");
                }

                if ( count($ltis) > 1 ) echo("</li></ul><!-- end of ltis -->\n");
            }

        echo("</ul>\n");

        if ( $nostyle ) {
            $styleoff = U::get_rest_path() . '?nostyle=no';
            echo('<p><a href="'.$styleoff.'">');
            echo(__('Turn styling back on'));
            echo("</a>\n");
        }

        if ( !isset($module->discuss) ) $module->discuss = true;
        if ( !isset($module->anchor) ) $module->anchor = $this->position;
        // For now do not add disqus to each page.
        if ( false && isset($CFG->disqushost) && isset($_SESSION['id']) && $module->discuss ) {
    ?>
<hr/>
<div id="disqus_thread" style="margin-top: 30px;"></div>
<script>

/**
 *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
 *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables */
var disqus_config = function () {
    this.page.url = '<?= $CFG->disqushost ?>';  // Replace PAGE_URL with your page's canonical URL variable
    this.page.identifier = '<?= $module->anchor ?>'; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
};
(function() { // DON'T EDIT BELOW THIS LINE
    var d = document, s = d.createElement('script');
    s.src = '//php-intro.disqus.com/embed.js';
    s.setAttribute('data-timestamp', +new Date());
    (d.head || d.body).appendChild(s);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<?php
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    } // End of renderSingle

    public function renderAll($buffer=false)
    {
        ob_start();
        echo('<div typeof="Course">'."\n");
        echo('<h1>'.$this->lessons->title."</h1>\n");
        echo('<p property="description">'.$this->lessons->description."</p>\n");
        echo('<div id="box">'."\n");
        $count = 0;
        foreach($this->lessons->modules as $module) {
	    if ( isset($module->login) && $module->login && !isset($_SESSION['id']) ) continue;
            $count++;
            echo('<div class="card"><div>'."\n");
            $href = U::get_rest_path() . '/' . urlencode($module->anchor);
            if ( isset($module->icon) ) {
                echo('<i class="fa '.$module->icon.' fa-2x" aria-hidden="true" style="float: left; padding-right: 5px;"></i>');
            }
            echo('<a href="'.$href.'">'."\n");
            echo($count.': '.$module->title."<br clear=\"all\"/>\n");
            if ( isset($module->description) ) {
                $desc = $module->description;
                if ( strlen($desc) > 1000 ) $desc = substr($desc, 0, 1000);
                echo('<br/>'.$desc."\n");
            }
            echo("</a></div></div>\n");
        }
        echo('</div> <!-- box -->'."\n");
        echo('</div> <!-- typeof="Course" -->'."\n");
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public function renderAssignments($allgrades, $buffer=false)
    {
        ob_start();
        echo('<h1>'.$this->lessons->title."</h1>\n");
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        $count = 0;
        foreach($this->lessons->modules as $module) {
            $count++;
            if ( !isset($module->lti) ) continue;
            echo('<tr><td class="info" colspan="3">'."\n");
            $href = U::get_rest_parent() . '/lessons/' . urlencode($module->anchor);
            echo('<a href="'.$href.'">'."\n");
            echo($module->title);
            echo("</td></tr>");
            if ( isset($module->lti) ) {
                foreach($module->lti as $lti) {
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
                        echo("<td>Score: ".(100*$allgrades[$lti->resource_link_id])."</td>");
                    } else {
                        echo("<td>&nbsp;</td>");
                    }

                    echo("</tr>\n");
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
        if ( isset($module->videos) ) {
            foreach($module->videos as $video ) {
                $resources[] = self::makeUrlResource('video',$video->title,
                    'https://www.youtube.com/watch?v='.urlencode($video->youtube));
            }
        }
        if ( isset($module->slides) ) {
            $resources[] = self::makeUrlResource('slides','Slides: '.$module->title, $module->slides);
        }
        if ( isset($module->assignment) ) {
            $resources[] = self::makeUrlResource('assignment','Assignment Specification', $module->assignment);
        }
        if ( isset($module->solution) ) {
            $resources[] = self::makeUrlResource('solution','Assignment Solution', $module->solution);
        }
        if ( isset($module->references) ) {
            foreach($module->references as $reference ) {
                $resources[] = self::makeUrlResource('reference',$reference->title, $reference->href);
            }
        }
        return $resources;
    }

    public function renderBadges($allgrades, $buffer=false)
    {
        ob_start();
        global $CFG;
        echo('<h1>'.$this->lessons->title."</h1>\n");
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
                $href = $rest_path->parent . '/lessons/' . urlencode($module->anchor);

                echo('<a href="'.$href.'">');
                echo('<i class="fa fa-square-o text-info" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                echo($lti->title."</a>\n");
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
        echo("<ul style=\"list-style: none;\">\n");
        foreach($awarded as $badge) {
            echo("<li><p>");
            $code = basename($badge->image,'.png');
            $decrypted = $_SESSION['id'].':'.$code.':'.$_SESSION['context_id'];
            $encrypted = bin2hex(AesCtr::encrypt($decrypted, $CFG->badge_encrypt_password, 256));
            echo('<a href="'.$CFG->wwwroot.'/badges/images/'.$encrypted.'.png" target="_blank">');
            echo('<img src="'.$CFG->wwwroot.'/badges/images/'.$encrypted.'.png" width="90"></a>');
            echo($badge->title);
            echo("</p></li>\n");
        }
        echo("</ul>\n");
?>
<p>These badges contain the official Open Badge metadata.  You can download the badge and
put it on your own server, or add the badge to a "badge packpack".  You could validate the badge
using <a href="http://www.dr-chuck.com/obi-sample/" target="_blank">A simple badge validator</a>.
</p>
<?php
    }
?>
</div>
<?php
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
        if ( strlen($custom) > 0 ) return $custom;

        if ( $rlid === false ) return false;
        $lti = $this->getLtiByRlid($rlid);
        if ( isset($lti->custom) ) foreach($lti->custom as $custom ) {
            if (isset($custom->key) && isset($custom->value) && $custom->key == $key ) {
                return $custom->value;
            }
        }
        return false;
    }

}
