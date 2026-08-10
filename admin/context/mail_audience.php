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
    global $CFG;

    $context_id = (int) $context_id;
    $days = (int) $days;
    $exclude_recent_bulk_days = (int) $exclude_recent_bulk_days;
    $limit = (int) $limit;
    if ( $context_id < 1 || $days < 1 || $days > 365 ) {
        return false;
    }
    if ( $exclude_recent_bulk_days < 0 || $exclude_recent_bulk_days > 365 ) {
        return false;
    }
    if ( $limit < 0 || $limit > 200 ) {
        return false;
    }

    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
    $params = array(':CID' => $context_id, ':CUTOFF' => $cutoff_date);

    $sql = "SELECT DISTINCT U.email, U.displayname, U.login_at, U.user_id, COALESCE(P.premium, 0) AS premium
            FROM {$CFG->dbprefix}lti_membership AS M
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

    if ( $limit > 0 ) {
        $sql .= " ORDER BY U.login_at DESC LIMIT ".$limit;
    } else {
        $sql .= " ORDER BY SUBSTRING_INDEX(U.email, '@', -1), U.email";
    }

    return array($sql, $params);
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
