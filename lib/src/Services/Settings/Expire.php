<?php

namespace Tsugi\Services\Settings;

use Tsugi\Util\U;

/**
 * PII / record-expiry helpers for the Settings controller.
 *
 * Ported from the former settings/expire/expire_util.php.
 */
class Expire {

    public static $tenant_days;
    public static $context_days;
    public static $user_days;
    public static $pii_days;

    /**
     * Clamp expiry-day values to minima. Returns an error string or true.
     *
     * @param string|false $base
     * @param int|false $days
     * @return string|true
     */
    public static function sanityCheckDays($base=false, $days=false) {
        $min_pii_days = 20;
        $min_user_days = 40;
        $min_context_days = 60;
        $min_tenant_days = 80;
        $retval = '';

        if ( $base == 'PII' ) self::$pii_days = $days;
        if ( $base == 'user' ) self::$user_days = $days;
        if ( $base == 'context' ) self::$context_days = $days;
        if ( $base == 'tenant' ) self::$tenant_days = $days;

        if ( isset(self::$tenant_days) && self::$tenant_days < $min_tenant_days ) {
            $retval .= "Tenant days cannot be less than $min_tenant_days";
            self::$tenant_days = $min_tenant_days;
        }

        if ( isset(self::$context_days) && self::$context_days < $min_context_days ) {
            if ( strlen($retval) > 0 ) $retval .=', ';
            $retval .=  "Context days cannot be less than $min_context_days";
            self::$context_days = $min_context_days;
        }

        if ( isset(self::$user_days) && self::$user_days < $min_user_days ) {
            if ( strlen($retval) > 0 ) $retval .=', ';
            $retval .=  "User days cannot be less than $min_user_days";
            self::$user_days = $min_user_days;
        }

        if ( isset(self::$pii_days) && self::$pii_days < $min_pii_days ) {
            if ( strlen($retval) > 0 ) $retval .=', ';
            $retval .=  "PII days cannot be less than $min_pii_days";
            self::$pii_days = $min_pii_days;
        }

        if ( strlen($retval) > 0 ) return $retval;
        return true;
    }

    public static function sanityCheck() {
        if ( ! U::isLoggedIn() ) {
            die('Must be logged in');
        }
        if ( U::loggedInUserId() == 0 ) {
            die('Cannot be super user');
        }
    }

    /**
     * @return array{sql:string,params:array}
     */
    public static function ownerClause() {
        global $CFG;
        self::sanityCheck();
        return array(
            'sql' => " key_id IN (SELECT key_id from {$CFG->dbprefix}lti_key WHERE user_id = :UID) ",
            'params' => array(':UID' => U::loggedInUserId())
        );
    }

    /**
     * @return array{sql:string,params:array}
     */
    public static function expirableWhere($days) {
        if ( !is_numeric($days) || $days < 0 ) {
            die('Invalid days parameter');
        }
        $owner = self::ownerClause();
        return array(
            'sql' => "WHERE created_at <= (CURRENT_DATE() - INTERVAL :DAYS DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL :DAYS2 DAY))
        AND ( " . $owner['sql'] . ")",
            'params' => array_merge(
                array(':DAYS' => (int)$days, ':DAYS2' => (int)$days),
                $owner['params']
            )
        );
    }

    /**
     * @return array{sql:string,params:array}
     */
    public static function piiWhere($days) {
        if ( !is_numeric($days) || $days < 0 ) {
            die('Invalid days parameter');
        }
        $owner = self::ownerClause();
        return array(
            'sql' => "
        WHERE created_at <= (CURRENT_DATE() - INTERVAL :DAYS DAY)
        AND (login_at IS NULL OR login_at <= (CURRENT_DATE() - INTERVAL :DAYS2 DAY))
        AND (displayname IS NOT NULL OR email IS NOT NULL)
        AND ( " . $owner['sql'] . ")",
            'params' => array_merge(
                array(':DAYS' => (int)$days, ':DAYS2' => (int)$days),
                $owner['params']
            )
        );
    }

    public static function countTable($table) {
        global $PDOX, $CFG;
        self::sanityCheck();
        $owner = self::ownerClause();
        $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} WHERE " . $owner['sql'];
        $row = $PDOX->rowDie($sql, $owner['params']);
        $count = $row ? $row['count'] : 0;
        return $count;
    }

    public static function expirableRecords($table, $days) {
        global $PDOX, $CFG;
        self::sanityCheck();
        $where = self::expirableWhere($days);
        $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} " . $where['sql'];
        $row = $PDOX->rowDie($sql, $where['params']);
        $count = $row ? $row['count'] : 0;
        return $count;
    }

    public static function safeKeyWhere() {
        return "(key_key <> 'google.com' AND key_key <> '12345')";
    }

    public static function piiCount($days) {
        global $PDOX, $CFG;
        self::sanityCheck();
        $where = self::piiWhere($days);
        $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user " . $where['sql'];
        $row = $PDOX->rowDie($sql, $where['params']);
        $count = $row ? $row['count'] : 0;
        return $count;
    }
}
