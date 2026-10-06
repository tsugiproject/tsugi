<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * BLOB status, migration, and cleanup screens.
 * The command-line cleaners under admin/blob-maint/ stay scripts.
 */
class Blob extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'status', self::paths('blob_status'));
        self::map($app, self::class, 'move', self::paths('blob_move'));
        self::map($app, self::class, 'clean', self::paths('blob_clean'));
    }

    public function status() {
        if ( $r = $this->gate('blob.status') ) return $r;
        $this->view('blob/status.php');
    }

    public function move() {
        if ( $r = $this->gate('blob.move') ) return $r;
        $this->view('blob/move.php');
    }

    public function clean() {
        if ( $r = $this->gate('blob.clean') ) return $r;
        $this->view('blob/clean.php');
    }
}
