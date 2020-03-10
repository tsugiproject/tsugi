<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Annotate;

require_once "../../config.php";
require_once "annotate_util.php";

$pieces = U::rest_path();
if ( ! isset($pieces->controller) || strlen($pieces->controller) < 1 ) {
    http_response_code(500);
    echo("<pre>\nMissing Session\n\n");
    echo(htmlentities(print_r($pieces, TRUE)));
    die();
}

$sessparts = explode(':',$pieces->controller);
if ( count($sessparts) != 2 ) {
    http_response_code(500);
    echo("<pre>\nMissing user_id\n\n");
    echo(htmlentities(print_r($pieces, TRUE)));
    die();
}

$sess_id = $sessparts[0];
$user_id = $sessparts[1];

// Force the session ID REST style :)
$_GET[session_name()] = $sess_id;
$LAUNCH = LTIX::requireData();

// http://docs.annotatorjs.org/en/v1.2.x/storage.html#core-storage-api
if ( strlen($pieces->action) < 1 ) {
    $retval = array(
          "name" => "Annotator Store API",
          "version" => "2.0.0",
          "author" => "Charles R. Severance"
    );
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($retval, JSON_PRETTY_PRINT));
    return;
}

if ( ! trim($pieces->action) == 'annotations' ) {
    http_response_code(404);
    die("Expecting 'session-id/annotations'");
}
    
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $annotations = loadAnnotations($LAUNCH, $user_id);
    $input = file_get_contents('php://input');
    $json = json_decode($input);
    $json->id = uniqid();
    $user = new \stdClass();
    $user->id = $LAUNCH->user->id;
    $user->name = $LAUNCH->user->displayname;
    $user->email = $LAUNCH->user->email;
    $json->user = $user;

    $permlist = array("group:__world__");
    $permissions = array(
        "read" => $permlist,
        "update" => $permlist,
        "delete" => $permlist,
    );
    $json->permissions = $permissions;

    $annotations[] = $json;

    storeAnnotations($LAUNCH, $user_id, $annotations);

    $location = $pieces->current . '/annotations/' . $json->id;
    http_response_code(303);
    header('Location: '.$location);
    echo(json_encode($json, JSON_PRETTY_PRINT));
    return;
}

if ( $_SERVER['REQUEST_METHOD'] === 'GET' && count($pieces->parameters) < 1 ) {
    $annotations = loadAnnotations($LAUNCH, $user_id);
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($annotations, JSON_PRETTY_PRINT));
    return;
}

if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
    $annotations = loadAnnotations($LAUNCH, $user_id);
    $id = $pieces->parameters[0];
    foreach($annotations as $annotation) {
        if ( $id == $annotation->id ) {
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($annotation, JSON_PRETTY_PRINT));
            return;
        }
    }
    http_response_code(404);
    return;
}

if ( $_SERVER['REQUEST_METHOD'] === 'PUT' ) {
    $annotations = loadAnnotations($LAUNCH, $user_id);

    $input = file_get_contents('php://input');
    $json = json_decode($input);
    $permlist = array("group:__world__");
    $permissions = array(
        "read" => $permlist,
        "update" => $permlist,
        "delete" => $permlist,
    );
    $json->permissions = $permissions;
    $id = $pieces->parameters[0];
    for($i=0; $i<count($annotations); $i++) {
        $annotation = $annotations[$i];
        if ( $id == $annotation->id ) {
            $annotations[$i] = $json;
        }
    }
    storeAnnotations($LAUNCH, $user_id, $annotations);
    $location = $pieces->current . '/annotations/' . $id;
    http_response_code(303);
    header('Location: '.$location);
    return;
}

if ( $_SERVER['REQUEST_METHOD'] === 'DELETE' ) {
    $annotations = loadAnnotations($LAUNCH, $user_id);

    $id = $pieces->parameters[0];
    $found = false;
    for($i=0; $i<count($annotations); $i++) {
        $annotation = $annotations[$i];
        if ( $id == $annotation->id ) {
            $found = $i;
        }
    }
    if ( $found !== false ) {
        unset($annotations[$found]);
        $annotations = array_values($annotations);
    }
    storeAnnotations($LAUNCH, $user_id, $annotations);
    http_response_code(204);
    return;
}

var_dump($pieces);
http_response_code(405);
die("Working on the rest...");



/*
 object(stdClass)#7 (8) {
  ["parent"]=>
  string(23) "/py4e/mod/ckpaper/store"
  ["base_url"]=>
  string(21) "http://localhost:8888"
  ["controller"]=>
  string(17) "92992929292929292"
  ["extra"]=>
  string(3) "zap"
  ["action"]=>
  string(3) "zap"
  ["parameters"]=>
  array(0) {
  }
  ["current"]=>
  string(41) "/py4e/mod/ckpaper/store/92992929292929292"
  ["full"]=>
  string(45) "/py4e/mod/ckpaper/store/92992929292929292/zap"
}
 */

/* Annotation format

    http://docs.annotatorjs.org/en/v1.2.x/annotation-format.html#annotation-format
 
{
  "id": "39fc339cf058bd22176771b3e3187329",  # unique id (added by backend)
  "annotator_schema_version": "v1.0",        # schema version: default v1.0
  "created": "2011-05-24T18:52:08.036814",   # created datetime in iso8601 format (added by backend)
  "updated": "2011-05-26T12:17:05.012544",   # updated datetime in iso8601 format (added by backend)
  "text": "A note I wrote",                  # content of annotation
  "quote": "the text that was annotated",    # the annotated text (added by frontend)
  "uri": "http://example.com",               # URI of annotated document (added by frontend)
  "ranges": [                                # list of ranges covered by annotation (usually only one entry)
    {
      "start": "/p[69]/span/span",           # (relative) XPath to start element
      "end": "/p[70]/span/span",             # (relative) XPath to end element
      "startOffset": 0,                      # character offset within start element
      "endOffset": 120                       # character offset within end element
    }
  ],
  "user": "alice",                           # user id of annotation owner (can also be an object with an 'id' property)
  "consumer": "annotateit",                  # consumer key of backend
  "tags": [ "review", "error" ],             # list of tags (from Tags plugin)
  "permissions": {                           # annotation permissions (from Permissions/AnnotateItPermissions plugin)
    "read": ["group:__world__"],
    "admin": [],
    "update": [],
    "delete": []
  }
}

 */
