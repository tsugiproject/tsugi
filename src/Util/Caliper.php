<?php

namespace Tsugi\Util;

use \Tsugi\Util\LTI;

/**
 * This is a general purpose LTI class with no Tsugi-specific dependencies.
 *
 * This class provides helper capabilities for Caliper
 * deal with how to use LTI data during the runtime of the tool.
 *
 * This is under construction and is a place where the real Caliper
 * implementation will eventually exist.
 *
 */
class Caliper {

    /**
     * This is just a test method to return properly formatted JSON for the Canvas prototype Caliper
     *
     * This is just a starting point to lay some initial groundwork.  
     * 
     * @param $application string This is the name of the application - typically it is 
     * the base domain name of the application like https://lti-tools.dr-chuck.com/tsugi/
     * @param $page This is the url of page of that was viewed.
     * @param $duration This is the duration of the activity on the page.
     */

    public static function sensorCanvasPageView ($user, $application, $page, $duration='PT5M30S') {

        $caliper = json_decode('{
            "@context" : "http://purl.imsglobal.org/ctx/caliper/v1/ViewEvent",
            "@type" : "http://purl.imsglobal.org/caliper/v1/ViewEvent",
            "action" : "viewed",
            "startedAtTime" : "Time.now.utc.to_i",
            "duration" : "PT5M30S",
            "actor" : {
                    "@id" : "user_id",
                    "@type" : "http://purl.imsglobal.org/caliper/v1/lis/Person"
            },
            "object" : {
                    "@id" : "context_url",
                    "@type" : "http://www.idpf.org/epub/vocab/structure/#volume",
                    "name" : "Test LTI Tool"
            },
            "edApp" : {
                    "@id" :"context_url",
                    "@type" : "http://purl.imsglobal.org/caliper/v1/SoftwareApplication",
                    "name" : "LTI Tool of All Things",
                    "properties" : {},
                    "lastModifiedTime" : "Time.now.utc.to_i"
            }
         }');

        $caliper->startedAtTime = time();
        $caliper->actor->{'@id'} = $user;
        $caliper->object->{'@id'} = $page;
        $caliper->edApp->{'@id'} = $application;
        $caliper->duration = $duration;
        $caliper = json_encode($caliper);
        return $caliper;
    }

}
