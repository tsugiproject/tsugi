<?php

require_once "pdox.class.php";

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoRowDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->rowDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoAllRowsDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->allRowsDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoQueryDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->queryDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoQuery($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->queryReturnError($sql, $arr, $error_log);
}

