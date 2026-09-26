<?php

namespace Tsugi\Core;

/**
 * ReqScope could not be hydrated from the given ids.
 */
class ReqScopeException extends \RuntimeException {

    /** @var int */
    public $httpStatus;

    public function __construct($message, $httpStatus = 404) {
        parent::__construct($message);
        $this->httpStatus = (int) $httpStatus;
    }
}
