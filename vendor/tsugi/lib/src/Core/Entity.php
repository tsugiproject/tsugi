<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;

/**
 * This is a class holding commmon functionality to be extended by various other classes.
 */

class Entity {
    /**
     * All extending classes must define these member variables
     */

    // protected $TABLE_NAME = "lti_result";
    // protected $PRIMARY_KEY = "result_id";
    // public $id = ...;

    /**
     * A reference to our containing launch
     */
    public $launch;

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
     * Set the JSON entry for this entity
     *
     * @params $json This is a string - no validation is done
     *
     */
    public function setJson($json)
    {
        global $CFG, $PDOX;

        $q = $PDOX->queryDie("UPDATE {$CFG->dbprefix}{$this->TABLE_NAME}
                SET json = :SET WHERE $this->PRIMARY_KEY = :PK",
            array(":SET" => $json, ":PK" => $this->id)
        );
    }

    /**
     * Set/update a JSON key for this entity
     *
     * @params $key The key to be inserted/updated in the JSON
     * @params $value The value to be inserted/updated in the JSON
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

}
