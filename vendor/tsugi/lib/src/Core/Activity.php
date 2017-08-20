<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use Tsugi\Util\Caliper;

/** Activity utilities */

class Activity {

    public static function sendCaliperEvent($delete=true) {
        global $CFG;
        $PDOX = LTIX::getConnection();

        $sql = "SELECT event_id, e.launch AS launch, e.created_at AS created_at, k.caliper_url, k.caliper_key,
               u.email AS email, user_key AS user_key,
               l.title AS link_title, l.path AS path, key_key, k.secret AS secret
            FROM {$CFG->dbprefix}lti_event AS e
            LEFT JOIN {$CFG->dbprefix}lti_key AS k ON k.key_id = e.key_id
            LEFT JOIN {$CFG->dbprefix}lti_user AS u ON u.user_id = e.user_id
            LEFT JOIN {$CFG->dbprefix}lti_context AS c ON c.context_id = e.context_id
            LEFT JOIN {$CFG->dbprefix}lti_link AS l ON l.link_id = e.link_id
            LEFT JOIN {$CFG->dbprefix}lti_membership AS m ON m.user_id = e.user_id AND m.context_id = e.context_id
            WHERE k.caliper_url IS NOT NULL and k.caliper_key IS NOT NULL
            ORDER BY e.created_at ASC LIMIT 1";
        $row = $PDOX->rowDie($sql);

        if ( $row === false ) {
            echo("Nothing to process\n");
            return false;
        }
        print_r($row);
        $launch = $row['launch'];
        $email = $row['email'];
        $user_key = $row['user_key'];
        $name = $row['link_title'];
        $application = $CFG->apphome;
        $path = $row['path'];
        $page = $row['path'];
        $caliper_url = $row['caliper_url'];
        $caliper_key = $row['caliper_key'];
        $key_key = $row['key_key'];

        if ( strpos($page, $CFG->apphome) === 0 ) {
            $page = substr($page, strlen($CFG->apphome) );
        }

        $iso8601 = Caliper::getISO8601($row['created_at']);
        $user = $row['key_key'].'::'.$row['user_key'];

/*
        echo("application=$application page=$page\n");
        echo("iso8601=".$iso8601."\n");
        echo("caliper_key=".$caliper_key."\n");
        echo("caliper_url=".$caliper_url."\n");
        echo("key_key=".$key_key."\n");
        echo("user=".$user."\n");
*/
        $json = Caliper::smallCaliper();

        $json->sendTime = $iso8601;
        $json->data[0]->actor->{'@id'} = $user;
        $json->data[0]->eventTime = $iso8601;
        $json->data[0]->object->{'@id'} = $path;

        $method = "POST";
        $body = json_encode($json, JSON_PRETTY_PRINT);
        echo($body);
        echo("\n");

        $header = "Content-type: application/json;
        Authorization: ".$caliper_key;
        $url = $caliper_url;

        $response = Net::bodyCurl($url, $method, $body, $header);

        $response_code = Net::getLastHttpResponse();

        echo("<pre>\n");
        global $LastOAuthBodyBaseString;
        echo("Registration Request Headers\n");
        echo(htmlentities(Net::getBodySentDebug()));
        echo("\nHttp Response code = $response_code\n");
        echo("Registration Response Headers\n");
        echo(htmlentities(Net::getBodyReceivedDebug()));
        echo("\nRegistration Response\n");
        echo(htmlent_utf8(LTI::jsonIndent($response)));
        echo("\n");

        if ( $delete ) {
            $sql = "DELETE FROM {$CFG->dbprefix}lti_event WHERE event_id = :event_id";
            $PDOX->queryDie($sql, array(':event_id' => $row['event_id']));
        }

        error_log("Sent event_id=".$row['event_id']." response=".$response_code);
        return true;
    }
}
