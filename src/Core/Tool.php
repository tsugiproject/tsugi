<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\PS;
use \Tsugi\UI\Output;

/**
 * Provide support for a Tsugi Tool
 */
class Tool {

    public $analytics = true;

    function __construct() {
        // TODO
    }

    public function run() {
        // Check for a few special cases
        $rest_path = U::rest_path();
        if ( file_exists('register.php') && $rest_path->controller == 'register.json') {
            $reg = $this->loadRegistration();
            if ( is_string($reg) ) {
                OUTPUT::jsonError($reg);
                return;
            }
            OUTPUT::headerJson();
            echo(json_encode($reg,JSON_PRETTY_PRINT));
            return;
        }

        // Make PHP paths pretty .../install => install.php
        $router = new \Tsugi\Util\FileRouter();
        $file = $router->fileCheck();
        if ( $file ) {
            require_once($file);
            return;
        }

        // Make a Tsugi Application
        $launch = \Tsugi\Core\LTIX::requireData();
        $app = new \Tsugi\Silex\Application($launch);

        // Add some routes
        if ( $this->analytics ) {
            \Tsugi\Controllers\Analytics::routes($app);
        }

        $app->run();
    }

    public static function loadRegistration() {
        global $CFG;
        require_once('register.php');
        $rest_path = U::rest_path();
        $url = $CFG->wwwroot . $rest_path->parent;

        if ( ! isset($REGISTER_LTI2) ) return "Error in register.php";
        if ( ! is_array($REGISTER_LTI2) ) return "Error in register.php";

        if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) &&
            isset($REGISTER_LTI2['description']) ) {
            // We are happy
        } else {
            error_log("Missing required name, short_name, and description in ".$tool_folder);
            return "Missing required name, short_name, and description in ".$tool_folder;
        }

        // Make an icon URL
        $fa_icon = isset($REGISTER_LTI2['FontAwesome']) ? $REGISTER_LTI2['FontAwesome'] : false;
        if ( $fa_icon !== false ) {
            $REGISTER_LTI2['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
        }
        $REGISTER_LTI2['url'] = $url;

        $screen_shots = U::get($REGISTER_LTI2, 'screen_shots');
        if ( is_array($screen_shots) && count($screen_shots) > 0 ) {
            $new = array();
            foreach($screen_shots as $screen_shot ) {
                $ps = PS::s($screen_shot);
                if ( $ps->startsWith('http://') || $ps->startsWith('https://') ) {
                    $new[] = $url;
                } else {
                    $new[] = $url . '/' . $screen_shot;
                }
            }
            $REGISTER_LTI2['screen_shots'] = $new;
        }

        return $REGISTER_LTI2;
    }
}
