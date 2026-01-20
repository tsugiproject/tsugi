<?php


namespace Koseu\Core;

class Application extends \Tsugi\Lumen\Application {

    public function __construct($launch)
    {
        parent::__construct($launch, __DIR__);
        $this['tsugi']->output->buffer = false;

        $this->router->group([
            'namespace' => 'Koseu\Controllers',
        ], function () {
            \Koseu\Controllers\Lessons::routes($this);
            \Koseu\Controllers\Topics::routes($this);
            \Koseu\Controllers\Discussions::routes($this);
            \Koseu\Controllers\Badges::routes($this);
            \Koseu\Controllers\Assignments::routes($this);
            \Koseu\Controllers\Courses::routes($this);
        });

        $this->router->group([
            'namespace' => 'Tsugi\Controllers',
        ], function () {
            \Tsugi\Controllers\Login::routes($this);
            \Tsugi\Controllers\Logout::routes($this);
            \Tsugi\Controllers\Profile::routes($this);
            \Tsugi\Controllers\Map::routes($this);
        });
    }
}

