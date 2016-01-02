<?php

namespace Tsugi\Util;

/**
 * This is our "improved" version of PDO
 *
 * The PDOX class adds a number of non-trivial convienence methods
 * to the underlying PHP PDO class.   These methods combine several
 * PDO calls into a single call for common patterns and add far more
 * extensive error checking and simpler error handling.
 *
 * The primary value is in the queryReturnError() function which
 * combines prepare() and execute() as well as adding in extensive
 * error checking.
 * It turns out that to properly check all of the return values
 * and possible errors which can happen using prepare() and execute()
 * is really challenging and not even obvious from the PDO documentation.
 * So we have collected all that wisdom into this method and then use
 * it throughout Tsugi.
 *
 * The rest of the methods are convienence methods to combine common
 * multi-step operations into a single call to make tool code more readable.
 *
 * While this seems to be bending over backwards, It makes the calling
 * code very succinct as follows:
 *
 *     $stmt = $PDOX->queryDie(
 *         "INSERT INTO .... ",
 *         array('SHA' => $userSHA, ... )
 *     );
 *     if ( $stmt->success) $profile_id = $PDOX->lastInsertId();
 *
 * Whilst many of these methods seem focused on calling the die() function,
 * the only time that die() is called is when there is an SQL syntax error.
 * Not finding a record is non-fatal.  In general SQL syntax errors only
 * happen during development (if you are doing it right) so you might as
 * well die() if there is an SQL syntax error as it most likely indicates
 * a coding bug rather than a runtime user error or missing data problem.
 *
 */
class PDOX extends \PDO {

    /**
     * Prepare and execute an SQL query with lots of error checking.
     *
     * This routine will call prepare() and then execute() with the
     * resulting PDOStatement and return the PDOStatement.
     * If the prepare() fails, we fake up a stdClass() with a few
     * fields that mimic a simple failed execute().
     *
     *     $stmt->errorCode
     *     $stmt->errorInfo
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
     * @param $sql The SQL to execute in a string.
     * @param $arr An optional array of the substitition values if needed by the query
     * @param $error_log Indicates whether or not errors are to be logged. Default is TRUE.
     * @return \PDOStatement  This is either the real PDOStatement from the prepare() call
     * or a stdClass mocked to have error indications as described above.
     */
    function queryReturnError($sql, $arr=FALSE, $error_log=TRUE) {
        $errormode = $this->getAttribute(\PDO::ATTR_ERRMODE);
        if ( $errormode != \PDO::ERRMODE_EXCEPTION) {
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        $q = FALSE;
        $success = FALSE;
        $message = '';
        if ( $arr !== FALSE && ! is_array($arr) ) $arr = Array($arr);
        $start = microtime(true);
        // debug_log($sql, $arr);
        try {
            $q = $this->prepare($sql);
            if ( $arr === FALSE ) {
                $success = $q->execute();
            } else {
                $success = $q->execute($arr);
            }
        } catch(\Exception $e) {
            $success = FALSE;
            $message = $e->getMessage();
            if ( $error_log ) error_log($message);
        }
        if ( ! is_object($q) ) $q = new \stdClass();
        if ( isset( $q->success ) ) {
            error_log("\PDO::Statement should not have success member");
            die("\PDO::Statement should not have success member"); // with error_log
        }
        $q->success = $success;
        if ( isset( $q->ellapsed_time ) ) {
            error_log("\PDO::Statement should not have ellapsed_time member");
            die("\PDO::Statement should not have ellapsed_time member"); // with error_log
        }
        $q->ellapsed_time = microtime(true)-$start;
        // In case we build this...
        if ( !isset($q->errorCode) ) $q->errorCode = '42000';
        if ( !isset($q->errorInfo) ) $q->errorInfo = Array('42000', '42000', $message);
        if ( !isset($q->errorImplode) ) $q->errorImplode = implode(':',$q->errorInfo);
        // Restore ERRMODE if we changed it
        if ( $errormode != \PDO::ERRMODE_EXCEPTION) {
            $this->setAttribute(\PDO::ATTR_ERRMODE, $errormode);
        }
        return $q;
    }

    /**
     * Prepare and execute an SQL query or die() in the attempt.
     *
     * @param $sql The SQL to execute in a string.  If the SQL is badly formed this function will die.
     * @param $arr An optional array of the substitition values if needed by the query
     * @param $error_log Indicates whether or not errors are to be logged. Default is TRUE.
     * @return \PDOStatement  This is either the real PDOStatement from the prepare() call
     * or a stdClass mocked to have error indications as described above.
     */
    function queryDie($sql, $arr=FALSE, $error_log=TRUE) {
        global $CFG;
        $stmt = self::queryReturnError($sql, $arr, $error_log);
        if ( ! $stmt->success ) {
            error_log("Sql Failure:".$stmt->errorImplode." ".$sql);
            if ( $CFG->DEVELOPER ) {
                die($stmt->errorImplode); // with error_log
            } else {
                die('Internal database error');
            }
        }
        return $stmt;
    }

    /**
     * Prepare and execute an SQL query and retrieve a single row.
     *
     * @param $sql The SQL to execute in a string.  If the SQL is badly formed this function will die.
     * @param $arr An optional array of the substitition values if needed by the query
     * @param $error_log Indicates whether or not errors are to be logged. Default is TRUE.
     * @return array This is either the row that was returned or FALSE if no rows were
     * returned.
     */
    function rowDie($sql, $arr=FALSE, $error_log=TRUE) {
        $stmt = self::queryDie($sql, $arr, $error_log);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    /**
     * Prepare and execute an SQL query and retrieve all the rows as an array
     *
     * While this might seem like a bad idea, the coding style for Tsugi is
     * to make every query a paged query with a limited number of records to
     * be retrieved to in most cases, it is quite reasonable to retrieve
     * 10-30 rows into an array.
     *
     * If code wants to stream the results of a query, they should do their
     * own query and loop through the rows in their own code.
     *
     * @param $sql The SQL to execute in a string.  If the SQL is badly formed this function will die.
     * @param $arr An optional array of the substitition values if needed by the query
     * @param $error_log Indicates whether or not errors are to be logged. Default is TRUE.
     * @return array This is either the rows that were retrieved or or an empty array
     * if there were no rows.
     */
    function allRowsDie($sql, $arr=FALSE, $error_log=TRUE) {
        $stmt = self::queryDie($sql, $arr, $error_log);
        $rows = array();
        while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            array_push($rows, $row);
        }
        return $rows;
    }

    //  TODO: Sample return data

    /**
     * Retrieve the metadata for a table.
     */
    function metadata($tablename) {
        $sql = "SHOW COLUMNS FROM ".$tablename;
        $q = self::queryReturnError($sql);
        if ( $q->success ) {
            return $q->fetchAll();
        } else {
            return false;
        }
    }

}
