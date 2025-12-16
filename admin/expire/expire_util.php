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

function get_count_table($table) {
    global $PDOX, $CFG;
    $row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table}");
    $count = $row ? $row['count'] : 0;
    return $count;
}

/**
 * Get expirable WHERE clause with parameterized query
 * Returns array with 'sql' and 'params' keys
 * 
 * Note: We use both :DAYS and :DAYS2 even though they have the same value.
 * PDO requires unique placeholder names within a single query - you cannot
 * reuse the same placeholder name (:DAYS) multiple times in one SQL statement.
 * Both parameters check the same number of days but are used in different
 * parts of the WHERE clause (created_at vs login_at).
 */
function get_expirable_where($days) {
    if ( !is_numeric($days) || $days < 0 ) {
        die('Invalid days parameter');
    }
    return array(
        'sql' => "WHERE created_at <= (CURRENT_DATE() - INTERVAL :DAYS DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL :DAYS2 DAY))",
        'params' => array(':DAYS' => (int)$days, ':DAYS2' => (int)$days)
    );
}

/**
 * Get PII WHERE clause with parameterized query
 * Returns array with 'sql' and 'params' keys
 * 
 * Note: We use both :DAYS and :DAYS2 even though they have the same value.
 * PDO requires unique placeholder names within a single query - you cannot
 * reuse the same placeholder name (:DAYS) multiple times in one SQL statement.
 * Both parameters check the same number of days but are used in different
 * parts of the WHERE clause (created_at vs login_at).
 */
function get_pii_where($days) {
    if ( !is_numeric($days) || $days < 0 ) {
        die('Invalid days parameter');
    }
    return array(
        'sql' => "
        WHERE created_at <= (CURRENT_DATE() - INTERVAL :DAYS DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL :DAYS2 DAY))
        AND (displayname IS NOT NULL OR email IS NOT NULL)",
        'params' => array(':DAYS' => (int)$days, ':DAYS2' => (int)$days)
    );
}

function get_expirable_records($table, $days) {
    global $PDOX, $CFG;
    $where = get_expirable_where($days);
    $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} " . $where['sql'];
    $row = $PDOX->rowDie($sql, $where['params']);
    $count = $row ? $row['count'] : 0;
    return $count;
}

function get_safe_key_where() {
    return "(key_key <> 'google.com' AND key_key <> '12345')";
}

function get_pii_count($days) {
    global $PDOX, $CFG;
    $where = get_pii_where($days);
    $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user " . $where['sql'];
    $row = $PDOX->rowDie($sql, $where['params']);
    $count = $row ? $row['count'] : 0;
    return $count;
}

