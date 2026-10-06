<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

/**
 * Site-wide data expiry at /admin/expire.
 * The batch jobs stay in admin/expire-maint/.
 */
class Expire extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('expire', true));
        self::map($app, self::class, 'loginDetail', self::paths('expire/login-detail'));
        self::map($app, self::class, 'loginExpire', self::paths('expire/login-expire'));
        self::map($app, self::class, 'piiDetail', self::paths('expire/pii-detail'));
        self::map($app, self::class, 'piiExpire', self::paths('expire/pii-expire'));
    }

    public function index() {
        if ( $r = $this->gate('expire.view') ) return $r;
        $this->view('expire/index.php');
    }

    public function loginDetail() {
        if ( $r = $this->gate('expire.login_detail') ) return $r;
        $this->view('expire/login-detail.php');
    }

    public function loginExpire() {
        if ( $r = $this->gate('expire.login_expire') ) return $r;
        $this->view('expire/login-expire.php');
    }

    public function piiDetail() {
        if ( $r = $this->gate('expire.pii_detail') ) return $r;
        $this->view('expire/pii-detail.php');
    }

    public function piiExpire() {
        if ( $r = $this->gate('expire.pii_expire') ) return $r;
        $this->view('expire/pii-expire.php');
    }
}
