<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Tsugi\UI\Labs as LabsUI;
use Symfony\Component\HttpFoundation\Request;

/**
 * Labs catalog route (/labs). Site mode (www vs labs) is configured by the host app (e.g. py4e).
 */
class Labs extends Tool {

    const ROUTE = '/labs';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Labs@get');
        $app->router->get($prefix.'/', 'Labs@get');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        $OUTPUT->header();
        LabsUI::printStyles();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        LabsUI::renderCatalog($CFG->lessons);
        $OUTPUT->footer();
    }
}
