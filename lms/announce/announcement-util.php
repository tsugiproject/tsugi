<?php

/**
 * Get announcements for a user in a context
 * 
 * Returns announcements created within the last 30 days, limited to 50.
 * Separates dismissed and undismissed announcements.
 * 
 * @param int $context_id The context ID
 * @param int $user_id The user ID
 * @return array Array with keys: all_announcements, undismissed, dismissed, announcements, dismissed_count, show_dismissed_section
 */
function getAnnouncementsForUser($context_id, $user_id) {
    global $CFG, $PDOX;
    
    // Get all announcements for this context, including dismissal status
    // Only show announcements created within the last 30 days, limit to 50
    $sql = "SELECT A.announcement_id, A.title, A.text, A.url, A.created_at, A.updated_at,
                U.displayname AS creator_name,
                CASE WHEN D.dismissal_id IS NOT NULL THEN 1 ELSE 0 END AS dismissed
            FROM {$CFG->dbprefix}announcement AS A
            LEFT JOIN {$CFG->dbprefix}lti_user AS U ON A.user_id = U.user_id
            LEFT JOIN {$CFG->dbprefix}announcement_dismissal AS D 
                ON A.announcement_id = D.announcement_id AND D.user_id = :UID
            WHERE A.context_id = :CID
              AND A.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY A.created_at DESC
            LIMIT 50";

    $all_announcements = $PDOX->allRowsDie($sql, array(
        ':CID' => $context_id,
        ':UID' => $user_id
    ));

    // Separate dismissed and undismissed announcements
    $undismissed = array();
    $dismissed = array();
    foreach ($all_announcements as $announcement) {
        if ($announcement['dismissed']) {
            $dismissed[] = $announcement;
        } else {
            $undismissed[] = $announcement;
        }
    }

    // If there are undismissed announcements, show only those
    // If no undismissed announcements, show dismissed ones directly
    $announcements = count($undismissed) > 0 ? $undismissed : $dismissed;
    $dismissed_count = count($dismissed);
    $show_dismissed_section = count($undismissed) > 0 && $dismissed_count > 0;

    return array(
        'all_announcements' => $all_announcements,
        'undismissed' => $undismissed,
        'dismissed' => $dismissed,
        'announcements' => $announcements,
        'dismissed_count' => $dismissed_count,
        'show_dismissed_section' => $show_dismissed_section
    );
}
