<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\Net;

\Tsugi\Core\LTIX::getConnection();

$oauth_consumer_key = isset($_GET['oauth_consumer_key']) ?
    $_GET['oauth_consumer_key'] : die('Missing oauth_consumer_key');
$commit = isset($_GET['commit']) ?
    $_GET['commit'] : die('Missing commit');
$key_sha256 = lti_sha256($oauth_consumer_key);

error_log("Committing Re-Registration key=".$oauth_consumer_key." commit=".$commit);

$method = $_SERVER['REQUEST_METHOD'];
if ( $method == "PUT" || $method == "DELETE" ) {
    // All good
} else {
    if (function_exists('http_response_code')) {
        http_response_code(400);
    }
    error_log("Method not allowed in commit: $method");
    die("Transaction not found");
}

$row = $PDOX->rowDie(
    "SELECT secret
        FROM {$CFG->dbprefix}lti_key
        WHERE ack = :ACK AND key_sha256 = :SHA LIMIT 1",
    array(":SHA" => $key_sha256, ":ACK" => $commit)
);

if ( $row == false ) {
    if (function_exists('http_response_code')) {
        http_response_code(404);
    }
    error_log("Transaction $commit not found $oauth_consumer_key");
    die("Transaction not found");
}

error_log('LTIX::curPageUrl()='.LTIX::curPageUrl());
$retval = LTI::verifyKeyAndSecret($oauth_consumer_key, $row['secret'],LTIX::curPageUrl());
if ( $retval !== true ) {
    if (function_exists('http_response_code')) {
        http_response_code(404);
    }
    error_log("LTI Failure:".$retval[0]."\n".$retval[1]);
    die("LTI Failure:".$retval[0]."\n".$retval[1]);
}

if ( $method == "PUT" ) {
    $stmt = $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}lti_key
            SET secret = new_secret,
	        new_secret = NULL, ack = NULL
            WHERE new_secret IS NOT NULL AND 
            ack = :ACK AND key_sha256 = :SHA",
        array(":SHA" => $key_sha256, ":ACK" => $commit)
    );
    $count = $stmt->rowCount();
    error_log("Committed Key=$oauth_consumer_key rows updated=$count");
} else {

    $stmt = $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}lti_key
            SET new_secret = NULL
            WHERE ack = :ACK AND key_sha256 = :SHA",
        array(":SHA" => $key_sha256, ":ACK" => $commit)
    );
    $count = $stmt->rowCount();
    error_log("Roll-Back Key=$oauth_consumer_key rows updated=$count");
}
