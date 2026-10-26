<?php

namespace Tsugi\Services\Plus;

use Tsugi\Core\Keyset;
use Tsugi\Core\LTIX;
use Tsugi\Util\LTI13;

/**
 * Push a Plus course column and its scores to the controlling LMS.
 *
 * A line item is created when the link first gets a result, and again when a
 * grade arrives and the link still has none. The returned id is stored on
 * lti_link.lti13_lineitem. A later grade posts to that line item.
 *
 * A failed send leaves server_status set and does not replace server_grade.
 * The next grade send tries again. There is no background retry.
 */
class GradeSync {

    const KIND_LINEITEM = 'lineitem_create';
    const KIND_LINEITEM_UPDATE = 'lineitem_update';
    const KIND_SCORE = 'score';

    /**
     * Plus activities are sent here. The course-navigation link is not a column.
     * Returns true when this send was handled, including a local-only skip.
     *
     * @param int|string|false $resultId
     * @param mixed $grade 0–1 fraction already stored on the result
     * @param string|null $comment
     * @param string $activity
     * @param string $grading
     * @param array<int,mixed>|false $debug
     */
    public static function deliverIfPlus($resultId, $grade, $comment, $activity, $grading, &$debug = false) {
        $row = self::loadResult($resultId);
        if ( ! is_array($row) || (int) ($row['plus_course'] ?? 0) !== 1 ) {
            return false;
        }
        if ( self::isShellLink($row['link_id'] ?? 0, $row['plus_launch_link_id'] ?? 0) ) {
            return true;
        }
        $row = self::adoptQuizPoints($row);
        if ( ! self::columnReady($row['title'] ?? '', $row['score_maximum'] ?? null) ) {
            $message = 'This activity needs a title and a point total before it can have a line item.';
            self::mark((int) $row['result_id'], null, $message);
            self::writeLog($row, self::KIND_LINEITEM, false, null, (string) ($row['lti13_lineitems'] ?? ''), null, $message, $debug);
            return true;
        }
        if ( self::withheld($row['grades_released'] ?? null) ) {
            self::ensureFromRow($row, $debug);
            return true;
        }
        $gap = self::configGap($row);
        if ( $gap !== null ) {
            $kind = trim((string) ($row['lti13_lineitem'] ?? '')) === '' ? self::KIND_LINEITEM : self::KIND_SCORE;
            self::mark((int) $row['result_id'], null, self::gapMessage($gap));
            self::writeLog($row, $kind, false, null, (string) ($row['lti13_lineitems'] ?? ''), null, self::gapMessage($gap), $debug);
            return true;
        }

        $url = trim((string) ($row['lti13_lineitem'] ?? ''));
        if ( $url === '' ) {
            $created = self::createUnderLock($row, $debug);
            if ( isset($created['error']) ) {
                self::mark((int) $row['result_id'], null, $created['error']);
                return true;
            }
            $url = $created['url'];
            $row['lti13_lineitem'] = $url;
        }

        self::postScore($row, $grade, $comment, $activity, $grading, $debug);
        return true;
    }

    /**
     * Teacher asked for this link's line item. A quiz with no point total
     * on the link uses the sum of its question points, and that sum is stored
     * on the link before the LMS call.
     *
     * @param int $linkId
     * @return array{ok:bool,status:string,message?:string}
     */
    public static function requestLineItem($linkId) {
        $row = self::loadLink($linkId);
        if ( ! is_array($row) ) {
            return array('ok' => false, 'status' => 'not_found');
        }
        $block = self::blockReason($row);
        if ( $block === 'exists' ) {
            return array('ok' => true, 'status' => 'exists');
        }
        if ( $block === 'not_ready' ) {
            $row = self::adoptQuizPoints($row);
            $block = self::blockReason($row);
        }
        if ( $block === 'exists' ) {
            return array('ok' => true, 'status' => 'exists');
        }
        if ( $block !== null ) {
            if ( self::configGap($row) === $block ) {
                self::writeLog($row, self::KIND_LINEITEM, false, null, (string) ($row['lti13_lineitems'] ?? ''), null, self::gapMessage($block), array());
            }
            return array('ok' => false, 'status' => $block);
        }
        $debug = array();
        $created = self::createUnderLock($row, $debug);
        if ( isset($created['error']) ) {
            return array('ok' => false, 'status' => 'error', 'message' => (string) $created['error']);
        }
        return array('ok' => true, 'status' => 'created');
    }

    /**
     * PUT the line item after this link's due date changed. A link with no
     * line item is left for the create path, which already sends the due date.
     *
     * @param int $contextId
     * @param string $linkKey
     * @return array{ok:bool,status:string,message?:string}
     */
    public static function pushDueDateForKey($contextId, $linkKey) {
        $row = self::loadLinkByKey($contextId, $linkKey);
        if ( ! is_array($row) ) {
            return array('ok' => false, 'status' => 'not_found');
        }
        if ( (int) ($row['plus_course'] ?? 0) !== 1 ) {
            return array('ok' => true, 'status' => 'not_plus');
        }
        if ( self::isShellLink($row['link_id'] ?? 0, $row['plus_launch_link_id'] ?? 0) ) {
            return array('ok' => true, 'status' => 'shell');
        }
        if ( trim((string) ($row['lti13_lineitem'] ?? '')) === '' ) {
            return array('ok' => true, 'status' => 'no_lineitem');
        }
        $row = self::adoptQuizPoints($row);
        $body = self::lineItemBody(
            (int) $row['link_id'],
            $row['title'] ?? '',
            $row['score_maximum'] ?? null,
            $row['end_datetime'] ?? null,
            $row['timezone'] ?? null
        );
        if ( $body === null ) {
            $message = 'This activity has no title or point total.';
            self::writeLog($row, self::KIND_LINEITEM_UPDATE, false, null, (string) ($row['lti13_lineitem'] ?? ''), null, $message, array());
            return array('ok' => false, 'status' => 'not_ready', 'message' => $message);
        }
        if ( ! isset($body->endDateTime) ) {
            $body->endDateTime = null;
        }
        $gap = self::configGap($row);
        if ( $gap !== null ) {
            self::writeLog($row, self::KIND_LINEITEM_UPDATE, false, null, (string) $row['lti13_lineitem'], $body, self::gapMessage($gap), array());
            return array('ok' => false, 'status' => $gap, 'message' => self::gapMessage($gap));
        }
        $debug = array();
        $error = self::updateExistingLineItem($row, $body, $debug);
        if ( is_string($error) && $error !== '' ) {
            return array('ok' => false, 'status' => 'error', 'message' => $error);
        }
        return array('ok' => true, 'status' => 'updated');
    }

    /**
     * Why this link should not be posted, or null when the LMS call can proceed.
     * exists means the line item is already stored.
     *
     * @param array<string,mixed> $row
     * @return string|null
     */
    public static function blockReason(array $row) {
        if ( (int) ($row['plus_course'] ?? 0) !== 1 ) {
            return 'not_plus';
        }
        if ( self::isShellLink($row['link_id'] ?? 0, $row['plus_launch_link_id'] ?? 0) ) {
            return 'shell';
        }
        if ( trim((string) ($row['lti13_lineitem'] ?? '')) !== '' ) {
            return 'exists';
        }
        if ( ! self::columnReady($row['title'] ?? '', $row['score_maximum'] ?? null) ) {
            return 'not_ready';
        }
        return self::configGap($row);
    }

    /**
     * Create the line item for a new result when the link can have a column.
     * A failure is logged. The launch still finishes.
     *
     * @param int $linkId
     */
    public static function ensureLineItem($linkId) {
        $debug = array();
        try {
            $row = self::loadLink($linkId);
            self::ensureFromRow(is_array($row) ? $row : array(), $debug);
        } catch ( \Throwable $e ) {
            error_log('Plus line item link='.(int) $linkId.' '.$e->getMessage());
        }
    }

    /**
     * @param mixed $linkId
     * @param mixed $shellLinkId
     */
    public static function isShellLink($linkId, $shellLinkId) {
        $linkId = (int) $linkId;
        $shellLinkId = (int) $shellLinkId;
        return $linkId > 0 && $shellLinkId > 0 && $linkId === $shellLinkId;
    }

    /**
     * A column needs a title and a positive point total. Anything else stays local.
     *
     * @param mixed $title
     * @param mixed $maximum
     */
    public static function columnReady($title, $maximum) {
        if ( ! is_string($title) || trim($title) === '' ) {
            return false;
        }
        return is_numeric($maximum) && (float) $maximum > 0;
    }

    /**
     * grades_released 0 holds the score. Null and 1 send it.
     *
     * @param mixed $flag
     */
    public static function withheld($flag) {
        if ( $flag === null || $flag === '' ) {
            return false;
        }
        return (int) $flag === 0;
    }

    /**
     * Store a teacher override. A link that already has a line item is sent.
     * This does not create a line item.
     *
     * @param int $resultId
     * @param mixed $points
     * @return array{ok:bool,status:string,message?:string}
     */
    public static function applyOverride($resultId, $points) {
        $row = self::loadResult($resultId);
        if ( ! is_array($row) ) {
            return array('ok' => false, 'status' => 'not_found');
        }
        $maximum = self::pointsMaximum($row);
        if ( ! is_numeric($points) ) {
            return array('ok' => false, 'status' => 'bad_points', 'message' => self::pointsMessage($maximum));
        }
        $earned = round((float) $points, 4);
        if ( $earned < 0 || $earned - $maximum > 0.0001 ) {
            return array('ok' => false, 'status' => 'bad_points', 'message' => self::pointsMessage($maximum));
        }
        $grade = $maximum > 0 ? $earned / $maximum : 0.0;
        self::storeOverride((int) $row['result_id'], $grade, $earned, $maximum);
        $lineitem = trim((string) ($row['lti13_lineitem'] ?? ''));
        if ( $lineitem === '' ) {
            return array('ok' => true, 'status' => 'stored');
        }
        if ( self::withheld($row['grades_released'] ?? null) ) {
            return array('ok' => true, 'status' => 'held');
        }
        if ( ! is_numeric($row['score_maximum']) || (float) $row['score_maximum'] <= 0 ) {
            return array('ok' => true, 'status' => 'stored');
        }
        $gap = self::configGap($row);
        if ( $gap !== null ) {
            self::mark((int) $row['result_id'], null, self::gapMessage($gap));
            self::writeLog($row, self::KIND_SCORE, false, null, $lineitem, null, self::gapMessage($gap), array());
            return array('ok' => true, 'status' => 'send_failed', 'message' => self::gapMessage($gap));
        }
        $debug = array();
        $error = self::postScore(
            $row,
            $grade,
            null,
            LTI13::ACTIVITY_PROGRESS_COMPLETED,
            LTI13::GRADING_PROGRESS_FULLYGRADED,
            $debug
        );
        if ( is_string($error) && $error !== '' ) {
            return array('ok' => true, 'status' => 'send_failed', 'message' => $error);
        }
        return array('ok' => true, 'status' => 'sent');
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function pointsMaximum(array $row) {
        if ( isset($row['score_maximum']) && is_numeric($row['score_maximum']) && (float) $row['score_maximum'] > 0 ) {
            return (float) $row['score_maximum'];
        }
        if ( isset($row['result_maximum']) && is_numeric($row['result_maximum']) && (float) $row['result_maximum'] > 0 ) {
            return (float) $row['result_maximum'];
        }
        return 1.0;
    }

    /**
     * Points shown for a result. score_given wins when the tool stored one.
     *
     * @param array<string,mixed> $row
     * @return float|null
     */
    public static function earnedPoints(array $row) {
        if ( isset($row['score_given']) && is_numeric($row['score_given']) ) {
            return (float) $row['score_given'];
        }
        if ( ! isset($row['grade']) || ! is_numeric($row['grade']) ) {
            return null;
        }
        $given = self::scoreGiven($row['grade'], self::pointsMaximum($row));
        return $given === null ? (float) $row['grade'] : $given;
    }

    /**
     * @param float $maximum
     */
    private static function pointsMessage($maximum) {
        $label = abs($maximum - round($maximum)) < 0.000001
            ? (string) (int) round($maximum)
            : rtrim(rtrim(sprintf('%.4f', $maximum), '0'), '.');
        return 'Enter a grade from 0 to '.$label.'.';
    }

    /**
     * @param int $resultId
     * @param float $grade
     * @param float $earned
     * @param float $maximum
     */
    private static function storeOverride($resultId, $grade, $earned, $maximum) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_result
             SET grade = :GRADE, score_given = :GIVEN, result_maximum = :MAX,
                 activity_progress = :ACT, grading_progress = :GRD,
                 score_timestamp = NOW(), updated_at = NOW()
             WHERE result_id = :RID",
            array(
                ':GRADE' => $grade,
                ':GIVEN' => $earned,
                ':MAX' => $maximum,
                ':ACT' => LTI13::ACTIVITY_PROGRESS_COMPLETED,
                ':GRD' => LTI13::GRADING_PROGRESS_FULLYGRADED,
                ':RID' => (int) $resultId,
            )
        );
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            $message = is_object($stmt) ? (string) $stmt->errorImplode : 'query failed';
            error_log('Plus grade override result='.(int) $resultId.' '.$message);
        }
    }

    /**
     * @param mixed $grade 0–1 fraction
     * @param mixed $maximum
     * @return float|null
     */
    public static function scoreGiven($grade, $maximum) {
        if ( ! is_numeric($grade) || ! is_numeric($maximum) || (float) $maximum <= 0 ) {
            return null;
        }
        $given = round((float) $grade * (float) $maximum, 4);
        if ( $given < 0 ) {
            $given = 0.0;
        }
        return $given;
    }

    /**
     * Line item body, or null when this link should not become a column.
     *
     * @param int $linkId
     * @param mixed $title
     * @param mixed $maximum
     * @param mixed $due
     * @param mixed $timezone
     * @return object|null
     */
    public static function lineItemBody($linkId, $title, $maximum, $due, $timezone) {
        if ( (int) $linkId < 1 || ! self::columnReady($title, $maximum) ) {
            return null;
        }
        $item = new \stdClass();
        $item->scoreMaximum = (float) $maximum;
        $item->label = trim((string) $title);
        $item->resourceId = (string) (int) $linkId;
        $end = self::endDateTime($due, $timezone);
        if ( $end !== null ) {
            $item->endDateTime = $end;
        }
        return $item;
    }

    /**
     * Due instant for the line item. A course zone uses 23:59:59 that day
     * in the zone. An empty zone sends the stored clock time unchanged.
     *
     * @param mixed $due
     * @param mixed $timezone
     * @return string|null
     */
    public static function endDateTime($due, $timezone) {
        $raw = trim((string) $due);
        if ( $raw === '' || strpos($raw, '0000-00-00') === 0 ) {
            return null;
        }
        if ( ! preg_match('/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}:\d{2}))?/', $raw, $match) ) {
            return null;
        }
        $zone = self::zone($timezone);
        if ( $zone === null ) {
            $clock = isset($match[2]) && $match[2] !== '' ? $match[2] : '23:59:59';
            return $match[1].'T'.$clock;
        }
        try {
            $dt = new \DateTime($match[1].' 23:59:59', $zone);
        } catch ( \Exception $e ) {
            return $match[1].'T23:59:59';
        }
        return $dt->format(\DateTime::ATOM);
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed>|false $debug
     */
    private static function ensureFromRow(array $row, &$debug) {
        if ( (int) ($row['plus_course'] ?? 0) !== 1 ) {
            return;
        }
        if ( self::isShellLink($row['link_id'] ?? 0, $row['plus_launch_link_id'] ?? 0) ) {
            return;
        }
        if ( trim((string) ($row['lti13_lineitem'] ?? '')) !== '' ) {
            return;
        }
        $row = self::adoptQuizPoints($row);
        if ( ! self::columnReady($row['title'] ?? '', $row['score_maximum'] ?? null) ) {
            return;
        }
        $gap = self::configGap($row);
        if ( $gap !== null ) {
            self::writeLog($row, self::KIND_LINEITEM, false, null, (string) ($row['lti13_lineitems'] ?? ''), null, self::gapMessage($gap), $debug);
            return;
        }
        self::createUnderLock($row, $debug);
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed>|false $debug
     * @return array{url?:string,error?:string}
     */
    private static function createUnderLock(array $row, &$debug) {
        $linkId = (int) ($row['link_id'] ?? 0);
        $locked = self::lock($linkId);
        if ( $locked === false ) {
            $fresh = self::loadLink($linkId);
            if ( is_array($fresh) && trim((string) ($fresh['lti13_lineitem'] ?? '')) !== '' ) {
                return array('url' => trim((string) $fresh['lti13_lineitem']));
            }
            return array('error' => 'Line item create is already running.');
        }
        try {
            if ( $locked === true ) {
                $fresh = self::loadLink($linkId);
                if ( is_array($fresh) && trim((string) ($fresh['lti13_lineitem'] ?? '')) !== '' ) {
                    return array('url' => trim((string) $fresh['lti13_lineitem']));
                }
                if ( is_array($fresh) ) {
                    $row = $fresh;
                }
            }
            return self::createLineItem($row, $debug);
        } finally {
            if ( $locked === true ) {
                self::unlock($linkId);
            }
        }
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed>|false $debug
     * @return array{url?:string,error?:string}
     */
    private static function createLineItem(array $row, &$debug) {
        $body = self::lineItemBody(
            (int) ($row['link_id'] ?? 0),
            $row['title'] ?? '',
            $row['score_maximum'] ?? null,
            $row['end_datetime'] ?? null,
            $row['timezone'] ?? null
        );
        if ( $body === null ) {
            return array('error' => 'This activity has no title or point total.');
        }
        $tokenDebug = array();
        $token = self::lineItemToken($row, $tokenDebug);
        if ( ! is_string($token) || $token === '' ) {
            $message = 'Could not get a line item token.';
            self::writeLog($row, self::KIND_LINEITEM, false, self::httpStatus($tokenDebug), (string) ($row['lti13_lineitems'] ?? ''), $body, $message, $tokenDebug);
            self::appendDebug($debug, $tokenDebug);
            return array('error' => $message);
        }
        $callDebug = array();
        $created = LTI13::createLineItem(trim((string) $row['lti13_lineitems']), $token, $body, $callDebug);
        $http = self::httpStatus($callDebug);
        self::appendDebug($debug, $callDebug);
        if ( ! is_array($created) || trim((string) ($created['id'] ?? '')) === '' ) {
            $message = is_string($created) && $created !== '' ? $created : 'Line item was not created.';
            self::writeLog($row, self::KIND_LINEITEM, false, $http, (string) $row['lti13_lineitems'], $body, $message, $callDebug);
            return array('error' => $message);
        }
        $url = trim((string) $created['id']);
        self::storeLineItem((int) $row['link_id'], $url);
        self::writeLog($row, self::KIND_LINEITEM, true, $http, (string) $row['lti13_lineitems'], $body, $url, $callDebug);
        return array('url' => $url);
    }

    /**
     * @param array<string,mixed> $row
     * @param object $body
     * @param array<int,mixed>|false $debug
     * @return string|null
     */
    private static function updateExistingLineItem(array $row, $body, &$debug) {
        $url = trim((string) ($row['lti13_lineitem'] ?? ''));
        $tokenDebug = array();
        $token = self::lineItemToken($row, $tokenDebug);
        if ( ! is_string($token) || $token === '' ) {
            $message = 'Could not get a line item token.';
            self::writeLog($row, self::KIND_LINEITEM_UPDATE, false, self::httpStatus($tokenDebug), $url, $body, $message, $tokenDebug);
            self::appendDebug($debug, $tokenDebug);
            return $message;
        }
        $callDebug = array();
        $updated = LTI13::updateLineItem($url, $token, $body, $callDebug);
        $http = self::httpStatus($callDebug);
        self::appendDebug($debug, $callDebug);
        if ( $updated !== true ) {
            $message = is_string($updated) && $updated !== '' ? $updated : 'Line item was not updated.';
            self::writeLog($row, self::KIND_LINEITEM_UPDATE, false, $http, $url, $body, $message, $callDebug);
            return $message;
        }
        self::writeLog($row, self::KIND_LINEITEM_UPDATE, true, $http, $url, $body, $url, $callDebug);
        return null;
    }

    /**
     * @param array<string,mixed> $row
     * @param mixed $grade
     * @param string|null $comment
     * @param string $activity
     * @param string $grading
     * @param array<int,mixed>|false $debug
     * @return string|null The error, or null when the score was accepted
     */
    private static function postScore(array $row, $grade, $comment, $activity, $grading, &$debug) {
        $resultId = (int) ($row['result_id'] ?? 0);
        $subject = trim((string) ($row['subject_key'] ?? ''));
        if ( $subject === '' ) {
            $message = 'This person has no LTI subject, so the score cannot be sent.';
            self::mark($resultId, null, $message);
            return $message;
        }
        $maximum = (float) $row['score_maximum'];
        $given = self::scoreGiven($grade, $maximum);
        $tokenDebug = array();
        $token = self::scoreToken($row, $tokenDebug);
        if ( ! is_string($token) || $token === '' ) {
            $message = 'Could not get a score token.';
            self::mark($resultId, null, $message);
            self::writeLog($row, self::KIND_SCORE, false, self::httpStatus($tokenDebug), (string) $row['lti13_lineitem'], null, $message, $tokenDebug);
            self::appendDebug($debug, $tokenDebug);
            return $message;
        }
        $extra = array(
            LTI13::ACTIVITY_PROGRESS => $activity,
            LTI13::GRADING_PROGRESS => $grading,
        );
        $callDebug = array();
        $status = LTI13::sendLineItemResult(
            $subject,
            $given,
            $maximum,
            is_string($comment) ? $comment : null,
            trim((string) $row['lti13_lineitem']),
            $token,
            $extra,
            $callDebug
        );
        $http = self::httpStatus($callDebug);
        self::appendDebug($debug, $callDebug);
        $payload = array(
            'userId' => $subject,
            'scoreGiven' => $given,
            'scoreMaximum' => $maximum,
            'activityProgress' => $activity,
            'gradingProgress' => $grading,
        );
        if ( is_string($comment) && $comment !== '' ) {
            $payload['comment'] = $comment;
        }
        if ( $status === true ) {
            self::mark($resultId, is_numeric($grade) ? (float) $grade : null, null);
            self::writeLog($row, self::KIND_SCORE, true, $http, (string) $row['lti13_lineitem'], $payload, 'sent', $callDebug);
            return null;
        }
        $message = is_string($status) && $status !== '' ? $status : 'The score was not sent.';
        self::mark($resultId, null, $message);
        self::writeLog($row, self::KIND_SCORE, false, $http, (string) $row['lti13_lineitem'], $payload, $message, $callDebug);
        return $message;
    }

    /**
     * @param array<string,mixed> $row
     * @return string|null
     */
    private static function configGap(array $row) {
        if ( trim((string) ($row['lti13_lineitems'] ?? '')) === '' ) {
            return 'no_lineitems_url';
        }
        $deployment = $row['lti13_deployment_id'] ?? '';
        if ( ! is_string($deployment) || trim($deployment) === '' ) {
            return 'no_deployment';
        }
        $tokenUrl = $row['lms_token_url'] ?? '';
        if ( ! is_string($tokenUrl) || trim($tokenUrl) === '' ) {
            return 'no_token_url';
        }
        $client = $row['lms_client'] ?? '';
        if ( ! is_string($client) || trim($client) === '' ) {
            return 'no_client';
        }
        return null;
    }

    /**
     * @param string $status
     */
    private static function gapMessage($status) {
        if ( $status === 'no_lineitems_url' ) {
            return 'This course has no line items URL.';
        }
        if ( $status === 'no_deployment' ) {
            return 'This course has no deployment id.';
        }
        if ( $status === 'no_token_url' ) {
            return 'This tenant has no token URL.';
        }
        if ( $status === 'no_client' ) {
            return 'This tenant has no client id.';
        }
        return 'The line item could not be created.';
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed> $debug
     * @return string|false
     */
    private static function lineItemToken(array $row, array &$debug) {
        return self::token($row, $debug, true);
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed> $debug
     * @return string|false
     */
    private static function scoreToken(array $row, array &$debug) {
        return self::token($row, $debug, false);
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,mixed> $debug
     * @param bool $lineItem
     * @return string|false
     */
    private static function token(array $row, array &$debug, $lineItem) {
        $privkey = null;
        $kid = null;
        $ok = Keyset::getSigning($privkey, $kid);
        if ( $ok !== true || ! is_string($privkey) || $privkey === '' || ! is_string($kid) || $kid === '' ) {
            $debug[] = is_string($ok) ? $ok : 'This site has no signing key.';
            return false;
        }
        $audience = $row['lms_token_audience'] ?? '';
        if ( ! is_string($audience) || trim($audience) === '' ) {
            $audience = false;
        }
        $deployment = trim((string) ($row['lti13_deployment_id'] ?? ''));
        $client = trim((string) ($row['lms_client'] ?? ''));
        $tokenUrl = trim((string) ($row['lms_token_url'] ?? ''));
        return self::withDeploymentClaim(function () use ($lineItem, $client, $tokenUrl, $privkey, $kid, $audience, $deployment, &$debug) {
            if ( $lineItem ) {
                return LTI13::getLineItemsToken($client, $tokenUrl, $privkey, $kid, $audience, $deployment, $debug);
            }
            return LTI13::getGradeToken($client, $tokenUrl, $privkey, $kid, $audience, $deployment, $debug);
        });
    }

    /**
     * @param callable $fn
     * @return mixed
     */
    private static function withDeploymentClaim($fn) {
        global $CFG;
        $existed = is_object($CFG) && isset($CFG->token_deployment_id);
        $previous = $existed ? $CFG->token_deployment_id : null;
        if ( is_object($CFG) ) {
            $CFG->token_deployment_id = true;
        }
        try {
            return $fn();
        } finally {
            if ( is_object($CFG) ) {
                if ( $existed ) {
                    $CFG->token_deployment_id = $previous;
                } else {
                    unset($CFG->token_deployment_id);
                }
            }
        }
    }

    /**
     * @param int|string|false $resultId
     * @return array<string,mixed>|null
     */
    private static function loadResult($resultId) {
        $resultId = (int) $resultId;
        if ( $resultId < 1 ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        global $CFG;
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT R.result_id, R.link_id, R.user_id, R.result_maximum AS result_maximum,
                    U.subject_key AS subject_key,
                    L.title, L.score_maximum, L.lti13_lineitem, L.grades_released, L.end_datetime,
                    C.context_id, C.plus_course, C.plus_launch_link_id, C.lti13_lineitems,
                    C.lti13_deployment_id, C.timezone,
                    K.lms_token_url, K.lms_token_audience, K.lms_client
             FROM {$p}lti_result AS R
             JOIN {$p}lti_link AS L ON L.link_id = R.link_id
             JOIN {$p}lti_user AS U ON U.user_id = R.user_id
             JOIN {$p}lti_context AS C ON C.context_id = L.context_id
             JOIN {$p}lti_key AS K ON K.key_id = C.key_id
             WHERE R.result_id = :RID
             LIMIT 1",
            array(':RID' => $resultId)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $linkId
     * @return array<string,mixed>|null
     */
    private static function loadLink($linkId) {
        $linkId = (int) $linkId;
        if ( $linkId < 1 ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        global $CFG;
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT L.link_id, L.title, L.score_maximum, L.lti13_lineitem, L.grades_released, L.end_datetime,
                    C.context_id, C.plus_course, C.plus_launch_link_id, C.lti13_lineitems,
                    C.lti13_deployment_id, C.timezone,
                    K.lms_token_url, K.lms_token_audience, K.lms_client
             FROM {$p}lti_link AS L
             JOIN {$p}lti_context AS C ON C.context_id = L.context_id
             JOIN {$p}lti_key AS K ON K.key_id = C.key_id
             WHERE L.link_id = :LID
             LIMIT 1",
            array(':LID' => $linkId)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $contextId
     * @param string $linkKey
     * @return array<string,mixed>|null
     */
    private static function loadLinkByKey($contextId, $linkKey) {
        $linkKey = trim((string) $linkKey);
        if ( (int) $contextId < 1 || $linkKey === '' ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        global $CFG;
        $p = $CFG->dbprefix;
        $found = $PDOX->rowDie(
            "SELECT link_id FROM {$p}lti_link
             WHERE context_id = :CID AND link_key = :LK AND (deleted IS NULL OR deleted = 0)
             LIMIT 1",
            array(':CID' => (int) $contextId, ':LK' => $linkKey)
        );
        if ( ! is_array($found) ) {
            return null;
        }
        return self::loadLink((int) $found['link_id']);
    }

    /**
     * A quiz's question points become the link total when the link has none.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function adoptQuizPoints(array $row) {
        if ( self::columnReady($row['title'] ?? '', $row['score_maximum'] ?? null) ) {
            return $row;
        }
        $linkId = (int) ($row['link_id'] ?? 0);
        $points = self::quizPoints($linkId);
        if ( $points === null || $points <= 0 ) {
            return $row;
        }
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_link
                 SET score_maximum = :MAX, updated_at = NOW()
                 WHERE link_id = :LID
                   AND (score_maximum IS NULL OR score_maximum <= 0)",
                array(':MAX' => $points, ':LID' => $linkId)
            );
            if ( is_object($stmt) && ! empty($stmt->success) ) {
                $row['score_maximum'] = $points;
            }
        } catch ( \Throwable $e ) {
            error_log('Plus quiz points link='.$linkId.' '.$e->getMessage());
        }
        return $row;
    }

    /**
     * @param int $linkId
     * @return float|null
     */
    private static function quizPoints($linkId) {
        if ( $linkId < 1 ) {
            return null;
        }
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $row = $PDOX->rowDie(
                "SELECT SUM(QQ.points) AS points
                 FROM {$CFG->dbprefix}quiz1_quiz AS Q
                 JOIN {$CFG->dbprefix}quiz1_question AS QQ ON QQ.quiz_id = Q.quiz_id
                 WHERE Q.link_id = :LID",
                array(':LID' => $linkId)
            );
        } catch ( \Throwable $e ) {
            error_log('Plus quiz points link='.$linkId.' '.$e->getMessage());
            return null;
        }
        if ( ! is_array($row) || ! is_numeric($row['points'] ?? null) ) {
            return null;
        }
        return (float) $row['points'];
    }

    /**
     * @param int $linkId
     * @param string $url
     */
    private static function storeLineItem($linkId, $url) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_link
             SET lti13_lineitem = :ITEM, updated_at = NOW()
             WHERE link_id = :LID",
            array(':ITEM' => $url, ':LID' => (int) $linkId)
        );
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            $message = is_object($stmt) ? (string) $stmt->errorImplode : 'query failed';
            error_log('Plus line item store link='.(int) $linkId.' '.$message);
        }
    }

    /**
     * Success stores the 0–1 fraction the LMS accepted and clears the error.
     * Failure stamps the attempt time and leaves the last accepted grade.
     *
     * @param int $resultId
     * @param float|null $grade
     * @param string|null $error
     */
    private static function mark($resultId, $grade, $error) {
        if ( (int) $resultId < 1 ) {
            return;
        }
        if ( is_string($error) ) {
            $error = trim($error);
            if ( strlen($error) > 500 ) {
                $error = substr($error, 0, 500);
            }
            if ( $error === '' ) {
                $error = 'The score was not sent.';
            }
        }
        global $CFG;
        $sets = array('retrieved_at = NOW()', 'updated_at = NOW()', 'server_status = :ST');
        $params = array(
            ':RID' => (int) $resultId,
            ':ST' => $error,
        );
        if ( $grade !== null ) {
            $sets[] = 'server_grade = :SG';
            $params[':SG'] = $grade;
        }
        try {
            $PDOX = LTIX::getConnection();
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_result
                 SET ".implode(', ', $sets)."
                 WHERE result_id = :RID",
                $params
            );
            if ( ! is_object($stmt) || empty($stmt->success) ) {
                $message = is_object($stmt) ? (string) $stmt->errorImplode : 'query failed';
                error_log('Plus grade status result='.(int) $resultId.' '.$message);
            }
        } catch ( \Throwable $e ) {
            error_log('Plus grade status result='.(int) $resultId.' '.$e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $row
     * @param string $kind
     * @param bool $success
     * @param int|null $http
     * @param string $url
     * @param object|array<string,mixed>|null $request
     * @param string $detail
     * @param array<int,mixed>|false $debug
     */
    private static function writeLog(array $row, $kind, $success, $http, $url, $request, $detail, $debug) {
        $contextId = (int) ($row['context_id'] ?? 0);
        if ( $contextId < 1 ) {
            return;
        }
        $body = null;
        if ( is_object($request) || is_array($request) ) {
            $json = json_encode($request, JSON_UNESCAPED_SLASHES);
            $body = is_string($json) ? $json : null;
        }
        $packed = array('detail' => $detail);
        if ( is_array($debug) ) {
            $packed['debug'] = RosterSync::scrub(self::debugText($debug));
        }
        $encoded = json_encode($packed, JSON_UNESCAPED_SLASHES);
        if ( ! is_string($encoded) ) {
            $encoded = '{}';
        }
        $linkId = (int) ($row['link_id'] ?? 0);
        $userId = (int) ($row['user_id'] ?? 0);
        global $CFG;
        try {
            $PDOX = LTIX::getConnection();
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$CFG->dbprefix}lti_context_plus_log
                    (context_id, link_id, user_id, kind, success, http_response, url, request, detail, created_at)
                 VALUES
                    (:CID, :LINK, :UID, :KIND, :OK, :HTTP, :URL, :REQ, :DETAIL, NOW())",
                array(
                    ':CID' => $contextId,
                    ':LINK' => $linkId > 0 ? $linkId : null,
                    ':UID' => $userId > 0 ? $userId : null,
                    ':KIND' => $kind,
                    ':OK' => $success ? 1 : 0,
                    ':HTTP' => $http === null ? null : (int) $http,
                    ':URL' => $url,
                    ':REQ' => $body,
                    ':DETAIL' => $encoded,
                )
            );
            if ( ! is_object($stmt) || empty($stmt->success) ) {
                $message = is_object($stmt) ? (string) $stmt->errorImplode : 'query failed';
                error_log('Plus grade log context='.$contextId.' '.$message);
            }
        } catch ( \Throwable $e ) {
            error_log('Plus grade log context='.$contextId.' '.$e->getMessage());
        }
    }

    /**
     * Line-item and score rows for this course. Failures and successes are
     * separate so the grade book can list errors first. Roster pulls are left out.
     *
     * @param int $contextId
     * @return array{failures:array<int,array<string,mixed>>,successes:array<int,array<string,mixed>>}
     */
    public static function activityLog($contextId) {
        return array(
            'failures' => self::activityRows($contextId, 0),
            'successes' => self::activityRows($contextId, 1),
        );
    }

    /**
     * @param int $contextId
     * @param int $success
     * @return array<int,array<string,mixed>>
     */
    private static function activityRows($contextId, $success) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT G.context_plus_log_id, G.created_at, G.kind, G.http_response, G.url, G.request, G.detail,
                    L.title AS link_title, U.displayname
             FROM {$p}lti_context_plus_log AS G
             LEFT JOIN {$p}lti_link AS L ON L.link_id = G.link_id
             LEFT JOIN {$p}lti_user AS U ON U.user_id = G.user_id
             WHERE G.context_id = :CID AND G.kind <> 'roster' AND G.success = :OK
             ORDER BY G.context_plus_log_id DESC
             LIMIT 20",
            array(':CID' => (int) $contextId, ':OK' => (int) $success)
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $packed = self::decodeLogDetail($row['detail'] ?? '');
            $row['kind_label'] = self::kindLabel($row['kind'] ?? '');
            $row['detail_text'] = $packed['detail'];
            $row['detail_debug'] = $packed['debug'];
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @param mixed $kind
     */
    private static function kindLabel($kind) {
        $kind = (string) $kind;
        if ( $kind === self::KIND_LINEITEM ) {
            return 'Line item';
        }
        if ( $kind === self::KIND_SCORE ) {
            return 'Score';
        }
        if ( $kind === 'lineitem_update' ) {
            return 'Line item update';
        }
        return $kind;
    }

    /**
     * @param mixed $raw
     * @return array{detail:string,debug:string}
     */
    private static function decodeLogDetail($raw) {
        if ( ! is_string($raw) || $raw === '' ) {
            return array('detail' => '', 'debug' => '');
        }
        $decoded = json_decode($raw, true);
        if ( ! is_array($decoded) ) {
            return array('detail' => $raw, 'debug' => '');
        }
        $detail = isset($decoded['detail']) ? trim((string) $decoded['detail']) : '';
        $debug = isset($decoded['debug']) ? trim((string) $decoded['debug']) : '';
        return array('detail' => $detail, 'debug' => $debug);
    }

    /**
     * @param mixed $timezone
     * @return \DateTimeZone|null
     */
    private static function zone($timezone) {
        if ( ! is_string($timezone) ) {
            return null;
        }
        $timezone = trim($timezone);
        // Abbreviations such as EST are not a course zone.
        if ( $timezone === '' || strpos($timezone, '/') === false ) {
            return null;
        }
        try {
            return new \DateTimeZone($timezone);
        } catch ( \Exception $e ) {
            return null;
        }
    }

    /**
     * @param array<int,mixed> $debug
     * @return int|null
     */
    private static function httpStatus(array $debug) {
        $status = null;
        foreach ( $debug as $entry ) {
            if ( ! is_string($entry) ) {
                continue;
            }
            if ( preg_match('/status=(\d+)/', $entry, $match) ) {
                $status = (int) $match[1];
            }
        }
        return $status;
    }

    /**
     * @param array<int,mixed> $debug
     */
    private static function debugText(array $debug) {
        $lines = array();
        foreach ( $debug as $entry ) {
            if ( is_string($entry) ) {
                $lines[] = $entry;
                continue;
            }
            $json = json_encode($entry);
            if ( is_string($json) ) {
                $lines[] = $json;
            }
        }
        return implode("\n", $lines);
    }

    /**
     * @param array<int,mixed>|false $debug
     * @param array<int,mixed> $more
     */
    private static function appendDebug(&$debug, array $more) {
        if ( ! is_array($debug) ) {
            return;
        }
        foreach ( $more as $entry ) {
            $debug[] = $entry;
        }
    }

    /**
     * True when this request holds the lock, false when another request holds
     * it, null when the database cannot lock and the caller should continue.
     *
     * @param int $linkId
     * @return bool|null
     */
    private static function lock($linkId) {
        try {
            $PDOX = LTIX::getConnection();
            $row = $PDOX->rowDie(
                "SELECT GET_LOCK(:NAME, 15) AS locked",
                array(':NAME' => 'tsugi_ags_'.(int) $linkId)
            );
        } catch ( \Throwable $e ) {
            error_log('Plus line item lock: '.$e->getMessage());
            return null;
        }
        if ( ! is_array($row) || ! array_key_exists('locked', $row) || $row['locked'] === null ) {
            return null;
        }
        return (int) $row['locked'] === 1;
    }

    /**
     * @param int $linkId
     */
    private static function unlock($linkId) {
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->rowDie(
                "SELECT RELEASE_LOCK(:NAME) AS released",
                array(':NAME' => 'tsugi_ags_'.(int) $linkId)
            );
        } catch ( \Throwable $e ) {
            error_log('Plus line item unlock: '.$e->getMessage());
        }
    }
}
