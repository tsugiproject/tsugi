<?php

namespace Tsugi\Controllers;

/**
 * Links for one discussion, either under the controller or under tool/tdiscus.
 */
class DiscussionUrls {

    /** @var string */
    private $home;

    /** @var string */
    private $api;

    /** @var bool */
    private $tool;

    /**
     * @param string $home
     * @param string $api
     * @param bool $tool
     */
    public function __construct($home, $api, $tool) {
        $this->home = $home;
        $this->api = $api;
        $this->tool = $tool;
    }

    public static function forTool() {
        $dir = dirname($_SERVER['SCRIPT_NAME']);
        if ( basename($dir) === 'api' ) {
            $dir = dirname($dir);
        }
        return new self($dir, $dir.'/api', true);
    }

    /**
     * @param string $home Controller path for this discussion, including the resource link id
     */
    public static function forController($home) {
        return new self($home, $home.'/api', false);
    }

    public function home() {
        return $this->home;
    }

    /**
     * Tool launches keep the relative analytics path that tool/tdiscus already serves.
     */
    public function analytics() {
        if ( $this->tool ) {
            return 'analytics';
        }
        return $this->home.'/analytics';
    }

    public function apiBase() {
        return $this->api;
    }

    public function thread($id) {
        return $this->home.'/thread/'.intval($id);
    }

    public function threadForm($id = null) {
        if ( $this->tool ) {
            if ( $id ) {
                return $this->home.'/threadform/'.intval($id);
            }
            return $this->home.'/threadform';
        }
        if ( $id ) {
            return $this->home.'/thread/'.intval($id).'/edit';
        }
        return $this->home.'/thread/new';
    }

    public function threadRemove($id) {
        if ( $this->tool ) {
            return $this->home.'/threadremove/'.intval($id);
        }
        return $this->home.'/thread/'.intval($id).'/remove';
    }

    public function commentForm($id) {
        if ( $this->tool ) {
            return $this->home.'/commentform/'.intval($id);
        }
        return $this->home.'/comment/'.intval($id).'/edit';
    }

    public function commentRemove($id) {
        if ( $this->tool ) {
            return $this->home.'/commentremove/'.intval($id);
        }
        return $this->home.'/comment/'.intval($id).'/remove';
    }

}
