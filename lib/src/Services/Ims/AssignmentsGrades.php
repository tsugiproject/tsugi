<?php

namespace Tsugi\Services\Ims;

use Tsugi\Core\LTIX;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Services\Outbound\ToolDeploymentService;
use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\LTI13;
use Tsugi\Util\U;

/**
 * LTI Advantage Assignment and Grade Services, version 2.
 *
 * A line item is an lti_link. A result is the one lti_result row for that
 * link and user. Posting a score writes that row. Reading results returns
 * that same row. There is no second score log, and a later score replaces
 * the current one.
 *
 * The launch claim points here only when the deployment allows a grade
 * scope. A call still checks that deployment again.
 */
class AssignmentsGrades {

    public const LIMIT_MAX = 500;

    /**
     * Absolute line-item container placed in the launch claim.
     */
    public static function lineItemsUrl(int $contextId) {
        global $CFG;
        return rtrim((string) $CFG->wwwroot, '/').'/ims/ags/context/'.$contextId.'/lineitems';
    }

    public static function lineItemUrl(int $contextId, int $linkId) {
        return self::lineItemsUrl($contextId).'/'.$linkId;
    }

    public static function resultsUrl(int $contextId, int $linkId) {
        return self::lineItemUrl($contextId, $linkId).'/results';
    }

    public static function resultUrl(int $contextId, int $linkId, string $userId) {
        return self::resultsUrl($contextId, $linkId).'/'.rawurlencode($userId);
    }

    /**
     * Line item coupled to this resource link, when the launch has a grade column.
     */
    public static function coupledLineItemUrl(int $contextId, string $resourceLinkId) {
        $resourceLinkId = trim($resourceLinkId);
        if ( $contextId < 1 || $resourceLinkId === '' ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT L.link_id
             FROM {$p}lti_content C
             JOIN {$p}lti_link L ON L.link_id = C.link_id AND L.context_id = C.context_id
             WHERE C.context_id = :context_id
               AND C.resource_link_sha256 = :sha
               AND L.deleted = 0",
            array(
                ':context_id' => $contextId,
                ':sha' => U::lti_sha256($resourceLinkId),
            )
        );
        if ( ! is_array($row) || ! isset($row['link_id']) ) {
            return null;
        }
        return self::lineItemUrl($contextId, (int) $row['link_id']);
    }

    /**
     * @return array{context_id:int, link_id:?int, user_id:?string, action:string}|null
     */
    public static function parsePath(string $path) {
        if ( ! preg_match('#/(?:ims|lti)/ags/context/([1-9][0-9]*)/lineitems(?:/([1-9][0-9]*))?(?:/(results|scores)(?:/([1-9][0-9]*))?)?/?$#', $path, $match) ) {
            return null;
        }
        if ( ! self::canonicalInt($match[1]) ) {
            return null;
        }
        $linkId = null;
        if ( isset($match[2]) && $match[2] !== '' ) {
            if ( ! self::canonicalInt($match[2]) ) {
                return null;
            }
            $linkId = (int) $match[2];
        }
        $action = 'container';
        $userId = null;
        if ( isset($match[3]) && $match[3] !== '' ) {
            if ( $linkId === null ) {
                return null;
            }
            $action = $match[3];
            if ( isset($match[4]) && $match[4] !== '' ) {
                if ( $action !== 'results' || ! self::canonicalInt($match[4]) ) {
                    return null;
                }
                $userId = $match[4];
            }
        }
        if ( $action !== 'container' && $linkId === null ) {
            return null;
        }
        return array(
            'context_id' => (int) $match[1],
            'link_id' => $linkId,
            'user_id' => $userId,
            'action' => $action === 'container' && $linkId !== null ? 'item' : $action,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function listItems(string $accessToken, int $contextId, array $query) {
        $auth = self::authorize($accessToken, $contextId, array(
            ToolRegistrationDocument::SCOPE_LINEITEM,
            ToolRegistrationDocument::SCOPE_LINEITEM_READONLY,
        ));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $limit = self::limit($query);
        if ( $limit === false ) {
            return self::error(400, 'invalid_request');
        }
        $offset = self::offset($query, $limit);
        if ( $offset === false ) {
            return self::error(400, 'invalid_request');
        }
        $resourceId = self::queryString($query, 'resource_id');
        $tag = self::queryString($query, 'tag');
        $resourceLinkId = self::queryString($query, 'resource_link_id');
        if ( $resourceId === false || $tag === false || $resourceLinkId === false ) {
            return self::error(400, 'invalid_request');
        }

        $items = array();
        foreach ( self::ownedRows($contextId, $auth['deployment_ids']) as $row ) {
            $document = self::document($contextId, $row);
            if ( is_string($resourceId) && ($document['resourceId'] ?? null) !== $resourceId ) {
                continue;
            }
            if ( is_string($tag) && ($document['tag'] ?? null) !== $tag ) {
                continue;
            }
            if ( is_string($resourceLinkId) && ($document['resourceLinkId'] ?? null) !== $resourceLinkId ) {
                continue;
            }
            $items[] = $document;
        }
        $page = $items;
        $next = null;
        if ( $limit !== null ) {
            $page = array_slice($items, $offset, $limit);
            if ( ($offset + $limit) < count($items) ) {
                $next = self::nextUrl(self::lineItemsUrl($contextId), $limit, $offset + $limit, array(
                    'resource_id' => $resourceId,
                    'tag' => $tag,
                    'resource_link_id' => $resourceLinkId,
                ));
            }
        }
        return self::ok(200, $page, $next);
    }

    /**
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function readItem(string $accessToken, int $contextId, int $linkId) {
        $auth = self::authorize($accessToken, $contextId, array(
            ToolRegistrationDocument::SCOPE_LINEITEM,
            ToolRegistrationDocument::SCOPE_LINEITEM_READONLY,
        ));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(404, 'not_found');
        }
        return self::ok(200, self::document($contextId, $row));
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function create(string $accessToken, int $contextId, array $body) {
        $auth = self::authorize($accessToken, $contextId, array(ToolRegistrationDocument::SCOPE_LINEITEM));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $fields = self::definition($body, null);
        if ( isset($fields['error']) ) {
            return self::error(400, 'invalid_request');
        }
        if ( is_string($fields['resource_link_id']) ) {
            $linkId = self::createCoupled($contextId, $auth, $fields);
        } else {
            $linkId = self::insertLink($contextId, self::newLinkKey(), null, $auth['deployment_ids'][0], $fields);
        }
        if ( is_array($linkId) ) {
            return self::error($linkId['status'], $linkId['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(500, 'server_error');
        }
        $document = self::document($contextId, $row);
        return self::ok(201, $document, null, $document['id']);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function update(string $accessToken, int $contextId, int $linkId, array $body) {
        $auth = self::authorize($accessToken, $contextId, array(ToolRegistrationDocument::SCOPE_LINEITEM));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(404, 'not_found');
        }
        $current = self::document($contextId, $row);
        $fields = self::definition($body, $current['id']);
        if ( isset($fields['error']) ) {
            return self::error(400, 'invalid_request');
        }
        $currentLink = $current['resourceLinkId'] ?? null;
        $postedLink = $fields['resource_link_id'];
        if ( is_string($postedLink) && $postedLink !== $currentLink ) {
            return self::error(400, 'invalid_request');
        }
        if ( $postedLink === null && is_string($currentLink) && array_key_exists('resourceLinkId', $body) ) {
            return self::error(400, 'invalid_request');
        }
        self::applyDefinition($contextId, $linkId, $fields);
        $updated = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $updated === null ) {
            return self::error(500, 'server_error');
        }
        return self::ok(200, self::document($contextId, $updated));
    }

    /**
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function delete(string $accessToken, int $contextId, int $linkId) {
        $auth = self::authorize($accessToken, $contextId, array(ToolRegistrationDocument::SCOPE_LINEITEM));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(404, 'not_found');
        }
        $p = self::prefix();
        self::db()->queryDie(
            "DELETE FROM {$p}lti_link WHERE link_id = :link_id AND context_id = :context_id",
            array(
                ':link_id' => $linkId,
                ':context_id' => $contextId,
            )
        );
        return self::ok(204, null);
    }

    /**
     * @param array<string, mixed> $query
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function results(string $accessToken, int $contextId, int $linkId, array $query, ?string $pathUserId) {
        $auth = self::authorize($accessToken, $contextId, array(ToolRegistrationDocument::SCOPE_RESULT));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(404, 'not_found');
        }
        $limit = self::limit($query);
        if ( $limit === false ) {
            return self::error(400, 'invalid_request');
        }
        $offset = self::offset($query, $limit);
        if ( $offset === false ) {
            return self::error(400, 'invalid_request');
        }
        $filter = $pathUserId;
        if ( $filter === null ) {
            $filter = self::queryString($query, 'user_id');
            if ( $filter === false ) {
                return self::error(400, 'invalid_request');
            }
        } else if ( isset($query['user_id']) && $query['user_id'] !== '' && $query['user_id'] !== $filter ) {
            return self::error(400, 'invalid_request');
        }
        if ( is_string($filter) && self::canonicalUserId($filter) === null ) {
            return self::error(400, 'invalid_request');
        }

        $results = self::resultDocuments($contextId, $row, $filter);
        $page = $results;
        $next = null;
        if ( $limit !== null ) {
            $page = array_slice($results, $offset, $limit);
            if ( ($offset + $limit) < count($results) ) {
                $next = self::nextUrl(self::resultsUrl($contextId, $linkId), $limit, $offset + $limit, array(
                    'user_id' => $filter,
                ));
            }
        }
        return self::ok(200, $page, $next);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    public static function score(string $accessToken, int $contextId, int $linkId, array $body) {
        $auth = self::authorize($accessToken, $contextId, array(ToolRegistrationDocument::SCOPE_SCORE));
        if ( isset($auth['error']) ) {
            return self::error($auth['status'], $auth['error']);
        }
        $row = self::ownedRow($contextId, $linkId, $auth['deployment_ids']);
        if ( $row === null ) {
            return self::error(404, 'not_found');
        }
        $parsed = self::scoreBody($body);
        if ( isset($parsed['error']) ) {
            return self::error(400, 'invalid_request');
        }
        $userId = self::memberId($contextId, $auth['key_id'], $parsed['user_id']);
        if ( $userId === null ) {
            return self::error(400, 'invalid_request');
        }
        if ( $parsed['scoring_set'] && $parsed['scoring_user_id'] !== null ) {
            if ( self::userOnKey($auth['key_id'], $parsed['scoring_user_id']) === null ) {
                return self::error(400, 'invalid_request');
            }
        }
        self::writeScore($linkId, $userId, $parsed);
        return self::ok(204, null);
    }

    /**
     * @param array{key_id:int, deployment_ids:array<int, int>} $auth
     * @param array<string, mixed> $fields
     * @return int|array{status:int, error:string}
     */
    private static function createCoupled(int $contextId, array $auth, array $fields) {
        $content = self::contentForResourceLink($contextId, $auth, (string) $fields['resource_link_id']);
        if ( $content === null ) {
            return array('status' => 400, 'error' => 'invalid_request');
        }
        if ( $content['link_id'] !== null ) {
            return self::insertLink($contextId, self::newLinkKey(), (string) $fields['resource_link_id'], $content['tool_deployment_id'], $fields);
        }
        $p = self::prefix();
        $sha = U::lti_sha256((string) $fields['resource_link_id']);
        $existing = self::db()->rowDie(
            "SELECT link_id, deleted FROM {$p}lti_link
             WHERE context_id = :context_id AND link_sha256 = :sha",
            array(
                ':context_id' => $contextId,
                ':sha' => $sha,
            )
        );
        if ( is_array($existing) && isset($existing['link_id']) ) {
            $linkId = (int) $existing['link_id'];
            self::applyDefinition($contextId, $linkId, $fields);
            if ( (int) $existing['deleted'] === 1 ) {
                self::db()->queryDie(
                    "UPDATE {$p}lti_link
                     SET deleted = 0, deleted_at = NULL, published = 1, updated_at = NOW()
                     WHERE link_id = :link_id AND context_id = :context_id",
                    array(
                        ':link_id' => $linkId,
                        ':context_id' => $contextId,
                    )
                );
            }
        } else {
            $linkId = self::insertLink($contextId, (string) $fields['resource_link_id'], null, null, $fields);
            if ( is_array($linkId) ) {
                return $linkId;
            }
        }
        self::db()->queryDie(
            "UPDATE {$p}lti_content
             SET link_id = :link_id, updated_at = NOW()
             WHERE content_id = :content_id AND context_id = :context_id AND link_id IS NULL",
            array(
                ':link_id' => $linkId,
                ':content_id' => $content['content_id'],
                ':context_id' => $contextId,
            )
        );
        return $linkId;
    }

    /**
     * @param array<string, mixed> $fields
     * @return int|array{status:int, error:string}
     */
    private static function insertLink(int $contextId, string $linkKey, ?string $resourceLinkId, ?int $ownerId, array $fields) {
        $p = self::prefix();
        $json = null;
        if ( $ownerId !== null ) {
            $payload = array('ags_tool_deployment_id' => $ownerId);
            if ( $resourceLinkId !== null && $resourceLinkId !== '' ) {
                $payload['ags_resource_link_id'] = $resourceLinkId;
            }
            $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $params = array(
            ':link_key' => $linkKey,
            ':link_sha256' => U::lti_sha256($linkKey),
            ':context_id' => $contextId,
            ':title' => $fields['label'],
            ':score_maximum' => $fields['score_maximum'],
            ':ags_resource_id' => $fields['resource_id'],
            ':ags_tag' => $fields['tag'],
            ':grades_released' => self::flag($fields['grades_released']),
            ':json' => $json,
        );
        $start = $fields['start'] === null ? 'NULL' : 'FROM_UNIXTIME(:start_epoch)';
        $end = $fields['end'] === null ? 'NULL' : 'FROM_UNIXTIME(:end_epoch)';
        if ( $fields['start'] !== null ) {
            $params[':start_epoch'] = $fields['start'];
        }
        if ( $fields['end'] !== null ) {
            $params[':end_epoch'] = $fields['end'];
        }
        self::db()->queryDie(
            "INSERT INTO {$p}lti_link
                (link_key, link_sha256, context_id, title, score_maximum,
                 ags_resource_id, ags_tag, grades_released,
                 submission_start_datetime, submission_end_datetime,
                 json, published, created_at, updated_at)
             VALUES
                (:link_key, :link_sha256, :context_id, :title, :score_maximum,
                 :ags_resource_id, :ags_tag, :grades_released,
                 {$start}, {$end},
                 :json, 1, NOW(), NOW())",
            $params
        );
        return (int) self::db()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function applyDefinition(int $contextId, int $linkId, array $fields) {
        $p = self::prefix();
        $params = array(
            ':title' => $fields['label'],
            ':score_maximum' => $fields['score_maximum'],
            ':ags_resource_id' => $fields['resource_id'],
            ':ags_tag' => $fields['tag'],
            ':grades_released' => self::flag($fields['grades_released']),
            ':link_id' => $linkId,
            ':context_id' => $contextId,
        );
        $start = $fields['start'] === null ? 'NULL' : 'FROM_UNIXTIME(:start_epoch)';
        $end = $fields['end'] === null ? 'NULL' : 'FROM_UNIXTIME(:end_epoch)';
        if ( $fields['start'] !== null ) {
            $params[':start_epoch'] = $fields['start'];
        }
        if ( $fields['end'] !== null ) {
            $params[':end_epoch'] = $fields['end'];
        }
        self::db()->queryDie(
            "UPDATE {$p}lti_link
             SET title = :title,
                 score_maximum = :score_maximum,
                 ags_resource_id = :ags_resource_id,
                 ags_tag = :ags_tag,
                 grades_released = :grades_released,
                 submission_start_datetime = {$start},
                 submission_end_datetime = {$end},
                 updated_at = NOW()
             WHERE link_id = :link_id AND context_id = :context_id",
            $params
        );
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function writeScore(int $linkId, int $userId, array $parsed) {
        $p = self::prefix();
        $params = array(
            ':link_id' => $linkId,
            ':user_id' => $userId,
            ':grade' => $parsed['grade'],
            ':score_given' => $parsed['score_given'],
            ':result_maximum' => $parsed['result_maximum'],
            ':activity_progress' => $parsed['activity_progress'],
            ':grading_progress' => $parsed['grading_progress'],
            ':score_epoch' => $parsed['timestamp'],
            ':set_comment' => $parsed['comment_set'] ? 1 : 0,
            ':comment' => $parsed['comment'],
            ':set_scoring' => $parsed['scoring_set'] ? 1 : 0,
            ':scoring_user_id' => $parsed['scoring_user_id'],
            ':set_started' => $parsed['started_set'] ? 1 : 0,
            ':set_submitted' => $parsed['submitted_set'] ? 1 : 0,
        );
        $started = $parsed['started'] === null ? 'NULL' : 'FROM_UNIXTIME(:started_epoch)';
        $submitted = $parsed['submitted'] === null ? 'NULL' : 'FROM_UNIXTIME(:submitted_epoch)';
        if ( $parsed['started'] !== null ) {
            $params[':started_epoch'] = $parsed['started'];
        }
        if ( $parsed['submitted'] !== null ) {
            $params[':submitted_epoch'] = $parsed['submitted'];
        }
        self::db()->queryDie(
            "INSERT INTO {$p}lti_result
                /*PDOX pk: result_id lk: link_id,user_id */
                (link_id, user_id, grade, score_given, result_maximum,
                 activity_progress, grading_progress, score_timestamp,
                 comment, scoring_user_id, started_at, submitted_at,
                 deleted, created_at, updated_at)
             VALUES
                (:link_id, :user_id, :grade, :score_given, :result_maximum,
                 :activity_progress, :grading_progress, FROM_UNIXTIME(:score_epoch),
                 :comment, :scoring_user_id, {$started}, {$submitted},
                 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                grade = VALUES(grade),
                score_given = VALUES(score_given),
                result_maximum = VALUES(result_maximum),
                activity_progress = VALUES(activity_progress),
                grading_progress = VALUES(grading_progress),
                score_timestamp = VALUES(score_timestamp),
                comment = IF(:set_comment = 1, VALUES(comment), comment),
                scoring_user_id = IF(:set_scoring = 1, VALUES(scoring_user_id), scoring_user_id),
                started_at = IF(:set_started = 1, VALUES(started_at), started_at),
                submitted_at = IF(:set_submitted = 1, VALUES(submitted_at), submitted_at),
                deleted = 0,
                deleted_at = NULL,
                updated_at = NOW()",
            $params
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private static function definition(array $body, ?string $currentId) {
        if ( $currentId !== null && array_key_exists('id', $body) && $body['id'] !== null && $body['id'] !== $currentId ) {
            return array('error' => 'invalid_request');
        }
        $label = self::text($body, 'label', true, 1024);
        if ( ! is_string($label) ) {
            return array('error' => 'invalid_request');
        }
        $maximum = self::positiveNumber($body, 'scoreMaximum', true);
        if ( ! is_float($maximum) ) {
            return array('error' => 'invalid_request');
        }
        $resourceId = self::text($body, 'resourceId', false, 256);
        $tag = self::text($body, 'tag', false, 256);
        $resourceLinkId = self::text($body, 'resourceLinkId', false, 1024);
        if ( $resourceId === false || $tag === false || $resourceLinkId === false ) {
            return array('error' => 'invalid_request');
        }
        $start = self::optionalInstant($body, 'startDateTime');
        $end = self::optionalInstant($body, 'endDateTime');
        if ( $start === false || $end === false ) {
            return array('error' => 'invalid_request');
        }
        $released = self::optionalBool($body, 'gradesReleased');
        if ( $released === 'invalid' ) {
            return array('error' => 'invalid_request');
        }
        return array(
            'label' => $label,
            'score_maximum' => $maximum,
            'resource_id' => $resourceId,
            'tag' => $tag,
            'resource_link_id' => $resourceLinkId,
            'start' => $start,
            'end' => $end,
            'grades_released' => $released,
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private static function scoreBody(array $body) {
        $userId = self::canonicalUserId(isset($body['userId']) && is_string($body['userId']) ? $body['userId'] : '');
        if ( $userId === null ) {
            return array('error' => 'invalid_request');
        }
        $activity = self::enum($body, 'activityProgress', array(
            LTI13::ACTIVITY_PROGRESS_INITIALIZED,
            LTI13::ACTIVITY_PROGRESS_STARTED,
            LTI13::ACTIVITY_PROGRESS_INPROGRESS,
            LTI13::ACTIVITY_PROGRESS_SUBMITTED,
            LTI13::ACTIVITY_PROGRESS_COMPLETED,
        ));
        $grading = self::enum($body, 'gradingProgress', array(
            LTI13::GRADING_PROGRESS_FULLYGRADED,
            LTI13::GRADING_PROGRESS_PENDING,
            LTI13::GRADING_PROGRESS_PENDINGMANUAL,
            LTI13::GRADING_PROGRESS_FAILED,
            LTI13::GRADING_PROGRESS_NOTREADY,
        ));
        if ( $activity === null || $grading === null ) {
            return array('error' => 'invalid_request');
        }
        if ( ! isset($body['timestamp']) || ! is_string($body['timestamp']) ) {
            return array('error' => 'invalid_request');
        }
        $timestamp = self::instant($body['timestamp']);
        if ( $timestamp === false ) {
            return array('error' => 'invalid_request');
        }
        $given = self::scoreNumber($body, 'scoreGiven');
        if ( $given === false ) {
            return array('error' => 'invalid_request');
        }
        $maximum = self::positiveNumber($body, 'scoreMaximum', false);
        if ( $maximum === false ) {
            return array('error' => 'invalid_request');
        }
        if ( $grading === LTI13::GRADING_PROGRESS_FULLYGRADED && $given === null ) {
            return array('error' => 'invalid_request');
        }
        if ( $given !== null && ! is_float($maximum) ) {
            return array('error' => 'invalid_request');
        }
        $comment = array('set' => false, 'value' => null);
        if ( array_key_exists('comment', $body) ) {
            if ( $body['comment'] !== null && ! is_string($body['comment']) ) {
                return array('error' => 'invalid_request');
            }
            if ( is_string($body['comment']) && strlen($body['comment']) > 65000 ) {
                return array('error' => 'invalid_request');
            }
            $comment = array('set' => true, 'value' => $body['comment']);
        }
        $scoring = array('set' => false, 'value' => null);
        if ( array_key_exists('scoringUserId', $body) ) {
            if ( $body['scoringUserId'] === null || $body['scoringUserId'] === '' ) {
                $scoring = array('set' => true, 'value' => null);
            } else if ( ! is_string($body['scoringUserId']) ) {
                return array('error' => 'invalid_request');
            } else {
                $scoringId = self::canonicalUserId($body['scoringUserId']);
                if ( $scoringId === null ) {
                    return array('error' => 'invalid_request');
                }
                $scoring = array('set' => true, 'value' => $scoringId);
            }
        }
        $started = array('set' => false, 'value' => null);
        $submitted = array('set' => false, 'value' => null);
        if ( array_key_exists('submission', $body) ) {
            $submission = $body['submission'];
            if ( $submission === null ) {
                $started = array('set' => true, 'value' => null);
                $submitted = array('set' => true, 'value' => null);
            } else if ( ! is_array($submission) || (array_is_list($submission) && count($submission) > 0) ) {
                return array('error' => 'invalid_request');
            } else {
                if ( array_key_exists('startedAt', $submission) ) {
                    $epoch = self::nullableInstant($submission['startedAt']);
                    if ( $epoch === false ) {
                        return array('error' => 'invalid_request');
                    }
                    $started = array('set' => true, 'value' => $epoch);
                }
                if ( array_key_exists('submittedAt', $submission) ) {
                    $epoch = self::nullableInstant($submission['submittedAt']);
                    if ( $epoch === false ) {
                        return array('error' => 'invalid_request');
                    }
                    $submitted = array('set' => true, 'value' => $epoch);
                }
            }
        }
        $grade = null;
        $scoreGiven = null;
        $resultMaximum = null;
        if ( $given !== null && is_float($maximum) && $maximum > 0 ) {
            $scoreGiven = $given;
            $resultMaximum = $maximum;
            $grade = $given / $maximum;
        }
        return array(
            'user_id' => $userId,
            'activity_progress' => $activity,
            'grading_progress' => $grading,
            'timestamp' => $timestamp,
            'grade' => $grade,
            'score_given' => $scoreGiven,
            'result_maximum' => $resultMaximum,
            'comment_set' => $comment['set'],
            'comment' => $comment['value'],
            'scoring_set' => $scoring['set'],
            'scoring_user_id' => $scoring['value'],
            'started_set' => $started['set'],
            'started' => $started['value'],
            'submitted_set' => $submitted['set'],
            'submitted' => $submitted['value'],
        );
    }

    /**
     * @param array<int, int> $deploymentIds
     * @return array<int, array<string, mixed>>
     */
    private static function ownedRows(int $contextId, array $deploymentIds) {
        if ( count($deploymentIds) < 1 ) {
            return array();
        }
        $p = self::prefix();
        $params = array(':context_id' => $contextId);
        $contentIn = self::inClause('content_dep', $deploymentIds, $params);
        $ownerIn = self::inClause('owner_dep', $deploymentIds, $params);
        $rows = self::db()->allRowsDie(
            "SELECT L.link_id, L.title, L.score_maximum, L.ags_resource_id, L.ags_tag,
                    L.grades_released, L.json,
                    UNIX_TIMESTAMP(L.submission_start_datetime) AS start_epoch,
                    UNIX_TIMESTAMP(L.submission_end_datetime) AS end_epoch,
                    C.resource_link_id AS content_resource_link_id
             FROM {$p}lti_link L
             LEFT JOIN {$p}lti_content C
               ON C.link_id = L.link_id AND C.context_id = L.context_id
             WHERE L.context_id = :context_id AND L.deleted = 0
               AND (
                    C.tool_deployment_id IN ({$contentIn})
                    OR (
                        JSON_VALID(L.json)
                        AND CAST(JSON_UNQUOTE(JSON_EXTRACT(L.json, '$.ags_tool_deployment_id')) AS UNSIGNED) IN ({$ownerIn})
                    )
               )
             ORDER BY L.link_id ASC",
            $params
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param array<int, int> $deploymentIds
     * @return array<string, mixed>|null
     */
    private static function ownedRow(int $contextId, int $linkId, array $deploymentIds) {
        foreach ( self::ownedRows($contextId, $deploymentIds) as $row ) {
            if ( (int) $row['link_id'] === $linkId ) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @param array{key_id:int, deployment_ids:array<int, int>} $auth
     * @return array{content_id:int, link_id:?int, tool_deployment_id:int}|null
     */
    private static function contentForResourceLink(int $contextId, array $auth, string $resourceLinkId) {
        $p = self::prefix();
        $params = array(
            ':context_id' => $contextId,
            ':key_id' => $auth['key_id'],
            ':sha' => U::lti_sha256($resourceLinkId),
        );
        $in = self::inClause('dep', $auth['deployment_ids'], $params);
        $row = self::db()->rowDie(
            "SELECT content_id, link_id, tool_deployment_id
             FROM {$p}lti_content
             WHERE context_id = :context_id AND key_id = :key_id
               AND resource_link_sha256 = :sha
               AND tool_deployment_id IN ({$in})",
            $params
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'content_id' => (int) $row['content_id'],
            'link_id' => isset($row['link_id']) && $row['link_id'] !== null ? (int) $row['link_id'] : null,
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function document(int $contextId, array $row) {
        $maximum = isset($row['score_maximum']) && is_numeric($row['score_maximum']) ? (float) $row['score_maximum'] : 0.0;
        if ( $maximum <= 0 ) {
            $maximum = 1.0;
        }
        $label = isset($row['title']) && is_string($row['title']) ? trim($row['title']) : '';
        if ( $label === '' ) {
            $label = 'Grade';
        }
        $document = array(
            'id' => self::lineItemUrl($contextId, (int) $row['link_id']),
            'scoreMaximum' => $maximum,
            'label' => $label,
        );
        $resourceId = isset($row['ags_resource_id']) && is_string($row['ags_resource_id']) ? $row['ags_resource_id'] : '';
        if ( $resourceId !== '' ) {
            $document['resourceId'] = $resourceId;
        }
        $tag = isset($row['ags_tag']) && is_string($row['ags_tag']) ? $row['ags_tag'] : '';
        if ( $tag !== '' ) {
            $document['tag'] = $tag;
        }
        $resourceLinkId = self::resourceLinkId($row);
        if ( $resourceLinkId !== null ) {
            $document['resourceLinkId'] = $resourceLinkId;
        }
        if ( isset($row['start_epoch']) && is_numeric($row['start_epoch']) && (int) $row['start_epoch'] > 0 ) {
            $document['startDateTime'] = gmdate('Y-m-d\TH:i:s\Z', (int) $row['start_epoch']);
        }
        if ( isset($row['end_epoch']) && is_numeric($row['end_epoch']) && (int) $row['end_epoch'] > 0 ) {
            $document['endDateTime'] = gmdate('Y-m-d\TH:i:s\Z', (int) $row['end_epoch']);
        }
        if ( isset($row['grades_released']) && $row['grades_released'] !== null && $row['grades_released'] !== '' ) {
            $document['gradesReleased'] = (int) $row['grades_released'] === 1;
        }
        return $document;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, array<string, mixed>>
     */
    private static function resultDocuments(int $contextId, array $row, ?string $userId) {
        $p = self::prefix();
        $params = array(':link_id' => (int) $row['link_id']);
        $userSql = '';
        if ( $userId !== null ) {
            $userSql = ' AND R.user_id = :user_id';
            $params[':user_id'] = (int) $userId;
        }
        $rows = self::db()->allRowsDie(
            "SELECT R.user_id, R.grade, R.score_given, R.result_maximum, R.comment, R.scoring_user_id
             FROM {$p}lti_result R
             WHERE R.link_id = :link_id AND R.deleted = 0{$userSql}
             ORDER BY R.user_id ASC",
            $params
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $lineMaximum = isset($row['score_maximum']) && is_numeric($row['score_maximum']) ? (float) $row['score_maximum'] : 0.0;
        $out = array();
        foreach ( $rows as $result ) {
            $score = self::currentScore($result, $lineMaximum);
            if ( $score === null ) {
                continue;
            }
            $user = (string) (int) $result['user_id'];
            $document = array(
                'id' => self::resultUrl($contextId, (int) $row['link_id'], $user),
                'scoreOf' => self::lineItemUrl($contextId, (int) $row['link_id']),
                'userId' => $user,
                'resultScore' => $score['score'],
                'resultMaximum' => $score['maximum'],
            );
            $comment = isset($result['comment']) && is_string($result['comment']) ? $result['comment'] : '';
            if ( $comment !== '' ) {
                $document['comment'] = $comment;
            }
            if ( isset($result['scoring_user_id']) && $result['scoring_user_id'] !== null && $result['scoring_user_id'] !== '' ) {
                $document['scoringUserId'] = (string) (int) $result['scoring_user_id'];
            }
            $out[] = $document;
        }
        return $out;
    }

    /**
     * The cell currently showing for this user. A posted score is that cell.
     * A grade stored as a 0-1 fraction, with no scoreGiven yet, is the same cell.
     *
     * @param array<string, mixed> $result
     * @return array{score:float, maximum:float}|null
     */
    private static function currentScore(array $result, float $lineMaximum) {
        if ( isset($result['score_given']) && $result['score_given'] !== null && $result['score_given'] !== '' ) {
            $maximum = 1.0;
            if ( isset($result['result_maximum']) && is_numeric($result['result_maximum']) && (float) $result['result_maximum'] > 0 ) {
                $maximum = (float) $result['result_maximum'];
            } else if ( $lineMaximum > 0 ) {
                $maximum = $lineMaximum;
            }
            return array(
                'score' => (float) $result['score_given'],
                'maximum' => $maximum,
            );
        }
        if ( isset($result['grade']) && $result['grade'] !== null && $result['grade'] !== '' ) {
            $maximum = $lineMaximum > 0 ? $lineMaximum : 1.0;
            return array(
                'score' => (float) $result['grade'] * $maximum,
                'maximum' => $maximum,
            );
        }
        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resourceLinkId(array $row) {
        if ( isset($row['content_resource_link_id']) && is_string($row['content_resource_link_id']) && $row['content_resource_link_id'] !== '' ) {
            return $row['content_resource_link_id'];
        }
        $json = self::agsJson(isset($row['json']) ? $row['json'] : null);
        if ( isset($json['ags_resource_link_id']) && is_string($json['ags_resource_link_id']) && $json['ags_resource_link_id'] !== '' ) {
            return $json['ags_resource_link_id'];
        }
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function agsJson($json) {
        if ( ! is_string($json) || $json === '' ) {
            return array();
        }
        $decoded = json_decode($json, true);
        if ( ! is_array($decoded) || array_is_list($decoded) ) {
            return array();
        }
        return $decoded;
    }

    /**
     * @param array<int, string> $needed
     * @return array{key_id:int, deployment_ids:array<int, int>}|array{status:int, error:string}
     */
    private static function authorize(string $accessToken, int $contextId, array $needed) {
        $token = AccessToken::verify($accessToken);
        if ( $token === null ) {
            return array('status' => 401, 'error' => 'invalid_token');
        }
        $held = array();
        foreach ( $needed as $scope ) {
            if ( in_array($scope, $token['scopes'], true) ) {
                $held[] = $scope;
            }
        }
        if ( count($held) < 1 ) {
            return array('status' => 403, 'error' => 'insufficient_scope');
        }
        if ( $contextId < 1 ) {
            return array('status' => 404, 'error' => 'not_found');
        }
        try {
            $visible = ToolDeploymentService::getDeploymentsForContext($contextId);
        } catch ( \InvalidArgumentException $ex ) {
            return array('status' => 404, 'error' => 'not_found');
        }
        $covering = array();
        foreach ( $visible as $deployment ) {
            if ( (int) $deployment['registration_id'] !== $token['registration_id'] ) {
                continue;
            }
            if ( (int) $deployment['key_id'] !== $token['key_id'] ) {
                continue;
            }
            $deploymentId = isset($deployment['deployment_id']) ? (string) $deployment['deployment_id'] : '';
            if ( $token['deployment_id'] !== null && $deploymentId !== $token['deployment_id'] ) {
                continue;
            }
            $scopes = ToolDeploymentGrant::allowedScopes((int) $deployment['tool_deployment_id']);
            $allowed = false;
            foreach ( $held as $scope ) {
                if ( in_array($scope, $scopes, true) ) {
                    $allowed = true;
                    break;
                }
            }
            if ( $allowed ) {
                $covering[] = (int) $deployment['tool_deployment_id'];
            }
        }
        if ( count($covering) < 1 ) {
            return array('status' => 403, 'error' => 'not_allowed');
        }
        sort($covering);
        return array(
            'key_id' => $token['key_id'],
            'deployment_ids' => $covering,
        );
    }

    private static function memberId(int $contextId, int $keyId, int $userId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT U.user_id
             FROM {$p}lti_membership M
             JOIN {$p}lti_user U ON U.user_id = M.user_id AND U.key_id = :key_id
             WHERE M.context_id = :context_id AND M.user_id = :user_id AND M.deleted = 0",
            array(
                ':context_id' => $contextId,
                ':key_id' => $keyId,
                ':user_id' => $userId,
            )
        );
        if ( ! is_array($row) || ! isset($row['user_id']) ) {
            return null;
        }
        return (int) $row['user_id'];
    }

    private static function userOnKey(int $keyId, int $userId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT user_id FROM {$p}lti_user WHERE user_id = :user_id AND key_id = :key_id",
            array(
                ':user_id' => $userId,
                ':key_id' => $keyId,
            )
        );
        if ( ! is_array($row) || ! isset($row['user_id']) ) {
            return null;
        }
        return (int) $row['user_id'];
    }

    /**
     * @param array<string, mixed> $body
     * @return string|null|false null means omitted or empty
     */
    private static function text(array $body, string $key, bool $required, int $max) {
        if ( ! array_key_exists($key, $body) || $body[$key] === null ) {
            return $required ? false : null;
        }
        if ( ! is_string($body[$key]) ) {
            return false;
        }
        $text = trim($body[$key]);
        if ( $text === '' ) {
            return $required ? false : null;
        }
        if ( mb_strlen($text) > $max ) {
            return false;
        }
        return $text;
    }

    /**
     * @param array<string, mixed> $body
     * @return float|null|false
     */
    private static function positiveNumber(array $body, string $key, bool $required) {
        if ( ! array_key_exists($key, $body) || $body[$key] === null ) {
            return $required ? false : null;
        }
        $value = self::finiteNumber($body[$key]);
        if ( $value === null || $value <= 0 ) {
            return false;
        }
        return $value;
    }

    /**
     * scoreGiven may be 0. Absent or null clears the current score.
     *
     * @param array<string, mixed> $body
     * @return float|null|false
     */
    private static function scoreNumber(array $body, string $key) {
        if ( ! array_key_exists($key, $body) || $body[$key] === null ) {
            return null;
        }
        $value = self::finiteNumber($body[$key]);
        if ( $value === null || $value < 0 ) {
            return false;
        }
        return $value;
    }

    /**
     * @param mixed $value
     */
    private static function finiteNumber($value) {
        if ( is_bool($value) || is_string($value) || is_array($value) ) {
            return null;
        }
        if ( ! is_int($value) && ! is_float($value) ) {
            return null;
        }
        if ( ! is_finite((float) $value) ) {
            return null;
        }
        return (float) $value;
    }

    /**
     * @param array<string, mixed> $body
     * @param array<int, string> $allowed
     */
    private static function enum(array $body, string $key, array $allowed) {
        if ( ! isset($body[$key]) || ! is_string($body[$key]) ) {
            return null;
        }
        if ( ! in_array($body[$key], $allowed, true) ) {
            return null;
        }
        return $body[$key];
    }

    /**
     * @param array<string, mixed> $body
     * @return int|null|false null means omitted
     */
    private static function optionalInstant(array $body, string $key) {
        if ( ! array_key_exists($key, $body) || $body[$key] === null || $body[$key] === '' ) {
            return null;
        }
        if ( ! is_string($body[$key]) ) {
            return false;
        }
        return self::instant($body[$key]);
    }

    /**
     * @param mixed $value
     * @return int|null|false
     */
    private static function nullableInstant($value) {
        if ( $value === null || $value === '' ) {
            return null;
        }
        if ( ! is_string($value) ) {
            return false;
        }
        return self::instant($value);
    }

    /**
     * @return int|false
     */
    private static function instant(string $value) {
        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value) ) {
            return false;
        }
        try {
            $parsed = new \DateTimeImmutable($value);
        } catch ( \Exception $ex ) {
            return false;
        }
        $epoch = $parsed->getTimestamp();
        if ( $epoch < 1 || $epoch > 2147483647 ) {
            return false;
        }
        return $epoch;
    }

    /**
     * @param array<string, mixed> $body
     * @return bool|null|string null means omitted, the string invalid means a bad value
     */
    private static function optionalBool(array $body, string $key) {
        if ( ! array_key_exists($key, $body) || $body[$key] === null ) {
            return null;
        }
        if ( $body[$key] === true || $body[$key] === false ) {
            return $body[$key];
        }
        return 'invalid';
    }

    /**
     * @param bool|null $value
     */
    private static function flag($value) {
        if ( $value === true ) {
            return 1;
        }
        if ( $value === false ) {
            return 0;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $query
     * @return int|null|false
     */
    private static function limit(array $query) {
        if ( ! isset($query['limit']) || $query['limit'] === '' ) {
            return null;
        }
        $raw = $query['limit'];
        if ( is_array($raw) || ! is_string($raw) && ! is_int($raw) ) {
            return false;
        }
        $raw = (string) $raw;
        if ( ! preg_match('/^[1-9][0-9]*$/', $raw) || (string) (int) $raw !== $raw ) {
            return false;
        }
        $limit = (int) $raw;
        if ( $limit > self::LIMIT_MAX ) {
            $limit = self::LIMIT_MAX;
        }
        return $limit;
    }

    /**
     * @param array<string, mixed> $query
     * @param int|null $limit
     * @return int|false
     */
    private static function offset(array $query, $limit) {
        if ( $limit === null || ! isset($query['offset']) || $query['offset'] === '' ) {
            return 0;
        }
        $raw = $query['offset'];
        if ( is_array($raw) || ! is_string($raw) && ! is_int($raw) ) {
            return false;
        }
        $raw = (string) $raw;
        if ( ! preg_match('/^(0|[1-9][0-9]*)$/', $raw) || (string) (int) $raw !== $raw ) {
            return false;
        }
        return (int) $raw;
    }

    /**
     * @param array<string, mixed> $query
     * @return string|null|false
     */
    private static function queryString(array $query, string $key) {
        if ( ! isset($query[$key]) || $query[$key] === '' ) {
            return null;
        }
        if ( ! is_string($query[$key]) ) {
            return false;
        }
        return $query[$key];
    }

    /**
     * @param array<string, string|null> $filters
     */
    private static function nextUrl(string $base, int $limit, int $offset, array $filters) {
        $built = array(
            'limit' => (string) $limit,
            'offset' => (string) $offset,
        );
        foreach ( $filters as $key => $value ) {
            if ( is_string($value) && $value !== '' ) {
                $built[$key] = $value;
            }
        }
        return $base.'?'.http_build_query($built);
    }

    private static function newLinkKey() {
        return 'ags_'.bin2hex(random_bytes(16));
    }

    private static function canonicalInt(string $raw) {
        return (bool) preg_match('/^[1-9][0-9]*$/', $raw) && (string) (int) $raw === $raw;
    }

    private static function canonicalUserId(string $raw) {
        if ( ! self::canonicalInt($raw) ) {
            return null;
        }
        return (int) $raw;
    }

    /**
     * @param array<int, int> $ids
     * @param array<string, mixed> $params
     */
    private static function inClause(string $prefix, array $ids, array &$params) {
        $parts = array();
        foreach ( array_values($ids) as $index => $id ) {
            $name = ':'.$prefix.$index;
            $parts[] = $name;
            $params[$name] = (int) $id;
        }
        return implode(', ', $parts);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string}
     */
    private static function ok(int $status, ?array $body, ?string $next = null, ?string $location = null) {
        return array(
            'status' => $status,
            'body' => $body,
            'next' => $next,
            'location' => $location,
        );
    }

    /**
     * @return array{status:int, body:array<string, mixed>, next:?string, location:?string}
     */
    private static function error(int $status, string $error) {
        return array(
            'status' => $status,
            'body' => array('error' => $error),
            'next' => null,
            'location' => null,
        );
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        $PDOX = LTIX::getConnection();
        if ( ! $PDOX ) {
            throw new \RuntimeException('Database connection is not available.');
        }
        return $PDOX;
    }

    private static function prefix() {
        global $CFG;
        return isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
    }
}
