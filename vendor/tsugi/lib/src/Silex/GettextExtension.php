<?php
namespace Tsugi\Silex;

/**
 * Support Tsugi's po-style of translation with the __ function 
 */

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

    /**
     * Translate a message using the current locale set by Tsugi
     *
     * Sample use in a Twig template:
     *
     *     {{ __('Enter code:') }}
     *
     * Pattern borrowed from WordPress
     */
    public function __($message, $textdomain=false)
    {
        return __($message, $textdomain);
    }
}
