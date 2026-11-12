<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * Permission tables at /admin/permissions.
 */
class Permissions extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('permissions', true));
    }

    public function index() {
        if ( $r = $this->gate('permissions.view') ) return $r;
        $this->view('permissions/index.php');
    }
}
