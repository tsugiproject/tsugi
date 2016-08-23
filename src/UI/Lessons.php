<?php

namespace Tsugi\UI;

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;


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
        $json_str = file_get_contents($name);
        $lessons = json_decode($json_str);

        if ( $lessons === null ) {
            echo("<pre>\n");
            echo("Problem parsing lessons.json: ");
            echo(json_last_error_msg());
            echo("\n");
            echo($json_str);
            die();
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
        $anchor = isset($_GET['anchor']) ? $_GET['anchor'] : null;
        $index = isset($_GET['index']) ? $_GET['index'] : null;

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

    /*
     ** indicate we are in a single lesson
     */
    public function isSingle() {
        return ( $this->anchor !== null || $this->position !== null );
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
        global $CFG;
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
                if ( ! is_array($videos) ) $videos = array($videos);
                echo('<ul class="bxslider">'."\n");
                foreach($videos as $video ) {
                    echo('<li><iframe src="https://www.youtube.com/embed/'.
                        $video->youtube.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen '.
                        ' alt="'.htmlentities($video->title).'"></iframe>'."\n");
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
                if ( is_array($module->references) ) {
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
                if ( ! is_array($ltis) ) {
                    $ltis = array($ltis);
                }
    
                if ( count($ltis) > 1 ) echo("<li>Tools:<ul> <!-- start of ltis -->\n");
                $count = 0;
                foreach($ltis as $lti ) {
                    $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
                    $secret = isset($_SESSION['secret']) ? $_SESSION['secret'] : false;
    
                    $resource_link_id = 'resource:';
                    if ( $this->anchor != null ) $resource_link_id .= $this->anchor . ':';
                    if ( $this->position != null ) $resource_link_id .= $this->position . ':';
                    if ( $count > 0 ) {
                        $resource_link_id .= '_' . $count;
                    }
                    $count++;
                    $resource_link_id .= md5($CFG->context_title);
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    $parms = array(
                        'lti_message_type' => 'basic-lti-launch-request',
                        'resource_link_id' => $resource_link_id,
                        'resource_link_title' => $resource_link_title,
                        'tool_consumer_info_product_family_code' => 'tsugi',
                        'tool_consumer_info_version' => '1.1',
                        'context_id' => 'course:'.md5($CFG->context_title),
                        'context_label' => $CFG->context_title,
                        'context_title' => $CFG->context_title,
                        'user_id' => 'google:'.md5($_SESSION['email']),
                        'user_image' => $_SESSION['avatar'],
                        'lis_person_name_full' => $_SESSION['displayname'],
                        'lis_person_contact_email_primary' => $_SESSION['email'],
                        'roles' => 'Learner'
                    );
    
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
    
                    $endpoint = $CFG->apphome . '/' . $lti->launch;
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
