<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * This is a class holding convienence methods to access the json column for the core objects.
 * 
 * This is a more general storage than the settings column.
 */

trait JsonTrait {

    /**
     * Load the json field for this entity
     *
     * @return string This returns the json string - it is not parsed - if there is
     * nothing to return - this returns "false"
     */
    public function getJson()
    {
        global $CFG, $PDOX;

        $row = $PDOX->rowDie("SELECT json FROM {$CFG->dbprefix}{$this->TABLE_NAME}
                WHERE $this->PRIMARY_KEY = :PK",
            array(":PK" => $this->id));
        if ( $row === false ) return false;
        $json = $row['json'];
        if ( $json === null ) return false;
        return $json;
    }

    /**
     * Get a JSON key for this entity (legacy array style)
     *
     *
     * @param $key The key to be retrieved from the JSON
     * @param $default The default value (optional)
     *
     */
    // TODO: Remove this
    public function getJsonKeyLegacy($key,$default=false)
    {
        global $CFG, $PDOX;

        $jsonStr = $this->getJson();
        if ( ! $jsonStr ) return $default;
        $json = json_decode($jsonStr, true);
        if ( ! $json ) return $default;
        return U::get($json, $key, $default);
    }

    /**
     * Get a JSON key for this entity
     *
     * @param $key The key to be retrieved from the JSON
     * @param $default The default value (optional)
     *
     */
    public function getJsonKey($key,$default=false)
    {
        global $CFG, $PDOX;

        $jsonStr = $this->getJson();
        if ( ! $jsonStr ) return $default;
        $json = json_decode($jsonStr);
        if ( ! $json ) return $default;
        return isset($json->{$key}) ? $json->{$key} : $default;
    }

    /**
     * Set the JSON entry for this entity
     *
     * @param $json This is a string - no validation is done
     *
     */
    public function setJson($json)
    {
        global $CFG, $PDOX;

        $q = $PDOX->queryDie("UPDATE {$CFG->dbprefix}{$this->TABLE_NAME}
                SET json = :SET, updated_at=NOW()
                WHERE $this->PRIMARY_KEY = :PK",
            array(":SET" => $json, ":PK" => $this->id)
        );
    }

    /**
     * Set/update a JSON key for this entity
     *
     * @param $key The key to be inserted/updated in the JSON
     * @param $value The value to be inserted/updated in the JSON
     *
     */
    public function setJsonKey($key,$value)
    {
        global $CFG, $PDOX;

        $old = $this->getJson();
        $old_json = json_decode($old);
        if ( $old_json == null ) $old_json = new \stdClass();
        $old_json->{$key} = $value;
        $new = json_encode($old_json);
        $this->setJson($new);
    }

    /**
     * Set/update an array of JSON keys for this entity
     *
     * @param $values An array of key/value pairs to be inserted/updated in the JSON
     *
     */
    public function setJsonKeys($values)
    {
        global $CFG, $PDOX;

        if ( ! is_array($values) ) throw new \Exception('setJsonKeys requires an array as parameter.');
        $old = $this->getJson();
        $old_json = json_decode($old);
        if ( $old_json == null ) $old_json = new \stdClass();
        foreach($values as $key => $value) {
            $old_json->{$key} = $value;
        }
        $new = json_encode($old_json);
        $this->setJson($new);
    }
}
