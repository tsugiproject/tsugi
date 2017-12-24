<?php

namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Lessons {

    const ROUTE = '/lessons';

    const REDIRECT = 'koseu_controllers_lessons';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix, 'Koseu\\Controllers\\Lessons::get');
        $app->get($prefix.'/', 'Koseu\\Controllers\\Lessons::get')->bind(self::REDIRECT);
        $app->get($prefix.'/{anchor}', 'Koseu\\Controllers\\Lessons::get');
        $app->get($prefix.'_launch/{anchor}', 'Koseu\\Controllers\\Lessons::launch');
    }

    public function get(Request $request, Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

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

        $context = array();
        $context['head'] = $l->header(true);
        $context['container'] = $l->render(true);
        $context['footer'] = $l->footer(true);

        return $app['twig']->render('@Koseu/Lessons.twig',$context);

    }

    public function launch(Request $request, Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);

        if ( ! isset($CFG->lessons) ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return $app->redirect($redirect_path);
        }

        /// Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        if ( ! $l ) {
            $app->tsugiFlashError(__('Canot load lessons.'));
            return $app->redirect($redirect_path);
        }

        $module = $l->getModuleByRlid($anchor);
        if ( ! $module ) {
            $app->tsugiFlashError(__('Cannot find module resource link id'));
            return $app->redirect($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return $app->redirect($redirect_path);
        }

        // Check that the session has the minimums...
        if ( isset($module->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            // All good
        } else {
            $app->tsugiFlashError(__('Missing session data required for launch'));
            return $app->redirect($redirect_path);
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
            "Finish Launch", $CFG->product_instance_guid, $CFG->servicename);

        $content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */);
        print($content);
        return "";
    }
}
