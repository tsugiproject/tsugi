<?php

class JOSE_JWKSet {
    var $keys;

    function __construct($keys = array()) {
        if (!is_array($keys) || array_values($keys) !== $keys) {
            $keys = array($keys);
        }
        $this->keys = $keys;
    }

    function toString() {
        $keys = array();
        foreach($this->keys as $key) {
            if ($key instanceof JOSE_JWK) {
                $keys[] = $key->components;
            } else {
                $keys[] = $key;
            }
        }
        return json_encode(array('keys' => $keys));
    }
    function __toString() {
        return $this->toString();
    }
}