<?php

namespace Tsugi\Controllers;

class Tsugi extends \Tsugi\Lumen\Application {

    public function __construct($launch, $baseDir = null)
    {
        // If no baseDir provided, use the directory where this class is located
        if ( $baseDir === null ) {
            $baseDir = __DIR__;
        }
        parent::__construct($launch, $baseDir);
        $this['tsugi']->output->buffer = false;

        // Register all controllers in a single group since they're all in the same namespace
        $this->router->group([
            'namespace' => 'Tsugi\Controllers',
        ], function () {
            // Register StaticFiles routes first to ensure they're matched before other routes
            \Tsugi\Controllers\StaticFiles::routes($this);
            \Tsugi\Controllers\Announcements::routes($this);
            \Tsugi\Controllers\Assignments::routes($this);
            \Tsugi\Controllers\Badges::routes($this);
            \Tsugi\Controllers\Courses::routes($this);
            \Tsugi\Controllers\Discussions::routes($this);
            \Tsugi\Controllers\Grades::routes($this);
            \Tsugi\Controllers\Lessons::routes($this);
            \Tsugi\Controllers\Login::routes($this);
            \Tsugi\Controllers\Logout::routes($this);
            \Tsugi\Controllers\Map::routes($this);
            \Tsugi\Controllers\Pages::routes($this);
            \Tsugi\Controllers\Profile::routes($this);
            \Tsugi\Controllers\Topics::routes($this);
        });
    }
}
