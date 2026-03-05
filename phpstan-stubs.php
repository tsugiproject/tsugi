<?php

/**
 * PHPStan stubs for globals and optional dependencies.
 */

namespace {
    const TSUGI_COOKIELESS_SESSION_NAME = "_LTI_TSUGI";
    const TSUGI_SESSION_LTI = 'lti';
    const TSUGI_SESSION_LTI_POST = 'lti_post';

    /** @var \Tsugi\Config\ConfigInfo|null $CFG */
    $CFG;

    /** @var \Tsugi\UI\Output|null $OUTPUT */
    $OUTPUT;

    /** @var \Tsugi\Util\PDOX|null $PDOX */
    $PDOX;

    /** @var bool $REDIRECTED */
    $REDIRECTED;

    /**
     * @return bool
     */
    function isAdmin(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    function isInstructor(): bool
    {
        return false;
    }

    /**
     * @param string|null $path
     * @return string
     */
    function lmsAnalyticsKey(?string $path = null): string
    {
        return '';
    }

    /**
     * @param int $context_id
     * @param string $link_key
     * @param string|null $title
     * @param string|null $path
     * @return int|false
     */
    function lmsEnsureAnalyticsLink(int $context_id, string $link_key, ?string $title = null, ?string $path = null): int|false
    {
        return false;
    }

    /**
     * @param string $analytics_path
     * @param string|null $title
     * @return int|false
     */
    function lmsRecordLaunchAnalytics(string $analytics_path, ?string $title = null): int|false
    {
        return false;
    }
}

namespace Ratchet {
    interface MessageComponentInterface
    {
        public function onOpen(ConnectionInterface $conn): void;
        public function onClose(ConnectionInterface $conn): void;
        public function onError(ConnectionInterface $conn, \Exception $e): void;
        public function onMessage(ConnectionInterface $from, mixed $msg): void;
    }

    class ConnectionInterface
    {
        /** @var mixed */
        public $httpRequest;
        /** @var mixed */
        public $room;
        /** @var mixed */
        public $token;
        /** @var mixed */
        public $decode;
        /** @var mixed */
        public $space;

        public function send(mixed $msg): void
        {
        }

        public function close(): void
        {
        }
    }

    class App
    {
        public function __construct(string $host, int $port, string $address)
        {
        }

        /** @param array<mixed> $options */
        public function route(string $path, MessageComponentInterface $component, array $options = []): void
        {
        }

        public function run(): void
        {
        }
    }
}
