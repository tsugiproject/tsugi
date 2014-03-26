<?php

if ( defined('PDO_WILL_CATCH') ) {
    $pdo = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} else {
    try {
        $pdo = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $ex){
        error_log("DB connection: "+$ex->getMessage());
        die($ex->getMessage());
    }
}

function pdoRowDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    $stmt = pdoQueryDie($pdo, $sql, $arr, $error_log);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row;
}

function pdoAllRowsDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    $stmt = pdoQueryDie($pdo, $sql, $arr, $error_log);
    $rows = array();
    while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
        array_push($rows, $row);
    }
    return $rows;
}

function pdoQueryDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    $stmt = pdoQuery($pdo, $sql, $arr, $error_log);
    if ( ! $stmt->success ) die($stmt->errorImplode);
    return $stmt;
}

// Run a PDO Query with lots of error checking
function pdoQuery($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    $errormode = $pdo->getAttribute(PDO::ATTR_ERRMODE);
	if ( $errormode != PDO::ERRMODE_EXCEPTION) {
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
    $q = FALSE;
    $success = FALSE;
    $message = '';
    if ( $arr !== FALSE && ! is_array($arr) ) $arr = Array($arr);
    $start = microtime(true);
    // debugLog($sql, $arr);
    try {
        $q = $pdo->prepare($sql);
        if ( $arr === FALSE ) {
            $success = $q->execute();
        } else {
            $success = $q->execute($arr);
        }
    } catch(Exception $e) {
        $success = FALSE;
        $message = $e->getMessage();
        if ( $error_log ) error_log($message);
    }
	if ( ! is_object($q) ) $q = stdClass();
    if ( isset( $q->success ) ) die("PDO::Statement should not have success member");
    $q->success = $success;
    if ( isset( $q->ellapsed_time ) ) die("PDO::Statement should not have ellapsed_time member");
    $q->ellapsed_time = microtime(true)-$start;
	// In case we build this...
    if ( !isset($q->errorCode) ) $q->errorCode = '42000';
    if ( !isset($q->errorInfo) ) $q->errorInfo = Array('42000', '42000', $message);
    if ( !isset($q->errorImplode) ) $q->errorImplode = implode(':',$q->errorInfo);
    // Restore ERRMODE if we changed it
	if ( $errormode != PDO::ERRMODE_EXCEPTION) {
		$pdo->setAttribute(PDO::ATTR_ERRMODE, $errormode);
	}
    return $q;
}

function pdoMetadata($pdo, $tablename) {
    $sql = "SHOW COLUMNS FROM ".$tablename;
    $q = pdoQuery($pdo, $sql);
    if ( $q->success ) {
        return $q->fetchAll();
    } else {
        return false;
    }
}

