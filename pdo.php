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
        die($ex->getMessage()); // with error_log
    }
}

require_once($CFG->dirroot."/lib/pdo_util.php");
