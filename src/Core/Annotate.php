<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * Our class to handle annotation storage
 */
class Annotate {

    /**
     * Load the Annotations
     *
     * @param $LAUNCH The current launch
     * @param $user_id The primary key of the user in question
     *
     * @return The annotation array
     */
    public static function loadAnnotations($LAUNCH, $user_id) {
        global $CFG, $PDOX;
        if ( ! $LAUNCH->user->instructor || $user_id == $LAUNCH->user->id ){
            $annotations = $LAUNCH->result->getJsonKey('annotations', '[ ]');
        } else if ( $LAUNCH->user->instructor ) {
            $p = $CFG->dbprefix;
            $row = $PDOX->rowDie(
                "SELECT json
                FROM {$p}lti_result AS R
                WHERE R.link_id = :LID and R.user_id = :UID",
                array(
                    ":UID" => $user_id,
                    ":LID" => $LAUNCH->link->id
                )
            );
            if ( ! $row ) return array();
            $json_str = $row['json'];
            $json = json_decode($json_str);
            if ( isset($json->annotations) ) return $json->annotations;
            return array();
        } else {
            http_response_code(403);
            die();
        }
        if ( is_string($annotations) ) $annotations = json_decode($annotations);
        if ( ! is_array($annotations) ) $annotations = array();
        return $annotations;
    }

    /**
     * Store the Annotations
     *
     * @param $LAUNCH The current launch
     * @param $user_id The primary key of the user in question
     * @param $annotations The annotation array
     */
    public static function storeAnnotations($LAUNCH, $user_id, $annotations) {
        if ( ! is_string($annotations) ) {
            $annotations = json_encode($annotations);
        }
    
        if ( $user_id == $LAUNCH->user->id ){
            $LAUNCH->result->setJsonKey('annotations', $annotations);
        } else if ( $LAUNCH->user->instructor ) {
            die('Still working on this');
        } else {
            http_response_code(403);
            die();
        }
    }

}

