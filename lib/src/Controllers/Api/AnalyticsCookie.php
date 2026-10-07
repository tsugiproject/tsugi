<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Core\Rest;
use Tsugi\Services\Analytics\AnalyticsService;
use Tsugi\Util\U;

/**
 * Cookie-session analytics for top-frame LMS tools.
 *
 * Separate from /api/analytics.php so the session mode is not ambiguous.
 * GET /api/analytics_cookie.php?link_id=
 */
class AnalyticsCookie {

    public function handle(): void
    {
        if ( Rest::preFlight() ) return;

        header('Content-Type: application/json; charset=utf-8');

        LTIX::getConnection();
        session_start();

        $link_id = U::get($_GET, 'link_id');
        if ( $link_id !== null ) $link_id = $link_id + 0;
        if ( ! $link_id || $link_id < 1 ) {
            http_response_code(403);
            echo(json_encode(array('error' => 'No link_id'), JSON_PRETTY_PRINT));
            return;
        }

        // Must be logged in via cookie session
        $user_id = U::get($_SESSION,'id');
        if ( ! $user_id ) {
            http_response_code(403);
            echo(json_encode(array('error' => 'Not logged in'), JSON_PRETTY_PRINT));
            return;
        }
        $user_id = $user_id + 0;

        // Admins are allowed (admin session sets $_SESSION['admin'])
        $is_admin = U::get($_SESSION,'admin') ? true : false;

        if ( ! $is_admin ) {
            global $CFG, $PDOX;
            // Same instructor rule as ReqScope: membership role, course owner, or key owner.
            $row = $PDOX->rowDie(
                "SELECT L.context_id, C.user_id AS owner_id, M.role, M.role_override,
                        (SELECT K.user_id FROM {$CFG->dbprefix}lti_key AS K
                          WHERE K.key_id = C.key_id AND K.user_id = :UID2 LIMIT 1) AS key_owner
                 FROM {$CFG->dbprefix}lti_link AS L
                 JOIN {$CFG->dbprefix}lti_context AS C ON C.context_id = L.context_id
                 LEFT JOIN {$CFG->dbprefix}lti_membership AS M
                    ON M.context_id = L.context_id AND M.user_id = :UID
                 WHERE L.link_id = :LID
                   AND (C.deleted IS NULL OR C.deleted = 0)",
                array(':UID' => $user_id, ':UID2' => $user_id, ':LID' => $link_id)
            );
            if ( ! $row ) {
                http_response_code(403);
                echo(json_encode(array('error' => 'Invalid link_id'), JSON_PRETTY_PRINT));
                return;
            }
            $role = isset($row['role']) ? ($row['role'] + 0) : 0;
            $role_override = isset($row['role_override']) ? ($row['role_override'] + 0) : 0;
            $max_role = max($role, $role_override);
            $owns = ((int) ($row['owner_id'] ?? 0) === $user_id)
                || ((int) ($row['key_owner'] ?? 0) === $user_id);
            if ( $max_role < LTIX::ROLE_INSTRUCTOR && ! $owns ) {
                http_response_code(403);
                echo(json_encode(array('error' => 'Not authorized'), JSON_PRETTY_PRINT));
                return;
            }
        }

        $retval = AnalyticsService::viewModelForLink($link_id);

        echo(json_encode($retval,JSON_PRETTY_PRINT));
    }

}
