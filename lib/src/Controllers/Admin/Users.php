<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Users extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('users', true));
        self::map($app, self::class, 'detail', self::paths('users/user-detail'));
    }

    public function index() {
        if ( $r = $this->gate('users.view') ) return $r;
        $this->view('users/index.php');
    }

    public function detail() {
        if ( $r = $this->gate('users.detail') ) return $r;
        $this->view('users/user-detail.php');
    }
}
