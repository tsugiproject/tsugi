<?php

namespace Tsugi\Laravel;

use Illuminate\Http\Request;

/**
 * This is a class to enable building Laravel Apps using Tsugi
 */
class LTIX extends \Tsugi\Core\LTIX {

    public function laravelSetup(Request $request) {
         $launch = self::LTIX::requireDataOverride(LTIX::ALL,
            null, /* pdox - default */
            $request->session(),
            null, /* current_url - default */
            null /* request_data - default */
        );
        return $launch;
    }

}
