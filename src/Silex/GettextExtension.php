<?php
namespace Tsugi\Silex;

class GettextExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('__', array($this, '__')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('__', array($this, '__')),
        );
    }

    // Convience method, pattern borrowed from WordPress
    public function __($message, $textdomain=false)
    {
        return __($message, $textdomain);
    }
}
