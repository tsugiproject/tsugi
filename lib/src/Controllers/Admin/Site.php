<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Site extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('site', true));
    }

    public function index() {
        if ( $r = $this->gate('site.edit') ) return $r;
        $this->view('site/index.php');
    }
}
