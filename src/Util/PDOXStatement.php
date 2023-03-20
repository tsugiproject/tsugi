<?php

namespace Tsugi\Util;

use \Tsugi\Util\PS;

/**
 * This is our "improved" version of PDOStatement
 *
 * If the prepare() fails, this class can fake up methods that
 * mimic a simple failed execute().
 *
 *     $stmt->errorCode()
 *     $stmt->errorInfo()
 *
 * We also augment the real or fake PDOStatement with these fields:
 *
 *     $stmt->success
 *     $stmt->ellapsed_time
 *     $stmt->errorImplode
 *
 * <var>$stmt->success</var> is TRUE/FALSE based on the success of the operation
 * to simplify error checking
 *
 * <var>$stmt->ellapsed_time</var> includes the length of time the query took
 *
 * <var>$stmt->errorImplode</var> an imploded version of errorInfo suitable for
 * dropping into a log.
 *
 * The technique for making this override is in the documentation for PDOStatement:
 *
 * https://www.php.net/manual/en/class.pdostatement.php
 * https://www.php.net/manual/en/pdo.setattribute.php
 */
class PDOXStatement extends \PDOStatement {

    public $success;
    public $ellapsed_time;
    public $errorImplode;
    public $sqlQuery;
    public $sqlOriginalQuery;

    public $errorCodeOverride = null;
    public $errorInfoOverride = null;
    public $PDOX = null;

    protected function __construct() {
        // error_log("In PDOXStatement constructor");
    }

    public function errorCode() : ?string {
        // error_log("In PDOXStatement errorCode");
        if ( $this->errorCodeOverride != null ) return $errorCodeOverride;
        return parent::errorCode();
    }

    public function errorInfo() : array {
        // error_log("In PDOXStatement errorInfo");
        if ( $this->errorInfoOverride != null ) return $errorInfoOverride;
        return parent::errorInfo();
    }
}
