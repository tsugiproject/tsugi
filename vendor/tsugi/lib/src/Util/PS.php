<?php

namespace Tsugi\Util;

/**
 * An Approximation of a Python String in PHP
 */
class PS {

    // Access at your peril :)
    public $internal = '';

    function __construct($str=false) {
        $this->set($str);
    }

    public function set($str=false) {
        if ( $str ) {
            $this->internal = $str;
        } else {
            $this->internal = '';
        }
    }

    public static function s($str=false) {
        $retval = new PS($str);
        return $retval;
    }

    public function __toString() {
        return $this->internal;
    }

    public function get() {
        return $this->internal;
    }

    // http://stackoverflow.com/questions/834303/startswith-and-endswith-public static functions-in-php
    public function startsWith($needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($this->internal, $needle, -strlen($this->internal)) !== FALSE;
    }

    public function endsWith($needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($this->internal) - strlen($needle)) >= 0 && strpos($this->internal, $needle, $temp) !== FALSE);
    }

    public function len() {
        return strlen($this->internal);
    }

    // TODO: handle negatives other than -1
    public function slice($start=0, $end=-1) {
        if ( $end == -1 ) return substr($this->internal, $start);
        $len = $end - $start - 1;
        return substr($this->internal, $start, $len);
    }

    public function replace($search, $replace) {
        return str_replace($search, $replace, $this->internal);
    }

    public function lower() {
        return strtolower($this->internal);
    }

    public function upper() {
        return strtoupper($this->internal);
    }

    public function find($sub, $start=0, $end=-1) {
        if ( $end == -1 ) {
            $tmp = $this->internal;
        } else {
            $tmp = $this->slice(0, $end);
        }
        $retval = strpos($tmp, $sub, $start);
        if ( $retval === false ) return -1;
        return $retval;
    }

    public function rfind($sub, $start=0, $end=-1) {
        if ( $end == -1 ) {
            $tmp = $this->internal;
        } else {
            $tmp = $this->slice(0, $end);
        }
        return strrpos($tmp, $sub);
    }
}
