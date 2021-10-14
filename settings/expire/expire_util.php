<?php

// To avoid wiping out all the data
function sanity_check_days($base=false, $days=false) {
    global $tenant_days, $context_days, $user_days, $pii_days;

    $min_pii_days = 20;
    $min_user_days = 40;
    $min_context_days = 60;
    $min_tenant_days = 80;
    $retval = '';

    if ( $base == 'PII' ) $pii_days = $days;
    if ( $base == 'user' ) $user_days = $days;
    if ( $base == 'context' ) $context_days = $days;
    if ( $base == 'tenant' ) $tenant_days = $days;

    if ( isset($tenant_days) && $tenant_days < $min_tenant_days ) {
        $retval .= "Tenant days cannot be less than $min_tenant_days";
        $tenant_days = $min_tenant_days;
    }

    if ( isset($context_days) && $context_days < $min_context_days ) {
        if ( strlen($retval) > 0 ) $retval .=', ';
        $retval .=  "Context days cannot be less than $min_context_days";
        $context_days = $min_context_days;
    }

    if ( isset($user_days) && $user_days < $min_user_days ) {
        if ( strlen($retval) > 0 ) $retval .=', ';
        $retval .=  "User days cannot be less than $min_user_days";
        $user_days = $min_user_days;
    }

    if ( isset($pii_days) && $pii_days < $min_pii_days ) {
        if ( strlen($retval) > 0 ) $retval .=', ';
        $retval .=  "PII days cannot be less than $min_pii_days";
        $pii_days = $min_pii_days;
    }

    if ( strlen($retval) > 0 ) return $retval;
    return true;
}

function expire_sanity_check() {
    if ( ! isset($_SESSION['id']) ) die('Must be logged in');
    if ( $_SESSION['id'] == 0 ) die('Cannot be super user');
}

function get_count_table($table) {
    global $PDOX, $CFG;
    expire_sanity_check();
    $row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} WHERE ".get_owner_clause());
    $count = $row ? $row['count'] : 0;
    return $count;
}

function get_expirable_records($table, $days) {
    global $PDOX, $CFG;
    expire_sanity_check();
    $row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} ".get_expirable_where($days));
    $count = $row ? $row['count'] : 0;
    return $count;
}

function get_safe_key_where() {
    return "(key_key <> 'google.com' AND key_key <> '12345')";
}

function get_expirable_where($days) {
    $sql = "WHERE created_at <= (CURRENT_DATE() - INTERVAL $days DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL $days DAY))
        AND ( " .get_owner_clause() . ")";
    return $sql;
}

function get_pii_count($days) {
    global $PDOX, $CFG;
    expire_sanity_check();
    $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user ".get_pii_where($days);
    $row = $PDOX->rowDie($sql);
    $count = $row ? $row['count'] : 0;
    return $count;
}

function get_owner_clause() {
    global $PDOX, $CFG;
    expire_sanity_check();
    $clause = " key_id IN (SELECT key_id from {$CFG->dbprefix}lti_key WHERE user_id = ".$_SESSION['id'].") ";
    return $clause;
}

function get_pii_where($days) {
    global $PDOX, $CFG;
    return "
        WHERE created_at <= (CURRENT_DATE() - INTERVAL $days DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL $days DAY))
        AND (displayname IS NOT NULL OR email IS NOT NULL)
        AND ( " .get_owner_clause() . ")";
}

