<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

$PDOX = LTIX::getConnection();

header('Content-Type: application/json; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    http_response_code(401);
    echo(json_encode(array("error" => "You are not logged in.")));
    exit();
}

$p = $CFG->dbprefix;

$row = $PDOX->rowDie("SELECT profile_id FROM {$p}lti_user WHERE user_id = :UID;",
    array(':UID' => $_SESSION['id'])
);

if ( $row === false || ! isset($row['profile_id']) ) {
    echo(json_encode(array("error" => "No profile_id")));
    exit();
}

$sql = "SELECT P.profile_id, U.user_id, U.email, C.context_id, C.title
    FROM {$p}profile AS P 
    JOIN {$p}lti_user AS U ON P.profile_id = U.profile_id
    JOIN {$p}lti_membership AS M ON U.user_id = M.user_id
    JOIN {$p}lti_context AS C ON M.context_id = C.context_id
    WHERE P.profile_id = :PID";

$rows = $PDOX->allRowsDie($sql, array(':PID' => $row['profile_id']));

echo(json_encode($rows));
