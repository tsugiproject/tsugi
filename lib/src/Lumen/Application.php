<?php

namespace Tsugi\Lumen;

use Tsugi\Util\U;
use Illuminate\Log\LogManager;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger as Monolog;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/**
 * The Tsugi variant of a Lumen Application
 *
 * Returns an augmented Lumen Application
 *
 *     <?php
 *     require_once "../config.php";
 *     $launch = \Tsugi\Core\LTIX::requireData();
 *     $app = new \Tsugi\Lumen\Application($launch);
 *     $app->get('/', 'AppBundle\\Attend::get')->bind('main');
 *     $app->post('/', 'AppBundle\\Attend::post');
 *     $app->run();
 *
 */

class Application extends \Laravel\Lumen\Application {

    /**
     * Requires a Tsugi Launch object for initializing.
     *
     *     $launch = \Tsugi\Core\LTIX::requireData();
     *     $app = new \Tsugi\Lumen\Application($launch);
     *
     * The launch object is added to the $app variable and can be accessed
     * as follows:
     *
     *     $app['tsugi']->user->displayname;
     *
     * This sets up a PHP bridge session to allow old session and new
     * session code to coexist.
     */
    function __construct($launch, $basePath = __DIR__) {
        global $CFG;
        parent::__construct($basePath);
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
        $this->tsugi_path = U::get_rest_path();
        $this->tsugi_parent = U::get_rest_parent();

        // At this point nothing should use storage
        // (no blade templates and logging goes to php error log).
        // $this->useStoragePath($CFG->lumen_storage);

        // We are not using any view technology from Lumen at this point
        // $this->registerViewBindings();

        // TODO: We get an error when we try to use the illuminate logging
        // tsugi.lumen.ERROR: RuntimeException: A facade root has not been set.
        // might want to fix this at some point.

        // TODO: It is not clear this is used at all - it is a vestige from Silex methinks
        // $CFG->loader->addPsr4('AppBundle\\', 'src/AppBundle');

    }

    /**
     * Override the Exception Handler
     *
     * I wrote a stack overflow question and after I wrote it I figured out the answer and wrote this method
     *
     * https://stackoverflow.com/questions/65777054/how-do-i-add-a-custom-404-page-to-a-lumen-not-laravel-application
     *
     * @return mixed
     */
    protected function resolveExceptionHandler()
    {
        $retval = $this->make('\Tsugi\Lumen\ExceptionHandler');
        $retval->pleaseDontReport('\Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        return($retval);
    }

    /**
     * Override the log bindings to use the php error log
     *
     * @return void
     */
    protected function registerLogBindings()
    {
        // https://github.com/illuminate/log/blob/master/LogManager.php
        $this->singleton('Psr\Log\LoggerInterface', function () {

            $handler = new \Monolog\Handler\ErrorLogHandler(
                    \Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, 'debug');

            $logger = new MonoLog("tsugi.lumen", [ $handler ] );
            return $logger;
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
        return redirect( addSession($this['url_generator']->generate($route)) );
    }

    function tsugiRedirectHome()
    {
        global $CFG;
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        return redirect($home);
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
