<?php

namespace Koseu\Twig;

/**
 * Loads a template from a class path
 *
 * Sample usage:
 *
 *    $loader = new Twig_Loader_Class();
 *    $app = new Silex\Application();
 *    $app->register(new Silex\Provider\TwigServiceProvider(), array(
 *        'twig.loader' => $loader
 *    ));

 *    $app->get('/class/{name}', function ($name) use ($app) {
 *        echo("<pre>\n");
 *        return $app['twig']->render('\myview', array(
 *            'name' => $name,
 *        ));
 *    });
 *
 *    $app->run();
 *
 * This code looks as the first character of the template name and ignores
 * all templates that do not start with '\\'.
 *
 * This loader should be last in a chain of Twig loaders because classes 
 * that do not exist are fatal, not exceptions so calling our exist() 
 * method with a class that does not exist or cannot be autoloaded, 
 * will be fatal, not simply return false :(
 *
 * We pretty much ignore working with Twig cache at all as the autoloader
 * will function as a "within request" cache.
 */
final class Twig_Loader_Class implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface, \Twig_SourceContextLoaderInterface
{

    public function getSourceContext($name)
    {
        $name = (string) $name;
        $view = $this->getCacheKey($name);
        return new \Twig_Source($view, $name);
    }

    public function exists($name)
    {
        $name = (string) $name;
        if ( strpos($name, '\\') !== 0 ) return false;
        try {
            $view = $this->getCacheKey($name);
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    // Understand that the loading of a class is a *Fatal* error, not an exception
    // So we need to be last in the chain without a bit more autoloader magic here
    public function getCacheKey($name)
    {
        $name = (string) $name;
        try {
            $class = new $name();
            return $class->view;
        } catch(Exception $e) {
            throw new \Twig_Error_Loader(sprintf('Template "%s" is not defined.', $name));
        }
    }

    public function isFresh($name, $time)
    {
        $name = (string) $name;
        $this->getCacheKey();
        return true;
    }
}
