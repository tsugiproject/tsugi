<?php

namespace Tsugi\Core;

/**
 * RequestContext could not be hydrated from the given ids.
 */
class RequestContextException extends \RuntimeException {

    /** @var int */
    public $httpStatus;

    public function __construct($message, $httpStatus = 404) {
        parent::__construct($message);
        $this->httpStatus = (int) $httpStatus;
    }
}
