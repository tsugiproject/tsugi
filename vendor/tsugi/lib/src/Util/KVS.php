<?php

namespace Tsugi\Util;

/*
 * KVS - A Simple Key/Value Store
 *
 * The Key-Value Store is a simple NoSql like abstraction that is likely
 * implemented by layering on top of an SQL table, with a JSON text field
 * and number of extracted primary logical keys that are used as indexes.
 *
 * Is designed to be very efficiently implemented in a MySQL 5.7 / 8.0
 * that supports the JSON data type natively - but can be implemented
 * efficiently on MySQL 5.6 or earlier.
 *
 * The main item in each row is a JSON body which at its top level
 * must be an object (i.e. a PHP array with the top level that contains
 * key / value pairs).  Below the top level of the JSON, it is schemaless
 * and any arbitrary structures can be represented.
 *
 * In addition for efficiency, some of the top-level keys are reserved,
 * have rules for naming, and imply semantic meaning.  Other than 'id',
 * these are optional.
 *
 * id - is an integer primary key for the record, it is auto-increment,
 * not null, and unique.  It must specified on updates, and will
 * be generated for inserts.
 *
 * uk1 - is a VARCHAR string with a maximum length of 150 and must
 * be unique across the store.  It is indexed for efficiency.
 *
 * sk1 - is a VARCHAR string with a maximum length of 75 and does
 * not have to be unique across the store.  It is indexed for efficiency.
 *
 * tk1 - is a TEXT string where the first 75 characters are indexed
 * but not expected to be unique.
 *
 * co1, co2 - are VARCHAR string maximum length 150.  It is not indexed and
 * does not need to be unique.  But it is more efficient than reading and
 * parsing all the JSON.
 *
 * All of these can be left blank - these keys will be faster than reading
 * all the JSON and looking through each object.  In MySQL these will be
 * pulled out of the JSON and maintained in their own columns with indexes.
 *
 * These keys will be more efficient in things like WHERE clauses, LIMIT
 * clauses, and ORDER BY clauses.
 */

class KVS {

    const ID = 'id';
    const UK1 = 'uk1';
    const SK1 = 'sk1';
    const TK1 = 'tk1';
    const CO1 = 'co1';
    const CO2 = 'co2';

    private static $allKeys = array(
        self::ID, self::UK1, self::SK1, self::TK1,
        self::CO1, self::CO2 );

    private $PDOX = null;
    private $NOW = 'NOW()'; // MySQL

    protected $KVS_TABLE = null;
    private $KVS_FK = null;
    protected $KVS_FK_NAME = null;
    private $KVS_SK = null;
    protected $KVS_SK_NAME = null;

    /*
     * Constructor
     *
     *     $PDOX = new \Tsugi\Util\PDOX('sqlite::memory');
     *     $kvs = new KVS($PDOX, 'lti_result_kvs', 'result_id', 1, 'user_id', 1);
     */
    public function __construct($PDOX, $KVS_TABLE, $KVS_FK_NAME, $KVS_FK, $KVS_SK_NAME, $KVS_SK) {
        $this->PDOX = $PDOX;
        $this->KVS_TABLE = $KVS_TABLE;
        $this->KVS_FK_NAME = $KVS_FK_NAME;
        $this->KVS_FK = $KVS_FK;
        $this->KVS_SK_NAME = $KVS_SK_NAME;
        $this->KVS_SK = $KVS_SK;
        // During unit tests...
        $driver = $PDOX->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ( strpos($driver, 'sqlite') !== false ) $this->NOW = "datetime('now')";
    }

    /*
     * Insert a row
     *
     * $data array A structured array to be inserted into the KVS.
     * The array must be completely key-value at its top level.  Below
     * that, anything that can be serialized into JSON is allowed.
     */
    public function insert($data) {
        $map = $this->extractMap($data);
        $sql = "INSERT INTO $this->KVS_TABLE
            /*PDOX pk: id lk: $this->KVS_FK_NAME,$this->KVS_SK_NAME,uk1 */
            ($this->KVS_FK_NAME, $this->KVS_SK_NAME,
            uk1, sk1, tk1, co1, co2, json_body, created_at)
            VALUES (:foreign_key, :secondary_key, :uk1, :sk1, :tk1, :co1, :co2, :json_body, $this->NOW)";
        $map[':foreign_key'] = $this->KVS_FK;
        $map[':secondary_key'] = $this->KVS_SK;

        $copy = self::preStoreCleanup($data);
        $map[':json_body'] = json_encode($copy);
        $stmt = $this->PDOX->queryDie($sql, $map);
        if ( $stmt->success) return(intval($this->PDOX->lastInsertId()));
        return false;
    }

    /*
     * Insert a row, or update it if there is a duplicate key clash
     *
     * $data array A structured array to be inserted into the KVS.
     * The array must be completely key-value at its top level.  Below
     * that, anything that can be serialized into JSON is allowed.
     */
    public function insertOrUpdate($data) {
        $map = $this->extractMap($data);
        $sql = "INSERT INTO $this->KVS_TABLE
            /*PDOX pk: id lk: $this->KVS_FK_NAME,$this->KVS_SK_NAME,uk1 */
            ($this->KVS_FK_NAME, $this->KVS_SK_NAME,
            uk1, sk1, tk1, co1, co2, json_body, created_at)
            VALUES (:foreign_key, :uk1, :sk1, :tk1, :co1, :co2, :json_body, $this->NOW)
            ON DUPLICATE KEY UPDATE
            $this->KVS_FK_NAME=:foreign_key, $this->KVS_SK_NAME=:secondary_key,
            sk1=:sk1, tk1=:tk1,
            co1=:co1, co2=:co2, json_body=:json_body, updated_at=$this->NOW ";
        $map[':foreign_key'] = $this->KVS_FK;
        $map[':secondary_key'] = $this->KVS_SK;
        $map[':json_body'] = json_encode($data);
        $stmt = $this->PDOX->queryDie($sql, $map);

        if ( $stmt->success) return($this->PDOX->lastInsertId());
        return false;
    }

    /*
     * Update a row
     *
     * $data array A structured array to be inserted into the KVS.
     * The array must be completely key-value at its top level.  Below
     * that, anything that can be serialized into JSON is allowed.
     */
    public function update($data, $changepk=false) {
        $map = $this->extractMap($data);
        $keys = $this->extractKeys($data);
        $where = false;
        $wheremap = array();
        $more = '';
        if ( $keys->id ) {
            $where = 'id=:id';
            $wheremap[':id'] = $keys->id;
            // TODO: Can we set this to null?
            if ( $changepk || $keys->uk1 ) {
                $more = ', uk1=:uk1';
            } else {
                unset($map[':uk1']); // Leave uk1 alone
            }
        } else if ( $keys->uk1 ) { // Update using uk1 as where clause
            $where = 'uk1=:uk1';
            $wheremap[':uk1'] = $keys->uk1;
        }
        if ( ! $where ) throw new \Exception('update requires id or pk1 field');

        $wheremap[':foreign_key'] = $this->KVS_FK;
        $wheremap[':secondary_key'] = $this->KVS_SK;
        $sql = "SELECT json_body FROM $this->KVS_TABLE
            WHERE $this->KVS_FK_NAME = :foreign_key AND $this->KVS_SK_NAME = :secondary_key AND $where";
        $rows = $this->PDOX->allRowsDie($sql, $wheremap);
        if ( count($rows) == 0 ) return 0;
        if ( count($rows) > 1 ) {
            throw new \Exception('Where clause selected more than one row');
        }

        $updatemap = array_merge($map, $wheremap);
        $row = $rows[0];
        $old_json = json_decode($row['json_body'], true);
        $copy = array_merge($old_json, $data);
        $copy = self::preStoreCleanup($copy);
        $updatemap[':json_body'] = json_encode($copy);


        $sql = "UPDATE $this->KVS_TABLE SET sk1=:sk1, tk1=:tk1, co1=:co1,
            co2=:co2, json_body=:json_body, updated_at=$this->NOW $more
            WHERE $this->KVS_FK_NAME = :foreign_key AND $this->KVS_SK_NAME = :secondary_key AND $where";

        $stmt = $this->PDOX->queryDie($sql, $updatemap);

        if ( ! $stmt->success ) return false;
        return $stmt->rowCount();
    }

    /*
     * Note that the JSON is returned as an associative array
     */
    public function selectOne($where) {
        $clause = false;
        $values = false;
        $retval = self::extractWhere($where, $clause, $values);
        if ( is_string($retval) ) throw new \Exception($val);

        $sql = "SELECT KVS.id AS id, json_body, KVS.created_at, KVS.updated_at
            FROM $this->KVS_TABLE AS KVS
            WHERE $this->KVS_FK_NAME = :foreign_key AND $this->KVS_SK_NAME = :secondary_key AND ".$clause." LIMIT 1";
        $values[':foreign_key'] = $this->KVS_FK;
        $values[':secondary_key'] = $this->KVS_SK;
        $row = $this->PDOX->rowDie($sql, $values);
        if ( $row === false ) return false;

        $retval = json_decode($row['json_body'], true);
        if ( is_array($retval) ) {
            $retval['id'] = intval($row['id']);
            $retval['created_at'] = $row['created_at'];
            $retval['updated_at'] = $row['updated_at'];
        }
        return $retval;
    }

    public function delete($where) {
        $clause = false;
        $values = false;
        $retval = self::extractWhere($where, $clause, $values);
        if ( is_string($retval) ) throw new \Exception($val);

        $sql = "DELETE FROM $this->KVS_TABLE
            WHERE $this->KVS_FK_NAME = :foreign_key AND $this->KVS_SK_NAME = :secondary_key AND ".$clause;
        $values[':foreign_key'] = $this->KVS_FK;
        $values[':secondary_key'] = $this->KVS_SK;
        $retval = $this->PDOX->queryDie($sql, $values);
        return $retval->success;
    }

    /*
     * $order array of the fields and the order
     */
    public function selectAll($where=false, $order=false, $limit=false) {
        $clause = false;
        $values = array();
        if ( is_array($where) ) {
            $retval = self::extractWhere($where, $clause, $values);
            if ( is_string($retval) ) throw new \Exception($val);
        }
        $orderby = '';
        if ( is_string($order) ) {
            $orderby = self::extractOrder($order);
            if ( $orderby === false ) throw new \Exception('Invalid Order value');
        }

        $sql = "SELECT KVS.id AS id, json_body, KVS.created_at, KVS.updated_at
            FROM $this->KVS_TABLE AS KVS
            WHERE $this->KVS_FK_NAME = :foreign_key";
        if ( $clause ) $sql .= ' AND '.$clause;
        if ( $orderby ) $sql .= ' '.$orderby;
        $values[':foreign_key'] = $this->KVS_FK;
        $rows = $this->PDOX->allRowsDie($sql, $values);
        return $rows;
    }

    private static function preStoreCleanup($data) {
        $copy = $data;
        if ( U::get($data,'id') ) unset($copy['id']);
        if ( U::get($data,'created_at') ) unset($copy['created_at']);
        if ( U::get($data,'updated_at') ) unset($copy['updated_at']);
        return $copy;
    }

    private static function extractKeys($data) {
        $val = self::validate($data);
        if ( is_string($val) ) throw new \Exception($val);

        $retval = new \stdClass();
        foreach (self::$allKeys as $key ) {
            $retval->{$key} = U::get($data, $key);
        }
        return $retval;
    }

    private static function extractMap($data) {
        $retval = self::extractKeys($data);
        $arr = array();
        $arr[':uk1'] = $retval->uk1;
        $arr[':sk1'] = $retval->sk1;
        $arr[':tk1'] = $retval->tk1;
        $arr[':co1'] = $retval->co1;
        $arr[':co2'] = $retval->co2;
        return $arr;
    }

    public static function extractWhere($data, &$where, &$values) {
        $keys = self::extractKeys($data);
        $values = array();
        $where = '';

        foreach (self::$allKeys as $key ) {
            if ( ! $keys->{$key} ) continue;
            if ( strlen($where) > 0 ) $where .= ' AND ';
            $value = $keys->{$key};
            if ( strpos($value, "LIKE ") === 0 ) {
                $where .=  $key . ' LIKE :' . $key;
                $values[':'.$key] = substr($value,5);
            } else {
                $where .=  $key . ' = :' . $key;
                $values[':'.$key] = $value;
            }
        }
        if ( count($values) < 1 ) return "No key found for WHERE clause";
        return true;
    }

    public static function extractOrder($orders) {
        $retval = '';
        foreach ($orders as $order ) {
            if ( strlen($retval) > 0 ) $retval .= ', ';
            if ( in_array($order, self::$allKeys) ) {
                $retval .= $order;
                continue;
            }
            $pieces = explode(' ', $order);
            if ( count($pieces) == 2 && $pieces[1] == 'DESC' &&
                in_array($pieces[0], self::$allKeys) ) {

                $retval .= $order;
                continue;
            }
            return false;
        }
        return $retval;
    }


    /**
     * Validate a kvs record
     */
    public static function validate($data) {
        if ( ! is_array($data) ) return '$data must be an array';
        $id = U::get($data, 'id');
        if ( $id ) {
            if ( ! is_int($id) ) return "id must be an an integer";
        }
        $uk1 = U::get($data, 'uk1');
        if ( $uk1 ) {
            if ( ! is_string($uk1) ) return "uk1 must be a string";
            if ( strlen($uk1) < 1 || strlen($uk1) > 150 ) return "uk1 must be no more than 150 characters";
        }
        $sk1 = U::get($data, 'sk1');
        if ( $sk1 ) {
            if ( ! is_string($sk1) ) return "sk1 must be a string";
            if ( strlen($sk1) < 1 || strlen($sk1) > 75 ) return "sk1 must be no more than 75 characters";
        }
        $tk1 = U::get($data, 'tk1');
        if ( $tk1 ) {
            if ( ! is_string($tk1) ) return "tk1 must be a string";
            if ( strlen($tk1) < 1 ) return "tk1 cannot be empty";
        }
        $co1 = U::get($data, 'co1');
        if ( $co1 ) {
            if ( ! is_string($co1) ) return "co1 must be a string";
            if ( strlen($co1) < 1 || strlen($co1) > 150 ) return "co1 must be no more than 150 characters";
        }
        $co2 = U::get($data, 'co2');
        if ( $co2 ) {
            if ( ! is_string($co2) ) return "co2 must be a string";
            if ( strlen($co2) < 1 || strlen($co2) > 150 ) return "co2 must be no more than 150 characters";
        }
        return true;
    }

}
