<?php

/**
 * The Tsugi variant of a Silex Application
 *
 * This needs the session started before it is called
 * 
 */

namespace Tsugi\Silex;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

class Application extends \Silex\Application {

    function __construct($launch, array $values = array()) {
        parent::__construct($values);
        $this['tsugi'] = $launch;
        $launch->output->buffer = true;  // Buffer output

        $session = new Session(new PhpBridgeSessionStorage());
        $session->start();
        $this['session'] = $session;

        $loader = new \Koseu\Twig\Twig_Loader_Class();
        $this->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.loader' => $loader
        ));
    }
}
