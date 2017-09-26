<?php

namespace Tsugi\Grades;

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;

class GradeUtil {
    public static function gradeLoadAll() {
        global $CFG, $USER, $LINK, $PDOX;
        $LAUNCH = LTIX::requireData(LTIX::LINK);
        if ( ! $USER->instructor ) die("Requires instructor role");
        $p = $CFG->dbprefix;

        // Get basic grade data
        $stmt = $PDOX->queryDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id,
                grade, note, R.json AS json, R.note as note, R.updated_at AS updated_at, displayname, email
            FROM {$p}lti_result AS R
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            WHERE R.link_id = :LID
            ORDER BY updated_at DESC",
            array(":LID" => $LINK->id)
        );
        return $stmt;
    }

    // $detail is either false or a class with methods
    public static function gradeShowAll($stmt, $detail = false) {
        echo('<table border="1">');
        echo("\n<tr><th>Name<th>Email</th><th>Grade</th><th>Date</th></tr>\n");

        while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            echo('<tr><td>');
            if ( $detail ) {
                $detail->link($row);
            } else {
                echo(htmlent_utf8($row['displayname']));
            }
            echo("</td>\n<td>".htmlent_utf8($row['email'])."</td>
                <td>".htmlent_utf8($row['grade'])."</td>
                <td>".htmlent_utf8($row['updated_at'])."</td>
            </tr>\n");
        }
        echo("</table>\n");
    }

    // Not cached
    public static function gradeLoad($user_id=false) {
        global $CFG, $USER, $LINK, $PDOX;
        $LAUNCH = LTIX::requireData(array(LTIX::LINK, LTIX::USER));
        if ( ! $USER->instructor && $user_id !== false ) die("Requires instructor role");
        if ( $user_id == false ) $user_id = $USER->id;
        $p = $CFG->dbprefix;

        // Get basic grade data
        $stmt = $PDOX->queryDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id,
                grade, note, R.json AS json, R.note as note, R.updated_at AS updated_at, displayname, email
            FROM {$p}lti_result AS R
            JOIN {$p}lti_user AS U ON R.user_id = U.user_id
            WHERE R.link_id = :LID AND R.user_id = :UID
            GROUP BY U.email",
            array(":LID" => $LINK->id, ":UID" => $user_id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    public static function gradeShowInfo($row) {
        echo('<p><a href="grades.php">Back to All Grades</a>'."</p><p>\n");
        echo("User Name: ".htmlent_utf8($row['displayname'])."<br/>\n");
        echo("User Email: ".htmlent_utf8($row['email'])."<br/>\n");
        echo("Last Submision: ".htmlent_utf8($row['updated_at'])."<br/>\n");
        echo("Score: ".htmlent_utf8($row['grade'])."<br/>\n");
        echo("</p>\n");
    }

    // newdata can be a string or array (preferred)
    public static function gradeUpdateJson($newdata=false) {
        global $CFG, $PDOX, $LINK, $RESULT;
        if ( $newdata == false ) return;
        if ( is_string($newdata) ) $newdata = json_decode($newdata, true);
        $LAUNCH = LTIX::requireData(array(LTIX::LINK));
        if ( ! isset($RESULT) ) return;
        $row = self::gradeLoad();
        $data = array();
        if ( $row !== false && isset($row['json'])) {
            $data = json_decode($row['json'], true);
        }

        $changed = false;
        foreach ($newdata as $k => $v ) {
            if ( (!isset($data[$k])) || $data[$k] != $v ) {
                $data[$k] = $v;
                $changed = true;
            }
        }

        if ( $changed === false ) return;

        $jstr = json_encode($data);

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result SET json = :json, updated_at = NOW()
                WHERE result_id = :RID",
            array(
                ':json' => $jstr,
                ':RID' => $RESULT->id)
        );
    }

    /**
     * Load all the grades for the current user / course including resource_link_id
     */
    public static function loadGradesForCourse($user_id, $context_id)
    {
        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $sql =
        "SELECT R.result_id AS result_id, L.title as title, L.link_key AS resource_link_id, 
            R.grade AS grade, R.note AS note
        FROM {$p}lti_result AS R
        JOIN {$p}lti_link as L ON R.link_id = L.link_id
        LEFT JOIN {$p}lti_service AS S ON R.service_id = S.service_id
        WHERE R.user_id = :UID AND L.context_id = :CID AND R.grade IS NOT NULL";
        $rows = $PDOX->allRowsDie($sql,array(':UID' => $user_id, ':CID' => $context_id));
        return $rows;
    }
}
