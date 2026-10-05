<?php

namespace Tsugi\Services\Admin;

use Tsugi\Util\Net;
use Tsugi\Util\U;

/**
 * Helpers that used to be required from admin/.
 *
 * Callers use these methods. The passphrase session is still
 * $_SESSION['admin'] === 'yes'.
 */
class AdminService {

        // admin/admin_util.php
    // TODO: deal with headers sent...
    public static function requireLogin() {
        global $CFG, $OUTPUT;
        if ( $CFG->google_glient_id && ! isset($_SESSION['user_id']) ) {
            U::flashError('Login required');
            $OUTPUT->doRedirect(\Tsugi\Controllers\Login::loginUrl()) ;
            exit();
        }
    }

    public static function isAdmin() {
        return isset( $_SESSION['admin']) && $_SESSION['admin'] == 'yes';
    }

    /**
     * Whether the current session email may attempt admin unlock.
     * Unset / empty $CFG->adminemails keeps legacy "any email" behavior.
     */
    public static function adminEmailAllowed() {
        global $CFG;
        $allowed = $CFG->adminemails ?? false;
        if ( is_string($allowed) && U::strlen(trim($allowed)) > 0 ) {
            $allowed = array($allowed);
        }
        if ( ! is_array($allowed) || count($allowed) < 1 ) return true;
        $email = strtolower(trim((string) U::get($_SESSION, 'email', '')));
        if ( $email === '' ) return false;
        foreach ( $allowed as $one ) {
            if ( strtolower(trim((string) $one)) === $email ) return true;
        }
        return false;
    }

    // One small APCu int per wwwroot (any IP). Session is only used if APCu is off.
    public static function adminUnlockCacheKey() {
        global $CFG;
        return 'admin_unlock_'.md5((string) $CFG->wwwroot);
    }

    public static function adminUnlockCount() {
        if ( U::apcuAvailable() ) {
            return (int) U::appCacheGet(self::adminUnlockCacheKey(), 0);
        }
        $until = $_SESSION['_admin_unlock_exp'] ?? 0;
        if ( ! is_int($until) || $until < time() ) {
            unset($_SESSION['_admin_unlock'], $_SESSION['_admin_unlock_exp']);
            return 0;
        }
        return (int) ($_SESSION['_admin_unlock'] ?? 0);
    }

    public static function adminUnlockBanned() {
        return self::adminUnlockCount() >= 2;
    }

    public static function adminUnlockRecordFail() {
        $n = self::adminUnlockCount() + 1;
        $ttl = ($n >= 2) ? 300 : 600;
        if ( U::apcuAvailable() ) {
            U::appCacheSet(self::adminUnlockCacheKey(), $n, $ttl);
        } else {
            $_SESSION['_admin_unlock'] = $n;
            $_SESSION['_admin_unlock_exp'] = time() + $ttl;
        }
        return $n >= 2;
    }

    public static function adminUnlockClearFails() {
        if ( U::apcuAvailable() ) {
            U::appCacheDelete(self::adminUnlockCacheKey());
        }
        unset($_SESSION['_admin_unlock'], $_SESSION['_admin_unlock_exp']);
    }

    public static function requireAdmin() {
        global $CFG, $OUTPUT;
        if ( $CFG->google_glient_id && $_SESSION['admin'] != 'yes' ) {
            U::flashError('Login required');
            $OUTPUT->doRedirect(\Tsugi\Controllers\Login::loginUrl()) ;
            exit();
        }
    }

    public static function findTools($dir, &$retval, $filenames=array("index.php", "tsugi.php")) {
        if ( ! is_array($filenames) ) $filenames = array($filenames);
        if ( is_dir($dir) ) {
            if ($dh = opendir($dir)) {
                while (($sub = readdir($dh)) !== false) {
                    if ( strpos($sub, ".") === 0 ) continue;
                    if ( in_array($sub, $filenames) ) {
                        $retval[] = $dir  ."/";
                    }
                    $path = $dir . '/' . $sub;
                    if ( ! is_dir($path) ) continue;
                    if ( $sh = opendir($path)) {
                        while (($file = readdir($sh)) !== false) {
                            if ( in_array($file, $filenames) ) {
                                $retval[] = $path  ."/" ;
                                break;
                            }
                        }
                        closedir($sh);
                    }
                }
                closedir($dh);
            }
        }
    }

    public static function findAllFolders($paths)
    {
        $folders = array();
        if ( is_string($paths) ) $paths = array($paths);
        foreach( $paths as $path) {
           if ( ! is_dir($path) ) continue;
           $files = scandir($path);
            foreach($files as $file) {
                if ( strpos($file,'.') === 0 ) continue;
                if ( strpos($file,'_') === 0 ) continue;
                $abs = self::addSlash($path).$file;
                if ( ! is_dir($abs) ) continue;
                $folders[] = $abs;
            }
        }
        return $folders;
    }

    public static function addSlash($path)
    {
        if ( U::strlen($path) < 1 ) return $path;
        if ( substr($path,strlen($path)-1) == DIRECTORY_SEPARATOR ) return $path;
        return $path . DIRECTORY_SEPARATOR;
    }

    public static function findAllTools()
    {
        global $CFG;
        // Load tools from various folders
        $tools = array();
        foreach( $CFG->tool_folders AS $tool_folder) {
            if ( $tool_folder == 'core' ) continue;
            if ( $tool_folder == 'admin' ) continue;
            self::findTools($CFG->dirroot.'/'.$tool_folder,$tools);
        }
        return $tools;
    }

    public static function findAllRegistrations($folders=false, $appStore=false)
    {
        global $CFG, $PDOX;

        $regCode = $CFG->dirroot . '/../load_registrations.php';
        if ( file_exists($regCode) ) {
            require_once($regCode);
            return loadRegistrations();
        }
        return self::findAllRegistrationsInternal($folders, $appStore);
    }

    public static function findAllRegistrationsInternal($folders=false, $appStore=false)
    {
        global $CFG, $PDOX;

        // Scan the tools folders for registration settings
        if ( $folders == false ) $folders = $CFG->tool_folders;
        if ( is_string($folders) ) $folders = array($folders);
        $tools = array();
        foreach( $folders AS $tool_folder) {
            if ( $tool_folder == 'core' ) continue;
            if ( $tool_folder == 'admin' ) continue;
            $some = self::findFiles('register.php', $CFG->dirroot . '/');
            foreach($some as $reg_file) {
                // Take off the $CFG->dirroot
                $relative = substr($reg_file,strlen($CFG->dirroot)+1);
                $url = $CFG->wwwroot . '/' . $relative;
                $url = U::remove_relative_path($url);
                $pieces = explode('/', $url);
                if ( count($pieces) < 2 || $pieces[count($pieces)-1] != 'register.php') {
                    error_log('Unable to load tool registration from '.$tool_folder);
                    continue;
                }
                $key = $pieces[count($pieces)-2];
                unset($REGISTER_LTI);
                unset($REGISTER_LTI2);
                require($reg_file);
                if ( ! isset($REGISTER_LTI) && isset($REGISTER_LTI2) ) $REGISTER_LTI = $REGISTER_LTI2;
                if ( ! isset($REGISTER_LTI) ) continue;
                if ( ! is_array($REGISTER_LTI) ) continue;

                if ( isset($REGISTER_LTI['name']) && isset($REGISTER_LTI['short_name']) &&
                    isset($REGISTER_LTI['description']) ) {
                    // Valid LTI Registration
                    // If Appstore - Check if the registration is marked as hidden
                    if ($appStore && isset($REGISTER_LTI['hide_from_store']) && $REGISTER_LTI['hide_from_store']) {
                        // Skip hidden app
                        continue;
                    }
                } else {
                    error_log("Missing required name, short_name, and description in ".$tool_folder);
                }

                // Make an icon URL
                $fa_icon = isset($REGISTER_LTI['FontAwesome']) ? $REGISTER_LTI['FontAwesome'] : false;
                if ( $fa_icon !== false ) {
                    $REGISTER_LTI['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
                }
                $launch_url = str_replace('/register.php','/',$url);
                $REGISTER_LTI['url'] = $launch_url;

                $screen_shots = U::get($REGISTER_LTI, 'screen_shots');
                if ( is_array($screen_shots) && count($screen_shots) > 0 ) {
                    $new = array();
                    foreach($screen_shots as $screen_shot ) {
                        $new[] = str_replace('/register.php','/'.$screen_shot, $url);
                    }
                    $REGISTER_LTI['screen_shots'] = $new;
                }

                $submissionReview = U::get($REGISTER_LTI, 'submissionReview');
                if ( is_array($submissionReview) && U::get($submissionReview, 'url')) {
                    // Make the URL absolute
                    $submissionUrl =  U::get($submissionReview, 'url');
                    $submissionReview['url'] = str_replace('/register.php','/'.$submissionUrl, $url);
                    $REGISTER_LTI['submissionReview'] = $submissionReview;
                }

                $tools[$key] = $REGISTER_LTI;
            }
        }

        // Find external applications
        $stmt = $PDOX->queryReturnError("SELECT * FROM {$CFG->dbprefix}lti_external");
        $rows = array();
        if ( $stmt->success ) while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            array_push($rows, $row);
        }

        foreach($rows as $row) {
            // echo("<pre>\n");var_dump($row['json']);echo("</pre>\n");
            $REGISTER_LTI = json_decode($row['json'], true);
            if ( ! is_array($REGISTER_LTI) ) $REGISTER_LTI = array();

            $REGISTER_LTI['url'] = $CFG->wwwroot . '/ext/' . $row['endpoint'];

            // Make an icon URL
            $fa_icon = isset($row['fa_icon']) ? $row['fa_icon'] : false;
            if ( $fa_icon !== false ) {
                $REGISTER_LTI['FontAwesome'] = $fa_icon;
                $REGISTER_LTI['icon'] = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
            }
            $REGISTER_LTI['name'] = $row['name'];
            $REGISTER_LTI['short_name'] = $row['name'];
            $REGISTER_LTI['description'] = $row['description'];


            $key = 'ext-' . $row['endpoint'];
            $tools[$key] = $REGISTER_LTI;
        }


        return $tools;
    }

    public static function findFiles($filename="index.php", $reldir=false) {
        global $CFG;
        $files = array();
        foreach ( $CFG->tool_folders as $dir ) {
            if ( $reldir !== false ) $dir = $reldir . $dir;
            if ( is_dir($dir) ) {
                if ($dh = opendir($dir)) {
                    while (($sub = readdir($dh)) !== false) {
                        if ( strpos($sub, ".") === 0 ) continue;
                        if ( $sub == $filename ) {
                            $files[] = $dir . '/' . $sub;
                            continue;
                        }
                        $path = $dir . '/' . $sub;
                        if ( ! is_dir($path) ) continue;
                        if ( $sh = opendir($path)) {
                            while (($file = readdir($sh)) !== false) {
                                if ( $file == $filename ) {
                                    $files[] = $path  ."/" . $file;
                                    break;
                                }
                            }
                            closedir($sh);
                        }
                    }
                    closedir($dh);
                }
            }
        }
        return $files;
    }

    public static function findToolFiles($filename="index.php", $reldir=false) {
        global $CFG;
        $retval = array();
        foreach ( $CFG->tool_folders as $dir ) {
            if ( $reldir !== false ) $dir = $reldir . '/' . $dir;
            $files = self::searchTwoLevels($filename, $dir);
            $retval = array_merge($retval, $files);
        }
        return $retval;
    }

    public static function searchTwoLevels($filename, $dir) {
        $files = array();
        if ( is_dir($dir) ) {
            if ($dh = opendir($dir)) {
                while (($sub = readdir($dh)) !== false) {
                    if ( strpos($sub, ".") === 0 ) continue;
                    if ( $sub == $filename ) {
                        $files[] = $dir . '/' . $sub;
                        continue;
                    }
                    $path = $dir . '/' . $sub;
                    if ( ! is_dir($path) ) continue;
                    if ( $sh = opendir($path)) {
                        while (($file = readdir($sh)) !== false) {
                            if ( $file == $filename ) {
                                $files[] = $path  ."/" . $file;
                                break;
                            }
                        }
                        closedir($sh);
                    }
                }
                closedir($dh);
            }
        }
        return $files;
    }

    // path = /x/y/z
    // root = /x/y
    // retval = z
    public static function trimAsMuchAsYouCan($path, $root) {
        $path_pieces = explode('/', $path);
        $root_pieces = explode('/', $root);
        for($i=0; $i < count($path_pieces) && $i < count($root_pieces) ; $i++) {
            if ( $path_pieces[$i] != $root_pieces[$i] ) break;
        }
        $pieces = array();
        for(;$i < count($path_pieces); $i++) {
            if ( U::strlen($path_pieces[$i] ) < 1 ) continue;
            $pieces[] = $path_pieces[$i];
        }
        $remainder = implode('/', $pieces);
        return $remainder;
    }

        // admin/key/key-util.php
    /**
     * Normalize LTI 1.3 deployment id from a form field for database storage.
     *
     * Empty string, whitespace only, or the literal "null" are stored as NULL.
     * Leaving the field blank means the key accepts any deployment id sent by the LMS;
     * Tsugi still uses the deployment id from the launch JWT for LTI 1.3 services (for example grading).
     * Any other non-empty string is trimmed and stored as the specific deployment id.
     *
     * @param mixed $v Raw POST value
     * @return string|null
     */
    public static function normalize_deploy_key_input($v) {
        if ( $v === null ) {
            return null;
        }
        if ( ! is_string($v) ) {
            return null;
        }
        $t = trim($v);
        if ( $t === '' || strcasecmp($t, 'null') === 0 ) {
            return null;
        }
        return $t;
    }

    /**
     * Resolve the LTI 1.3 (issuer URL, client id) pair for a tenant key.
     *
     * @return array{issuer_sha256:string,issuer_client:string}|null
     */
    public static function resolve_lti13_issuer_client($lms_issuer, $lms_client) {
        if ( U::isNotEmpty($lms_issuer) && U::isNotEmpty($lms_client) ) {
            $issuer_sha256 = U::lti_sha256($lms_issuer);
            if ( ! $issuer_sha256 ) {
                return null;
            }
            return array(
                'issuer_sha256' => $issuer_sha256,
                'issuer_client' => $lms_client,
            );
        }

        return null;
    }

    /**
     * Reject a new or updated tenant when another key already matches the same
     * LTI 1.3 issuer URL and client id with a wildcard deployment (blank deploy_key).
     *
     * Keys with a specific deployment id may share the same issuer and client id.
     */
    public static function validate_issuer_client_unique($lms_issuer, $lms_client, $deploy_key, $exclude_key_id=null) {
        global $PDOX, $CFG;

        if ( U::isNotEmpty($deploy_key) ) {
            return true;
        }

        $pair = self::resolve_lti13_issuer_client($lms_issuer, $lms_client);
        if ( $pair === null ) {
            return true;
        }

        $parms = array(
            ':issuer_sha256' => $pair['issuer_sha256'],
            ':issuer_client' => $pair['issuer_client'],
        );
        $exclude_sql = '';
        if ( ! empty($exclude_key_id) && (int) $exclude_key_id > 0 ) {
            $exclude_sql = ' AND k.key_id <> :exclude_key_id ';
            $parms[':exclude_key_id'] = (int) $exclude_key_id;
        }

        $row = $PDOX->rowDie(
            "SELECT k.key_id, k.key_title FROM {$CFG->dbprefix}lti_key AS k
                WHERE (k.deleted IS NULL OR k.deleted = 0)
                    AND (k.deploy_key IS NULL OR TRIM(k.deploy_key) = '')
                    $exclude_sql
                    AND (k.lms_issuer_sha256 IS NULL OR k.lms_issuer_sha256 = :issuer_sha256)
                    AND k.lms_client = :issuer_client
                LIMIT 1",
            $parms
        );

        if ( $row ) {
            $label = U::isNotEmpty($row['key_title']) ? $row['key_title'] : ('key_id '.$row['key_id']);
            U::flashError('A tenant/key with this LTI 1.3 Platform Issuer URL and Client ID already exists ('.$label.')');
            return false;
        }

        return true;
    }

    public static function validate_key_details($key_key, $deploy_key, $lms_issuer, $old_key_key=null, $old_deploy_key=null, $lms_client=null, $exclude_key_id=null) {
        global $PDOX, $CFG;

        // Enforce in software because MySQL can't do it
        // CONSTRAINT `{$CFG->dbprefix}lti_key_both_not_null`
        //  CHECK (
        //        (key_sha256 IS NOT NULL OR deploy_sha256 IS NOT NULL)
        //  )
        // deploy_key may be NULL/blank (accept any deployment id from the LMS; deploy_sha256 NULL); then we still need
        // either an LTI 1.1 consumer key or enough LTI 1.3 identity (platform issuer URL + client id).
        $have_oauth = U::isNotEmpty($key_key);
        $have_deploy = U::isNotEmpty($deploy_key);
        $have_per_tenant_lti13 = U::isNotEmpty($lms_issuer) && U::isNotEmpty($lms_client);
        if ( ! $have_oauth && ! $have_deploy && ! $have_per_tenant_lti13 ) {
            U::flashError('Either an LTI 1.1 consumer key, a specific LTI 1.3 deployment id, or LTI 1.3 platform issuer URL and client id is required');
            return false;
        }

        $key_sha256 = U::lti_sha256($key_key);
        $deploy_sha256 = U::lti_sha256($deploy_key);

        // TODO: Decide if we put this in the DB
        // Extra constraint in software is oauth key is there is must be unique
        if ( $key_key != $old_key_key && !empty($key_key) ) {
            $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                    WHERE key_sha256 = :key_sha256",
                array(':key_sha256' => $key_sha256)
            );
            if ( $row ) {
                U::flashError("Cannot add the same OAuth Consumer Key more than once");
                return false;
            }
        }

        // Now check these
        // CONSTRAINT `{$CFG->dbprefix}lti_key_const_1` UNIQUE(key_sha256, deploy_sha256),
        if ( ($key_key != $old_key_key || $deploy_key != $old_deploy_key) && 
            !empty($key_key) && !empty($deploy_key) ) {
            $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                    WHERE key_sha256 = :key_sha256 AND deploy_sha256 = :deploy_sha256",
                array(':key_sha256' => $key_sha256, ":deploy_sha256" => $deploy_sha256)
            );
            if ( $row ) {
                U::flashError("The combination of Consumer key and Deployment ID must be unique");
                return false;
            }
        }

        if ( ! self::validate_issuer_client_unique($lms_issuer, $lms_client, $deploy_key, $exclude_key_id) ) {
            return false;
        }

        return true;
    }

        // admin/context/mail_audience.php
    /**
     * Shared audience query for context mailing-list export and bulk mail.
     *
     * @param int $exclude_recent_bulk_days When > 0, exclude users who already have a
     *   successful bulk send (mail_sent with bulk_id, status sent) in this context
     *   within that many days. Scoped per context_id.
     * @param int $limit When > 0, return only the most recently logged-in N users
     *   (ORDER BY login_at DESC LIMIT N). 0 = no limit (email-domain sort).
     * @return array{0: string, 1: array}|false SQL + params, or false if args invalid
     */
    public static function mail_context_audience_sql($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
        $built = self::mail_context_audience_from_where($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
        if ( $built === false ) {
            return false;
        }
        list($from_where, $params) = $built;
        $limit = (int) $limit;
        if ( $limit < 0 ) {
            return false;
        }

        $sql = "SELECT DISTINCT U.email, U.displayname, U.login_at, U.user_id, COALESCE(P.premium, 0) AS premium
                ".$from_where;

        if ( $limit > 0 ) {
            $sql .= " ORDER BY U.login_at DESC LIMIT ".$limit;
        } else {
            $sql .= " ORDER BY SUBSTRING_INDEX(U.email, '@', -1), U.email";
        }

        return array($sql, $params);
    }

    /**
     * Build FROM/JOIN/WHERE for context audience (shared by select + counts).
     *
     * @return array{0: string, 1: array}|false
     */
    public static function mail_context_audience_from_where($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0) {
        global $CFG;

        $context_id = (int) $context_id;
        $days = (int) $days;
        $exclude_recent_bulk_days = (int) $exclude_recent_bulk_days;
        if ( $context_id < 1 || $days < 1 || $days > 365 ) {
            return false;
        }
        if ( $exclude_recent_bulk_days < 0 || $exclude_recent_bulk_days > 365 ) {
            return false;
        }

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $params = array(':CID' => $context_id, ':CUTOFF' => $cutoff_date);

        $sql = "FROM {$CFG->dbprefix}lti_membership AS M
                JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
                LEFT JOIN {$CFG->dbprefix}profile AS P ON U.profile_id = P.profile_id
                WHERE M.context_id = :CID
                  AND U.email IS NOT NULL
                  AND U.email != ''
                  AND U.login_at IS NOT NULL
                  AND U.login_at >= :CUTOFF";

        if ( !$include_opted_out ) {
            $sql .= " AND (U.subscribe IS NULL OR U.subscribe != -1)
                      AND (P.subscribe IS NULL OR P.subscribe != -1)";
        }

        if ( $premium_only ) {
            $sql .= " AND COALESCE(P.premium, 0) > 0";
        }

        if ( $exclude_recent_bulk_days > 0 ) {
            $bulk_cutoff = date('Y-m-d H:i:s', strtotime("-$exclude_recent_bulk_days days"));
            $params[':BULK_CUTOFF'] = $bulk_cutoff;
            // Per-context: successful bulk sends only (json status=sent).
            $sql .= " AND NOT EXISTS (
                SELECT 1 FROM {$CFG->dbprefix}mail_sent AS S
                WHERE S.context_id = :CID
                  AND S.user_to = U.user_id
                  AND S.bulk_id IS NOT NULL
                  AND S.created_at >= :BULK_CUTOFF
                  AND S.json LIKE '%\"status\":\"sent\"%'
            )";
        }

        return array($sql, $params);
    }

    /**
     * Count distinct users matching audience filters (no LIMIT).
     *
     * @return int|false
     */
    public static function mail_context_audience_count($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0) {
        global $PDOX;

        $built = self::mail_context_audience_from_where($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
        if ( $built === false ) {
            return false;
        }
        list($from_where, $params) = $built;
        $sql = "SELECT COUNT(DISTINCT U.user_id) AS c ".$from_where;
        $row = $PDOX->rowDie($sql, $params);
        if ( $row === false || $row === null ) {
            return 0;
        }
        return (int) $row['c'];
    }

    /**
     * Audience size breakdown for bulk preview / CLI dry-run.
     *
     * @return array{
     *   matched_login: int,
     *   excluded_recent_bulk: int,
     *   eligible_no_limit: int,
     *   limit: int,
     *   will_send: int,
     *   exclude_recent_bulk_days: int
     * }|false
     */
    public static function mail_context_audience_stats($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
        $limit = (int) $limit;
        $exclude_recent_bulk_days = (int) $exclude_recent_bulk_days;
        if ( $limit < 0 ) {
            return false;
        }

        $matched_login = self::mail_context_audience_count($context_id, $days, $include_opted_out, $premium_only, 0);
        if ( $matched_login === false ) {
            return false;
        }

        if ( $exclude_recent_bulk_days > 0 ) {
            $eligible_no_limit = self::mail_context_audience_count($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days);
            if ( $eligible_no_limit === false ) {
                return false;
            }
            $excluded_recent_bulk = max(0, $matched_login - $eligible_no_limit);
        } else {
            $eligible_no_limit = $matched_login;
            $excluded_recent_bulk = 0;
        }

        $will_send = $eligible_no_limit;
        if ( $limit > 0 && $will_send > $limit ) {
            $will_send = $limit;
        }

        return array(
            'matched_login' => $matched_login,
            'excluded_recent_bulk' => $excluded_recent_bulk,
            'eligible_no_limit' => $eligible_no_limit,
            'limit' => $limit,
            'will_send' => $will_send,
            'exclude_recent_bulk_days' => $exclude_recent_bulk_days,
        );
    }

    /**
     * @return array List of audience rows
     */
    public static function mail_context_audience($context_id, $days, $include_opted_out=false, $premium_only=false, $exclude_recent_bulk_days=0, $limit=0) {
        global $PDOX;

        $built = self::mail_context_audience_sql($context_id, $days, $include_opted_out, $premium_only, $exclude_recent_bulk_days, $limit);
        if ( $built === false ) {
            return array();
        }
        return $PDOX->allRowsDie($built[0], $built[1]);
    }

    /**
     * Single-recipient audience: one context member matching email (case-insensitive).
     *
     * @return array Zero or one audience row (same shape as mail_context_audience)
     */
    public static function mail_context_audience_by_email($context_id, $email) {
        global $CFG, $PDOX;

        $context_id = (int) $context_id;
        $email = strtolower(trim((string) $email));
        if ( $context_id < 1 || $email === '' || strpos($email, '@') === false ) {
            return array();
        }

        $sql = "SELECT U.email, U.displayname, U.login_at, U.user_id, COALESCE(P.premium, 0) AS premium
                FROM {$CFG->dbprefix}lti_membership AS M
                JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
                LEFT JOIN {$CFG->dbprefix}profile AS P ON U.profile_id = P.profile_id
                WHERE M.context_id = :CID
                  AND U.email IS NOT NULL
                  AND U.email != ''
                  AND LOWER(U.email) = :E
                ORDER BY U.user_id DESC
                LIMIT 1";
        $row = $PDOX->rowDie($sql, array(':CID' => $context_id, ':E' => $email));
        if ( $row === false || $row === null ) {
            return array();
        }
        return array($row);
    }

        // admin/expire/expire_util.php
    // To avoid wiping out all the data
    public static function sanity_check_days($base=false, $days=false) {
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

    public static function get_count_table($table) {
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
    public static function get_expirable_where($days) {
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
    public static function get_pii_where($days) {
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

    public static function get_expirable_records($table, $days) {
        global $PDOX, $CFG;
        $where = self::get_expirable_where($days);
        $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}{$table} " . $where['sql'];
        $row = $PDOX->rowDie($sql, $where['params']);
        $count = $row ? $row['count'] : 0;
        return $count;
    }

    public static function get_safe_key_where() {
        return "(key_key <> 'google.com' AND key_key <> '12345')";
    }

    public static function get_pii_count($days) {
        global $PDOX, $CFG;
        $where = self::get_pii_where($days);
        $sql = "SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_user " . $where['sql'];
        $row = $PDOX->rowDie($sql, $where['params']);
        $count = $row ? $row['count'] : 0;
        return $count;
    }

        // admin/install/install_util.php
    public static function getRepoOrigin($repo) {
        $output = $repo->run('remote -v');
        $lines = explode("\n",$output);
        foreach($lines as $line) {
            $matches = array();
            preg_match( '/^origin\s+([^ ]*)\s+\(fetch\)$/', $line, $matches);
            if ( count($matches) < 2 ) continue;
            $origin = trim($matches[1]);
            if ( strrpos($origin, '.git') == strlen($origin)-4) return $origin;
            return $origin . '.git';
        }
        return false;
    }

    /** True if ref exists locally or as origin/<ref>. */
    public static function repoHasRef($repo, $ref) {
        if ( ! is_string($ref) || ! preg_match('/^[A-Za-z0-9._\/+-]+$/', $ref) ) {
            return false;
        }
        try {
            $repo->run('rev-parse --verify --quiet '.$ref);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /** Best-effort default branch (origin/HEAD, current, then main/master). */
    public static function getRepoDefaultBranch($repo) {
        try {
            $out = trim($repo->run('symbolic-ref --short refs/remotes/origin/HEAD'));
            if ( strpos($out, 'origin/') === 0 ) {
                return substr($out, 7);
            }
            if ( U::strlen($out) > 0 ) return $out;
        } catch (Exception $e) {
            // fall through
        }
        try {
            $out = trim($repo->run('rev-parse --abbrev-ref HEAD'));
            if ( U::strlen($out) > 0 && $out !== 'HEAD' ) return $out;
        } catch (Exception $e) {
            // fall through
        }
        foreach ( array('main', 'master') as $candidate ) {
            if ( self::repoHasRef($repo, $candidate) || self::repoHasRef($repo, 'origin/'.$candidate) ) {
                return $candidate;
            }
        }
        return 'master';
    }

    /**
     * Resolve checkout target. Empty or stale "master" falls back to the repo default (often main).
     */
    public static function resolveGitCheckout($repo, $gitversion) {
        if ( U::strlen($gitversion) < 1 ) {
            return self::getRepoDefaultBranch($repo);
        }
        if ( self::repoHasRef($repo, $gitversion) || self::repoHasRef($repo, 'origin/'.$gitversion) ) {
            return $gitversion;
        }
        if ( $gitversion === 'master' ) {
            $default = self::getRepoDefaultBranch($repo);
            if ( $default !== 'master' ) return $default;
            if ( self::repoHasRef($repo, 'main') || self::repoHasRef($repo, 'origin/main') ) {
                return 'main';
            }
        }
        return $gitversion;
    }

    // https://stackoverflow.com/questions/3433465/mysql-delete-all-rows-older-than-10-minutes
    public static function ghostBust() {
        global $PDOX, $CFG;
        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lms_tools_status 
                WHERE updated_at < (NOW() - INTERVAL 55 MINUTE)");
        return;
    }

    public static function getClusterInfo() {
        global $PDOX, $CFG;
        self::ghostBust();
        $rows = $PDOX->allRowsDie(
            "SELECT ipaddr, name, description, commit, commit_log, clone_url, gitversion, status_note, S.updated_at
             FROM {$CFG->dbprefix}lms_tools_status AS S
             JOIN {$CFG->dbprefix}lms_tools as T ON S.tool_id = T.tool_id
             ORDER BY ipaddr"
        );
        return ( $rows ) ;
    }

    /**
     * Get a list of IPs of the other servers in the cluster
     */
    public static function getClusterIPs($rows=false) {
        if ( ! $rows ) $rows = self::getClusterInfo();
        $retval = array();
        $serverIP = Net::serverIP();
        foreach ( $rows as $row ) {
            if ( in_array($row['ipaddr'], $retval) ) continue;
            if ( $row['ipaddr'] == $serverIP ) continue;
            $retval[] = $row['ipaddr'];
        }
        return $retval;
    }

    public static function doClone($remote, $folder) {
        global $PDOX, $CFG;

        $repo = new \Tsugi\Util\GitRepo($folder, true,  false);
        $log = $repo->clone_from($remote);
        $results = "Command: git clone $remote\n";
        $results .= "Folder: $folder\n\n";
        $results .= $log;

        // Read the files...
        $files = scandir($folder);
        if ( count($files) < 2 ) {
            $results .= "No Files Checked Out\n";
        } else {
            $results .= "Checked Out:\n";
            foreach($files as $file) {
                if ( $file == '.' || $files == '..' ) continue;
                $results .= '  '.$file."\n";
            }
            $detail = new \stdClass();
            self::addRepoInfo($detail, $repo);

            $sql = "INSERT INTO {$CFG->dbprefix}lms_tools
                ( toolpath, name, description, clone_url, gitversion, created_at, updated_at ) VALUES
                ( :toolpath, :name, :description, :clone_url, :gitversion, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE
                    name=:name, description=:description, clone_url=:clone_url,
                    gitversion=:gitversion, updated_at=NOW()
            ";
            $values = array(
                ":toolpath" => $folder,
                ":name" => 'name',
                ":description" => 'description',
                ":clone_url" => $remote,
                ":gitversion" => self::getRepoDefaultBranch($repo)
            );
            $q = $PDOX->queryReturnError($sql, $values);

            // Update the status for this cluster
            self::updateToolStatus($folder, $detail);
        }
        return $results;
    }

    public static function updateToolStatus($tool_path, $detail) {
        global $PDOX, $CFG;

        $row = $PDOX->rowDie(
            "SELECT tool_id FROM {$CFG->dbprefix}lms_tools WHERE toolpath = :toolpath",
            array(":toolpath" => $tool_path)
        );

        if ( ! $row || ! U::get($row, 'tool_id')) {
            error_log("Could not find tool_id for $tool_path");
            return false;
        }

        $tool_id = $row['tool_id'];

        $serverIP = Net::serverIP();
        $sql = "INSERT INTO {$CFG->dbprefix}lms_tools_status
                ( tool_id, ipaddr, status_note, commit_log, 
                    commit, created_at, updated_at ) 
            VALUES
                ( :tool_id, :ipaddr, :status_note, :commit_log, 
                    :commit, NOW(), NOW() )
            ON DUPLICATE KEY UPDATE
                 status_note = :status_note, commit_log=:commit_log, 
                commit=:commit, updated_at = NOW()";
        $values = array(
            ":tool_id" => $tool_id,
            ":ipaddr" => $serverIP,
            ":status_note" => $detail->status_note,
            ":commit_log" => $detail->commit_log,
            ":commit" => $detail->commit,
        );
        $q = $PDOX->queryDie($sql, $values);
        return true;
    }

    // Notes
    // git reset --hard 5979437e27bd47637c4b562b33e861ce32b6468b

    /**
      * Load Information for a github repo
      *
      * Does not set name or description
      */
    public static function addRepoInfo($detail, $repo) {
        // Gather the information for the repo folder
        $errors = array();
        try {
            $update = $repo->run('remote update');
            $detail->writeable = true;
        } catch (Exception $e) {
            $detail->writeable = false;
            $update = 'Caught exception: '.$e->getMessage(). "\n";
            $errors[] = 'remote update: '.$e->getMessage();
        }
        $detail->update_note = $update;
        try {
            $status = $repo->run('status -uno');
        } catch (Exception $e) {
            $status = 'Caught exception: '.$e->getMessage(). "\n";
            $errors[] = 'status: '.$e->getMessage();
        }
        $detail->status_note = $status;
        $detail->updates = strpos($status, 'Your branch is behind') !== false;
        // Use -1 so single-commit / shallow repos work (HEAD^ fails there).
        try {
            $commit_log = $repo->run('log -1 --name-status');
        } catch (Exception $e) {
            $commit_log = 'Caught exception: '.$e->getMessage(). "\n";
            $errors[] = 'log: '.$e->getMessage();
        }
        $detail->commit_log = $commit_log;
        $lines = explode("\n",$commit_log);
        $detail->commit = '';
        if ( count($lines) > 0 ) {
            $line = $lines[0];
            $matches = array();            
            preg_match( '/^commit\s+([0-9a-f]*)$/', $line, $matches);
            if ( count($matches) >= 2 ) {
                $detail->commit = trim($matches[1]);
            }
        }
        if ( count($errors) > 0 ) {
            $detail->error = implode("\n", $errors);
        }
        try {
            $detail->gitversion = self::getRepoDefaultBranch($repo);
        } catch (Exception $e) {
            $detail->gitversion = 'main';
        }
    }

        // admin/mail/purge_util.php
    /**
     * Helpers to purge old mail audit rows (admin only).
     */

    public const MAIL_ADMIN_PURGE_DAYS = 30;

    /**
     * @return string Cutoff datetime for purge (UTC/server local as MySQL NOW()-compatible)
     */
    public static function mail_admin_purge_cutoff($days = self::MAIL_ADMIN_PURGE_DAYS) {
        $days = (int) $days;
        if ( $days < 1 ) {
            $days = self::MAIL_ADMIN_PURGE_DAYS;
        }
        return date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
    }

    /**
     * @return int Number of rows older than $days
     */
    public static function mail_admin_purge_count($table, $days = self::MAIL_ADMIN_PURGE_DAYS) {
        global $CFG, $PDOX;

        $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
        if ( $table === '' ) {
            return 0;
        }
        $row = $PDOX->rowDie(
            "SELECT COUNT(*) AS c FROM {$CFG->dbprefix}{$table} WHERE created_at < :CUTOFF",
            array(':CUTOFF' => self::mail_admin_purge_cutoff($days))
        );
        return is_array($row) ? (int) $row['c'] : 0;
    }

    /**
     * @return int Rows deleted
     */
    public static function mail_admin_purge_delete($table, $days = self::MAIL_ADMIN_PURGE_DAYS) {
        global $CFG, $PDOX;

        $table = preg_replace('/[^a-z0-9_]/i', '', (string) $table);
        if ( $table === '' ) {
            return 0;
        }
        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}{$table} WHERE created_at < :CUTOFF",
            array(':CUTOFF' => self::mail_admin_purge_cutoff($days))
        );
        if ( !$q->success ) {
            return -1;
        }
        return (int) $q->rowCount();
    }

    /**
     * Render purge form for a mail audit table.
     *
     * @param string $action_url Relative form action (current script)
     * @param string $table Logical table name without prefix (mail_ses_events|mail_sent)
     * @param string $label Human label for the table
     */
    public static function mail_admin_purge_form($action_url, $table, $label) {
        $days = self::MAIL_ADMIN_PURGE_DAYS;
        $old = self::mail_admin_purge_count($table, $days);
        $confirm = 'Delete '.$old.' '.$label.' row(s) older than '.$days.' days?';
        ?>
    <div style="margin:8px 0;">
      Older than <?= (int) $days ?> days: <strong><?= (int) $old ?></strong>
      <?php if ( $old > 0 ) { ?>
      <form method="post" action="<?= htmlentities($action_url) ?>" style="display:inline;margin-left:8px;"
            onsubmit="return confirm(<?= htmlentities(json_encode($confirm), ENT_QUOTES) ?>);">
        <?= \Tsugi\Controllers\Tool::csrfField() ?>
        <input type="hidden" name="purge_old" value="1">
        <input type="hidden" name="confirm_purge" value="1">
        <button type="submit" class="btn btn-danger btn-xs">Purge</button>
      </form>
      <?php } ?>
    </div>
        <?php
    }

    /**
     * Count SES delivery events that were logged as ignore_delivery.
     */
    public static function mail_admin_delivery_event_count() {
        global $CFG, $PDOX;

        $row = $PDOX->rowDie(
            "SELECT COUNT(*) AS c FROM {$CFG->dbprefix}mail_ses_events
             WHERE event_type = 'delivery' OR action = 'ignore_delivery'"
        );
        return is_array($row) ? (int) $row['c'] : 0;
    }

    /**
     * Delete SES delivery / ignore_delivery audit rows.
     *
     * @return int Rows deleted, or -1 on failure
     */
    public static function mail_admin_delete_delivery_events() {
        global $CFG, $PDOX;

        $q = $PDOX->queryReturnError(
            "DELETE FROM {$CFG->dbprefix}mail_ses_events
             WHERE event_type = 'delivery' OR action = 'ignore_delivery'"
        );
        if ( !$q->success ) {
            return -1;
        }
        return (int) $q->rowCount();
    }

    /**
     * Render button to delete all delivery (ignored) SES events.
     *
     * @param string $action_url Relative form action
     */
    public static function mail_admin_delete_delivery_events_form($action_url) {
        $n = self::mail_admin_delivery_event_count();
        $confirm = 'Delete '.$n.' delivery (ignored) SES event row(s)? Bounce/complaint/suppress rows are kept.';
        ?>
    <div style="margin:8px 0;">
      Delivery (ignored) events: <strong><?= (int) $n ?></strong>
      <?php if ( $n > 0 ) { ?>
      <form method="post" action="<?= htmlentities($action_url) ?>" style="display:inline;margin-left:8px;"
            onsubmit="return confirm(<?= htmlentities(json_encode($confirm), ENT_QUOTES) ?>);">
        <?= \Tsugi\Controllers\Tool::csrfField() ?>
        <input type="hidden" name="delete_delivery_events" value="1">
        <input type="hidden" name="confirm_delete_delivery" value="1">
        <button type="submit" class="btn btn-warning btn-xs">Delete delivery events</button>
      </form>
      <?php } ?>
    </div>
        <?php
    }
}
