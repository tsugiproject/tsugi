<?php

namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

class Lessons {

    const ROUTE = '/lessons';

    const REDIRECT = 'koseu_controllers_lessons';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Lessons@get');
        $app->router->get($prefix.'/', 'Lessons@get');
        $app->router->get('/'.self::REDIRECT, 'Lessons@get');
        $app->router->get($prefix.'/{anchor}', 'Lessons@get');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Lessons::launch($app, $anchor);
        });
    }

    public function get(Request $request, $anchor=null)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons,$anchor);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->header();
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
            return redirect($redirect_path);
        }

        /// Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot load lessons.'));
            return redirect($redirect_path);
        }

        $module = $l->getModuleByRlid($anchor);
        if ( ! $module ) {
            $app->tsugiFlashError(__('Cannot find module resource link id'));
            return redirect($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return redirect($redirect_path);
        }

        // Check that the session has the minimums...
        if ( isset($module->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            // All good
        } else {
            $app->tsugiFlashError(__('Missing session data required for launch'));
            return redirect($redirect_path);
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

        $endpoint = $lti->launch;
        $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
            "Finish Launch", $CFG->wwwroot, $CFG->servicename);

        $content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */);
        print($content);
        return "";
    }

}
