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
     * $json->data[0]->actor->id = $user;
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
 "dataVersion": "http://purl.imsglobal.org/ctx/caliper/v1p1",
 "data": [
   {
     "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
     "type": "SessionEvent",
     "actor": {
       "id": "https://example.edu/user/554433",
       "type": "Person"
     },
     "action": "LoggedIn",
     "eventTime": "2004-01-01T06:00:00.000Z",
     "object": "http://localhost/tsugi/mod/michat"
   }
 ]
}');
        $json->sendTime = self::getISO8601();
        $json->data[0]->id = uniqid();
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
 "dataVersion": "http://purl.imsglobal.org/ctx/caliper/v1p1",
 "type": "Event",
 "actor": "https://example.edu/users/554433",
 "action": "Viewed",
 "object": "https://example.edu/terms/201601/courses/7/sections/1/resources/123",
 "eventTime": "2004-01-01T06:00:00.000Z",
}');
        $json->data[0]->id = uniqid();
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

/*

https://www.imsglobal.org/sites/default/files/caliper/v1p1/caliper-spec-v1p1/caliper-spec-v1p1.html#viewEvent

{
  "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
  "id": "urn:uuid:cd088ca7-c044-405c-bb41-0b2a8506f907",
  "type": "ViewEvent",
  "actor": {
    "id": "https://example.edu/users/554433",
    "type": "Person"
  },
  "action": "Viewed",
  "object": {
    "id": "https://example.edu/etexts/201.epub",
    "type": "Document",
    "name": "IMS Caliper Implementation Guide",
    "dateCreated": "2018-08-01T06:00:00.000Z",
    "datePublished": "2018-10-01T06:00:00.000Z",
    "version": "1.1"
  },
  "eventTime": "2018-11-15T10:15:00.000Z",
  "edApp": "https://example.edu",
  "group": {
    "id": "https://example.edu/terms/201801/courses/7/sections/1",
    "type": "CourseSection",
    "courseNumber": "CPS 435-01",
    "academicSession": "Fall 2018"
  },
  "membership": {
    "id": "https://example.edu/terms/201801/courses/7/sections/1/rosters/1",
    "type": "Membership",
    "member": "https://example.edu/users/554433",
    "organization": "https://example.edu/terms/201801/courses/7/sections/1",
    "roles": [ "Learner" ],
    "status": "Active",
    "dateCreated": "2018-08-01T06:00:00.000Z"
  },
  "session": {
    "id": "https://example.edu/sessions/1f6442a482de72ea6ad134943812bff564a76259",
    "type": "Session",
    "startedAtTime": "2018-11-15T10:00:00.000Z"
  }
*/

