<?php
namespace Tsugi\Lumen;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Support Tsugi's po-style of translation with the __ function 
 */

class GettextExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('__', array($this, '__')),
        );
    }

    public function getFunctions()
    {
        return array(
            new TwigFilter('__', array($this, '__')),
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
