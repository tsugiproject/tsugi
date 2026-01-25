<?php

namespace Koseu\Core;

/**
 * Koseu Application - Thin wrapper around Tsugi\Controllers\Tsugi
 * 
 * This class exists for backward compatibility during transition.
 * New code should use \Tsugi\Controllers\Tsugi directly.
 * 
 * @deprecated Use \Tsugi\Controllers\Tsugi instead
 */
class Application extends \Tsugi\Controllers\Tsugi {

    public function __construct($launch)
    {
        // Pass __DIR__ to maintain compatibility with existing behavior
        parent::__construct($launch, __DIR__);
    }
}

