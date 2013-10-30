<?php

require_once("config.php");

try {
    $db = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $ex){
    error_log("DB connection: "+$ex->getMessage());
    die($ex->getMessage());
}

