<?php

namespace Tsugi\Views;

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;

class Lessons {

    public static function render($anchor=null) {
        global $CFG, $OUTPUT;
        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons,$anchor);
        $l->use_rest_urls = true;

        $l->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();

        echo('<div id="container">'."\n");

        $OUTPUT->flashMessages();

        $l->render();

        echo('</div> <!-- container -->'."\n");

        $OUTPUT->footerStart();
        $l->footer();
        $OUTPUT->footerend();
    }
}
