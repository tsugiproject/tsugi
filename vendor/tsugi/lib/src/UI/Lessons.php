<?php

namespace Tsugi\UI;

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
    public static function header() {
        global $CFG;
?>
<style>
    .card {
        border: 1px solid black;
        margin: 5px;
        padding: 5px;
        min-height: 8em;
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
    }

    /*
     ** Load up the JSON from the file
     **/
    public function __construct($name='lessons.json')
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
        foreach($lessons->badges as $badge) {
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
                self::makeAbsolute($this->lessons->modules[$i]->slides);
            }
            if ( isset($this->lessons->modules[$i]->assignment) ) {
                self::makeAbsolute($this->lessons->modules[$i]->assignment);
            }
            if ( isset($this->lessons->modules[$i]->solution) ) {
                self::makeAbsolute($this->lessons->modules[$i]->solution);
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

        $anchor = isset($_GET['anchor']) ? $_GET['anchor'] : null;
        $index = isset($_GET['index']) ? $_GET['index'] : null;

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
     * Make a path absolute
     */
    public static function makeAbsolute(&$path) {
        global $CFG;
        if ( strpos($path,'http://') === 0 ) {
            return;
        } else if ( strpos($path,'https://') === 0 ) {
            return;
        } else {
            if ( strpos('/', $path) !== 0 ) $path = '/' . $path;
            $path =$CFG->apphome . $path;
        }
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
            if ( is_string($entry[$i]) ) self::makeAbsolute($entry[$i]);
            if ( isset($entry[$i]->href) && is_string($entry[$i]->href) ) self::makeAbsolute($entry[$i]->href);
            if ( isset($entry[$i]->launch) && is_string($entry[$i]->launch) ) self::makeAbsolute($entry[$i]->launch);
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
    public function render() {
        if ( $this->isSingle() ) {
            $this->renderSingle();
        } else {
            $this->renderAll();
        }
    }

    /*
     * render a lesson
     */
    public function renderSingle() {
        global $CFG, $OUTPUT;
        $module = $this->module;
            echo('<div style="float:right; padding-left: 5px; vertical-align: text-top;"><ul class="pager">'."\n");
            $disabled = ($this->position == 1) ? ' disabled' : '';
            if ( $this->position == 1 ) {
                echo('<li class="previous disabled"><a href="#" onclick="return false;">&larr; Previous</a></li>'."\n");
            } else {
                $prev = 'index='.($this->position-1);
                if ( isset($this->lessons->modules[$this->position-2]->anchor) ) {
                    $prev = 'anchor='.$this->lessons->modules[$this->position-2]->anchor;
                }
                echo('<li class="previous"><a href="lessons.php?'.$prev.'">&larr; Previous</a></li>'."\n");
            }
            echo('<li><a href="lessons.php">All ('.$this->position.' / '.count($this->lessons->modules).')</a></li>');
            if ( $this->position >= count($this->lessons->modules) ) {
                echo('<li class="next disabled"><a href="#" onclick="return false;">&rarr; Next</a></li>'."\n");
            } else {
                $next = 'index='.($this->position+1);
                if ( isset($this->lessons->modules[$this->position]->anchor) ) {
                    $next = 'anchor='.$this->lessons->modules[$this->position]->anchor;
                }
                echo('<li class="next"><a href="lessons.php?'.$next.'">&rarr; Next</a></li>'."\n");
            }
            echo("</ul></div>\n");
            echo('<h1>'.$module->title."</h1>\n");
    
            if ( isset($module->videos) ) {
                $videos = $module->videos;
                echo('<ul class="bxslider">'."\n");
                foreach($videos as $video ) {
                    echo('<li>');
                    $OUTPUT->embedYouTube($video->youtube, $video->title);
/*
                    echo('<div class="youtube-player" data-id="'.$video->youtube.'"></div>');
                    echo('<iframe src="https://www.youtube.com/embed/'.
                        $video->youtube.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen '.
                        ' alt="'.htmlentities($video->title).'"></iframe>'."\n");
*/
                    echo('</li>');
                }
                echo("</ul>\n");
            }
    
            if ( isset($module->description) ) {
                echo('<p>'.$module->description."</p>\n");
            }
    
            echo("<ul>\n");
            if ( isset($module->slides) ) {
                echo('<li><a href="'.$module->slides.'" target="_blank">Slides</a></li>'."\n");
            }
            if ( isset($module->chapters) ) {
                echo('<li>Chapters: '.$module->chapters.'</a></li>'."\n");
            }
            if ( isset($module->assignment) ) {
                echo('<li><a href="'.$module->assignment.'" target="_blank">Assignment Specification</a></li>'."\n");
            }
            if ( isset($module->solution) ) {
                echo('<li><a href="'.$module->solution.'" target="_blank">Assignment Solution</a></li>'."\n");
            }
            if ( isset($module->references) ) {
                if ( count($module->references) > 0 ) {
                    echo("<li>References:<ul>\n");
                    foreach($module->references as $reference ) {
                        echo('<li><a href="'.$reference->href.'" target="_blank">'.
                            $reference->title."</a></li>\n");
                    }
                    echo("</ul></li>\n");
                } else {
                    echo('<li>Reference: <a href="'.
                        $module->references->href.'" target="_blank">'.
                        $module->references->title."</a></li>\n");
                }
            }
    
            if ( isset($module->lti) && isset($_SESSION['secret']) ) {
                $ltis = $module->lti;
    
                if ( count($ltis) > 1 ) echo("<li>Tools:<ul> <!-- start of ltis -->\n");
                $count = 0;
                foreach($ltis as $lti ) {
                    $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
                    $secret = isset($_SESSION['secret']) ? $_SESSION['secret'] : false;
    
                    if ( isset($lti->resource_link_id) ) {
                        $resource_link_id = $lti->resource_link_id;
                    } else {
                        $resource_link_id = 'resource:';
                        if ( $this->anchor != null ) $resource_link_id .= $this->anchor . ':';
                        if ( $this->position != null ) $resource_link_id .= $this->position . ':';
                        if ( $count > 0 ) {
                            $resource_link_id .= '_' . $count;
                        }
                        $resource_link_id .= md5($CFG->context_title);
                    }
                    $count++;
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    $parms = array(
                        'lti_message_type' => 'basic-lti-launch-request',
                        'resource_link_id' => $resource_link_id,
                        'resource_link_title' => $resource_link_title,
                        'tool_consumer_info_product_family_code' => 'tsugi',
                        'tool_consumer_info_version' => '1.1',
                        'context_id' => $_SESSION['context_key'],
                        'context_label' => $CFG->context_title,
                        'context_title' => $CFG->context_title,
                        'user_id' => $_SESSION['user_key'],
                        'lis_person_name_full' => $_SESSION['displayname'],
                        'lis_person_contact_email_primary' => $_SESSION['email'],
                        'roles' => 'Learner'
                    );
                    if ( isset($_SESSION['avatar']) ) $parms['user_image'] = $_SESSION['avatar'];
    
                    if ( isset($lti->custom) ) {
                        foreach($lti->custom as $custom) {
                            if ( isset($custom->value) ) {
                                $parms['custom_'.$custom->key] = $custom->value;
                            }
                            if ( isset($custom->json) ) {
                                $parms['custom_'.$custom->key] = json_encode($custom->json);
                            }
                        }
                    }
    
                    $return_url = $CFG->getCurrentUrl();
                    if ( $this->anchor ) $return_url .= '?anchor='.urlencode($this->anchor);
                    elseif ( $this->position ) $return_url .= '?index='.urlencode($this->position);
                    $parms['launch_presentation_return_url'] = $return_url;
    
                    if ( isset($_SESSION['tsugi_top_nav']) ) {
                        $parms['ext_tsugi_top_nav'] = $_SESSION['tsugi_top_nav'];
                    }
    
                    $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
                    $parms['ext_lti_form_id'] = $form_id;
    
                    $endpoint = $lti->launch;
                    $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
                        "Finish Launch", $CFG->product_instance_guid, $CFG->servicename);
    
                    $content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */, '_pause');
                    $title = isset($lti->title) ? $lti->title : "Autograder";
                    echo('<li><a href="#" onclick="document.'.$form_id.'.submit();return false">'.htmlentities($title).'</a></li>'."\n");
                    echo("<!-- Start of content -->\n");
                    print($content);
                    echo("<!-- End of content -->\n");
                }
    
                if ( count($ltis) > 1 ) echo("</li></ul><!-- end of ltis -->\n");
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
    } // End of renderSingle

    public function renderAll()
    {
        echo('<h1>'.$this->lessons->title."</h1>\n");
        echo('<p>'.$this->lessons->description."</p>\n");
        echo('<div id="box">'."\n");
        $count = 0;
        foreach($this->lessons->modules as $module) {
	    if ( isset($module->login) && $module->login && !isset($_SESSION['id']) ) continue;
            $count++;
            echo('<div class="card">'."\n");
            if ( isset($module->anchor) ) {
                $href = 'lessons.php?anchor='.htmlentities($module->anchor);
            } else {
                $href = 'lessons.php?index='.$count;
            }
            if ( isset($module->icon) ) {
                echo('<i class="fa '.$module->icon.' fa-2x" aria-hidden="true" style="float: left; padding-right: 5px;"></i>');
            }
            echo('<a href="'.$href.'">'."\n");
            echo('<p>'.$count.': '.$module->title."</p>\n");
            if ( isset($module->description) ) {
                $desc = $module->description;
                if ( strlen($desc) > 100 ) $desc = substr($desc, 0, 100) . " ...";
                echo('<p>'.$desc."</p>\n");
            }
            echo("</a></div>\n");
        }
        echo('</div> <!-- box -->'."\n");
    }

    public function renderAssignments($allgrades)
    {
        echo('<h1>'.$this->lessons->title."</h1>\n");
        echo('<table class="table table-striped table-hover "><tbody>'."\n");
        $count = 0;
        foreach($this->lessons->modules as $module) {
            $count++;
            if ( !isset($module->lti) ) continue;
            echo('<tr><td class="info" colspan="3">'."\n");
            $href = 'lessons.php?anchor='.htmlentities($module->anchor);
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
        $retval->thumbnail = $CFG->staticroot.'/font-awesome-4.4.0/png/'.str_replace('fa-','',$retval->icon).'.png';

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

    public function renderBadges($allgrades)
    {
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
            echo('<i class="fa fa-certificate" aria-hidden="true" style="padding-right: 5px;"></i>');
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
                echo('<a href="lessons.php?anchor='.$module->anchor.'">');
                echo('<i class="fa fa-square-o text-info" aria-hidden="true" style="label label-success; padding-right: 5px;"></i>');
                echo($lti->title."</a>\n");
                echo('</td><td style="width: 30%; min-width: 200px;">');
                echo('<a href="lessons.php?anchor='.$module->anchor.'">');
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
<?php
    if ( count($awarded) < 1 ) {
        echo("<p>No badges have been awarded yet.</p>");
    } else {
        echo("</ul>\n");
        foreach($awarded as $badge) {
            echo("<li>");
            $code = basename($badge->image,'.png');
            $decrypted = $_SESSION['id'].':'.$code.':'.$_SESSION['context_id'];
            $encrypted = bin2hex(AesCtr::encrypt($decrypted, $CFG->badge_encrypt_password, 256));
            echo('<a href="badges/images/'.$encrypted.'.png" target="_blank">');
            echo('<img src="badges/images/'.$encrypted.'.png" width="90"></a>');
            echo($badge->title);
            echo("</li>\n");
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
    }

    public function footer()
    {
        global $CFG;
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

    } // end footer

}
