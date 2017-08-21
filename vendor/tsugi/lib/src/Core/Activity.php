<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use Tsugi\Util\Caliper;

/** Activity utilities */

class Activity {

    /**
     * Send the backlog of caliper events
     */
    public static function pushCaliperEvents($seconds=3, $max=100, $debug=false) {
        $start = time();
        $now = $start;
        $end = $start + $seconds;
        $count = 0;
        $failure = 0;
        $failure_code = false;
        $retval = array();
        while ($count < $max && $now < $end ) {
            $result = self::sendCaliperEvent(!$debug);
            if ( $debug ) {
                echo(U::safe_var_dump($result));
            }
            if ( $result === false ) break;

            if ( $result['code'] != 200 ) {
                $failure++;
                if ( $failure_code === false ) $failure_code = $result['code'];
            }
            $count++;
            $now = time();
            $delta = $now - $start;
        }

        $now = time();
        $delta = $now - $start;
        $retval['count'] = $count;
        $retval['fail'] = $failure;
        if ( $failure_code !== false ) $retval['failcode'] = $failure_code;
        $retval['seconds'] = $delta;
        return $retval;
    }

    public static function sendCaliperEvent($delete=true) {
        global $CFG;

        $PDOX = LTIX::getConnection();

        // Get an ID from a possibly hot row.
        // In a future version of MySQL, we can add "FOR UPDATE ON e NOWAIT"
        $sql = "SELECT event_id
            FROM {$CFG->dbprefix}lti_event AS e
            LEFT JOIN {$CFG->dbprefix}lti_key AS k ON k.key_id = e.key_id
            WHERE k.caliper_url IS NOT NULL and k.caliper_key IS NOT NULL AND e.state IS NULL
            ORDER BY e.created_at ASC LIMIT 1 FOR UPDATE";

        // This is a transaction. Tread carefully...
        $PDOX->beginTransaction();

        $q = $PDOX->queryReturnError($sql);
        if ( ! $q->success ) {
            $PDOX->rollBack();
            error_log("Rollback 1: ".$q->errorImplode);
            return false;
        }
        $row = $q->fetch(\PDO::FETCH_ASSOC);

	// There was nothing to retrieve - we are good
        if ( $row === false ) {
            $PDOX->rollBack();
            return false;
        }

        // State 0 = "in progress"
        $sql = "UPDATE {$CFG->dbprefix}lti_event
            SET state=0,updated_at=NOW()
            WHERE event_id = :event_id";
        $q = $PDOX->queryReturnError($sql, array(':event_id' => $row['event_id']));

        // We made it through the rain...
        $PDOX->commit();

        // Now grab our single event and all its data for processing...
        $event_id = $row['event_id'];
        $sql = "SELECT event_id, e.launch AS launch, e.created_at AS created_at, k.caliper_url, k.caliper_key,
               u.displayname AS displayname, u.email AS email, user_key AS user_key,
               l.title AS link_title, l.path AS path, key_key, k.secret AS secret
            FROM {$CFG->dbprefix}lti_event AS e
            LEFT JOIN {$CFG->dbprefix}lti_key AS k ON k.key_id = e.key_id
            LEFT JOIN {$CFG->dbprefix}lti_user AS u ON u.user_id = e.user_id
            LEFT JOIN {$CFG->dbprefix}lti_context AS c ON c.context_id = e.context_id
            LEFT JOIN {$CFG->dbprefix}lti_link AS l ON l.link_id = e.link_id
            LEFT JOIN {$CFG->dbprefix}lti_membership AS m ON m.user_id = e.user_id AND m.context_id = e.context_id
            WHERE e.event_id = :event_id AND k.caliper_url IS NOT NULL and k.caliper_key IS NOT NULL AND e.state = 0
            ORDER BY e.created_at ASC LIMIT 1";
        $row = $PDOX->rowDie($sql,array(':event_id' => $event_id) );

        print_r($row);

        $launch = $row['launch'];
        $email = $row['email'];
        $user_key = $row['user_key'];
        $name = $row['link_title'];
        $displayname = $row['displayname'];
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

        $json = Caliper::smallCaliper();

        $json->sendTime = $iso8601;
        $json->data[0]->actor->{'@id'} = $user;
        $json->data[0]->eventTime = $iso8601;
        $json->data[0]->object->{'@id'} = $path;
        if ( $displayname ) {
            $json->data[0]->name = $displayname;
        }
        if ( $email ) {
            $json->data[0]->extensions = new \stdClass();
            $json->data[0]->extensions->email = $email;
        }

        $method = "POST";
        $body = json_encode($json, JSON_PRETTY_PRINT);

        $header = "Content-type: application/json;\n" .
            "Authorization: ".$caliper_key;
        $url = $caliper_url;

        $response = Net::bodyCurl($url, $method, $body, $header);

        $retval = Net::getLastBODYDebug();
        $retval['deleted'] = 'no';
        $retval['body_url'] = $url;
        $retval['body_sent'] = $body;
        $retval['body_received'] = $response;

        $response_code = Net::getLastHttpResponse();
        if ( $response_code != 200 ) {
            error_log("Caliper error code=".$response_code." url=".$url);
            $sql = "UPDATE {$CFG->dbprefix}lti_event
                SET state=1, json=:json, updated_at=NOW()
                WHERE event_id = :event_id";
            $PDOX->queryDie($sql, array(
                ':json' => LTI::jsonIndent(json_encode($retval)),
                ':event_id' => $row['event_id'])
            );

        }

        if ( $delete ) {
            $sql = "DELETE FROM {$CFG->dbprefix}lti_event WHERE event_id = :event_id";
            $PDOX->queryDie($sql, array(':event_id' => $row['event_id']));
            $retval['deleted'] = 'yes';
        }

        // error_log("Sent event_id=".$row['event_id']." response=".$response_code);
        unset($retval['headers_sent']); // Contains API key
        return $retval;
    }
}
