<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Annotator storage API.
 *
 * GET/POST/PUT/DELETE /api/annotate/{session}:{user}/...
 */
class Annotate {

    public function handle(): void
    {
        $pieces = U::rest_path();
        if ( ! isset($pieces->controller) || U::strlen($pieces->controller) < 1 ) {
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
        if ( U::strlen($pieces->action) < 1 ) {
            $retval = array(
                  "name" => "Annotator Store API",
                  "version" => "2.0.0",
                  "author" => "Charles R. Severance"
            );
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($retval, JSON_PRETTY_PRINT));
            return;
        }

        // ! binds tighter than ==. Leave that comparison as it was.
        if ( ! trim($pieces->action) == 'annotations' ) {
            http_response_code(404);
            die("Expecting 'session-id/annotations'");
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);
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

            $this->storeAnnotations($LAUNCH, $user_id, $annotations);

            $location = $pieces->current . '/annotations/' . $json->id;
            http_response_code(303);
            header('Location: '.$location);
            echo(json_encode($json, JSON_PRETTY_PRINT));
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'GET' && count($pieces->parameters) < 1 ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode($annotations, JSON_PRETTY_PRINT));
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);
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
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);

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
            $this->storeAnnotations($LAUNCH, $user_id, $annotations);
            $location = $pieces->current . '/annotations/' . $id;
            http_response_code(303);
            header('Location: '.$location);
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'DELETE' ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);

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
            $this->storeAnnotations($LAUNCH, $user_id, $annotations);
            http_response_code(204);
            return;
        }

        var_dump($pieces);
        http_response_code(405);
        die("Working on the rest...");
    }

    private function loadAnnotations($LAUNCH, $user_id)
    {
        $annotations = $LAUNCH->result->getJsonKeyForUser('annotations', '[ ]', $user_id);
        if ( is_string($annotations) ) $annotations = json_decode($annotations);
        if ( ! is_array($annotations) ) $annotations = array();
        return $annotations;
    }

    private function storeAnnotations($LAUNCH, $user_id, $annotations): void
    {
        if ( $user_id == $LAUNCH->user->id ){
            $LAUNCH->result->setJsonKey('annotations', $annotations);
        } else if ( $LAUNCH->user->instructor ) {
            $LAUNCH->result->setJsonKeyForUser('annotations', $annotations, $user_id);
        } else {
            http_response_code(403);
            die();
        }
    }

}
