<?php

namespace Tsugi\Laravel;

use Illuminate\Http\Request;

/**
 * This is a class to enable building Laravel Apps using Tsugi
 */
class LTIX extends \Tsugi\Core\LTIX {

    public static function laravelSetup(Request $request, $needed=LTIX::ALL) {
         $launch = self::requireDataOverride(LTIX::ALL,
            null, /* pdox - default */
            $request->session(),
            null, /* current_url - default */
            null /* request_data - default */
        );
        return $launch;
    }

}
