<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Profile extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('profile', true));
        self::map($app, self::class, 'detail', self::paths('profile/profile-detail'));
    }

    public function index() {
        if ( $r = $this->gate('profile.view') ) return $r;
        $this->view('profile/index.php');
    }

    public function detail() {
        if ( $r = $this->gate('profile.detail') ) return $r;
        $this->view('profile/profile-detail.php');
    }
}
