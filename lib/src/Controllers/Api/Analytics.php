<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Core\Rest;
use Tsugi\Services\Analytics\AnalyticsService;

/**
 * LTI/cookieless analytics for the current launch link.
 *
 * GET /api/analytics and /api/analytics.php
 */
class Analytics {

    public function handle(): void
    {
        if ( Rest::preFlight() ) return;

        header('Content-Type: application/json; charset=utf-8');

        // LTI/cookieless endpoint: requires an LTI launch and uses the current $LINK.
        $LAUNCH = LTIX::requireData();
        global $LINK;
        $link_id = $LINK->id + 0;
        $retval = AnalyticsService::viewModelForLink($link_id);

        echo(json_encode($retval,JSON_PRETTY_PRINT));
    }

}
