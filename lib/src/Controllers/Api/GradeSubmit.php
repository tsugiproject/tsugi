<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;

/**
 * POST /api/grade-submit.php
 *
 * The tool must set a GSRF count in the session like this:
 * $_SESSION['GSRF'] = 5;
 *
 * The GSRF is a count down number of uses of this endpoint. The default is zero
 * uses unless the tool places the number of times it wants to call this in the session.
 */
class GradeSubmit {

    public function handle(): void
    {
        // Set content type to JSON
        header('Content-Type: application/json');

        // Sanity checks
        $LAUNCH = LTIX::requireData();
        global $USER;
        $user_id = $USER->id;

        if (!isset($_SESSION['GSRF']) || !is_numeric($_SESSION['GSRF']) || $_SESSION['GSRF'] < 1) {
            echo json_encode(array("status" => "failure", "detail" => "Missing GSRF token"));
            return;
        }

        // Decrement use count
        $_SESSION['GSRF'] = @$_SESSION['GSRF'] - 1;

        // Get and validate grade
        if (!isset($_POST['grade'])) {
            echo json_encode(array("status" => "failure", "detail" => "Grade parameter is required"));
            return;
        }

        $grade = floatval($_POST['grade']);
        if ($grade < 0.0 || $grade > 1.0) {
            echo json_encode(array("status" => "failure", "detail" => "Grade must be between 0.0 and 1.0"));
            return;
        }

        // Get code if provided (optional)
        $code = isset($_POST['code']) ? $_POST['code'] : '';

        // Log the grade submission
        error_log("Grade submission: user_id=$user_id, grade=$grade, code=$code");

        $debug_log = array();
        $retval = LTIX::gradeSend($grade, false, $debug_log);
        if ( is_string($retval) ) {
            echo json_encode(array("status" => "failure", "detail" => $retval, "debug_log" => $debug_log));
            return;
        }

        $retval = array("status" => "success", "debug_log" => $debug_log);
        echo json_encode($retval);
    }

}
