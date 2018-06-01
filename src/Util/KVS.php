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

    private $PDOX = null;
    private $KVS_FK = null;
    private $NOW = 'NOW()';

    protected $KVS_TABLE = null;
    protected $KVS_FK_NAME = null;

    /*
     * Constructor
     *
     *     $PDOX = new \Tsugi\Util\PDOX('sqlite::memory');
     *     $kvs = new KVS($PDOX, 'lti_result_kvs', 'result_id', 1);
     */
    public function __construct($PDOX, $KVS_TABLE, $KVS_FK_NAME, $KVS_FK) {
        $this->PDOX = $PDOX;
        $this->KVS_TABLE = $KVS_TABLE;
        $this->KVS_FK_NAME = $KVS_FK_NAME;
        $this->KVS_FK = $KVS_FK;
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
        if ( ! is_array($data) ) throw new \Exception('KVS->insert requires array as parameter');
        $keys = $this->extractKeys($data);
        $map = $this->extractMap($data);
        $sql = "INSERT INTO $this->KVS_TABLE ($this->KVS_FK_NAME, uk1, sk1, tk1, co1, co2, json_body, created_at)
            VALUES (:foreign_key, :uk1, :sk1, :tk1, :co1, :co2, :json_body, $this->NOW)";
        $map[':foreign_key'] = $this->KVS_FK;
        $map[':json_body'] = json_encode($data);
        $stmt = $this->PDOX->queryDie($sql, $map);

        if ( $stmt->success) return($this->PDOX->lastInsertId());
        return false;
    }

    // public function update($data);
    // public function insertOrUpdate($data);
    // public function delete($where);
    // public function getOneRow($where);
    // public function getAllRows($where, $order, $limit);

    private function extractKeys($data) {
        $retval = new \stdClass();
        $retval->uk1 = U::get($data, 'uk1');
        $retval->sk1 = U::get($data, 'sk1');
        $retval->tk1 = U::get($data, 'tk1');
        $retval->co1 = U::get($data, 'co1');
        $retval->co2 = U::get($data, 'co2');
        return $retval;
    }

    private function extractMap($data) {
        $retval = self::extractKeys($data);
        $arr = array();
        $arr[':uk1'] = $retval->uk1;
        $arr[':sk1'] = $retval->sk1;
        $arr[':tk1'] = $retval->tk1;
        $arr[':co1'] = $retval->co1;
        $arr[':co2'] = $retval->co2;
        return $arr;
    }

}
