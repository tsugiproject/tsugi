<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * Modal checks linked from the administration console.
 */
class Check extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'recent', self::paths('recent'));
        self::map($app, self::class, 'cache', self::paths('cache'));
        self::map($app, self::class, 'mcache', self::paths('mcache'));
        self::map($app, self::class, 'crypt', self::paths('crypt'));
        self::map($app, self::class, 'nonce', self::paths('nonce'));
        self::map($app, self::class, 'dbsize', self::paths('dbsize'));
        self::map($app, self::class, 'keyset', self::paths('keyset'));
        self::map($app, self::class, 'events', self::paths('events'));
        self::map($app, self::class, 'socket', self::paths('sock-test'));
        self::map($app, self::class, 'info', self::paths('info'));
    }

    public function recent() {
        if ( $r = $this->gate('admin.recent') ) return $r;
        $this->view('check/recent.php');
    }

    public function cache() {
        if ( $r = $this->gate('admin.cache') ) return $r;
        $this->view('check/cache.php');
    }

    public function mcache() {
        if ( $r = $this->gate('admin.mcache') ) return $r;
        $this->view('check/mcache.php');
    }

    public function crypt() {
        if ( $r = $this->gate('admin.crypt') ) return $r;
        $this->view('check/crypt.php');
    }

    public function nonce() {
        if ( $r = $this->gate('admin.nonce') ) return $r;
        $this->view('check/nonce.php');
    }

    public function dbsize() {
        if ( $r = $this->gate('admin.dbsize') ) return $r;
        $this->view('check/dbsize.php');
    }

    public function keyset() {
        if ( $r = $this->gate('admin.keyset') ) return $r;
        $this->view('check/keyset.php');
    }

    public function events() {
        if ( $r = $this->gate('admin.events') ) return $r;
        $this->view('check/events.php');
    }

    public function socket() {
        if ( $r = $this->gate('admin.socket') ) return $r;
        $this->view('check/sock-test.php');
    }

    public function info() {
        if ( $r = $this->gate('admin.info') ) return $r;
        $this->view('check/info.php');
    }
}
