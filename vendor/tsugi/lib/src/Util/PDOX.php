<?php

namespace Tsugi\Util;

use \Tsugi\Util\PS;

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
     * Threshold for logging slow queries - 0 means don't log
     */
    public $slow_query = 0;
    public $PDOX_LastSqlQuery = False;
    public $PDOX_LastInsertStatement = False;

    /**
     * Array of meta entries
     */
    public $PDOX_MetaEntries = array();

    /**
     * Our extension to make lastInsertId() work across MySQL and PostgreSQL
     */
    public $PGSQL_LastInsertId = 0;
    public $PGSQL_LastInsertIdWarningCount = 0;

    private $PDOX_upsert_marker = '/* upsert */';

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
        $this->PDOX_LastSqlQuery = False;
        if ( self::isInsertStatement($sql) ) {
            $this->PGSQL_LastInsertId = 0;
            $this->PDOX_LastInsertStatement = False;
            if ( strpos($sql, $this->PDOX_upsert_marker) === false ) {
                return self::upsertGetPKReturnError($sql, $arr, $error_log);
            }
        }
        return self::queryReturnErrorInternal($sql, $arr, $error_log);
    }

    function queryReturnErrorInternal($sql, $arr=FALSE, $error_log=TRUE) {
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

        // Optionally patch the SQL to support different variants
        $todo = array();
        $todo[] = $sql;
        if ( isset($this->sqlPatch) && is_callable($this->sqlPatch) ) {
            $func = $this->sqlPatch;
            $check = $func($this, $sql);
            if ( is_array($check) ) {
                $todo = $check;
            } else {
                $todo = array();
                $todo[] = $check;
            }
        }

        try {
            foreach($todo as $query) {
                $q = $this->prepare($query);
                if ( $arr === FALSE ) {
                    $success = $q->execute();
                } else {
                    $success = $q->execute($arr);
                }
                if ( ! $success ) break;
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
        if ( self::isInsertStatement($sql) && $q->success ) $this->PDOX_LastInsertStatement = $q;
        if ( isset( $q->ellapsed_time ) ) {
            error_log("\PDO::Statement should not have ellapsed_time member");
            die("\PDO::Statement should not have ellapsed_time member"); // with error_log
        }
        $q->ellapsed_time = microtime(true)-$start;
        if ( $this->slow_query < 0 || ($this->slow_query > 0 && $q->ellapsed_time > $this->slow_query ) ) {
            $dbt = U::getCaller(2);
            $caller_uri = U::get($_SERVER,'REQUEST_URI');
            error_log("PDOX Slow Query:".$q->ellapsed_time.' '.$caller_uri.' '.$dbt.' '.$sql);
        }

        // In case we build this...
        if ( !isset($q->errorCode) ) $q->errorCode = '42000';
        if ( !isset($q->errorInfo) ) $q->errorInfo = Array('42000', '42000', $message);
        if ( !isset($q->errorImplode) ) $q->errorImplode = implode(':',$q->errorInfo);
        if ( !isset($q->sqlQuery) ) {
            $q->sqlQuery = implode('; ', $todo);
            $this->PDOX_LastSqlQuery = $q->sqlQuery;
        }
        if ( !isset($q->sqlOriginalQuery) ) $q->sqlOriginalQuery = $sql;
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
            // $stmt->closeCursor();
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
        $stmt->closeCursor();
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
        $stmt->closeCursor();
        return $rows;
    }

    //  TODO: Sample return data

    /**
     * Retrieve the metadata for a table.
     */
    function metadata($tablename) {
        if ( ! $this->isPgSQL() ) {
            $sql = "SHOW COLUMNS FROM ".$tablename;
        } else {
            $sql = 'SELECT column_name AS "Field", data_type AS "Type", is_nullable AS "Null"
                FROM information_schema.columns WHERE table_name = \''.$tablename.'\';';
        }
        $stmt = self::queryReturnErrorInternal($sql);
        if ( $stmt->success ) {
            $retval= $stmt->fetchAll();
            if ( count($retval) == 0 ) $retval = false;
        } else {
            $retval = false;
        }
        $stmt->closeCursor();
	    return $retval;
    }

    /**
     * Retrieve the indexes for a table.
     */
    function indexDetail($tablename) {
        if ( $this->isPgSQL() ) {
            $sql = "PSQL NOT IMPLEMENTED ".$tablename;
        } else {
            $sql = 'SHOW INDEX FROM '.$tablename.';';
        }
        $stmt = self::queryReturnErrorInternal($sql);
        if ( $stmt->success ) {
            $retval= $stmt->fetchAll();
            if ( count($retval) == 0 ) $retval = false;
        } else {
            $retval = false;
        }
        $stmt->closeCursor();
	    return $retval;
    }

    function indexes($tablename) {
        $indexDetail = $this->indexDetail($tablename);
        $retval = array();
        if ( ! is_array($indexDetail) ) return $retval;
        foreach($indexDetail as $row) {
            $keyname = U::get($row, "Key_name");
            if ( ! is_string($keyname) ) continue;
            if ( in_array($keyname, $retval) ) continue;
            $retval[] = $keyname;
        }

        return $retval;
    }

    /**
     * Retrieve the metadata for a table.
     */
    function describe($tablename) {
        return $this->metadata($tablename);
    }

    /**
     * Retrieve the metadata for a column in a table.
     *
     * For the output format for MySQL, see:
     * https://dev.mysql.com/doc/refman/5.7/en/explain.html
     *
     * @param $fieldname The name of the column
     * @param $source Either an array of the column metadata or the name of the table
     *
     * @return mixed An array of the column metadata or null
     */
    function describeColumn($fieldname, $source) {
        if ( ! is_array($source) ) {
            if ( ! is_string($source) ) {
                throw new \Exception('Source must be an array of metadata or a table name');
            }
            $source = self::describe($source);
            if ( ! is_array($source) ) return null;
        }
        foreach( $source as $column ) {
            $name = U::get($column, "Field");
            if ( $fieldname == $name ) return $column;
        }
        return null;
    }

    /**
     * Check if a column is null
     *
     * @param $fieldname The name of the column
     * @param $source Either an array of the column metadata or the name of the table
     *
     * @return mixed Returns true / false if the columns exists and null if the column does not exist.
     */
    function columnIsNull($fieldname, $source)
    {
        $column = self::describeColumn($fieldname, $source);
        if ( ! $column ) throw new \Exception("Could not find $fieldname");
        return U::get($column, "Null") == "YES";
    }

    /**
     * Check if a column exists
     *
     * @param $fieldname The name of the column
     * @param $source Either an array of the column metadata or the name of the table
     *
     * @return boolean Returns true/false
     */
    function columnExists($fieldname, $source)
    {
        if ( is_string($source) ) {  // Demand table exists
            $check = self::describe($source);
            if ( ! $check ) throw new \Exception("Could not find $source");
            $source = $check;
        }
        $column = self::describeColumn($fieldname, $source);
        return is_array($column);
    }

    /**
     * Get the column type
     *
     * @param $fieldname The name of the column
     * @param $source Either an array of the column metadata or the name of the table
     *
     * @return mixed Returns a string if the columns exists and null if the column does not exist.
     */
    function columnType($fieldname, $source)
    {
        $column = self::describeColumn($fieldname, $source);
        if ( ! $column ) throw new \Exception("Could not find $fieldname");
        $type = U::get($column, "Type");
        if ( ! $type ) return null;
        if ( strpos($type, '(') === false ) return $type;
        $matches = array();
        preg_match('/([a-z]+)\([0-9]+\)/', $type, $matches);
        if ( count($matches) == 2 ) return $matches[1];
        return null;
    }

    /**
     * Get the column length
     *
     * @param $fieldname The name of the column
     * @param $source Either an array of the column metadata or the name of the table
     *
     * @return mixed Returns an integer has an explicit length, 0 if the column has no length and null if the column does not exist.
     */
    function columnLength($fieldname, $source)
    {
        $column = self::describeColumn($fieldname, $source);
        if ( ! $column ) throw new \Exception("Could not find $fieldname");
        $type = U::get($column, "Type");
        if ( ! $type ) return null;
        if ( strpos($type, '(') === false ) return 0;
        $matches = array();
        preg_match('/[a-z]+\(([0-9]+)\)/', $type, $matches);
        if ( count($matches) == 2 ) return 0+$matches[1];
        return 0;
    }

    /**
     * Get the version number of the current connection
     *
     * $version = $PDOX->versionNumber();
     * $min = '5.6.0';
     * if ( version_compare($version, $min) >= 0) {
     *
     */
    // https://stackoverflow.com/questions/31788297/get-mysql-server-version-with-pdo
    function versionNumber()
    {
        $version = $this->query('select version()')->fetchColumn();
        preg_match("/^[0-9\.]+/", $version, $match);

        if ( count($match) < 1 ) return "0.0.0";
        $version = $match[0];

        return $version;
    }

    /**
     * Check the current connection against a version
     *
     * if ( $PDOX->versionAtLeast('8.0.0') ) {
     */
    function versionAtLeast($min)
    {
        $version = $this->versionNumber();
        return (version_compare($version, $min) >= 0);
    }

    /**
     * getPDOXDriverName - Get the driver name
     */
    function getPDOXDriverName() {
        $name = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return $name;
    }

    /**
     * Return true if the current connection is MySQL
     */
    function isMySQL()
    {
        return self::getPDOXDriverName() == 'mysql';
    }

    /**
     * Return true if the current connection is PgSQL
     */
    function isPgSQL()
    {
        return self::getPDOXDriverName() == 'pgsql';
    }

    /**
     * Return true if the current connection is SQLite
     */
    function isSQLite()
    {
        return self::getPDOXDriverName() == 'sqlite';
    }

    /**
     * Check if an SQL statement is an INSERT statement
     */
    function isInsertStatement($sql) {
        $table = self::getInsertTable($sql);
        return is_string($table);
    }

    /**
     * Check if an SQL statement is an INSERT and return the table name
     */
    function getInsertTable($sql)
    {
        $retval = false;
        $matches = array();
        preg_match('/\s*INSERT\s+INTO\s+[^\s]+\s+/i', $sql, $matches, PREG_OFFSET_CAPTURE);
        if ( count($matches) == 1 ) {
            $str = $matches[0][0];
            $pos = $matches[0][1];
            $pieces = (new PS($str))->split();
            $retval = $pieces[2];
        } else {
            $matches = array();
            preg_match('/\s*INSERT\s+IGNORE\s+INTO\s+[^\s]+\s+/i', $sql, $matches, PREG_OFFSET_CAPTURE);
            if ( count($matches) == 1 ) {
                $str = $matches[0][0];
                $pos = $matches[0][1];
                $pieces = (new PS($str))->split();
                $retval = $pieces[3];
            } else {
                $retval = false;
            }
        }
        return $retval;
    }


    /**
     * prepare - Override prepare() to capture the PDOX and LastInsertStatement (for INSERTs)
     *
     * We won't really have a statement until they run execute() but we tolerate that.
     */
    // Switch to declaring return value after our minimum version if PHP 8.0
    // function prepare($statement, $options = NULL) : \PDOStatement|false {

    // Quick fix to suppress the deprecation warnings in 8.1
    // https://wiki.php.net/rfc/internal_method_return_types
    #[\ReturnTypeWillChange]
    function prepare($statement, $options = NULL) {
        if ( $options === NULL ) {
            $stmt = parent::prepare($statement);
        } else {
            $stmt = parent::prepare($statement, $options);
        }
        $stmt->PDOX = $this;
        if ( self::isInsertStatement($statement) ) {
            $this->PDOX_LastInsertStatement = $stmt;
        }
        return $stmt;
    }

    /**
     * addPDOXMeta - Add a meta entry for a table
     *
     * Usage:
     *     $PDOX->addPDOXMeta("{$p}lti_context", array("pk" => "context_id", "lk" => array("context_sha256", "key_id"))
     *     $PDOX->addPDOXMeta("{$p}lti_link", array("pk" => "link_id", "lk" => array("link_sha256", "context_id"))
     */
    function addPDOXMeta($table, $meta) {
        $this->PDOX_MetaEntries[$table] = $meta;
    }

    /**
     * getPDOXMeta - Extract and parse the PDOX Metadata comment from a query or entries
     *
     * Space added between the slash and star below because this documentation is in a comment :)
     *
     * INSERT INTO lti_context / *PDOX pk: context_id lk: context_sha256,key_id * /
     *
     * The syntax is very picky:
     *
     * A space is required after the colon but no space is allowed before the colon.
     * There must be a space after the comment start and comment end.
     * There cannot be a space after the comma when there is more than one logical key.
     *
     * A default for this metadata can be stored for a table by calling $PDOX->addPDOXMeta();
     * If a comment is included it overrides any stored default.
     */
    function getPDOXMeta($sql, $table) {

        // Comment overrides meta table entry
        preg_match('/\s*\/\*PDOX\s+[^*]*\s+\*\/\s+/i', $sql, $matches, PREG_OFFSET_CAPTURE);
        if ( count($matches) < 1 ) {
            if ( isset($this->PDOX_MetaEntries) && is_array($this->PDOX_MetaEntries) ) {
                $meta = U::get($this->PDOX_MetaEntries, $table);
                if ( is_array($meta) ) {
                    $meta['table'] = $table;
                    return $meta;
                }
            }
            return false;
        }
        $str = $matches[0][0];
        $pos = $matches[0][1];
        $pieces = (new PS($str))->split();

        $pk = False;
        $lk = False;
        for($i=0;$i<(count($pieces)-1); $i++) {
            $piece = $pieces[$i];
            if ( $piece == 'pk:' ) $pk = $pieces[$i+1];
            if ( $piece == 'lk:' ) {
                $lkstr =  $pieces[$i+1];
                $lk =  (new PS($lkstr))->split(',');
            }
        }
        if ( $pk && $lk ) {
            return array('table' => $table, 'pk' => $pk, 'lk' => $lk);
        }
        return false;
    }

    /**
     * upsertGetPKReturnError - Insert or update a record and return the lastInsertID portably
     *
     * Upsert is very non-portable between MySQL and PostgreSQL.  And even worse,
     * the lastInsertID() does not work at all for PostgreSQL.
     *
     * So we use the (much simpler) MySQL INSERT format and add a meta comment to
     * indicate the primary key and logical key(s).
     *
     * @param $sql The SQL to execute in a string.
     * @param $arr An optional array of the substitition values if needed by the query
     * @param $error_log Indicates whether or not errors are to be logged. Default is True.
     * @return \PDOStatement  This is either the real PDOStatement from the prepare() call
     * or a stdClass mocked to have error indications as in queryReturnError().
     *
     * Usage (# are actually slashes - C comment style):
     *
     *     $sql = "INSERT INTO {$p}lti_membership
     *         #*PDOX pk: membership_id lk: context_id,user_id *#
     *         ( context_id, user_id, role, created_at, updated_at ) VALUES
     *         ( :context_id, :user_id, :role, NOW(), NOW() )
     *         ON DUPLICATE KEY UPDATE updated_at = NOW();";
     *     $PDOX->upsertGetPKReturnError($sql, array(
     *         ':context_id' => $row['context_id'],
     *         ':user_id' => $row['user_id'],
     *         ':role' => $post['role']));
     *
     * Methodology:
     *
     * MySQL is the easiest case and one statement because we just use a
     * LAST_INSERT_ID(primary_key) trick to get the primary key whether
     * the INSERT works or the UPDATE clause is triggered.  With this trick,
     * the later call to lastInsertID() works regardless of whether the
     * effect is an INSERT of UPDATE.
     *
     * PostgreSQL is one statement if we don't have an "ON DUPLICATE KEY UPDATE"
     * clause.  We just use the "RETURNING primary_key" syntax and retrieve the new
     * primary key and stash it so we can make our (override) lastInsertID() work.
     *
     * For queries requesting duplicate key UPDATE processing in PostgreSQL we
     * break it into the INSERT ... RETURNING, and a SELECT to find the primary key
     * if the INSERT hit a duplicate key (stashing the primary key).  And if there
     * is an UPDATE clause, we run it using the primary key.
     *
     * A key to this is not to be transaction safe for the ON DUPLICATE handling for PotgreSQL.
     * The use case * for this is either the classic upsert operation or not to
     * fail when we see a race between multiple INSERT operations with the same
     * logical key.  If two records INSERT the same record - we let one win and
     * assume that eventually consistency is enough.
     *
     * References:
     *
     *     https://www.php.net/manual/en/pdo.lastinsertid.php#102614
     *     https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
     *     https://stackoverflow.com/questions/10492566/lastinsertid-does-not-work-in-postgresql
     *     https://stackoverflow.com/questions/34708509/how-to-use-returning-with-on-conflict-in-postgresql
     *     https://stackoverflow.com/questions/37204749/serial-in-postgres-is-being-increased-even-though-i-added-on-conflict-do-nothing
     */
    function upsertGetPKReturnError($sql, $values, $error_log=TRUE)
    {
        if ( stripos($sql, "RETURNING") !== false ) {
            if ( ! $this->isPgSQL() ) error_log('$PDOX->upsertGetPKReturnError() RETURNING is a non-portable construct'."\n".$sql);
            return self::queryReturnErrorInternal($sql, $values, $error_log);
        }

        if ( stripos($sql, "LAST_INSERT_ID") !== false ) {
            if ( ! $this->isMySQL() ) error_log('$PDOX->upsertGetPKReturnError() LAST_INSERT_ID is a non-portable construct'."\n".$sql);
            return self::queryReturnErrorInternal($sql, $values, $error_log);
        }

        $table = self::getInsertTable($sql);
        if ( !is_string($table) ) return self::queryReturnErrorInternal($sql, $values, $error_log);

        // Construct the meta data from the query
        $meta = self::getPDOXMeta($sql, $table);
        if ( ! is_array($meta) ) return self::queryReturnErrorInternal($sql, $values, $error_log);

        // Split the query into before and after the on DUPLICATE KEY clause
        $matches = array();
        preg_match('/ON\s+DUPLICATE\s+KEY\s+UPDATE\s+/i', $sql, $matches, PREG_OFFSET_CAPTURE);
        $sql_update = false;
        if ( count($matches) == 1 ) {
            $str = $matches[0][0];
            $pos = $matches[0][1];
            $len = strlen($str);
            $sql_update = substr($sql, $pos+$len);
            $sql = substr($sql,0,$pos);
        }
        // echo("<br/>sql=$sql\n"); echo("<br/>sql_update=$sql_update\n");

        // Do the actual upsert
        $this->PGSQL_LastInsertId = 0;

        // https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
        $pk = U::get($meta, 'pk');
        // For now MySQL and SQLITE
        if ( $this->isMySQL() ) {
            // If this table has no primary key - there is no need to handle lastInsertId()
            if ( $pk == 'none') {
                $retval = self::queryReturnErrorInternal($sql, $values);
                return $retval;
            }
            $newsql = $sql .  "\nON DUPLICATE KEY UPDATE\n" .
                "\n".$this->PDOX_upsert_marker." $pk=LAST_INSERT_ID($pk)";
            if ( is_string($sql_update) ) $newsql = $newsql . ",\n" . $sql_update;
            // echo("==========\n$newsql\n---------------\n");
            $retval = self::queryReturnErrorInternal($newsql, $values);
            return $retval;
        }

        if ( $this->isSQLite() ) {
            $newsql = $sql ;
            if ( is_string($sql_update) ) $newsql = $newsql .
                "\n".$this->PDOX_upsert_marker." ON DUPLICATE KEY UPDATE\n" . $sql_update;
            // echo("==========\n$newsql\n---------------\n");
            $retval = self::queryReturnErrorInternal($newsql, $values);
            return $retval;
        }

        // https://stackoverflow.com/questions/34708509/how-to-use-returning-with-on-conflict-in-postgresql
        // https://www.php.net/manual/en/pdo.lastinsertid.php#102614
        if ( $this->isPgSQL() ) {

            // If we have no ON DUPLICATE KEY, we can get away with adding a RETURNING

            if ( ! is_string($sql_update) ) {
                // If this table has no primary key - there is no need to handle lastInsertId()
                if ( $pk == 'none') {
                    $retval = self::queryReturnErrorInternal($sql, $values);
                    return $retval;
                }
                $newsql = $sql .  "\n".$this->PDOX_upsert_marker." RETURNING $pk";
                // echo("==========\n$newsql\n---------------\n");
                $retval = self::queryReturnErrorInternal($newsql, $values);
                if ( ! $retval->success ) return $retval;

                // If the INSERT worked we record the returned key and we are done
                if ( $retval->rowCount() > 0 ) {
                    $row = $retval->fetch(\PDO::FETCH_NUM);
                    if ( is_array($row) ) {
                        $this->PGSQL_LastInsertId = $row[0];
                    }
                }
                return $retval;
            }

            // We have an on DUPLICATE KEY - lets do this.
            $table = U::get($meta, 'table');
            $lkeys = U::get($meta, 'lk');

            if ( !is_string($table) || !is_array($lkeys) || count($lkeys) < 1) {
                error_log($sql);
                die('$PDOX->upsertGetPKReturnError() needs "table" and "lk" entries in the $meta parameter for PostgreSQL');
            }

            // Construct a where clause from the logical keys provided in values
            $whereclause = '';
            $wherevalues = array();
            foreach($lkeys as $lk) {
                $valkey = ':' . $lk;
                $value = U::get($values, $valkey, False);
                if ( $value === False ) {
                    error_log($sql);
                    die('$PDOX->upsertGetPKReturnError() missing '.$valkey.' in the values array for PostgreSQL');
                }
                if ( strlen($whereclause) > 0 ) $whereclause .= ' AND ';
                $whereclause .= $lk . '=' . $valkey;
                $wherevalues[$valkey] = $value;
            }
            // echo("++++++\n".$whereclause."\n");var_dump($wherevalues);echo("\n---------\n");

            // Check for the row existing before the insert to (a) avoid sequence explosion
            // and (b) because it is inexpensive
            // https://stackoverflow.com/questions/37204749/serial-in-postgres-is-being-increased-even-though-i-added-on-conflict-do-nothing

            // Lets try to find that primary key and make sure the where clause is only hitting one record
            $newsql = "SELECT $pk FROM $table WHERE ".$whereclause;

            $rows = self::allRowsDie($newsql, $wherevalues);
            $primary_key = False;
            if ( count($rows) == 1 ) {
                $primary_key = $rows[0][$pk];
                $this->PGSQL_LastInsertId = $primary_key;
            } else if ( count($rows) > 1 ) {
                error_log($newsql."\n".'$PDOX->upsertGetPKReturnError() pre-SELECT expects 0 or 1 row, got '.count($rows));
            }

            // If it is not there, do the INSERT, but still allow for another thread doing
            // the INSERT first.  If we win the race, get the returned primary key
            if ( ! $primary_key ) {
                $newsql = $sql .  "\n".$this->PDOX_upsert_marker." ON CONFLICT DO NOTHING RETURNING $pk";
                // echo("==========\n$newsql\n---------------\n");
                $retval = self::queryReturnErrorInternal($newsql, $values);
                if ( ! $retval->success ) return $retval;

                // If the INSERT worked we record the returned key and we are done
                if ( $retval->rowCount() > 0 ) {
                    $row = $retval->fetch(\PDO::FETCH_NUM);
                    if ( is_array($row) ) {
                        $this->PGSQL_LastInsertId = $row[0];
                        return $retval;
                    }
                }
            }

            // In case there was a race we lost - lets double check for that primary key
            // At this point, the record darn well better exist
            if ( ! $primary_key ) {
                $newsql = "SELECT $pk FROM $table WHERE ".$whereclause;

                $rows = self::allRowsDie($newsql, $wherevalues);
                if ( count($rows) != 1 ) {
                    error_log($newsql."\n".'$PDOX->upsertGetPKReturnError() post-SELECT expects exactly 1 row, got '.count($rows));
                    return $retval;
                }

                $primary_key = $rows[0][$pk];
                $this->PGSQL_LastInsertId = $primary_key;
            }

            // At this point we should have a primary key which we will use for UPDATE
            if ( $primary_key && is_string($sql_update) ) {
                $updatevalues = array();
                $updatevalues[':'.$pk] = $primary_key;
                foreach($values as $key => $value) {
                    if ( strpos($sql_update, $key) !== false) $updatevalues[$key] = $value;
                }

                $newsql = "UPDATE ".$table." SET\n" . $sql_update . "\nWHERE :$pk = $primary_key";

                // echo("******\n".$newsql."\n");var_dump($updatevalues);echo("\n---------\n");

                $retval = self::queryReturnErrorInternal($newsql, $updatevalues);
                if ( ! $retval->success ) return $retval;
            }

            return $retval;
        }
    }

    /**
     * lastInsertId - Override this in the name of portability
     *
     * This is needed because upsert in MySQL, and PostgreSQL are quite different
     * and in particular lastInsertId() in stock PDO is only useful for MySQL.
     */
    // Switch to declaring return value after our minimum version is PHP 8.0
    // function lastInsertId($seqname = NULL) : string|false {

    // Quick fix to suppress the deprecation warnings in 8.1
    // https://wiki.php.net/rfc/internal_method_return_types
    #[\ReturnTypeWillChange]
    function lastInsertId($seqname = NULL) {
        // Is there is a sequence, assume they know what they are doing :)
        if ( $seqname != NULL ) return parent::lastInsertId($seqname);
        if ( ! $this->isPgSQL() ) return parent::lastInsertId($seqname);

        if ( isset($this->PGSQL_LastInsertId) && $this->PGSQL_LastInsertId > 0 ) return($this->PGSQL_LastInsertId);

        if ( isset($this->PDOX_LastInsertStatement) && $this->PDOX_LastInsertStatement instanceof \PDOStatement ) {
            $stmt = $this->PDOX_LastInsertStatement;
            if ( $stmt->rowCount() > 0 ) {
                $row = $stmt->fetch(\PDO::FETCH_NUM);
                if ( is_array($row) ) {
                    $this->PGSQL_LastInsertId = $row[0];
                    return $this->PGSQL_LastInsertId;
                }
            }
        }

        $msg = 'Unable to determine lastInsertId()';
        if ( isset($this->PGSQL_LastInsertQuery) && is_string($this->PGSQL_LastInsertQuery) ) {
            $msg = $this->PGSQL_LastInsertQuery . " unable to determing lastInsertId()";;
        }
        error_log($msg);
        return $this->PGSQL_LastInsertId;
    }

    /** limitVars - Filter out substitution variables that are not needed
     * @param $sql The SQL to execute in a string.
     * @param $arr An optional array of the substitition values if needed by the query
     * @return An array of the variables that actually can be found in the SQL.
     */
    public static function limitVars($sql, $vars) {
        $retval = array();
        $parts = preg_split('/\s+/', str_replace(array('(',')'),array(' ',' '), $sql));
        foreach($parts as $part) {
            if ( strpos($part,':') !== 0 ) continue;
            if ( ! U::get($vars, $part) ) continue;
            $retval[$part] = U::get($vars, $part);
        }
        return $retval;
    }

}
