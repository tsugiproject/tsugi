<?php

/**
 * Minimal stubs for PHPStan to avoid undefined globals/functions in standalone scripts.
 * These are only for static analysis and are not used at runtime.
 */

namespace {
    /** @var \Tsugi\Config\ConfigInfo|null $CFG */
    $CFG = null;

    /** @var \Tsugi\UI\Output|null $OUTPUT */
    $OUTPUT = null;

    /** @var \Tsugi\Util\PDOX|null $PDOX */
    $PDOX = null;

    /** @var bool $REDIRECTED */
    $REDIRECTED = false;

    if (!function_exists('_m')) {
        /** @return string */
        function _m(string $message, $textdomain = false)
        {
            return $message;
        }
    }

    if (!function_exists('_me')) {
        /** @return string */
        function _me(string $message, $textdomain = false)
        {
            return $message;
        }
    }

    if (!function_exists('htmlent_utf8')) {
        /** @return string */
        function htmlent_utf8(string $string)
        {
            return $string;
        }
    }

    if (!function_exists('htmlencode')) {
        /** @return string */
        function htmlencode(string $string)
        {
            return $string;
        }
    }

    if (!function_exists('lti_sha256')) {
        /** @return string */
        function lti_sha256(string $val)
        {
            return hash('sha256', $val);
        }
    }

    if (!function_exists('addSession')) {
        /** @return string */
        function addSession(string $url)
        {
            return $url;
        }
    }

    if (!function_exists('htmlspec_utf8')) {
        /** @return string */
        function htmlspec_utf8(string $string)
        {
            return $string;
        }
    }

    if (!function_exists('die_with_error_log')) {
        function die_with_error_log(string $msg, $extra = false, string $prefix = 'DIE:'): void
        {
            // No-op for static analysis.
        }
    }

    if (!function_exists('isCli')) {
        function isCli(): bool
        {
            return false;
        }
    }

    if (!function_exists('route_get_local_path')) {
        /** @return string */
        function route_get_local_path(string $dir)
        {
            return $dir;
        }
    }
}

namespace Ratchet {
    interface MessageComponentInterface
    {
        public function onOpen(ConnectionInterface $conn): void;
        public function onClose(ConnectionInterface $conn): void;
        public function onError(ConnectionInterface $conn, \Exception $e): void;
        public function onMessage(ConnectionInterface $from, $msg): void;
    }

    class ConnectionInterface
    {
        public $httpRequest;
        public $room;
        public $token;
        public $decode;
        public $space;

        public function close(): void
        {
        }
    }

    class App
    {
        public function __construct(string $host, int $port, string $address)
        {
        }

        public function route(string $path, MessageComponentInterface $component, array $options = []): void
        {
        }

        public function run(): void
        {
        }
    }
}
