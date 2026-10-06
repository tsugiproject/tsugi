<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class External extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('external', true));
        self::map($app, self::class, 'add', self::paths('external/ext-add'));
        self::map($app, self::class, 'detail', self::paths('external/ext-detail'));
    }

    public function index() {
        if ( $r = $this->gate('external.view') ) return $r;
        $this->view('external/index.php');
    }

    public function add() {
        if ( $r = $this->gate('external.add') ) return $r;
        $this->view('external/ext-add.php');
    }

    public function detail() {
        if ( $r = $this->gate('external.detail') ) return $r;
        $this->view('external/ext-detail.php');
    }
}
