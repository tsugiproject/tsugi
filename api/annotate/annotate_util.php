<?php

function loadAnnotations($LAUNCH, $result_id) {
    if ( ! $LAUNCH->user->instructor || $result_id == $LAUNCH->result->id ){
        $annotations = $LAUNCH->result->getJsonKey('annotations', '[ ]');
    } else if ( $LAUNCH->user->instructor ) {
        die('Still working on this');
    } else {
        http_response_code(403);
        die();
    }
    $annotations = json_decode($annotations);
    if ( ! is_array($annotations) ) $annotations = array();
    return $annotations;
}

function storeAnnotations($LAUNCH, $result_id, $annotations) {
    if ( ! is_string($annotations) ) {
        $annotations = json_encode($annotations);
    }

    if ( $result_id == $LAUNCH->result->id ){
        $LAUNCH->result->setJsonKey('annotations', $annotations);
    } else if ( $LAUNCH->user->instructor ) {
        die('Still working on this');
    } else {
        http_response_code(403);
        die();
    }
}

