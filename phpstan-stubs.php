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

    /**
     * From lib/include/lms_lib.php (loaded via config); PHPStan does not analyse that file in paths.
     *
     * @return bool
     */
    function isLoggedIn(): bool
    {
        return false;
    }

    /**
     * @return int
     */
    function loggedInUserId(): int
    {
        return 0;
    }

    /**
     * @return int
     */
    function currentContextId(): int
    {
        return 0;
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

namespace PHPUnit\Framework {
    /**
     * Lightweight PHPUnit TestCase stub for static analysis in environments
     * where phpunit classes are not available (for example some CI jobs).
     */
    abstract class TestCase
    {
        public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
        {
        }

        public function assertTrue(mixed $condition, string $message = ''): void
        {
        }

        public function assertFalse(mixed $condition, string $message = ''): void
        {
        }

        public function assertCount(int $expectedCount, mixed $haystack, string $message = ''): void
        {
        }

        /**
         * @param iterable<mixed> $haystack
         */
        public function assertContains(mixed $needle, iterable $haystack, string $message = ''): void
        {
        }

        public function assertNotEmpty(mixed $actual, string $message = ''): void
        {
        }

        public function assertEmpty(mixed $actual, string $message = ''): void
        {
        }

        public function assertGreaterThan(mixed $expected, mixed $actual, string $message = ''): void
        {
        }

        public function assertGreaterThanOrEqual(mixed $expected, mixed $actual, string $message = ''): void
        {
        }

        public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
        {
        }

        public function markTestSkipped(string $message = ''): void
        {
        }
    }
}
