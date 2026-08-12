<?php

/**
 * Shared audience query for context mailing-list export and bulk mail.
 *
 * @param int $exclude_recent_bulk_days When > 0, exclude users who already have a
 *   successful bulk send (mail_sent with bulk_id, status sent) in this context
 *   within that many days. Scoped per context_id.
 * @param int $limit When > 0, return only the most recently logged-in N users
 *   (ORDER BY login_at DESC LIMIT N). 0 = no limit (email-domain sort).
 * @return array{0: string, 1: array}|false SQL + params, or false if args invalid
 */
function mail_context_audience_sql($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
    $built = mail_context_audience_from_where($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
    if ( $built === false ) {
        return false;
    }
    list($from_where, $params) = $built;
    $limit = (int) $limit;
    if ( $limit < 0 ) {
        return false;
    }

    $sql = "SELECT DISTINCT U.email, U.displayname, U.login_at, U.user_id, COALESCE(P.premium, 0) AS premium
            ".$from_where;

    if ( $limit > 0 ) {
        $sql .= " ORDER BY U.login_at DESC LIMIT ".$limit;
    } else {
        $sql .= " ORDER BY SUBSTRING_INDEX(U.email, '@', -1), U.email";
    }

    return array($sql, $params);
}

/**
 * Build FROM/JOIN/WHERE for context audience (shared by select + counts).
 *
 * @return array{0: string, 1: array}|false
 */
function mail_context_audience_from_where($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0) {
    global $CFG;

    $context_id = (int) $context_id;
    $days = (int) $days;
    $exclude_recent_bulk_days = (int) $exclude_recent_bulk_days;
    if ( $context_id < 1 || $days < 1 || $days > 365 ) {
        return false;
    }
    if ( $exclude_recent_bulk_days < 0 || $exclude_recent_bulk_days > 365 ) {
        return false;
    }

    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    $params = array(':CID' => $context_id, ':CUTOFF' => $cutoff_date);

    $sql = "FROM {$CFG->dbprefix}lti_membership AS M
            JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
            LEFT JOIN {$CFG->dbprefix}profile AS P ON U.profile_id = P.profile_id
            WHERE M.context_id = :CID
              AND U.email IS NOT NULL
              AND U.email != ''
              AND U.login_at IS NOT NULL
              AND U.login_at >= :CUTOFF";

    if ( !$include_opted_out ) {
        $sql .= " AND (U.subscribe IS NULL OR U.subscribe != -1)
                  AND (P.subscribe IS NULL OR P.subscribe != -1)";
    }

    if ( $premium_only ) {
        $sql .= " AND COALESCE(P.premium, 0) > 0";
    }

    if ( $exclude_recent_bulk_days > 0 ) {
        $bulk_cutoff = date('Y-m-d H:i:s', strtotime("-$exclude_recent_bulk_days days"));
        $params[':BULK_CUTOFF'] = $bulk_cutoff;
        // Per-context: successful bulk sends only (json status=sent).
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM {$CFG->dbprefix}mail_sent AS S
            WHERE S.context_id = :CID
              AND S.user_to = U.user_id
              AND S.bulk_id IS NOT NULL
              AND S.created_at >= :BULK_CUTOFF
              AND S.json LIKE '%\"status\":\"sent\"%'
        )";
    }

    return array($sql, $params);
}

/**
 * Count distinct users matching audience filters (no LIMIT).
 *
 * @return int|false
 */
function mail_context_audience_count($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0) {
    global $PDOX;

    $built = mail_context_audience_from_where($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
    if ( $built === false ) {
        return false;
    }
    list($from_where, $params) = $built;
    $sql = "SELECT COUNT(DISTINCT U.user_id) AS c ".$from_where;
    $row = $PDOX->rowDie($sql, $params);
    if ( $row === false || $row === null ) {
        return 0;
    }
    return (int) $row['c'];
}

/**
 * Audience size breakdown for bulk preview / CLI dry-run.
 *
 * @return array{
 *   matched_login: int,
 *   excluded_recent_bulk: int,
 *   eligible_no_limit: int,
 *   limit: int,
 *   will_send: int,
 *   exclude_recent_bulk_days: int
 * }|false
 */
function mail_context_audience_stats($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
    $limit = (int) $limit;
    $exclude_recent_bulk_days = (int) $exclude_recent_bulk_days;
    if ( $limit < 0 ) {
        return false;
    }

    $matched_login = mail_context_audience_count($context_id, $days, $include_opted_out, $premium_only, 0);
    if ( $matched_login === false ) {
        return false;
    }

    if ( $exclude_recent_bulk_days > 0 ) {
        $eligible_no_limit = mail_context_audience_count($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
        if ( $eligible_no_limit === false ) {
            return false;
        }
        $excluded_recent_bulk = max(0, $matched_login - $eligible_no_limit);
    } else {
        $eligible_no_limit = $matched_login;
        $excluded_recent_bulk = 0;
    }

    $will_send = $eligible_no_limit;
    if ( $limit > 0 && $will_send > $limit ) {
        $will_send = $limit;
    }

    return array(
        'matched_login' => $matched_login,
        'excluded_recent_bulk' => $excluded_recent_bulk,
        'eligible_no_limit' => $eligible_no_limit,
        'limit' => $limit,
        'will_send' => $will_send,
        'exclude_recent_bulk_days' => $exclude_recent_bulk_days,
    );
}

/**
 * @return array List of audience rows
 */
function mail_context_audience($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
    global $PDOX;

    $built = mail_context_audience_sql($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days, $limit);
    if ( $built === false ) {
        return array();
    }
    return $PDOX->allRowsDie($built[0], $built[1]);
}

/**
 * Single-recipient audience: one context member matching email (case-insensitive).
 *
 * @return array Zero or one audience row (same shape as mail_context_audience)
 */
function mail_context_audience_by_email($context_id, $email) {
    global $CFG, $PDOX;

    $context_id = (int) $context_id;
    $email = strtolower(trim((string) $email));
    if ( $context_id < 1 || $email === '' || strpos($email, '@') === false ) {
        return array();
    }

    $sql = "SELECT U.email, U.displayname, U.login_at, U.user_id, COALESCE(P.premium, 0) AS premium
            FROM {$CFG->dbprefix}lti_membership AS M
            JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
            LEFT JOIN {$CFG->dbprefix}profile AS P ON U.profile_id = P.profile_id
            WHERE M.context_id = :CID
              AND U.email IS NOT NULL
              AND U.email != ''
              AND LOWER(U.email) = :E
            ORDER BY U.user_id DESC
            LIMIT 1";
    $row = $PDOX->rowDie($sql, array(':CID' => $context_id, ':E' => $email));
    if ( $row === false || $row === null ) {
        return array();
    }
    return array($row);
}
