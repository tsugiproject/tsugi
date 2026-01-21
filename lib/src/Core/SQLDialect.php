<?php

namespace Tsugi\Core;

use \Tsugi\Util\PS;

/**
 * This is a helper class for SQL dialect handling
 * 
 * @deprecated PostgreSQL and SQLite support have been removed. This class now only returns SQL unchanged.
 */

class SQLDialect
{
    public static $marker = '/*PDOX SQLDialect */';

    /**
     * SQL patch function - returns SQL unchanged since only MySQL is supported
     */
    public static function sqlPatch($PDOX, $sql) {
        // Only MySQL is supported, return SQL unchanged
        return $sql;
    }
}
