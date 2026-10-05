<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Catalog extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('catalog', true));
        self::map($app, self::class, 'edit', self::paths('catalog/edit'));
    }

    public function index() {
        if ( $r = $this->gate('catalog.view') ) return $r;
        $this->view('catalog/index.php');
    }

    public function edit() {
        if ( $r = $this->gate('catalog.edit') ) return $r;
        $this->view('catalog/edit.php');
    }
}
