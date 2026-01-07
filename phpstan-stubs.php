<?php

/**
 * PHPStan stubs for globals and optional dependencies.
 */

namespace {
    /** @var \Tsugi\Config\ConfigInfo|null $CFG */
    $CFG;

    /** @var \Tsugi\UI\Output|null $OUTPUT */
    $OUTPUT;

    /** @var \Tsugi\Util\PDOX|null $PDOX */
    $PDOX;

    /** @var bool $REDIRECTED */
    $REDIRECTED;

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
