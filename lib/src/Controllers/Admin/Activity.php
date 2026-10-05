<?php

namespace Tsugi\Controllers\Admin;

use Tsugi\Controllers\Admin;
use Tsugi\Lumos\Application;

class Activity extends Admin {

    public static function routes(Application $app) {
        self::map($app, self::class, 'index', self::paths('activity', true));
        self::map($app, self::class, 'detail', self::paths('activity/activity-detail'));
        self::map($app, self::class, 'analytics', self::paths('activity/analytics'));
    }

    public function index() {
        if ( $r = $this->gate('activity.view') ) return $r;
        $this->view('activity/index.php');
    }

    public function detail() {
        if ( $r = $this->gate('activity.detail') ) return $r;
        $this->view('activity/activity-detail.php');
    }

    public function analytics() {
        if ( $r = $this->gate('activity.analytics') ) return $r;
        $this->view('activity/analytics.php');
    }
}
