<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Lessons extends Tool {

    const ROUTE = '/lessons';

    const REDIRECT = 'tsugi_controllers_lessons';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Lessons@get');
        $app->router->get($prefix.'/', 'Lessons@get');
        $app->router->get('/'.self::REDIRECT, 'Lessons@get');
        $app->router->get($prefix.'/{anchor}', 'Lessons@get');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Lessons::launch($app, $anchor);
        });
        $app->router->get($prefix.'_tool/analytics', 'Lessons@analytics');
        $app->router->get($prefix.'_tool/author', 'Lessons@author');
        $app->router->post($prefix.'_tool/author', 'Lessons@author');
    }

    public function get(Request $request, $anchor=null)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Record learner analytics (synthetic lti_link in this context) - only if logged in with context
        if ( U::get($_SESSION,'id') && isset($_SESSION['context_id']) ) {
            $this->lmsRecordLaunchAnalytics('/lessons', 'Lessons');
        }

        // Check if user is instructor/admin for analytics button (handles missing id/context gracefully)
        $is_instructor = false;
        $is_admin = false;
        if ( isset($_SESSION['id']) && isset($_SESSION['context_id']) ) {
            $is_instructor = $this->isInstructor();
            $is_admin = $this->isAdmin();
        }
        $show_analytics = $is_instructor || $is_admin;

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        // Load the Lesson - use Lessons2 if enabled
        $use_lessons2 = $CFG->getExtension('lessons2_enable', false);
        if ( $use_lessons2 ) {
            $l = new \Tsugi\UI\Lessons2($CFG->lessons, $anchor);
        } else {
            $l = new \Tsugi\UI\Lessons($CFG->lessons, $anchor);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->header();
        if ( $show_analytics || ($use_lessons2 && !$l->isSingle() && $CFG->localhost() && $is_instructor) ) {
            $tool_home = $this->toolHome(self::ROUTE);
            
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;">');
            if ( $show_analytics ) {
                $analytics_url = $tool_home . '_tool/analytics';
                echo('<a href="'.htmlspecialchars($analytics_url).'" class="btn btn-default" style="margin-right: 5px;"><span class="glyphicon glyphicon-signal"></span> Analytics</a>');
            }
            // Show Author button if: using Lessons2, at top level (not in a module), on localhost, and instructor
            if ( $use_lessons2 && !$l->isSingle() && $CFG->localhost() && $is_instructor ) {
                $author_url = $tool_home . '_tool/author';
                echo('<a href="'.htmlspecialchars($author_url).'" class="btn btn-default"><span class="glyphicon glyphicon-edit"></span> Author</a>');
            }
            echo('</span>');
        }
        echo('<div class="container">');
        $l->render();
        echo('</div>');
        $OUTPUT->footerStart();
        $l->footer();
        $OUTPUT->footerEnd();
    }

    public static function launch(Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ( $redirect_path == '') $redirect_path = '/';

        if ( ! isset($CFG->lessons) ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return new RedirectResponse($redirect_path);
        }

        /// Load the Lesson - use Lessons2 if enabled (same as get() method)
        $use_lessons2 = $CFG->getExtension('lessons2_enable', false);
        if ( $use_lessons2 ) {
            $l = new \Tsugi\UI\Lessons2($CFG->lessons);
        } else {
            $l = new \Tsugi\UI\Lessons($CFG->lessons);
        }
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot load lessons.'));
            return new RedirectResponse($redirect_path);
        }

        $module = $l->getModuleByRlid($anchor);
        if ( ! $module ) {
            $app->tsugiFlashError(__('Cannot find module resource link id'));
            return new RedirectResponse($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        // Check that the session has the minimums...
        if ( U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            // All good
        } else {
            $app->tsugiFlashError(__('Missing session data required for launch'));
            return new RedirectResponse($redirect_path);
        }

        $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
        $key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
        $secret = false;
        if ( isset($_SESSION['secret']) ) {
            $secret = LTIX::decrypt_secret($_SESSION['secret']);
        }

        $resource_link_id = $lti->resource_link_id;
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

        $return_url = $path->parent . '/' . str_replace('_launch', '', $path->controller) . '/' . $module->anchor;
        $parms['launch_presentation_return_url'] = $return_url;

        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        if ( isset($_SESSION[$sess_key]) ) {
            // $parms['ext_tsugi_top_nav'] = $_SESSION[$sess_key];
        }

        $form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
        $parms['ext_lti_form_id'] = $form_id;

        // Expand {apphome} placeholder to actual URL (like the old launch.php does)
        $endpoint = $l->expandLink($lti->launch);
        $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
            "Finish Launch", $CFG->wwwroot, $CFG->servicename);

        // Check for launch debug flag - when true, form will pause before auto-submitting
        $debug = $CFG->getExtension('launch_debug', false);
        $content = LTI::postLaunchHTML($parms, $endpoint, $debug);
        print($content);
        return "";
    }

    public function analytics(Request $request)
    {
        // Use the base class method to show analytics
        return $this->showAnalytics(self::ROUTE, 'Lessons');
    }

    public function author(Request $request)
    {
        global $CFG, $OUTPUT;

        LTIX::getConnection();
        header('Content-Type: text/html; charset=utf-8');
        // Session is already started by LTIX, no need to call session_start() again

        // Security checks
        if ( ! $CFG->localhost() ) {
            die('This tool only works on localhost');
        }

        if ( ! $this->isInstructor() ) {
            die('This tool requires instructor permission');
        }

        // Get the lessons file path
        if ( ! isset($CFG->lessons) ) {
            die('Cannot find lessons file ($CFG->lessons)');
        }

        $lessons_file = $CFG->lessons;

        // Handle AJAX requests
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            header('Content-Type: application/json');
            
            $action = U::get($_POST, 'action');
            
            if ( $action === 'save' ) {
                $data = U::get($_POST, 'data');
                if ( ! $data ) {
                    echo json_encode(['success' => false, 'error' => 'No data provided']);
                    exit;
                }
                
                // Decode the JSON data
                $lessons_data = json_decode($data, true);
                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
                    exit;
                }
                
                // Pretty print JSON with 4-space indentation
                $json_output = json_encode($lessons_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                
                // Write to file
                $result = @file_put_contents($lessons_file, $json_output);
                if ( $result === false ) {
                    echo json_encode(['success' => false, 'error' => 'Failed to write file']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'message' => 'File saved successfully']);
                exit;
            }
            
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
        }

        // Load the lessons file
        if ( ! file_exists($lessons_file) ) {
            die('Lessons file not found: ' . htmlentities($lessons_file));
        }

        $lessons_json = file_get_contents($lessons_file);
        $lessons_data = json_decode($lessons_json, true);

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            die('Error parsing JSON: ' . json_last_error_msg());
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        
        // Back to Lessons link - use toolHome() to get full path (e.g., /dj4e/lessons)
        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;">');
        echo('<a href="'.htmlspecialchars($back_url).'" class="btn btn-default" id="back-to-lessons-btn">');
        echo('<span class="glyphicon glyphicon-arrow-left"></span> Back to Lessons');
        echo('</a>');
        echo('</span>');
        
        // Output the author interface - all content embedded directly (no /lms references)
        $lessons_json = json_encode($lessons_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Embed the full author interface HTML/CSS/JavaScript
        // This replaces the old /lms/lessons/author.php file completely
        echo $this->getAuthorInterfaceHtml($lessons_data, $lessons_file, $lessons_json);
        
        $OUTPUT->footer();
        return "";
    }

    /**
     * Get the HTML for the lesson authoring interface
     * 
     * All content is embedded directly - no references to /lms files
     * 
     * @param array $lessons_data The parsed lessons JSON data
     * @param string $lessons_file Path to the lessons file
     * @param string $lessons_json JSON-encoded lessons data for JavaScript
     * @return string The complete HTML/CSS/JavaScript for the author interface
     */
    private function getAuthorInterfaceHtml($lessons_data, $lessons_file, $lessons_json)
    {
        // Escape values for HTML output
        $lessons_title = htmlspecialchars($lessons_data['title'] ?? 'Untitled');
        $lessons_file_escaped = htmlspecialchars($lessons_file);
        
        // Use the base class template rendering method with standardized convention
        // Template path: templates/Lessons/author_interface.inc.php
        return $this->renderTemplate('author_interface', [
            'lessons_title' => $lessons_title,
            'lessons_file_escaped' => $lessons_file_escaped,
            'lessons_json' => $lessons_json
        ]);
    }

}
