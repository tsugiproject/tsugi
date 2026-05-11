<?php

namespace Tsugi\Util;

/**
 * Optional Memcached client for application data.
 *
 * When {@see \Tsugi\Config\ConfigInfo::$memcached} is set to a non-empty string
 * (same format as PHP's memcached session save_path, for example
 * `host.example.com:11211` or `host1:11211,host2:11211`) and the
 * memcached PHP extension is available, this class wraps a {@see \Memcached}
 * instance configured from that string. The client uses the text (non-binary)
 * protocol ({@see \Memcached::OPT_BINARY_PROTOCOL} is disabled).
 *
 * If memcached is unset, empty, the extension is missing, or no servers could be
 * added, {@see self::isEnabled()} is false and mutating calls are no-ops that
 * return false; {@see self::get()} returns false (same as a cache miss).
 */
class MCache {

    /** @var \Memcached|null */
    private $memcached = null;

    /** @var bool */
    private $enabled = false;

    /**
     * @param object $CFG Typically global `$CFG`; uses `$CFG->memcached` when set.
     */
    public function __construct($CFG) {
        if ( ! is_object($CFG) ) {
            return;
        }
        if ( ! isset($CFG->memcached) ) {
            return;
        }
        $path = $CFG->memcached;
        if ( ! is_string($path) ) {
            return;
        }
        $path = trim($path);
        if ( strlen($path) < 1 ) {
            return;
        }
        if ( ! class_exists('\Memcached', false) ) {
            return;
        }

        $m = new \Memcached();
        $m->setOption(\Memcached::OPT_BINARY_PROTOCOL, false);
        $added = self::addServersFromPath($m, $path);
        if ( $added < 1 ) {
            return;
        }
        $this->memcached = $m;
        $this->enabled = true;
    }

    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * @return mixed|false Value or false if disabled, miss, or error.
     */
    public function get($key) {
        if ( ! $this->enabled || $this->memcached === null ) {
            return false;
        }
        return $this->memcached->get($key);
    }

    public function set($key, $value, $expiration = 0) {
        if ( ! $this->enabled || $this->memcached === null ) {
            return false;
        }
        return $this->memcached->set($key, $value, $expiration);
    }

    public function add($key, $value, $expiration = 0) {
        if ( ! $this->enabled || $this->memcached === null ) {
            return false;
        }
        return $this->memcached->add($key, $value, $expiration);
    }

    public function delete($key) {
        if ( ! $this->enabled || $this->memcached === null ) {
            return false;
        }
        return $this->memcached->delete($key);
    }

    /**
     * @return \Memcached|null Underlying client when {@see self::isEnabled()} is true.
     */
    public function getMemcached() {
        return $this->memcached;
    }

    /**
     * Parse a memcached session save_path style server list and add servers.
     *
     * @param \Memcached $m
     * @param string $path Comma-separated entries; each is host, [ipv6]:port, or host:port (default port 11211).
     * @return int Number of servers successfully added.
     */
    private static function addServersFromPath($m, $path) {
        $added = 0;
        $specs = preg_split('/\s*,\s*/', $path, -1, PREG_SPLIT_NO_EMPTY);
        if ( $specs === false ) {
            return 0;
        }
        foreach ( $specs as $spec ) {
            $spec = trim($spec);
            if ( strlen($spec) < 1 ) {
                continue;
            }
            $port = 11211;
            if ( preg_match('/^\[([^\]]+)\]:(\d+)$/', $spec, $matches) ) {
                $host = $matches[1];
                $port = (int) $matches[2];
            } elseif ( preg_match('/^([^:]+):(\d+)$/', $spec, $matches) ) {
                $host = $matches[1];
                $port = (int) $matches[2];
            } else {
                $host = $spec;
            }
            if ( strlen($host) < 1 ) {
                continue;
            }
            if ( $m->addServer($host, $port) ) {
                $added++;
            }
        }
        return $added;
    }
}
