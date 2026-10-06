<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * Installed modules. php admin/install/update.php remains the shell entry.
 */
class Install extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('install', true));
        self::map($app, self::class, 'git', self::paths('install/git'));
        self::map($app, self::class, 'update', self::paths('install/update'));
    }

    public function index() {
        if ( $r = $this->gate('install.view') ) return $r;
        $this->view('install/index.php');
    }

    public function git() {
        if ( $r = $this->gate('install.git', 'forbid') ) return $r;
        $this->view('install/git.php');
    }

    public function update() {
        global $CFG;
        if ( $r = $this->gate('install.update') ) return $r;
        require_once $CFG->dirroot.'/admin/install/update.php';
        tsugi_admin_install_update_run();
        exit;
    }
}
