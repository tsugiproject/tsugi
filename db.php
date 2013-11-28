<?php

require_once("config.php");

try {
    $db = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex){
    error_log("DB connection: "+$ex->getMessage());
    die($ex->getMessage());
}

// Run a PDO Query with lots of error checking
// TODO: Work in progress
function pdoQuery($db, $sql, $arr=FALSE, $log_error=TRUE) {
    $errormode = $db->getAttribute(PDO::ATTR_ERRMODE);
	if ( $errormode != PDO::ERRMODE_EXCEPTION) {
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
    $q = FALSE;
    $success = FALSE;
    $message = '';
    if ( $arr !== FALSE && ! is_array($arr) ) $arr = Array($arr);
    $start = microtime(true);
    debugLog($sql, $arr);
    try {
        $q = $db->prepare($sql);
        if ( $arr === FALSE ) {
            $success = $q->execute();
        } else {
            $success = $q->execute($arr);
        }
    } catch(Exception $e) {
        $success = FALSE;
        $message = $e->getMessage();
    }
	if ( ! is_object($q) ) $q = stdClass();
    if ( isset( $q->success ) ) die("PDO::Statement should not have success member");
    $q->success = $success;
    if ( isset( $q->ellapsed_time ) ) die("PDO::Statement should not have success member");
    $q->ellapsed_time = $microtime(true)-$start;
	// In case we build this...
    if ( !isset($q->errorCode) ) $q->errorCode = '42000';
    if ( !isset($q->errorInfo) ) $q->errorInfo = Array('42000', '42000', $message);
    // Restore ERRMODE if we changed it
	if ( $errormode != PDO::ERRMODE_EXCEPTION) {
		$db->setAttribute(PDO::ATTR_ERRMODE, $errormode);
	}
    return $q;
}
