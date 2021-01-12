<?php

class MockSession {
    public $sess = array();
    public function get($key, $default) {
        return isset($this->sess[$key]) ? $this->sess[$key] : $default;
    }
    public function put($key, $value) {
        $this->sess[$key] = $value;
    }
    public function forget($key) {
        if ( isset($this->sess[$key]) ) unset($this->sess[$key]);
    }
    public function flush() {
        $this->sess = array();
    }
    public function all() {
        $retval = $this->sess;
        return $retval;
    }
}
