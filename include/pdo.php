<?php

global $PDOX;
$PDOX = false;

if ( defined('PDO_WILL_CATCH') ) {
    $PDOX = new \Tsugi\Util\PDOX($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $PDOX->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} else {
    try {
        $PDOX = new \Tsugi\Util\PDOX($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
        $PDOX->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(\PDOException $ex){
        error_log("DB connection: "+$ex->getMessage());
        die($ex->getMessage()); // with error_log
    }
}

