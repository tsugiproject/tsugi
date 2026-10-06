<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Badges extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('badges', true));
    }

    public function index() {
        if ( $r = $this->gate('badges.view') ) return $r;
        $this->view('badges/index.php');
    }
}
