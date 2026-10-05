<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Org extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('org', true));
    }

    public function index() {
        if ( $r = $this->gate('org.manage') ) return $r;
        $this->view('org/index.php');
    }
}
