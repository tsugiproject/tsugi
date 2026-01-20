<?php


namespace Koseu\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\LTI;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

class Topics {

    const ROUTE = '/topics';

    const REDIRECT = 'koseu_controllers_topics';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Topics@get');
        $app->router->get($prefix.'/', 'Topics@get');
        $app->router->get('/'.self::REDIRECT, 'Topics@get');
        $app->router->get($prefix.'/{anchor}', 'Topics@get');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Topics::launch($app, $anchor);
        });
    }

    public function get(Request $request, $anchor=null)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->topics) ) {
            die_with_error_log('Cannot find topics.json ($CFG->topics)');
        }

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        // Load the Topic
        $t = new \Tsugi\UI\Topics($CFG->topics,$anchor);

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        $t->header();
        echo('<div class="container">');
        $t->render();
        echo('</div>');
        $OUTPUT->footerStart();
        $t->footer();
        $OUTPUT->footerEnd();
    }

    public static function launch(Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ( $redirect_path == '') $redirect_path = '/';

        if ( ! isset($CFG->topics) ) {
            $app->tsugiFlashError(__('Cannot find topics.json ($CFG->topics)'));
            return redirect($redirect_path);
        }

        /// Load the Topic
        $l = new \Tsugi\UI\Topics($CFG->topics);
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot load topics.'));
            return redirect($redirect_path);
        }

        $topic = $l->getTopicByRlid($anchor);
        if ( ! $topic ) {
            $app->tsugiFlashError(__('Cannot find topic resource link id'));
            return redirect($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return redirect($redirect_path);
        }

        // Check that the session has the minimums...
        if ( isset($topic->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
            && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            // All good
        } else {
            $app->tsugiFlashError(__('Missing session data required for launch'));
            return redirect($redirect_path);
        }

        $resource_link_title = isset($lti->title) ? $lti->title : $topic->title;
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

        $return_url = $path->parent . '/' . str_replace('_launch', '', $path->controller) . '/' . $topic->anchor;
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
