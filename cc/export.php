<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;
use \Tsugi\Util\CC_WebLink;

require_once "../config.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

// here we go...
$service = strtolower($CFG->servicename);
$filename = tempnam(sys_get_temp_dir(), $CFG->servicename);
if ( isCli() ) $filename = 'cc.zip';
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("Cannot open $filename\n");
}

if ( ! isCli() ) {
    header( "Content-Type: application/x-zip" );
    header( "Content-Disposition: attachment; filename=\"".$service."_export.zip\"" );
}

$cc_dom = new CC();
$cc_dom->set_title($CFG->context_title);
// $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

function add_url_to_module($zip, $cc_dom, $sub_module, $title, $url) {
    $file = $cc_dom->add_web_link($sub_module, $title);
    $web_dom = new CC_WebLink();
    $web_dom->set_title($title);
    $web_dom->set_url($url, array("target" => "_iframe"));
    $zip->addFromString($file,$web_dom->saveXML());
}

function add_lti_to_module($zip, $cc_dom, $sub_module, $title, $url, $custom=null, $extensions=null) {
    $file = $cc_dom->add_lti_link($sub_module, $title);
    $lti_dom = new CC_LTI();
    $lti_dom->set_title($title);
    // $lti_dom->set_description('Create a single SQL table and insert some records.');
    $lti_dom->set_secure_launch_url($url);
    if ( $custom != null ) foreach($custom as $key => $value) {
        $lti_dom->set_custom($key,$value);
    }
    if ( $extensions != null ) foreach($extensions as $key => $value) {
        $lti_dom->set_extension($key,$value);
    }
    $zip->addFromString($file,$lti_dom->saveXML());
}

function absolute_url($url) {
    global $CFG;
    if ( strpos($url,'http://') === 0 ) return $url;
    if ( strpos($url,'https://') === 0 ) return $url;
    $retval = $CFG->apphome;
    if ( strpos($url,'/') !== 0 ) $retval .= '/';
    $retval .= $url;
    return $retval;
}

foreach($l->lessons->modules as $module) {
    if ( isCli() ) echo("title=$module->title\n");
    $sub_module = $cc_dom->add_module($module->title);

    if ( isset($module->videos) ) {
        foreach($module->videos as $video ) {
            $title = 'Video: '.$video->title;
            $url = 'https://www.youtube.com/embed/' . $video->youtube;
            add_url_to_module($zip, $cc_dom, $sub_module, $title, $url);
        }
    }

    if ( isset($module->slides) ) {
        $url = absolute_url($module->slides);
        $title = 'Slides';
        add_url_to_module($zip, $cc_dom, $sub_module, $title, $url);
    }

    if ( isset($module->assignment) ) {
        $url = absolute_url($module->assignment);
        $title = 'Assignment';
        add_url_to_module($zip, $cc_dom, $sub_module, $title, $url);
    }

    if ( isset($module->solution) ) {
        $url = absolute_url($module->solution);
        $title = 'Solution';
        add_url_to_module($zip, $cc_dom, $sub_module, $title, $url);
    }

    if ( isset($module->references) ) {
        foreach($module->references as $reference ) {
            $title = 'Reference: '.$reference->title;
            $url = absolute_url($reference->href);
            add_url_to_module($zip, $cc_dom, $sub_module, $title, $url);
        }
    }

    if ( isset($module->lti) ) {
        foreach($module->lti as $lti ) {
            $title = isset($lti->title) ? $lti->title : $module->title;
            $title = 'Tool: '.$title;
            $custom_arr = array();
            if ( isset($lti->custom) ) {
                foreach($lti->custom as $custom) {
                    if ( isset($custom->value) ) {
                        $custom_arr[$custom->key] = $custom->value;
                    }
                    if ( isset($custom->json) ) {
                        $custom_arr[$custom->key] = json_encode($custom->json);
                    }
                }
            }
            $endpoint = absolute_url($lti->launch);
            $extensions = array('apphome' => $CFG->apphome);
            add_lti_to_module($zip, $cc_dom, $sub_module, $title, $endpoint, $custom_arr, $extensions);
        }
    }
}

$zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

$zip->close();

if ( isCli() ) {
    echo("\nCLI run: Left zip file on cc.zip\n\n");
    return;
}

// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



