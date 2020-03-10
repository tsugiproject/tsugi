<?php

function loadAnnotations($LAUNCH, $user_id) {
    if ( ! $LAUNCH->user->instructor || $user_id == $LAUNCH->user->id ){
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

function storeAnnotations($LAUNCH, $user_id, $annotations) {
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

