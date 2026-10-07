<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Controllers\Tool;
use Tsugi\Core\LTIX;
use Tsugi\Core\Rest;
use Tsugi\Core\Settings as LinkSettings;

/**
 * POST /api/settings.php
 */
class Settings {

    public function handle(): void
    {
        if ( Rest::preFlight() ) return;

        $LAUNCH = LTIX::requireData();

        if ( ! Tool::csrfOk() ) {
            http_response_code(403);
            echo json_encode(array('error' => 'Missing or invalid CSRF token'));
            return;
        }

        // Takes raw data from the request
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        LinkSettings::linkUpdate((array) $data);

        // Avoid empty body which creates havoc with JQuery
        echo("{}");
    }

}
