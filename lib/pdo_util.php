<?php

require_once "pdox.class.php";

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoRowDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->RowDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoAllRowsDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->AllRowsDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoQueryDie($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->QueryDie($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoQuery($pdo, $sql, $arr=FALSE, $error_log=TRUE) {
    global $PDOX;
    return $PDOX->Query($sql, $arr, $error_log);
}

/**
 * @deprecated deprecated since refactor to classes
 */
function pdoMetadata($pdo, $tablename) {
    global $PDOX;
    return $PDOX->Metadata($tablename);
}

