<?php

namespace Tsugi\Services\Grades;

use Tsugi\Core\ReqScope;

/**
 * Store a grade on the lti_result row already loaded in ReqScope.
 *
 * The gradebook reads lti_result.grade. This is the write side of that row
 * for an internal activity that has no LTI launch. Callers pass a 0–1
 * fraction. Quiz points, discussion scores, and anything else are converted
 * before they get here.
 */
class Gradebook {

    /**
     * @param float $grade Fraction from 0 through 1.
     * @return float|string|null The stored fraction, an error string, or null when this request has no result.
     */
    public static function record(ReqScope $rc, $grade) {
        if ( ! $rc->result || ! $rc->link ) {
            return null;
        }
        $grade = (float) $grade;

        $row = array(
            'result_id' => $rc->result->id,
            'link_id' => $rc->link->id,
            'service' => '',
            'sourcedid' => '',
            'result_url' => '',
            'lti13_lineitem' => '',
            'lti13_lineitems' => '',
            'lti13_subject_key' => '',
        );

        global $LINK;
        $savedLink = isset($LINK) ? $LINK : null;
        if ( isset($LINK) && ( ! isset($LINK->launch) || ! is_object($LINK->launch) ) ) {
            $LINK = null;
        }
        $debug = array();
        try {
            $status = $rc->result->gradeSend($grade, $row, $debug);
        } finally {
            $LINK = $savedLink;
        }

        if ( $status !== true ) {
            return is_string($status) ? $status : 'Grade was not stored.';
        }
        $rc->result->grade = $grade;
        return $grade;
    }
}
