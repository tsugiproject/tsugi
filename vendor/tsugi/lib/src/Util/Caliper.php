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

    /** Get Caliper-style ISO8601 Datetime from unix timestamp
     */
    public static function getISO8601($timestamp=false) {
        if ( $timestamp === false ) {
            $dt = new \DateTime();
        } else {
            $format = 'Y-m-d H:i:s';
            $dt = \DateTime::createFromFormat($format, $timestamp);
        }

        // 2017-08-16T16:26:31-1000
        $iso8601 = $dt->format(\DateTime::ISO8601);

        // 2017-08-20T10:34:05.000Z
        $iso8601 = str_replace('-1000','.000Z',$iso8601);

        return $iso8601;
    }

    /**
     * Required:
     * $json->data[0]->actor->{'@id'} = $user;
     * $json->data[0]->object->{'@id'} = $path;
     * $json->eventTime = Caliper::getISO8601($timestamp);
     *
     * Optional:
     * $json->data[0]->name = $name;
     * $json->data[0]->extensions = new \stdClass();
     * $json->data[0]->extensions->email = $email;
     */
    public static function smallCaliper() {
        $json = json_decode('{
 "sensor": "https://example.edu/sensor/001",
 "sendTime": "2004-01-01T06:00:00.000Z",
 "data": [
   {
     "@context": "http://purl.imsglobal.org/ctx/caliper/v1/Context",
     "@type": "http://purl.imsglobal.org/caliper/v1/Event",
     "actor": {
       "@id": "https://example.edu/user/554433",
       "@type": "http://purl.imsglobal.org/caliper/v1/lis/Person" 
     },
     "action": "http://purl.imsglobal.org/vocab/caliper/v1/action#Viewed",
     "eventTime": "2004-01-01T06:00:00.000Z",
     "object": {
       "@id": "https://example.com/viewer/book/34843#epubcfi(/4/3)",
       "@type": "http://www.idpf.org/epub/vocab/structure/#volume" 
    }
   }
 ]
}');
        $json->sendTime = self::getISO8601();
        return $json;
    }

    /**
     * Minimal Caliper Event
     *
     * $json = Caliper::miniCaliper();
     * $json->actor = $key_key . '::' . $user_id;
     * $json->object = $path;
     * $json->eventTime = Caliper::getISO8601($timestamp);
     */
    public static function miniCaliper () {
        $json = json_decode('{
 "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
 "id": "urn:uuid:3a648e68-f00d-4c08-aa59-8738e1884f2c",
 "type": "Event",
 "actor": "https://example.edu/users/554433",
 "action": "Viewed",
 "object": "https://example.edu/terms/201601/courses/7/sections/1/resources/123",
 "eventTime": "2004-01-01T06:00:00.000Z",
}');
        return $json;
    }
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
    public static function sensorCanvasPageView ($user, $application, $page, 
            $timestamp=false, $name, $duration='PT5M30S') {

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

        if ( ! $timestamp ) $timestamp = time();
        $caliper->startedAtTime = $timestamp;
        $caliper->actor->{'@id'} = $user;
        $caliper->object->{'@id'} = $page;
        $caliper->edApp->{'@id'} = $application;
        $caliper->edApp->name = $name;
        $caliper->duration = $duration;
        $caliper = json_encode($caliper, JSON_PRETTY_PRINT);
        return $caliper;
    }

}
