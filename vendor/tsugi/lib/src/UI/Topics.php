<?php

namespace Tsugi\UI;

use Tsugi\Core\LTIX;
use Tsugi\Crypt\AesCtr;
use Tsugi\Util\U;


class Topics {

    /**
     * All the topics
     */
    public $topics;

    /**
     * The course object
     */
    public $course;

    /**
     * The individual topic
     */
    public $topic;

    /*
     ** The anchor of the topic
     */
    public $anchor;

    /*
     ** The position of the topic
     */
    public $topicposition;

    /*
     * The instition's warpwire url if used e.g. https://udayton.warpwire.com
     */
    public $warpwire_baseurl = false;


    /**
     * Index by resource_link
     */
    public $resource_links;

    public function __construct($name='topics.json', $anchor=null, $index=null)
    {
        global $CFG;

        $json_str = file_get_contents($name);
        $course = json_decode($json_str);
        $this->resource_links = array();

        if (isset($CFG->warpwire_baseurl)) {
            $this->warpwire_baseurl = $CFG->warpwire_baseurl;
        }

        if ( $course === null ) {
            echo("<pre>\n");
            echo("Problem parsing topics.json: ");
            echo(json_last_error_msg());
            echo("\n");
            echo($json_str);
            die();
        }

        // Demand that every topic have required elments
        foreach($course->topics as $topic) {
            if ( !isset($topic->title) ) {
                die_with_error_log('All topics in a course must have a title');
            }
            if ( !isset($topic->anchor) ) {
                die_with_error_log('All topics must have an anchor: '.$topic->title);
            }
        }

        // Filter topics based on login
        if ( !isset($_SESSION['id']) ) {
            $filtered_topics = array();
            $filtered = false;
            foreach($course->topics as $topic) {
                if ( isset($topic->login) && $topic->login ) {
                    $filtered = true;
                    continue;
                }
                $filtered_topics[] = $topic;
            }
            if ( $filtered ) $course->topics = $filtered_topics;
        }
        $this->course = $course;

        // Pretty up the data structure

        for($i=0;$i<count($this->course->topics);$i++) {
            if ( isset($this->course->topics[$i]->videos) ) self::adjustArray($this->course->topics[$i]->videos);
            if ( isset($this->course->topics[$i]->lti) ) self::adjustArray($this->course->topics[$i]->lti);
        }

        // Make sure resource links are unique and remember them
        foreach($this->course->topics as $topic) {
            if ( isset($topic->lti) ) {
                $ltis = $topic->lti;
                if ( ! is_array($ltis) ) $ltis = array($ltis);
                foreach($ltis as $lti) {
                    if ( ! isset($lti->title) ) {
                        die_with_error_log('Missing lti title in topic:'. $topic->title);
                    }
                    if ( ! isset($lti->resource_link_id) ) {
                        die_with_error_log('Missing resource link in Topics '. $lti->title);
                    }
                    if (isset($this->resource_links[$lti->resource_link_id]) ) {
                        die_with_error_log('Duplicate resource link in Topics '. $lti->resource_link_id);
                    }
                    $this->resource_links[$lti->resource_link_id] = $topic->anchor;
                }
            }
        }

        $anchor = isset($_GET['anchor']) ? $_GET['anchor'] : $anchor;
        $index = isset($_GET['index']) ? $_GET['index'] : $index;

        // Search for the selected anchor or index position
        $count = 0;
        $topic = false;
        if ( $anchor || $index ) {
            foreach($course->topics as $topic) {
                $count++;
                if ( $anchor !== null && isset($topic->anchor) && $anchor != $topic->anchor ) continue;
                if ( $index !== null && $index != $count ) continue;
                if ( $anchor == null && isset($topic->anchor) ) $anchor = $topic->anchor;
                $this->topic = $topic;
                $this->topicposition = $count;
                if ( $topic->anchor ) $this->anchor = $topic->anchor;
            }
        }

        return true;
    }

    /*
     ** Load up the JSON from the file
     **/

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
     * emit the header material
     */
    public static function header($buffer=false) {
        global $CFG;
        ob_start();
        ?>
        <style type="text/css">
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
            div.videoWrapper {
                position: relative;
                padding-bottom: 56.25%; /* 16:9 */
                padding-top: 25px;
                height: 0;
                margin-bottom: 1em;
                -webkit-box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
                -moz-box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
                box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
            }
            div.videoWrapper:after,  div.videoWrapper:before {
                content:"";
                position:absolute;
                z-index:-1;
                -webkit-box-shadow:0 0 20px rgba(0,0,0,0.8);
                -moz-box-shadow:0 0 20px rgba(0,0,0,0.8);
                box-shadow:0 0 20px rgba(0,0,0,0.8);
                top:0;
                bottom:0;
                left:10px;
                right:10px;
                -moz-border-radius:100px / 10px;
                border-radius:100px / 10px;
            }
            div.videoWrapper:after {
                right:10px;
                left:auto;
                -webkit-transform:skew(8deg) rotate(3deg);
                -moz-transform:skew(8deg) rotate(3deg);
                -ms-transform:skew(8deg) rotate(3deg);
                -o-transform:skew(8deg) rotate(3deg);
                transform:skew(8deg) rotate(3deg);
            }
            div.videoWrapper iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            .panel-heading .accordion-toggle:before {
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                margin-right: 0.75rem;
                content: "\f078";
            }
            .panel-heading .accordion-toggle.collapsed:before {
                /* symbol for "collapsed" panels */
                content: "\f054";    /* adjust as needed, taken from bootstrap.css */
            }
            .list-group-item {
                border: none;
                padding-top: 4px;
                padding-bottom: 4px;
            }
            .navbar-inverse .nav > li > a.disabled,
            .navbar-inverse .nav > li > a.disabled:hover {
                cursor: not-allowed;
                border-bottom-color: transparent;
                opacity: 0.6;
            }
            .nav-popover+.popover .popover-content {
                font-style: italic;
                color: var(--text-light);
                width: max-content;
                width: -moz-max-content;
                width: -webkit-max-content;
            }
            .topiccard {
                height: 400px
                margin-bottom: 15px;
                vertical-align: top;
                white-space: normal;
                cursor: pointer;
                background-color: #fff;
                border:1px solid rgba(0,0,0,.2);
                box-shadow: 0 8px 6px -6px #111;
                overflow: hidden;
            }
            .topiccard-container {
                padding-top: 56.25%;
                background-color: var(--primary);
                background-position: initial initial;
                background-repeat: initial initial;
                position: relative !important;
                width: 100% !important;
                z-index: 0 !important;
            }
            .topiccard-header {
                position: absolute !important;
                top: 0px !important;
                bottom: 0px !important;
                left: 0px !important;
                right: 0px !important;
                height: 100% !important;
                width: 100% !important;
            }
            .topiccard-image {
                background-position: center center;
                background-repeat: no-repeat;
                background-size: cover;
                height: 100%;
                width: 100%;
                position: relative;
            }
            .topiccard-finished {
                position: absolute;
                left: 10px;
                bottom: 10px;
                background: rgba(0,0,0,.7);
                color: #fff;
                font-size: 13px;
                line-height: 14px;
                padding: 3px 5px 3px 5px;
                font-weight: 700;
            }
            .topiccard-time {
                position: absolute;
                right: 10px;
                bottom: 10px;
                background: rgba(0,0,0,.7);
                color: #fff;
                font-size: 13px;
                line-height: 14px;
                padding: 3px 5px 3px 5px;
                font-weight: 700;
            }
            .topiccard-info {
                color: var(--pimary);
                padding: 1rem;
            }
            .topiccard-info h4 {
                margin: .64rem 0;
                line-height: 1.59rem;
            }
            .topiccard-info p {
                line-height: 1.5rem;
                overflow: hidden;
            }
            h4.category {
                height: 65px;
                overflow: hidden;
            }
        </style>
        <?php
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public static function getUrlResources($topic) {
        $resources = array();
        $topics = new Topics();
        if ( isset($topic->videos) ) {
            foreach($topic->videos as $video ) {
                if (isset($video->youtube)) {
                    $resources[] = self::makeUrlResource('video',$video->title,
                        'https://www.youtube.com/watch?v='.urlencode($video->youtube));
                } else if (isset($video->warpwire) && ($topics->warpwire_baseurl)) {
                    $resources[] = self::makeUrlResource('video',$video->title,
                        $topics->warpwire_baseurl.'/w/'.urlencode($video->warpwire));
                }
            }
        }
        return $resources;
    }

    public static function makeUrlResource($type,$title,$url) {
        global $CFG;
        $RESOURCE_ICONS = array(
            'video' => 'fa-video-camera'
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

    /**
     * Get a topic associated with an anchor
     */
    public function getTopicByAnchor($anchor)
    {
        foreach($this->course->topics as $topic) {
            if ( $topic->anchor == $anchor) return $topic;
        }
        return null;
    }

    public function render($buffer=false) {
        if ( $this->isSingle() ) {
            return $this->renderSingle($buffer);
        } else {
            return $this->renderAll($buffer);
        }
    }

    /*
     ** render
     */

    /**
     * Indicate we are in a single topic
     */
    public function isSingle() {
        return ( $this->anchor !== null || $this->topicposition !== null );
    }

    /*
     * A Nostyle URL Link with title
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

        $topic = $this->topic;

        if ( $nostyle && isset($_SESSION['gc_count']) ) {
            ?>
            <script src="https://apis.google.com/js/platform.js" async defer></script>
            <div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
                <iframe name="iframe-frame" style="height:200px" id="iframe-frame"
                        src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
            </div>
            <?php
        }
        $all = U::get_rest_parent();
        ?>
        <ul class="breadcrumb">
            <li><a href="<?=$all?>"><?= $this->course->title ?></a></li>
            <li class="active"><?= $topic->title ?></li>
        </ul>
        <?php
        echo ('<div class="container-fluid" style="padding-bottom: 60px;">');
        echo('<h1 property="oer:name" style="margin:0;font-weight:500;line-height: 2.64rem;margin: .795rem 0;"><small class="text-muted" style="font-weight:300;">'.$topic->category.'</small><br />'.$topic->title."</h1>\n");
        $topicnurl = $CFG->apphome . U::get_rest_path();
        if ( $nostyle ) {
            self::nostyleUrl($topic->title, $topicnurl);
            echo("<hr/>\n");
        }
        if (isset($topic->author)) {
            echo '<h4 style="font-weight:500;margin-top:0;" class="text-muted">By '.$topic->author.'</h4>';
        }
        ?>
        <div class="row">
            <div class="col-lg-9 col-md-10 col-sm-11">
                <?php
                if (isset($topic->content)) {
                    echo ('<p style="font-size: 1.26rem;font-weight:300;margin-top:0.613rem;">'.$topic->content.'</p>');
                }
                if ( isset($topic->videos) ) {
                    $videos = $topic->videos;
                    foreach($videos as $video ) {
                        if (isset($video->youtube)) {
                            if ( $nostyle ) {
                                echo(htmlentities($video->title)."<br/>");
                                $yurl = 'https://www.youtube.com/watch?v='.$video->youtube;
                                self::nostyleUrl($video->title, $yurl);
                            } else {
                                $OUTPUT->embedYouTube($video->youtube, $video->title);
                            }
                        } else if (isset($video->warpwire) && $this->warpwire_baseurl) {
                            echo '<div class="videoWrapper">';
                            echo('<iframe src="'.$this->warpwire_baseurl.'/w/'.$video->warpwire.'/?share=0&title=0" frameborder="0" scrolling="0" allow="autoplay; encrypted-media; fullscreen;  picture-in-picture;" allowfullscreen></iframe>');
                            echo '</div>';
                        } else if (isset($video->embed)) {
                            echo '<div class="videoWrapper">';
                            echo($video->embed);
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-10 col-md-8">
                <?php
                // LTIs not logged in
                if ( isset($topic->lti) && ! isset($_SESSION['secret']) ) {
                    echo ('<h4><em>'.__('What Do You Think? (Login Required)').'</em></h4>');
                }

                // LTIs logged in
                if ( isset($topic->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                    && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
                {
                    $ltis = $topic->lti;
                    foreach($ltis as $lti ) {
                        $resource_link_title = isset($lti->title) ? $lti->title : $topic->title;

                        $rest_path = U::rest_path();
                        $launch_path = $rest_path->parent . '/' . $rest_path->controller . '_launch/' . $lti->resource_link_id;
                        $title = isset($lti->title) ? $lti->title : $topic->title;
                        ?>
                        <div class="videoWrapper">
                            <iframe src="<?=$launch_path?>" style="border:none;width:100%;"></iframe>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php
        echo('</div>');

        if ( $nostyle ) {
            $styleoff = U::get_rest_path() . '?nostyle=no';
            echo('<p><a href="'.$styleoff.'">');
            echo(__('Turn styling back on'));
            echo("</a>\n");
        }

        if ( !isset($topic->anchor) ) $topic->anchor = $this->topicposition;

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    /*
     * render a topic
     */

    public static function nostyleUrl($title, $url) {
        echo('<a href="'.$url.'" target="_blank" typeof="oer:SupportingMaterial">'.htmlentities($url)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
            echo(' data-title="'.htmlentities($title).'" ');
            echo('></div>');
        }
    } // End of renderSingle

    public function renderAll($buffer=false)
    {
        ob_start();
        echo('<div>'."\n");
        echo('<h1>'.$this->course->title."</h1>\n");
        echo('<p class="lead">'.$this->course->description."</p>\n");
        echo('<div id="topics" class="row">'."\n");
        foreach($this->course->topics as $topic) {
            ?>
            <div class="col-sm-4">
                <div class="topiccard">
                    <a href="<?=U::get_rest_path() . '/' . urlencode($topic->anchor)?>">
                        <div class="topiccard-container">
                            <div class="topiccard-header">
                                <div class="topiccard-image" style="background-image: url('<?=$topic->thumbnail?>');">
                                    <div class="topiccard-time">
                                        <span class="far fa-clock" aria-hidden="true"></span> <?=$topic->time?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="topiccard-info">
                            <h4 class="category"><small><?=$topic->category?></small><br /><?=$topic->title?></h4>
                            <p><?=$topic->description?></p>
                        </div>
                    </a>
                </div>
            </div>
            <?php
        }
        echo('</div> <!-- end row -->'."\n");
        echo('</div> <!-- course container -->'."\n");

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    /**
     * Get a topic associated with a resource link ID
     */
    public function getTopicByRlid($resource_link_id)
    {
        foreach($this->course->topics as $topic) {
            if ( ! isset($topic->lti) ) continue;
            foreach($topic->lti as $lti ) {
                if ( $lti->resource_link_id == $resource_link_id) return $topic;
            }
        }
        return null;
    }

    /**
     * Get an LTI associated with a resource link ID
     */
    public function getLtiByRlid($resource_link_id)
    {
        foreach($this->course->topics as $topic) {
            if ( ! isset($topic->lti) ) continue;
            foreach($topic->lti as $lti ) {
                if ( $lti->resource_link_id == $resource_link_id) return $lti;
            }
        }
        return null;
    }

    public function footer($buffer=false)
    {
        global $CFG;
        ob_start();
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);

    } // end footer

    /**
     * Check if a setting value is in a resource in a Topic
     *
     * This solves the problems that (a) most LMS systems do not handle
     * custom well for Common Cartridge Imports and (b) some systems
     * do not handle custom at all when links are installed via
     * ContentItem.  Canvas has this problem for sure and others might
     * as well.
     *
     * The solution is to add the resource link from the Topic as a GET
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
     *         if ( $rlid && isset($CFG->topics) ) {
     *             $l = new Topics($CFG->topics);
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
