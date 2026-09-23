<?php

namespace Tsugi\Services\Analytics;

use \Tsugi\Event\Entry;

/**
 * Launch-activity view model for one resource link.
 */
class AnalyticsService {

    /**
     * Load lti_link_activity for a link and return the chart view model.
     *
     * @param int|string $link_id
     * @return object
     */
    public static function viewModelForLink($link_id)
    {
        global $CFG, $PDOX;
        $link_id = $link_id + 0;
        $sql = "SELECT link_count, activity FROM {$CFG->dbprefix}lti_link_activity
            WHERE link_id = :link_id AND event = 0";
        $row = $PDOX->rowDie($sql, array(':link_id' => $link_id));
        $ent = new Entry();
        if ( is_array($row) ) {
            $ent->deSerialize($row['activity']);
            $ent->total = $row['link_count']+0;
        }
        return $ent->viewModel();
    }

}
