<?php

namespace Koseu\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use \Tsugi\Core\LTIX;

class Application extends \Tsugi\Silex\Application {

    public function __construct($launch)
    {
        // $app = new \Tsugi\Silex\Application($launch);
        parent::__construct($launch);
        $this['tsugi']->output->buffer = false;

        // Hook up the Koseu and Tsugi tools
        \Tsugi\Controllers\Login::routes($this);
        \Tsugi\Controllers\Logout::routes($this);
        \Tsugi\Controllers\Profile::routes($this);
        \Tsugi\Controllers\Map::routes($this);
        \Koseu\Controllers\Badges::routes($this);
        \Koseu\Controllers\Assignments::routes($this);
        \Koseu\Controllers\Lessons::routes($this);
        \Koseu\Controllers\Courses::routes($this);
    }
}

