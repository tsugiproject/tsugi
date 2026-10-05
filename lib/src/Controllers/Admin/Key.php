<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Keys for the passphrase admin.
 * Instructors apply for a key under Settings. /admin/key/auto stays the
 * public dynamic-registration endpoint.
 */
class Key extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('key', true));
        self::map($app, self::class, 'add', self::paths('key/key-add'));
        self::map($app, self::class, 'detail', self::paths('key/key-detail'));
        self::map($app, self::class, 'settings', self::paths('key/key-settings'));
        self::map($app, self::class, 'requests', self::paths('key/requests'));
        self::map($app, self::class, 'request', self::paths('key/request-detail'));
        self::map($app, self::class, 'approve', self::paths('key/approve-key'));
        self::map($app, self::class, 'auto', self::paths('key/auto'));
        self::map($app, self::class, 'using', self::paths('key/using'));
    }

    public function index() {
        if ( $r = $this->gate('keys.view') ) return $r;
        $this->view('key/index.php');
    }

    public function add() {
        if ( $r = $this->gate('keys.add') ) return $r;
        $this->view('key/key-add.php');
    }

    public function detail() {
        if ( $r = $this->gate('keys.detail') ) return $r;
        $this->view('key/key-detail.php');
    }

    public function settings() {
        if ( $r = $this->gate('keys.settings') ) return $r;
        $this->view('key/key-settings.php');
    }

    public function requests() {
        if ( $r = $this->gate('keys.requests') ) return $r;
        $this->view('key/requests.php');
    }

    public function request() {
        if ( $r = $this->gate('keys.request') ) return $r;
        $this->view('key/request-detail.php');
    }

    public function approve() {
        if ( $r = $this->gate('keys.approve') ) return $r;
        $this->view('key/approve-key.php');
    }

    public function auto() {
        $this->view('key/auto.php');
    }

    public function using() {
        global $CFG;
        return new RedirectResponse(rtrim((string) $CFG->wwwroot, '/').'/settings/key/using');
    }
}
