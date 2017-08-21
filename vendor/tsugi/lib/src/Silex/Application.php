<?php

namespace Tsugi\Silex;

use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/**
 * The Tsugi variant of a Silex Application
 *
 * Returns an augmented Silex Application
 *
 *     <?php
 *     require_once "../config.php";
 *     $launch = \Tsugi\Core\LTIX::requireData();
 *     $app = new \Tsugi\Silex\Application($launch);
 *     $app->get('/', 'AppBundle\\Attend::get')->bind('main');
 *     $app->post('/', 'AppBundle\\Attend::post');
 *     $app->run();
 * 
 */

class Application extends \Silex\Application {

    /**
     * Requires a Tsugi Launch object for initializing.
     *
     *     $launch = \Tsugi\Core\LTIX::requireData();
     *     $app = new \Tsugi\Silex\Application($launch);
     *
     * The launch object is added to the $app variable and can be accessed
     * as follows:
     * 
     *     $app['tsugi']->user->displayname;
     *
     * Or in a Twig template:
     *
     *     app.tsugi.user.displayname
     *
     * This sets up a PHP bridge session to allow old session and new
     * session code to coexist.
     */
    function __construct($launch, array $values = array()) {
        global $CFG;
        parent::__construct($values);
        if ( ! isset($CFG->loader) ) {
            echo("<pre>\n".'Please fix your config.php to set $CFG->loader as follows:'."\n");
            echo('$loader = require_once($dirroot."/vendor/autoload.php");'."\n");
            echo("...\n".'$CFG = ...'."\n...\n");
            echo('$CFG->loader = $loader;'."\n");
            echo("Please see config-dist.php for sample code.\n</pre>\n");
            die('Need to set $CFG->loader');
        }
        $launch->cfg = $CFG;
        $this['tsugi'] = $launch;
        $launch->output->buffer = true;  // Buffer output

        $session = new Session(new PhpBridgeSessionStorage());
        $session->start();
        $this['session'] = $session;

        if ( file_exists('templates') ) {
            $loader = new \Twig_Loader_Filesystem('templates');
        } else {
            $loader = new \Twig_Loader_Filesystem('.');
        }

        $this->tsugi_path = U::get_rest_path();
        $this->tsugi_parent = U::get_rest_parent();
        
        $yourNewPath = $CFG->dirroot . '/vendor/tsugi/lib/src/Templates';
        $loader->addPath($yourNewPath, 'Tsugi');
        $yourNewPath = $CFG->dirroot . '/vendor/koseu/lib/src/Templates';
        if ( file_exists($yourNewPath) ) {
            $loader->addPath($yourNewPath, 'Koseu');
        }
        $CFG->loader->addPsr4('AppBundle\\', 'src/AppBundle');

        //$loader = new \Tsugi\Twig\Twig_Loader_Class();
        $this->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.loader' => $loader
        ));

        // Add the __() and __ filter for translations
        $this->extend('twig', function($twig, $app) {
            $twig->addExtension(new \Tsugi\Silex\GettextExtension());
            return $twig;
        });

        // Handle failure of the routes
        $this->error(function (NotFoundHttpException $e, Request $request, $code) {
            global $CFG, $LAUNCH, $OUTPUT, $USER, $CONTEXT, $LINK, $RESULT;

            return $this['twig']->render('@Tsugi/Error.twig',
                array('error' => '<p>Page not found.</p>')
            );
        });

    }

    /**
     * tsugiReroute - Send the browser to a new location with session
     *
     * Usage:
     *     $app->get('/', 'AppBundle\\Attend::get')->bind('main');
     *     $app->post('/', 'AppBundle\\Attend::post');
     *
     * Then at the end of the POST code, do this:
     *
     *     return $app->tsugiReroute('main');
     *
     */

    function tsugiReroute($route) 
    {
        return $this->redirect( addSession($this['url_generator']->generate($route)) );
    }

    function tsugiRedirectHome()
    {
        global $CFG;
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        return $this->redirect($home);
    }

    function tsugiRedirect($route) { return $this->tsugiReroute($route); } // Deprecated

    /**
     * tsugiFlashSuccess - Add a success message to the old and new flash session.
     */
    function tsugiFlashSuccess($message)
    {
        $_SESSION['success'] = $message;
        $this['session']->getFlashBag()->add('success', $message);
    }

    /**
     * tsugiFlashError - Add an error message to the old and new flash session.
     */
    function tsugiFlashError($message)
    {
        $_SESSION['error'] = $message;
        $this['session']->getFlashBag()->add('error', $message);
    }

}
