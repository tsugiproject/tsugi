<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * Sticky grader annotation store.
 *
 * GET/POST/DELETE /api/stickygrader/{session}:{user}/...
 *
 * Loaded annotations that come back as an object are cast to an array.
 * The annotate endpoint leaves them as a list.
 */
class StickyGrader {

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

        if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);
            header('Content-Type: application/json; charset=utf-8');
            echo(json_encode(array_values($annotations), JSON_PRETTY_PRINT));
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $input = file_get_contents('php://input');
            $json = json_decode($input);
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);
            $annotations[$json->id] = $json;
            $this->storeAnnotations($LAUNCH, $user_id, $annotations);
            return;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'DELETE' ) {
            $annotations = $this->loadAnnotations($LAUNCH, $user_id);

            if ( isset($pieces->extra) && U::strlen($pieces->extra) > 0 ) {
                $id = $pieces->extra;
                unset($annotations[$id]);
            } else {
                error_log('Resetting annotations');
                $annotations = array();
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
        if ( is_object($annotations) ) $annotations = (array) $annotations;
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
