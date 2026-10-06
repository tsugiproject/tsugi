<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Mail extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('mail', true));
        self::map($app, self::class, 'bulk', self::paths('mail/bulk'));
        self::map($app, self::class, 'event', self::paths('mail/event-detail'));
        self::map($app, self::class, 'sent', self::paths('mail/sent'));
        self::map($app, self::class, 'events', self::paths('mail/ses-events'));
        self::map($app, self::class, 'suppress', self::paths('mail/suppress'));
        self::map($app, self::class, 'test', self::paths('testmail'));
    }

    public function index() {
        if ( $r = $this->gate('mail.view') ) return $r;
        $this->view('mail/index.php');
    }

    public function bulk() {
        if ( $r = $this->gate('mail.bulk') ) return $r;
        $this->view('mail/bulk.php');
    }

    public function event() {
        if ( $r = $this->gate('mail.event') ) return $r;
        $this->view('mail/event-detail.php');
    }

    public function sent() {
        if ( $r = $this->gate('mail.sent') ) return $r;
        $this->view('mail/sent.php');
    }

    public function events() {
        if ( $r = $this->gate('mail.events') ) return $r;
        $this->view('mail/ses-events.php');
    }

    public function suppress() {
        if ( $r = $this->gate('mail.suppress') ) return $r;
        $this->view('mail/suppress.php');
    }

    public function test() {
        if ( $r = $this->gate('mail.test') ) return $r;
        $this->view('mail/testmail.php');
    }
}
