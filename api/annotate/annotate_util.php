<?php

function loadAnnotations($LAUNCH, $user_id) {
    $annotations = $LAUNCH->result->getJsonKeyForUser('annotations', '[ ]', $user_id);
    if ( is_string($annotations) ) $annotations = json_decode($annotations);
    if ( ! is_array($annotations) ) $annotations = array();
    return $annotations;
}

function storeAnnotations($LAUNCH, $user_id, $annotations) {
    if ( $user_id == $LAUNCH->user->id ){
        $LAUNCH->result->setJsonKey('annotations', $annotations);
    } else if ( $LAUNCH->user->instructor ) {
        $LAUNCH->result->setJsonKeyForUser('annotations', $annotations, $user_id);
    } else {
        http_response_code(403);
        die();
    }
}

