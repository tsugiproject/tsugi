<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * Installed modules, at /admin/modules.
 * The shell script stays at admin/install/update.php.
 */
class Install extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('modules', true));
        self::map($app, self::class, 'git', self::paths('modules/git'));
        self::map($app, self::class, 'update', self::paths('modules/update'));
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
