<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Context extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('context', true));
        self::map($app, self::class, 'settings', self::paths('context/context-settings'));
        self::map($app, self::class, 'membership', self::paths('context/membership'));
        self::map($app, self::class, 'member', self::paths('context/member-detail'));
        self::map($app, self::class, 'mailingList', self::paths('context/mailing-list'));
        self::map($app, self::class, 'bulkMail', self::paths('context/bulk-mail'));
        self::map($app, self::class, 'bulkDetail', self::paths('context/bulk-detail'));
    }

    public function index() {
        if ( $r = $this->gate('context.view') ) return $r;
        $this->view('context/index.php');
    }

    public function settings() {
        if ( $r = $this->gate('context.settings') ) return $r;
        $this->view('context/context-settings.php');
    }

    public function membership() {
        if ( $r = $this->gate('context.membership') ) return $r;
        $this->view('context/membership.php');
    }

    public function member() {
        if ( $r = $this->gate('context.member') ) return $r;
        $this->view('context/member-detail.php');
    }

    public function mailingList() {
        if ( $r = $this->gate('context.mailing_list') ) return $r;
        $this->view('context/mailing-list.php');
    }

    public function bulkMail() {
        if ( $r = $this->gate('context.bulk_mail') ) return $r;
        $this->view('context/bulk-mail.php');
    }

    public function bulkDetail() {
        if ( $r = $this->gate('context.bulk_detail') ) return $r;
        $this->view('context/bulk-detail.php');
    }
}
