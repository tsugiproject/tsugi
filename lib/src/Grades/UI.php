<?php

namespace Tsugi\Grades;

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

class UI {

    public static function gradeTable($GRADE_DETAIL_CLASS, $done_href=false, $done_text=false, $menu=false) {
        global $CFG, $OUTPUT, $USER, $LINK;
        // Require CONTEXT, USER, and LINK
        $LAUNCH = LTIX::requireData();
        if ( ! $USER->instructor ) die("Requires instructor role");
        $p = $CFG->dbprefix;

        // Get basic grade data
        $query_parms = array(":LID" => $LINK->id);
        $orderfields =  array("R.updated_at", "displayname", "email", "grade", "user_key", "R.ipaddr");
        $searchfields = $orderfields;
        $sql =
            "SELECT R.user_id AS user_id, displayname, email,
                grade, R.ipaddr, user_key, R.updated_at AS updated_at
            FROM {$p}lti_result AS R
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            WHERE R.link_id = :LID";

        // View
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        if ( $menu ) $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        $OUTPUT->welcomeUserCourse();

        if ( isset($GRADE_DETAIL_CLASS) && is_object($GRADE_DETAIL_CLASS) ) {
            $detail = $GRADE_DETAIL_CLASS;
        } else {
            $detail = false;
        }

        Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, "grade-detail.php");

        // Since this is in a popup, put out a done button
        if ( ! $done_href ) {
            $OUTPUT->closeButton();
        } else if ( $done_href == 'none' ) {
            // DO nothing
        } else {
            if ( ! $done_text ) $done_text = __('Done');
            echo('<a href="'.$done_href.'" class="btn btn-info">'.htmlentities($done_text).'</a>');
        }
        $OUTPUT->footer();
    }
}
