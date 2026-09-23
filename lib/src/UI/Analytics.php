<?php

namespace Tsugi\UI;

/**
 * Old class name for LTI tools outside this repo.
 *
 * Those tools call button(), graphBody(), and graphScript(). The chart
 * markup lives on the analytics controller. The view model lives on
 * AnalyticsService. This subclass keeps both available under the old name.
 */
class Analytics extends \Tsugi\Services\Analytics\AnalyticsService
{
    public static function button($right = false)
    {
        \Tsugi\Controllers\Analytics::button($right);
    }

    public static function graphBody()
    {
        return \Tsugi\Controllers\Analytics::graphBody();
    }

    public static function graphScript($json)
    {
        return \Tsugi\Controllers\Analytics::graphScript($json);
    }
}
