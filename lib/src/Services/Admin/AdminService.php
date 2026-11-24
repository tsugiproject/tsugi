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

    /** @var array<int, array<string, mixed>>|null */
    private static $issuerClientDuplicateRows = null;

    /** @var array<int, array<string, mixed>>|null */
    private static $duplicateLti11KeyRows = null;

    /** @var array<int, string> */
    private static $upgradeBeaconNotes = array();

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
     * Rows for a client id check. A failed query throws so the caller can
     * keep going. allRowsDie() would end the upgrade.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function diagnosticRows($sql) {
        global $PDOX;
        $rows = $PDOX->allRows($sql);
        if ( ! is_array($rows) ) {
            throw new \RuntimeException('client id check query failed');
        }
        return $rows;
    }

    /**
     * Column names for lti_key. A failed describe throws so an unread table
     * is not reported as an empty result.
     *
     * @return array<int, string>
     */
    private static function ltiKeyColumnNames($table) {
        global $PDOX;
        $columns = $PDOX->describe($table);
        if ( ! is_array($columns) ) {
            throw new \RuntimeException('client id check query failed');
        }
        $names = array();
        foreach ( $columns as $column ) {
            if ( is_array($column) && isset($column['Field']) ) $names[] = $column['Field'];
        }
        return $names;
    }

    /**
     * Tenant keys that share an issuer, client id, and deployment id.
     * A blank deployment id is its own value in that tuple. This does not change any unique key.
     *
     * The cache is set only after the query returns. A throw leaves it unset
     * so a later call retries instead of reporting zero duplicates.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function issuerClientDuplicateRows() {
        global $PDOX, $CFG;

        if ( is_array(self::$issuerClientDuplicateRows) ) {
            return self::$issuerClientDuplicateRows;
        }
        $table = "{$CFG->dbprefix}lti_key";
        if ( ! is_object($PDOX) ) {
            return array();
        }
        $names = self::ltiKeyColumnNames($table);
        if ( ! in_array('lms_issuer', $names, true) || ! in_array('lms_client', $names, true) ) {
            return array();
        }
        if ( in_array('deploy_key', $names, true) ) {
            $deploy_sql = 'k.deploy_key';
            $deploy_group = "CASE WHEN deploy_key IS NULL OR TRIM(deploy_key) = '' OR LOWER(TRIM(deploy_key)) = 'null' THEN '' ELSE TRIM(deploy_key) END";
            $deploy_group_k = "CASE WHEN k.deploy_key IS NULL OR TRIM(k.deploy_key) = '' OR LOWER(TRIM(k.deploy_key)) = 'null' THEN '' ELSE TRIM(k.deploy_key) END";
        } else {
            $deploy_sql = 'NULL';
            $deploy_group = "''";
            $deploy_group_k = "''";
        }
        $deleted_sql = in_array('deleted', $names, true) ? 'k.deleted' : '0';
        $rows = self::diagnosticRows(
            "SELECT k.key_id, k.key_title, TRIM(k.lms_issuer) AS lms_issuer,
                    TRIM(k.lms_client) AS lms_client, {$deploy_sql} AS deploy_key, {$deleted_sql} AS deleted
                FROM {$table} AS k
                INNER JOIN (
                    SELECT TRIM(lms_issuer) AS lms_issuer, TRIM(lms_client) AS lms_client,
                            {$deploy_group} AS deploy_group
                        FROM {$table}
                        WHERE lms_issuer IS NOT NULL AND TRIM(lms_issuer) <> ''
                            AND lms_client IS NOT NULL AND TRIM(lms_client) <> ''
                        GROUP BY TRIM(lms_issuer), TRIM(lms_client), {$deploy_group}
                        HAVING COUNT(*) > 1
                ) AS d
                    ON TRIM(k.lms_issuer) = d.lms_issuer
                    AND TRIM(k.lms_client) = d.lms_client
                    AND {$deploy_group_k} = d.deploy_group
                ORDER BY k.lms_issuer, k.lms_client, {$deploy_group_k}, k.key_id"
        );
        self::$issuerClientDuplicateRows = $rows;
        return self::$issuerClientDuplicateRows;
    }

    /**
     * How many (client id, issuer, deployment id) tuples are used by more than one key.
     */
    public static function issuerClientDuplicateCount() {
        $groups = array();
        foreach ( self::issuerClientDuplicateRows() as $row ) {
            $issuer = trim((string) U::get($row, 'lms_issuer', ''));
            $client = trim((string) U::get($row, 'lms_client', ''));
            if ( $issuer === '' || $client === '' ) continue;
            $deploy = U::get($row, 'deploy_key');
            $deploy = is_string($deploy) ? trim($deploy) : '';
            if ( strcasecmp($deploy, 'null') === 0 ) $deploy = '';
            $groups[$issuer."\n".$client."\n".$deploy] = true;
        }
        return count($groups);
    }

    /**
     * Red warning for the admin console and the key list. Empty when there are no duplicates.
     */
    public static function issuerClientDuplicateNotice() {
        $count = self::issuerClientDuplicateCount();
        if ( $count < 1 ) {
            return '';
        }
        $tuples = $count === 1 ? 'tuple' : 'tuples';
        $verb = $count === 1 ? 'is' : 'are';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>There '.$verb.' '.$count.' duplicate (client id, issuer, deployment id) '.$tuples.".</p>\n"
            ."</div>\n";
    }

    /**
     * How a stored issuer, client id, or deployment id should be read.
     * An empty string is a value. NULL is a different value.
     *
     * @param mixed $value
     */
    public static function identityValueLabel($value) {
        if ( $value === null ) return '(NULL)';
        if ( ! is_string($value) || $value === '' ) return '(empty string)';
        if ( trim($value) === '' ) {
            $spaces = strlen($value);
            $word = $spaces === 1 ? 'space' : 'spaces';
            return '('.$spaces.' '.$word.')';
        }
        return $value;
    }

    /**
     * Keys that block UNIQUE(lms_issuer, lms_client, deploy_key).
     *
     * A NULL in any of the three columns is not a member. An empty string is.
     * Soft-deleted keys count. Each group lists every key id.
     *
     * @return array<int, array{group_id:int, keys:array<int, array<string, mixed>>}>
     */
    public static function issuerClientDeployUniqueBlockingGroups() {
        global $PDOX, $CFG;

        $table = "{$CFG->dbprefix}lti_key";
        if ( ! is_object($PDOX) ) return array();
        $names = self::ltiKeyColumnNames($table);
        foreach ( array('key_id', 'lms_issuer', 'lms_client', 'deploy_key') as $need ) {
            if ( ! in_array($need, $names, true) ) return array();
        }
        $deleted_sql = in_array('deleted', $names, true) ? 'k.deleted' : '0';
        $title_sql = in_array('key_title', $names, true) ? 'k.key_title' : 'NULL';
        $rows = self::diagnosticRows(
            "SELECT k.key_id, {$title_sql} AS key_title, {$deleted_sql} AS deleted,
                    k.lms_issuer, k.lms_client, k.deploy_key, d.group_id
                FROM {$table} AS k
                INNER JOIN (
                    SELECT MIN(key_id) AS group_id, lms_issuer, lms_client, deploy_key
                        FROM {$table}
                        WHERE lms_issuer IS NOT NULL
                            AND lms_client IS NOT NULL
                            AND deploy_key IS NOT NULL
                        GROUP BY lms_issuer, lms_client, deploy_key
                        HAVING COUNT(*) > 1
                ) AS d
                    ON k.lms_issuer = d.lms_issuer
                    AND k.lms_client = d.lms_client
                    AND k.deploy_key = d.deploy_key
                ORDER BY d.group_id, k.key_id"
        );
        $groups = array();
        foreach ( $rows as $row ) {
            $group_id = (int) U::get($row, 'group_id', 0);
            if ( ! isset($groups[$group_id]) ) {
                $groups[$group_id] = array(
                    'group_id' => $group_id,
                    'keys' => array(),
                );
            }
            $groups[$group_id]['keys'][] = $row;
        }
        return array_values($groups);
    }

    /**
     * Remember a short upgrade note for the beacon comment.
     * No key ids or stored values. A later beacon in this process still sees it.
     */
    public static function addUpgradeBeaconNote($note) {
        $note = is_string($note) ? trim($note) : '';
        if ( $note === '' || in_array($note, self::$upgradeBeaconNotes, true) ) return;
        self::$upgradeBeaconNotes[] = $note;
    }

    /**
     * @return array<int, string>
     */
    public static function upgradeBeaconNotes() {
        return self::$upgradeBeaconNotes;
    }

    /**
     * Whether UNIQUE(lms_issuer, lms_client, deploy_key) can be added.
     *
     * The three columns must already be varchar(255) character set ascii
     * collation ascii_bin and nullable. A value longer than 255 characters,
     * or a character outside ASCII, blocks the varchar alter and this unique.
     * A NULL in any of the three columns stays repeatable, so those rows are
     * not duplicates. An empty string is a value. Soft-deleted rows count.
     * ascii_bin is PAD SPACE, so GROUP BY matches the unique index.
     * A prefix index does not count as present.
     *
     * blocked is the beacon phrase when the unique is absent and the ALTER
     * must not run. It has no key ids and no stored values. An empty blocked
     * string means the unique is present, or the data is ready for the ALTER.
     *
     * @return array{present:bool, can_add:bool, replace:bool, have:array<int, string>, index:string, groups:int, examples:array<int, array<string, mixed>>, blocked:string}
     */
    public static function issuerClientDeployUniqueGate() {
        global $PDOX, $CFG;

        $index = $CFG->dbprefix.'lti_key_const_issuer_client_deploy';
        $empty = array(
            'present' => false,
            'can_add' => false,
            'replace' => false,
            'have' => array(),
            'index' => $index,
            'groups' => 0,
            'examples' => array(),
            'blocked' => 'unique (issuer, client id, deployment id) not added: could not read lti_key',
        );
        $table = $CFG->dbprefix.'lti_key';
        if ( ! is_object($PDOX) ) return $empty;
        if ( ! is_array($PDOX->describe($table)) ) return $empty;

        $columns = array('lms_issuer', 'lms_client', 'deploy_key');
        $blocked_columns = array();
        foreach ( $columns as $column ) {
            $column_block = self::uniqueColumnBlock($table, $column, 255, true);
            if ( $column_block !== '' ) $blocked_columns[] = $column_block;
        }
        if ( count($blocked_columns) > 0 ) {
            $empty['blocked'] = 'unique (issuer, client id, deployment id) not added: '
                .implode('; ', $blocked_columns);
            return $empty;
        }

        $detail = $PDOX->indexDetail($table);
        if ( ! is_array($detail) ) {
            $empty['blocked'] = 'unique (issuer, client id, deployment id) not added: could not read indexes';
            return $empty;
        }
        $wanted = array('lms_issuer', 'lms_client', 'deploy_key');
        $by_name = array();
        foreach ( $detail as $index_row ) {
            $index_name = (string) U::getIgnoreCase($index_row, 'Key_name');
            if ( $index_name === '' || strcasecmp($index_name, 'PRIMARY') === 0 ) continue;
            $seq = (int) U::getIgnoreCase($index_row, 'Seq_in_index');
            if ( ! isset($by_name[$index_name]) ) {
                $by_name[$index_name] = array(
                    'columns' => array(),
                    'full_unique' => true,
                );
            }
            $by_name[$index_name]['columns'][$seq] = strtolower((string) U::getIgnoreCase($index_row, 'Column_name'));
            $sub_part = U::getIgnoreCase($index_row, 'Sub_part');
            if ( (int) U::getIgnoreCase($index_row, 'Non_unique') !== 0 || $sub_part !== null ) {
                $by_name[$index_name]['full_unique'] = false;
            }
        }
        foreach ( $by_name as $info ) {
            $ordered = $info['columns'];
            ksort($ordered);
            if ( $info['full_unique'] && array_values($ordered) === $wanted ) {
                return array(
                    'present' => true,
                    'can_add' => false,
                    'replace' => false,
                    'have' => array(),
                    'index' => $index,
                    'groups' => 0,
                    'examples' => array(),
                    'blocked' => '',
                );
            }
        }
        $have = array();
        if ( isset($by_name[$index]) ) {
            $have = $by_name[$index]['columns'];
            ksort($have);
            $have = array_values($have);
        }
        $count_rows = $PDOX->allRows(
            "SELECT COUNT(*) AS duplicate_groups FROM (
                SELECT 1 AS present
                FROM {$table}
                WHERE lms_issuer IS NOT NULL
                    AND lms_client IS NOT NULL
                    AND deploy_key IS NOT NULL
                GROUP BY lms_issuer, lms_client, deploy_key
                HAVING COUNT(*) > 1
            ) AS duplicate_rows",
            false,
            false
        );
        if ( ! is_array($count_rows) || ! isset($count_rows[0]) ) {
            $empty['blocked'] = 'unique (issuer, client id, deployment id) not added: could not count duplicate values';
            return $empty;
        }
        $groups = (int) U::get($count_rows[0], 'duplicate_groups', 0);
        $examples = array();
        if ( $groups > 0 ) {
            $example_rows = $PDOX->allRows(
                "SELECT MIN(key_id) AS example_id, MAX(key_id) AS id_max, COUNT(*) AS n,
                        MIN(lms_issuer) AS lms_issuer, MIN(lms_client) AS lms_client, MIN(deploy_key) AS deploy_key
                    FROM {$table}
                    WHERE lms_issuer IS NOT NULL
                        AND lms_client IS NOT NULL
                        AND deploy_key IS NOT NULL
                    GROUP BY lms_issuer, lms_client, deploy_key
                    HAVING COUNT(*) > 1
                    ORDER BY n DESC, example_id
                    LIMIT 5",
                false,
                false
            );
            if ( is_array($example_rows) ) $examples = $example_rows;
            $group_word = $groups === 1 ? 'group' : 'groups';
            return array(
                'present' => false,
                'can_add' => false,
                'replace' => count($have) > 0,
                'have' => $have,
                'index' => $index,
                'groups' => $groups,
                'examples' => $examples,
                'blocked' => 'unique (issuer, client id, deployment id) not added: '.$groups.' duplicate '.$group_word,
            );
        }
        return array(
            'present' => false,
            'can_add' => true,
            'replace' => count($have) > 0,
            'have' => $have,
            'index' => $index,
            'groups' => 0,
            'examples' => array(),
            'blocked' => '',
        );
    }

    /**
     * Why a unique cannot be added on this column, or '' when the column is ready.
     *
     * Ready means varchar($width) character set ascii collation ascii_bin, and
     * the requested nullability when $nullable is true or false. A value longer
     * than $width, or a character outside ASCII, is the reason the varchar
     * alter cannot run. The unique waits until that alter has already landed.
     * The phrase has no row ids and no stored values.
     *
     * @param string $table Physical table name
     * @param string $column
     * @param int $width
     * @param bool|null $nullable true requires NULL, false requires NOT NULL, null does not check
     */
    public static function uniqueColumnBlock($table, $column, $width, $nullable = null) {
        global $PDOX;

        $width = (int) $width;
        if ( ! is_object($PDOX)
            || preg_match('/^[A-Za-z0-9_]+$/', (string) $table) !== 1
            || preg_match('/^[A-Za-z0-9_]+$/', (string) $column) !== 1
            || $width < 1 ) {
            return $column.' could not be read';
        }
        $rows = $PDOX->allRows(
            "SELECT DATA_TYPE AS data_type,
                CHARACTER_MAXIMUM_LENGTH AS char_length,
                CHARACTER_SET_NAME AS charset_name,
                COLLATION_NAME AS collation_name,
                IS_NULLABLE AS is_nullable
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table_name
                AND COLUMN_NAME = :column_name",
            array(
                ':table_name' => $table,
                ':column_name' => $column,
            ),
            false
        );
        if ( ! is_array($rows) ) return $column.' could not be read';
        if ( ! isset($rows[0]) ) return $column.' is absent';
        $row = $rows[0];
        $is_nullable = strtoupper((string) U::getIgnoreCase($row, 'is_nullable')) === 'YES';
        $type_ok = strtolower((string) U::getIgnoreCase($row, 'data_type')) === 'varchar'
            && (int) U::getIgnoreCase($row, 'char_length') === $width
            && strtolower((string) U::getIgnoreCase($row, 'charset_name')) === 'ascii'
            && strtolower((string) U::getIgnoreCase($row, 'collation_name')) === 'ascii_bin';
        $null_ok = $nullable === null || ($nullable ? $is_nullable : ! $is_nullable);
        if ( $type_ok && $null_ok ) return '';

        $counts = $PDOX->allRows(
            "SELECT COALESCE(SUM(CASE WHEN CHAR_LENGTH({$column}) > {$width} THEN 1 ELSE 0 END), 0) AS too_long,
                COALESCE(SUM(CASE WHEN {$column} IS NOT NULL AND CHAR_LENGTH({$column}) <> LENGTH({$column}) THEN 1 ELSE 0 END), 0) AS non_ascii
            FROM {$table}",
            false,
            false
        );
        if ( is_array($counts) && isset($counts[0]) ) {
            $too_long = (int) U::get($counts[0], 'too_long', 0);
            $non_ascii = (int) U::get($counts[0], 'non_ascii', 0);
            $bits = array();
            if ( $too_long > 0 ) {
                $bits[] = $too_long.' '.($too_long === 1 ? 'value' : 'values').' longer than '.$width.' characters';
            }
            if ( $non_ascii > 0 ) {
                $bits[] = $non_ascii.' '.($non_ascii === 1 ? 'value' : 'values').' outside ASCII';
            }
            if ( count($bits) > 0 ) return $column.' has '.implode(' and ', $bits);
        }
        if ( ! $type_ok ) {
            $null_sql = $nullable === true ? ' NULL' : ($nullable === false ? ' NOT NULL' : '');
            return $column.' is not varchar('.$width.') character set ascii collation ascii_bin'.$null_sql;
        }
        if ( $nullable === true ) return $column.' does not allow NULL';
        if ( $nullable === false ) return $column.' allows NULL';
        return $column.' is not varchar('.$width.') character set ascii collation ascii_bin';
    }

    /**
     * Schema steps that have not landed. Empty when every step has.
     *
     * A unique is listed only as waiting while its column is not yet the
     * ascii varchar. No row ids and no stored values. The next upgrade tries
     * the unique after the column step has succeeded.
     *
     * @return array<int, string>
     */
    public static function pendingUpgradeNotes() {
        global $CFG, $PDOX;

        if ( ! is_object($PDOX) ) return array('schema progress check failed');
        $prefix = isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
        try {
            $notes = array();
            foreach ( self::asciiColumnMigrations() as $column ) {
                $table = $column[0];
                $field = $column[1];
                $width = (int) $column[2];
                $needs_dynamic = ! empty($column[3]);
                $physical = $prefix.$table;
                $block = self::uniqueColumnBlock($physical, $field, $width, null);
                if ( $block === '' ) continue;
                if ( $needs_dynamic && str_starts_with($block, $field.' is not varchar(') ) {
                    $format = self::tableRowFormat($physical);
                    if ( $format !== 'dynamic' ) {
                        $shown = $format === '' ? 'unknown' : $format;
                        $notes[] = $table.'.'.$field.' left unchanged: row format is '.$shown;
                        continue;
                    }
                }
                $notes[] = $table.'.'.$field.' left unchanged: '.$block;
            }
            foreach ( self::uniqueUpgradeSteps() as $step ) {
                $note = self::uniqueUpgradeNote($prefix, $step);
                if ( $note !== '' ) $notes[] = $note;
            }
            return $notes;
        } catch (\Throwable $e) {
            return array('schema progress check failed');
        }
    }

    /**
     * @return array<int, array{0:string,1:string,2:int,3:bool}>
     */
    private static function asciiColumnMigrations() {
        $columns = array();
        $seen = array();
        foreach ( self::identityAsciiColumns() as $column ) {
            $name = $column[0].'.'.$column[1];
            $seen[$name] = true;
            $columns[] = array($column[0], $column[1], 255, false);
        }
        foreach ( self::naturalKeyAsciiColumns() as $column ) {
            $name = $column[0].'.'.$column[1];
            if ( isset($seen[$name]) ) continue;
            $columns[] = array($column[0], $column[1], (int) $column[3], true);
        }
        return $columns;
    }

    /**
     * @return array<int, array{table:string, phrase:string, group:array<int, string>, columns:array<int, array{0:string,1:int,2:bool|null}>, dynamic:bool}>
     */
    private static function uniqueUpgradeSteps() {
        return array(
            array(
                'table' => 'lti_key',
                'phrase' => 'unique (key_key)',
                'group' => array('key_key'),
                'columns' => array(array('key_key', 255, null)),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_key',
                'phrase' => 'unique (issuer, client id, deployment id)',
                'group' => array('lms_issuer', 'lms_client', 'deploy_key'),
                'columns' => array(
                    array('lms_issuer', 255, true),
                    array('lms_client', 255, true),
                    array('deploy_key', 255, true),
                ),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_user',
                'phrase' => 'unique (key_id, user_key)',
                'group' => array('key_id', 'user_key'),
                'columns' => array(array('user_key', 512, true)),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_user',
                'phrase' => 'unique (key_id, subject_key)',
                'group' => array('key_id', 'subject_key'),
                'columns' => array(array('subject_key', 255, true)),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_service',
                'phrase' => 'unique (key_id, service_key)',
                'group' => array('key_id', 'service_key'),
                'columns' => array(array('service_key', 1024, false)),
                'dynamic' => true,
            ),
            array(
                'table' => 'lti_context',
                'phrase' => 'unique (key_id, context_key)',
                'group' => array('key_id', 'context_key'),
                'columns' => array(array('context_key', 255, false)),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_link',
                'phrase' => 'unique (link_key, context_id)',
                'group' => array('link_key', 'context_id'),
                'columns' => array(array('link_key', 255, false)),
                'dynamic' => false,
            ),
            array(
                'table' => 'lti_content',
                'phrase' => 'unique (context_id, resource_link_id)',
                'group' => array('context_id', 'resource_link_id'),
                'columns' => array(array('resource_link_id', 255, false)),
                'dynamic' => false,
            ),
            array(
                'table' => 'profile',
                'phrase' => 'unique (profile_key)',
                'group' => array('profile_key'),
                'columns' => array(array('profile_key', 512, false)),
                'dynamic' => false,
            ),
        );
    }

    /**
     * @param array{table:string, phrase:string, group:array<int, string>, columns:array<int, array{0:string,1:int,2:bool|null}>, dynamic:bool} $step
     */
    private static function uniqueUpgradeNote($prefix, $step) {
        $physical = $prefix.$step['table'];
        $phrase = $step['phrase'];
        $present = self::fullUniquePresent($physical, $step['group']);
        if ( $present === true ) return '';
        $blocks = array();
        foreach ( $step['columns'] as $column ) {
            $block = self::uniqueColumnBlock($physical, $column[0], (int) $column[1], $column[2]);
            if ( $block !== '' ) $blocks[] = $block;
        }
        if ( count($blocks) > 0 ) return $phrase.' not added: '.implode('; ', $blocks);
        if ( $present === null ) return $phrase.' not added: could not read indexes';
        $groups = self::duplicateGroupCount($physical, $step['group']);
        if ( $groups === null ) return $phrase.' not added: could not count duplicate values';
        if ( $groups > 0 ) {
            $word = $groups === 1 ? 'group' : 'groups';
            return $phrase.' not added: '.$groups.' duplicate '.$word;
        }
        if ( ! empty($step['dynamic']) ) {
            $format = self::tableRowFormat($physical);
            if ( $format !== 'dynamic' ) {
                $shown = $format === '' ? 'unknown' : $format;
                return $phrase.' not added: row format is '.$shown;
            }
        }
        return $phrase.' not added';
    }

    /**
     * @param array<int, string> $wanted
     * @return bool|null null when the indexes cannot be read
     */
    private static function fullUniquePresent($physical, $wanted) {
        global $PDOX;

        if ( ! is_object($PDOX) ) return null;
        $detail = $PDOX->indexDetail($physical);
        if ( ! is_array($detail) ) return null;
        $by_name = array();
        foreach ( $detail as $index_row ) {
            $index_name = (string) U::getIgnoreCase($index_row, 'Key_name');
            if ( $index_name === '' || strcasecmp($index_name, 'PRIMARY') === 0 ) continue;
            $seq = (int) U::getIgnoreCase($index_row, 'Seq_in_index');
            if ( ! isset($by_name[$index_name]) ) {
                $by_name[$index_name] = array(
                    'columns' => array(),
                    'full_unique' => true,
                );
            }
            $by_name[$index_name]['columns'][$seq] = strtolower((string) U::getIgnoreCase($index_row, 'Column_name'));
            $sub_part = U::getIgnoreCase($index_row, 'Sub_part');
            if ( (int) U::getIgnoreCase($index_row, 'Non_unique') !== 0 || $sub_part !== null ) {
                $by_name[$index_name]['full_unique'] = false;
            }
        }
        $expect = array();
        foreach ( $wanted as $column ) $expect[] = strtolower((string) $column);
        foreach ( $by_name as $info ) {
            $ordered = $info['columns'];
            ksort($ordered);
            if ( $info['full_unique'] && array_values($ordered) === $expect ) return true;
        }
        return false;
    }

    /**
     * @param array<int, string> $columns
     * @return int|null
     */
    private static function duplicateGroupCount($physical, $columns) {
        global $PDOX;

        if ( preg_match('/^[A-Za-z0-9_]+$/', (string) $physical) !== 1 ) return null;
        $where = array();
        foreach ( $columns as $column ) {
            if ( preg_match('/^[A-Za-z0-9_]+$/', (string) $column) !== 1 ) return null;
            $where[] = $column.' IS NOT NULL';
        }
        if ( count($where) < 1 ) return null;
        $rows = $PDOX->allRows(
            "SELECT COUNT(*) AS duplicate_groups FROM (
                SELECT 1 AS present
                FROM {$physical}
                WHERE ".implode(' AND ', $where)."
                GROUP BY ".implode(', ', $columns)."
                HAVING COUNT(*) > 1
            ) AS duplicate_rows",
            false,
            false
        );
        if ( ! is_array($rows) || ! isset($rows[0]) ) return null;
        return (int) U::get($rows[0], 'duplicate_groups', 0);
    }

    /**
     * @return string
     */
    private static function tableRowFormat($physical) {
        global $PDOX;

        $rows = $PDOX->allRows(
            "SELECT ROW_FORMAT AS row_format
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = :table_name",
            array(':table_name' => $physical),
            false
        );
        if ( ! is_array($rows) || ! isset($rows[0]) ) return '';
        return strtolower(trim((string) U::getIgnoreCase($rows[0], 'row_format')));
    }

    /**
     * Red warning for the admin console and the key list. Empty when URLs agree.
     *
     * Same lti_key scan shape as the duplicate notice. Not a per-launch query.
     */
    public static function clientConfigConflictNotice() {
        $count = count(self::clientConfigConflictGroups());
        if ( $count < 1 ) {
            return '';
        }
        $pairs = $count === 1 ? 'pair disagrees' : 'pairs disagree';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>'.$count.' issuer and client id '.$pairs.' on configuration URLs.</p>'."\n"
            ."</div>\n";
    }

    /**
     * Red warning for the admin console and the key list. Empty when each key
     * has a single deployment id.
     *
     * Reads lti_key and the contexts that stored a deployment id. Admin only.
     */
    public static function keyDeploymentConflictNotice() {
        $count = count(self::keyDeploymentConflictGroups());
        if ( $count < 1 ) {
            return '';
        }
        $verb = $count === 1 ? 'key has' : 'keys have';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>'.$count.' '.$verb.' more than one deployment id.</p>'."\n"
            ."</div>\n";
    }

    /**
     * Red warning for the admin console and the key list. Empty when every
     * issuer, client id, deployment id, context id, resource link id,
     * consumer key, and secret fits in varchar(255).
     *
     * Same admin-only scan as the other Client ID Check notices.
     */
    public static function valuesLongerThan255Notice() {
        $count = count(self::valuesLongerThan255());
        if ( $count < 1 ) {
            return '';
        }
        $verb = $count === 1 ? 'value is' : 'values are';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>'.$count.' '.$verb.' longer than 255 characters.</p>'."\n"
            ."</div>\n";
    }

    /**
     * Red warning for the admin console and the key list. Empty when every
     * checked identifier is ASCII.
     *
     * Same columns and same admin-only scan as the varchar(255) length notice.
     */
    public static function nonAsciiValuesNotice() {
        $count = count(self::nonAsciiValues());
        if ( $count < 1 ) {
            return '';
        }
        $verb = $count === 1 ? 'value contains' : 'values contain';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>'.$count.' '.$verb.' a character outside ASCII.</p>'."\n"
            ."</div>\n";
    }

    /**
     * Rows that share an LTI 1.1 consumer key. NULL stays out. ascii_bin
     * treats a trailing space as the same key, matching UNIQUE(key_key).
     * A soft-deleted row counts, because that unique includes it.
     *
     * The cache is set only after the query returns.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function duplicateLti11KeyRows() {
        global $PDOX, $CFG;

        if ( is_array(self::$duplicateLti11KeyRows) ) {
            return self::$duplicateLti11KeyRows;
        }
        $table = "{$CFG->dbprefix}lti_key";
        if ( ! is_object($PDOX) ) {
            return array();
        }
        $names = self::ltiKeyColumnNames($table);
        if ( ! in_array('key_key', $names, true) ) {
            return array();
        }
        $deleted_sql = in_array('deleted', $names, true) ? 'k.deleted' : '0';
        $title_sql = in_array('key_title', $names, true) ? 'k.key_title' : 'NULL';
        $rows = self::diagnosticRows(
            "SELECT k.key_id, {$title_sql} AS key_title, k.key_key, {$deleted_sql} AS deleted
                FROM {$table} AS k
                INNER JOIN (
                    SELECT key_key
                        FROM {$table}
                        WHERE key_key IS NOT NULL
                        GROUP BY key_key
                        HAVING COUNT(*) > 1
                ) AS d ON k.key_key = d.key_key
                ORDER BY k.key_key, k.key_id"
        );
        self::$duplicateLti11KeyRows = $rows;
        return self::$duplicateLti11KeyRows;
    }

    /**
     * How many distinct LTI 1.1 consumer keys are stored on more than one row.
     * A trailing space does not make a second key.
     */
    public static function duplicateLti11KeyGroupCount() {
        $groups = array();
        foreach ( self::duplicateLti11KeyRows() as $row ) {
            $key = (string) U::get($row, 'key_key', '');
            $key = preg_replace('/ *$/', '', $key);
            $groups[$key] = true;
        }
        return count($groups);
    }

    /**
     * Red warning for the admin console and the key list. Empty when each
     * consumer key is stored once.
     */
    public static function duplicateLti11KeyNotice() {
        $count = self::duplicateLti11KeyGroupCount();
        if ( $count < 1 ) {
            return '';
        }
        $keys = $count === 1 ? 'key is' : 'keys are';
        return '<div class="alert alert-danger" style="margin: 10px;">'."\n"
            .'<p>'.$count.' LTI 1.1 consumer '.$keys.' stored on more than one row.</p>'."\n"
            ."</div>\n";
    }

    /**
     * The client id warnings, or one warning when a check cannot run.
     */
    public static function clientIdCheckNotices() {
        try {
            return self::issuerClientDuplicateNotice()
                .self::duplicateLti11KeyNotice()
                .self::clientConfigConflictNotice()
                .self::keyDeploymentConflictNotice()
                .self::valuesLongerThan255Notice()
                .self::nonAsciiValuesNotice();
        } catch (\Throwable $e) {
            error_log('Client id check failed: '.$e->getMessage());
            return '<div class="alert alert-warning" style="margin: 10px;">'."\n"
                .'<p>Client id check failed.</p>'."\n"
                ."</div>\n";
        }
    }

    /**
     * URL columns compared across keys that share an issuer and client id.
     *
     * @return array<string, string> column => label
     */
    public static function clientConfigColumns() {
        return array(
            'lms_oidc_auth' => 'OIDC auth URL',
            'lms_keyset_url' => 'Keyset URL',
            'lms_token_url' => 'Token URL',
            'lms_token_audience' => 'Token audience',
        );
    }

    /**
     * Value used to decide whether two configuration URLs are the same.
     *
     * Scheme and host are case-insensitive. The path, query, and fragment are
     * compared as stored, so a path that differs only by case still conflicts.
     */
    private static function clientConfigCompareValue($value) {
        $value = trim((string) $value);
        if ( ! preg_match('#^([a-z][a-z0-9+.-]*:)(//[^/?\#]*)#i', $value, $match) ) {
            return $value;
        }
        $scheme = strtolower($match[1]);
        $authority = $match[2];
        $rest = substr($value, strlen($match[0]));
        if ( preg_match('#^(//(?:[^@/]*@)?)(\[[^\]]+\]|[^:/]*)(:[0-9]+)?$#', $authority, $parts) ) {
            $authority = $parts[1].strtolower($parts[2]).(isset($parts[3]) ? $parts[3] : '');
        }
        return $scheme.$authority.$rest;
    }

    /**
     * Keys that share an issuer and client id but disagree on URL configuration.
     *
     * A blank value is ignored. Two different non-blank values for one column
     * disagree. Scheme and host case does not. Path, query, and fragment case does.
     *
     * @return array<int, array{lms_issuer:string, lms_client:string, fields:array<string, string>, keys:array<int, array<string, mixed>>}>
     */
    public static function clientConfigConflictGroups() {
        global $PDOX, $CFG;

        $empty = array();
        $table = "{$CFG->dbprefix}lti_key";
        if ( ! is_object($PDOX) ) {
            return $empty;
        }
        $names = self::ltiKeyColumnNames($table);
        if ( ! in_array('lms_issuer', $names, true) || ! in_array('lms_client', $names, true) ) {
            return $empty;
        }

        $present = array();
        foreach ( self::clientConfigColumns() as $column => $label ) {
            if ( in_array($column, $names, true) ) $present[$column] = $label;
        }
        if ( count($present) < 1 ) {
            return $empty;
        }

        $having = array();
        $select = array();
        foreach ( $present as $column => $label ) {
            // The column collation folds case, including the path. Compare the
            // bytes here so a path that differs only by case still reaches PHP.
            $having[] = "COUNT(DISTINCT CAST(NULLIF(TRIM({$column}), '') AS BINARY)) > 1";
            $select[] = "NULLIF(TRIM(k.{$column}), '') AS {$column}";
        }
        $deleted_sql = in_array('deleted', $names, true) ? 'k.deleted' : '0';
        $rows = self::diagnosticRows(
            "SELECT k.key_id, k.key_title, {$deleted_sql} AS deleted,
                    TRIM(k.lms_issuer) AS lms_issuer, TRIM(k.lms_client) AS lms_client,
                    ".implode(",\n                    ", $select)."
                FROM {$table} AS k
                INNER JOIN (
                    SELECT TRIM(lms_issuer) AS lms_issuer, TRIM(lms_client) AS lms_client
                        FROM {$table}
                        WHERE lms_issuer IS NOT NULL AND TRIM(lms_issuer) <> ''
                            AND lms_client IS NOT NULL AND TRIM(lms_client) <> ''
                        GROUP BY TRIM(lms_issuer), TRIM(lms_client)
                        HAVING ".implode("\n                            OR ", $having)."
                ) AS d
                    ON TRIM(k.lms_issuer) = d.lms_issuer
                    AND TRIM(k.lms_client) = d.lms_client
                ORDER BY TRIM(k.lms_issuer), TRIM(k.lms_client), k.key_id"
        );
        if ( ! is_array($rows) || count($rows) < 1 ) {
            return $empty;
        }

        $grouped = array();
        foreach ( $rows as $row ) {
            $issuer = trim((string) U::get($row, 'lms_issuer', ''));
            $client = trim((string) U::get($row, 'lms_client', ''));
            $bucket = $issuer."\n".$client;
            if ( ! isset($grouped[$bucket]) ) {
                $grouped[$bucket] = array(
                    'lms_issuer' => $issuer,
                    'lms_client' => $client,
                    'keys' => array(),
                );
            }
            $grouped[$bucket]['keys'][] = $row;
        }

        $groups = array();
        foreach ( $grouped as $group ) {
            $fields = array();
            foreach ( $present as $column => $label ) {
                $values = array();
                foreach ( $group['keys'] as $keyrow ) {
                    $value = U::get($keyrow, $column);
                    if ( ! is_string($value) ) continue;
                    $value = trim($value);
                    if ( $value === '' ) continue;
                    $values[self::clientConfigCompareValue($value)] = true;
                }
                if ( count($values) > 1 ) $fields[$column] = $label;
            }
            if ( count($fields) < 1 ) continue;
            $group['fields'] = $fields;
            $groups[] = $group;
        }
        return $groups;
    }

    /**
     * Tables that store an LTI deployment id which should be a function of key_id.
     *
     * lti_link has no deployment id column. lti_tool_deployment.deployment_id is
     * an outbound tool id. One key may have many.
     *
     * @return array<int, array{table:string, column:string, id_column:string, label:string}>
     */
    private static function deploymentIdCopies() {
        return array(
            array(
                'table' => 'lti_key',
                'column' => 'deploy_key',
                'id_column' => 'key_id',
                'label' => 'Key',
            ),
            array(
                'table' => 'lti_context',
                'column' => 'lti13_deployment_id',
                'id_column' => 'context_id',
                'label' => 'Context',
            ),
        );
    }

    /**
     * Keys whose deployment id is not a single value.
     *
     * The key's own deployment id and every copy on a context are read together.
     * A blank value, or the literal
     * word "null" in any ASCII case, does not count. That matches how the key
     * form stores a blank deployment id. Every other value is compared as
     * stored bytes, so a difference in case or accents is a conflict. Deleted
     * rows are included.
     *
     * @return array<int, array{key_id:int, key_title:string, deleted:int, values:array<int, array{deployment_id:string, places:array<int, array{label:string, count:int, example_id:int}>}>}>
     */
    public static function keyDeploymentConflictGroups() {
        global $PDOX, $CFG;

        $empty = array();
        if ( ! is_object($PDOX) ) {
            return $empty;
        }
        $prefix = $CFG->dbprefix;
        $key_table = "{$prefix}lti_key";
        $key_names = self::ltiKeyColumnNames($key_table);
        $deleted_sql = in_array('deleted', $key_names, true) ? 'MIN(k.deleted)' : '0';
        $title_sql = in_array('key_title', $key_names, true) ? 'MIN(k.key_title)' : "''";

        $found_rows = array();
        foreach ( self::deploymentIdCopies() as $copy ) {
            $table = "{$prefix}{$copy['table']}";
            if ( $copy['table'] === 'lti_key' ) {
                $names = $key_names;
            } else {
                $described = $PDOX->describe($table);
                if ( ! is_array($described) ) continue;
                $names = array();
                foreach ( $described as $column ) {
                    if ( is_array($column) && isset($column['Field']) ) $names[] = $column['Field'];
                }
            }
            if ( ! in_array($copy['column'], $names, true) || ! in_array($copy['id_column'], $names, true) ) {
                continue;
            }
            if ( ! in_array('key_id', $names, true) ) {
                continue;
            }

            $expr = 't.'.$copy['column'];
            $label_sql = str_replace("'", "''", $copy['label']);
            $sql = "SELECT t.key_id AS key_id,
                           {$title_sql} AS key_title,
                           {$deleted_sql} AS deleted,
                           '{$label_sql}' AS source,
                           MIN(TRIM({$expr})) AS deployment_id,
                           COUNT(*) AS row_count,
                           MIN(t.{$copy['id_column']}) AS example_id
                        FROM {$table} AS t
                        INNER JOIN {$key_table} AS k ON k.key_id = t.key_id
                        WHERE {$expr} IS NOT NULL
                            AND TRIM({$expr}) <> ''
                            -- The word null, in any ASCII case, is a blank. Compare
                            -- those bytes so the column collation cannot fold an
                            -- accented value into that word. The GROUP BY below
                            -- keeps every other difference, including case.
                            AND CAST(LOWER(TRIM({$expr})) AS BINARY) <> 'null'
                        GROUP BY t.key_id, CAST(TRIM({$expr}) AS BINARY)";
            $found = self::diagnosticRows($sql);
            foreach ( $found as $row ) $found_rows[] = $row;
        }

        $by_key = array();
        foreach ( $found_rows as $row ) {
            $key_id = (int) U::get($row, 'key_id', 0);
            if ( $key_id < 1 ) continue;
            $raw = U::get($row, 'deployment_id');
            $deployment = self::normalize_deploy_key_input(is_scalar($raw) ? (string) $raw : null);
            if ( $deployment === null ) continue;
            if ( ! isset($by_key[$key_id]) ) {
                $title = U::get($row, 'key_title');
                $by_key[$key_id] = array(
                    'key_id' => $key_id,
                    'key_title' => is_string($title) ? $title : '',
                    'deleted' => (int) U::get($row, 'deleted', 0),
                    'values' => array(),
                );
            }
            if ( ! isset($by_key[$key_id]['values'][$deployment]) ) {
                $by_key[$key_id]['values'][$deployment] = array(
                    'deployment_id' => $deployment,
                    'places' => array(),
                );
            }
            $by_key[$key_id]['values'][$deployment]['places'][] = array(
                'label' => (string) U::get($row, 'source', ''),
                'count' => (int) U::get($row, 'row_count', 0),
                'example_id' => (int) U::get($row, 'example_id', 0),
            );
        }

        ksort($by_key);
        $groups = array();
        foreach ( $by_key as $group ) {
            if ( count($group['values']) < 2 ) continue;
            ksort($group['values']);
            $group['values'] = array_values($group['values']);
            $groups[] = $group;
        }
        return $groups;
    }

    /**
     * Identity columns the upgrade narrows to varchar(255) character set ascii collation ascii_bin.
     *
     * @return array<int, array{0:string,1:string,2:string}>
     */
    public static function identityAsciiColumns() {
        return array(
            array('lti_key', 'key_key', 'key_id'),
            array('lti_key', 'secret', 'key_id'),
            array('lti_key', 'new_secret', 'key_id'),
            array('lti_key', 'deploy_key', 'key_id'),
            array('lti_key', 'lms_issuer', 'key_id'),
            array('lti_key', 'lms_client', 'key_id'),
            array('lti_context', 'context_key', 'context_id'),
            array('lti_context', 'lti13_deployment_id', 'context_id'),
            array('lti_link', 'link_key', 'link_id'),
            array('lti_content', 'resource_link_id', 'content_id'),
            array('lti_domain', 'consumer_key', 'domain_id'),
            array('lti_domain', 'secret', 'domain_id'),
            array('lti_tool_registration', 'lti11_key', 'registration_id'),
            array('lti_tool_registration', 'lti11_secret', 'registration_id'),
            array('lti_tool_registration', 'lti13_client_id', 'registration_id'),
            array('lti_tool_deployment', 'deployment_id', 'tool_deployment_id'),
        );
    }

    /**
     * Natural keys the upgrade narrows to ascii_bin. The fourth value is the varchar length.
     *
     * subject_key is an LTI 1.3 sub, so it matches the other identity columns at 255.
     * user_key and profile_key also store googlemail: plus an email. An email can be
     * 254 characters, so 255 would reject a legal value. 512 covers it.
     * service_key is lis_outcome_service_url. 1024. A COMPACT index holds 767 bytes,
     * and 1024 plus key_id does not fit, so that alter waits until the table is DYNAMIC.
     * context_key, link_key, and resource_link_id are varchar(255) and NOT NULL.
     * string_text stays TEXT. A translation string is not one of these columns.
     *
     * The fifth value is true when the varchar accepts NULL.
     * NULL user_key and NULL subject_key stay allowed.
     *
     * @return array<int, array{0:string,1:string,2:string,3:int,4:bool}>
     */
    public static function naturalKeyAsciiColumns() {
        return array(
            array('lti_user', 'subject_key', 'user_id', 255, true),
            array('lti_user', 'user_key', 'user_id', 512, true),
            array('profile', 'profile_key', 'profile_id', 512, false),
            array('lti_service', 'service_key', 'service_id', 1024, false),
            array('lti_context', 'context_key', 'context_id', 255, false),
            array('lti_link', 'link_key', 'link_id', 255, false),
            array('lti_content', 'resource_link_id', 'content_id', 255, false),
        );
    }

    /**
     * Why this value cannot be stored in a natural-key column, or null when it can.
     *
     * Null is allowed for user_key and subject_key. A leading or trailing space
     * is rejected, and so is a non-printing character, same as an issuer or client id.
     * A space in the middle is kept. The value itself is left out of the message,
     * because user_key and profile_key can be an email address.
     *
     * @param string $column
     * @param mixed $value
     * @return string|null
     */
    public static function naturalKeyProblem($column, $value) {
        $spec = null;
        foreach ( self::naturalKeyAsciiColumns() as $natural ) {
            if ( $natural[1] === $column ) {
                $spec = $natural;
                break;
            }
        }
        if ( $spec === null ) {
            return $column.' is not a natural key column.';
        }
        $width = (int) $spec[3];
        $nullable = ! empty($spec[4]);
        if ( $value === null ) {
            return $nullable ? null : $column.' has no value.';
        }
        if ( ! is_string($value) ) {
            return $column.' is not text.';
        }
        $reasons = array();
        if ( preg_match('/[^\x00-\x7F]/', $value) === 1 ) {
            $reasons[] = 'has a character outside ASCII';
        }
        $length = strlen($value);
        if ( $reasons && function_exists('mb_strlen') ) {
            $counted = mb_strlen($value, 'UTF-8');
            if ( is_int($counted) ) $length = $counted;
        }
        if ( $length > $width ) {
            $reasons[] = 'is '.$length.' characters and the limit is '.$width;
        }
        if ( preg_match('/^ /', $value) === 1 ) {
            $reasons[] = 'has a leading space';
        }
        if ( preg_match('/ $/', $value) === 1 ) {
            $reasons[] = 'has a trailing space';
        }
        if ( preg_match('/[\x00-\x1F\x7F]/', $value) === 1 ) {
            $reasons[] = 'contains a non-printing character';
        }
        if ( count($reasons) < 1 ) return null;
        if ( count($reasons) === 1 ) return $column.' '.$reasons[0].'.';
        $last = array_pop($reasons);
        return $column.' '.implode(', ', $reasons).' and '.$last.'.';
    }

    /**
     * Identity columns that are not yet varchar(255) character set ascii collation ascii_bin.
     * A missing table or column is included. Names are table.column without the database prefix.
     *
     * @return array<int, string>
     */
    public static function columnsNotYetAscii255() {
        global $PDOX, $CFG;

        $wanted = array();
        foreach ( self::identityAsciiColumns() as $column ) {
            $wanted[$column[0].'.'.$column[1]] = true;
        }
        if ( ! is_object($PDOX) ) {
            throw new \RuntimeException('could not read identity column types');
        }
        $prefix = isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
        $clauses = array();
        $params = array();
        $i = 0;
        foreach ( self::identityAsciiColumns() as $column ) {
            $clauses[] = '(TABLE_NAME = :t'.$i.' AND COLUMN_NAME = :c'.$i.')';
            $params[':t'.$i] = $prefix.$column[0];
            $params[':c'.$i] = $column[1];
            $i++;
        }
        $rows = $PDOX->allRows(
            "SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name,
                DATA_TYPE AS data_type, CHARACTER_MAXIMUM_LENGTH AS char_length,
                CHARACTER_SET_NAME AS charset_name, COLLATION_NAME AS collation_name
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND (".implode(' OR ', $clauses).")",
            $params,
            false
        );
        if ( ! is_array($rows) ) {
            throw new \RuntimeException('could not read identity column types');
        }
        $ready = array();
        foreach ( $rows as $row ) {
            $table = (string) U::getIgnoreCase($row, 'table_name');
            $column = (string) U::getIgnoreCase($row, 'column_name');
            if ( $prefix !== '' && str_starts_with($table, $prefix) ) {
                $table = substr($table, strlen($prefix));
            }
            $name = $table.'.'.$column;
            $ok = strtolower((string) U::getIgnoreCase($row, 'data_type')) === 'varchar'
                && (int) U::getIgnoreCase($row, 'char_length') === 255
                && strtolower((string) U::getIgnoreCase($row, 'charset_name')) === 'ascii'
                && strtolower((string) U::getIgnoreCase($row, 'collation_name')) === 'ascii_bin';
            if ( $ok ) $ready[$name] = true;
        }
        $notYet = array();
        foreach ( array_keys($wanted) as $name ) {
            if ( ! isset($ready[$name]) ) $notYet[] = $name;
        }
        return $notYet;
    }

    /**
     * TEXT columns that store an issuer, client id, deployment id, context id,
     * resource link id, LTI 1.1 consumer key, or secret.
     *
     * These are the values a varchar(255) column would have to hold, and the
     * values an ascii column would have to hold. The length check and the
     * non-ASCII check both read this list. Outbound
     * lti_tool_registration.lti13_client_id, lti_tool_registration.lti11_key,
     * and lti_tool_deployment.deployment_id are already varchar(255). lti_link
     * has no deployment id column, and one will not be added. A link's resource
     * link id is link_key. lti_content keeps its own copy. A secret column is marked redact
     * so a check can report the row without returning the secret.
     *
     * @return array<int, array{table:string, column:string, label:string, id_column:string, via_context?:bool, redact?:bool}>
     */
    private static function valuesLongerThan255Copies() {
        return array(
            array(
                'table' => 'lti_key',
                'column' => 'lms_issuer',
                'label' => 'Issuer',
                'id_column' => 'key_id',
            ),
            array(
                'table' => 'lti_key',
                'column' => 'lms_client',
                'label' => 'Client id',
                'id_column' => 'key_id',
            ),
            array(
                'table' => 'lti_key',
                'column' => 'deploy_key',
                'label' => 'Deployment id',
                'id_column' => 'key_id',
            ),
            array(
                'table' => 'lti_context',
                'column' => 'lti13_deployment_id',
                'label' => 'Deployment id',
                'id_column' => 'context_id',
            ),
            array(
                'table' => 'lti_context',
                'column' => 'context_key',
                'label' => 'Context id',
                'id_column' => 'context_id',
            ),
            array(
                'table' => 'lti_link',
                'column' => 'link_key',
                'label' => 'Resource link id',
                'id_column' => 'link_id',
                'via_context' => true,
            ),
            array(
                'table' => 'lti_content',
                'column' => 'resource_link_id',
                'label' => 'Resource link id',
                'id_column' => 'content_id',
            ),
            array(
                'table' => 'lti_key',
                'column' => 'key_key',
                'label' => 'Consumer key',
                'id_column' => 'key_id',
            ),
            array(
                'table' => 'lti_key',
                'column' => 'secret',
                'label' => 'Secret',
                'id_column' => 'key_id',
                'redact' => true,
            ),
            array(
                'table' => 'lti_key',
                'column' => 'new_secret',
                'label' => 'New secret',
                'id_column' => 'key_id',
                'redact' => true,
            ),
            array(
                'table' => 'lti_domain',
                'column' => 'consumer_key',
                'label' => 'Consumer key',
                'id_column' => 'domain_id',
            ),
            array(
                'table' => 'lti_domain',
                'column' => 'secret',
                'label' => 'Secret',
                'id_column' => 'domain_id',
                'redact' => true,
            ),
            array(
                'table' => 'lti_tool_registration',
                'column' => 'lti11_secret',
                'label' => 'Secret',
                'id_column' => 'registration_id',
                'redact' => true,
            ),
        );
    }

    /**
     * Identity values that do not fit in varchar(255).
     *
     * CHAR_LENGTH counts characters, which is the varchar limit. Blank values
     * are short, so they are absent. Deleted rows are included.
     *
     * @return array<int, array{label:string, table:string, column:string, row_id:int, key_id:int, length:int, sample:string, redacted:bool}>
     */
    public static function valuesLongerThan255() {
        global $PDOX, $CFG;

        $empty = array();
        if ( ! is_object($PDOX) ) {
            return $empty;
        }
        $prefix = $CFG->dbprefix;
        $found = array();
        foreach ( self::valuesLongerThan255Copies() as $copy ) {
            $table = "{$prefix}{$copy['table']}";
            $described = $PDOX->describe($table);
            if ( ! is_array($described) ) continue;
            $names = array();
            foreach ( $described as $column ) {
                if ( is_array($column) && isset($column['Field']) ) $names[] = $column['Field'];
            }
            if ( ! in_array($copy['column'], $names, true) || ! in_array($copy['id_column'], $names, true) ) {
                continue;
            }
            if ( ! empty($copy['via_context']) && ! in_array('context_id', $names, true) ) {
                continue;
            }

            $expr = 't.'.$copy['column'];
            $redact = ! empty($copy['redact']);
            $key_sql = in_array('key_id', $names, true) ? 't.key_id' : '0';
            $join = '';
            if ( ! empty($copy['via_context']) ) {
                $key_sql = 'ctx.key_id';
                $join = "INNER JOIN {$prefix}lti_context AS ctx ON ctx.context_id = t.context_id";
            }
            $sample_sql = $redact ? 'NULL AS sample_value' : "SUBSTRING({$expr}, 1, 80) AS sample_value";
            $rows = self::diagnosticRows(
                "SELECT {$key_sql} AS key_id,
                        t.{$copy['id_column']} AS row_id,
                        CHAR_LENGTH({$expr}) AS value_length,
                        {$sample_sql}
                    FROM {$table} AS t
                    {$join}
                    WHERE {$expr} IS NOT NULL
                        AND CHAR_LENGTH({$expr}) > 255
                    ORDER BY CHAR_LENGTH({$expr}) DESC, t.{$copy['id_column']}"
            );
            foreach ( $rows as $row ) {
                $length = (int) U::get($row, 'value_length', 0);
                $sample = $redact ? '' : (string) U::get($row, 'sample_value', '');
                if ( ! $redact && $length > 80 ) $sample .= '...';
                $found[] = array(
                    'label' => $copy['label'],
                    'table' => $copy['table'],
                    'column' => $copy['column'],
                    'row_id' => (int) U::get($row, 'row_id', 0),
                    'key_id' => (int) U::get($row, 'key_id', 0),
                    'length' => $length,
                    'sample' => $sample,
                    'redacted' => $redact,
                );
            }
        }

        usort($found, function ($a, $b) {
            if ( $a['length'] !== $b['length'] ) return $b['length'] - $a['length'];
            if ( $a['key_id'] !== $b['key_id'] ) return $a['key_id'] - $b['key_id'];
            return $a['row_id'] - $b['row_id'];
        });
        return $found;
    }

    /**
     * Identity values that contain a character outside ASCII.
     *
     * These columns are utf8, so a character outside ASCII is more than one
     * byte. LENGTH counts bytes and CHAR_LENGTH counts characters. An ascii
     * column, including ascii_bin, cannot store that character. Blank values
     * are ASCII, so they are absent. Deleted rows are included. A secret is
     * reported by its lengths only.
     *
     * @return array<int, array{label:string, table:string, column:string, row_id:int, key_id:int, length:int, bytes:int, sample:string, redacted:bool}>
     */
    public static function nonAsciiValues() {
        global $PDOX, $CFG;

        $empty = array();
        if ( ! is_object($PDOX) ) {
            return $empty;
        }
        $prefix = $CFG->dbprefix;
        $found = array();
        foreach ( self::valuesLongerThan255Copies() as $copy ) {
            $table = "{$prefix}{$copy['table']}";
            $described = $PDOX->describe($table);
            if ( ! is_array($described) ) continue;
            $names = array();
            foreach ( $described as $column ) {
                if ( is_array($column) && isset($column['Field']) ) $names[] = $column['Field'];
            }
            if ( ! in_array($copy['column'], $names, true) || ! in_array($copy['id_column'], $names, true) ) {
                continue;
            }
            if ( ! empty($copy['via_context']) && ! in_array('context_id', $names, true) ) {
                continue;
            }

            $expr = 't.'.$copy['column'];
            $redact = ! empty($copy['redact']);
            $key_sql = in_array('key_id', $names, true) ? 't.key_id' : '0';
            $join = '';
            if ( ! empty($copy['via_context']) ) {
                $key_sql = 'ctx.key_id';
                $join = "INNER JOIN {$prefix}lti_context AS ctx ON ctx.context_id = t.context_id";
            }
            $sample_sql = $redact ? 'NULL AS sample_value' : "SUBSTRING({$expr}, 1, 255) AS sample_value";
            $rows = self::diagnosticRows(
                "SELECT {$key_sql} AS key_id,
                        t.{$copy['id_column']} AS row_id,
                        CHAR_LENGTH({$expr}) AS value_length,
                        LENGTH({$expr}) AS value_bytes,
                        {$sample_sql}
                    FROM {$table} AS t
                    {$join}
                    WHERE {$expr} IS NOT NULL
                        AND CHAR_LENGTH({$expr}) <> LENGTH({$expr})
                    ORDER BY LENGTH({$expr}) DESC, t.{$copy['id_column']}"
            );
            foreach ( $rows as $row ) {
                $length = (int) U::get($row, 'value_length', 0);
                $bytes = (int) U::get($row, 'value_bytes', 0);
                $sample = '';
                if ( ! $redact ) {
                    $raw = (string) U::get($row, 'sample_value', '');
                    $sample = self::nonAsciiSample($raw, $length > 255);
                }
                $found[] = array(
                    'label' => $copy['label'],
                    'table' => $copy['table'],
                    'column' => $copy['column'],
                    'row_id' => (int) U::get($row, 'row_id', 0),
                    'key_id' => (int) U::get($row, 'key_id', 0),
                    'length' => $length,
                    'bytes' => $bytes,
                    'sample' => $sample,
                    'redacted' => $redact,
                );
            }
        }

        usort($found, function ($a, $b) {
            if ( $a['bytes'] !== $b['bytes'] ) return $b['bytes'] - $a['bytes'];
            if ( $a['key_id'] !== $b['key_id'] ) return $a['key_id'] - $b['key_id'];
            return $a['row_id'] - $b['row_id'];
        });
        return $found;
    }

    /**
     * Identity columns checked for a stored trailing space.
     *
     * The length and non-ASCII lists, plus the wider natural keys and the
     * registration columns that are already varchar. A secret is marked redact.
     *
     * @return array<int, array{table:string, column:string, label:string, id_column:string, via_context?:bool, redact?:bool}>
     */
    private static function identityFieldCopies() {
        $copies = self::valuesLongerThan255Copies();
        $extra = array(
            array(
                'table' => 'lti_user',
                'column' => 'user_key',
                'label' => 'User id',
                'id_column' => 'user_id',
            ),
            array(
                'table' => 'lti_user',
                'column' => 'subject_key',
                'label' => 'Subject',
                'id_column' => 'user_id',
            ),
            array(
                'table' => 'profile',
                'column' => 'profile_key',
                'label' => 'Profile',
                'id_column' => 'profile_id',
            ),
            array(
                'table' => 'lti_service',
                'column' => 'service_key',
                'label' => 'Service',
                'id_column' => 'service_id',
            ),
            array(
                'table' => 'lti_tool_registration',
                'column' => 'lti11_key',
                'label' => 'Consumer key',
                'id_column' => 'registration_id',
            ),
            array(
                'table' => 'lti_tool_registration',
                'column' => 'lti13_client_id',
                'label' => 'Client id',
                'id_column' => 'registration_id',
            ),
            array(
                'table' => 'lti_tool_deployment',
                'column' => 'deployment_id',
                'label' => 'Deployment id',
                'id_column' => 'tool_deployment_id',
            ),
        );
        foreach ( $extra as $copy ) $copies[] = $copy;
        return $copies;
    }

    /**
     * Identity values with a trailing space stored in the column.
     *
     * ascii_bin treats a trailing space as equal, so a comparison with RTRIM
     * does not see it. CHAR_LENGTH counts the stored characters. Blank values
     * have no trailing space. Deleted rows are included. A secret is reported
     * by its lengths only.
     *
     * @return array<int, array{label:string, table:string, column:string, row_id:int, key_id:int, length:int, trailing:int, redacted:bool}>
     */
    public static function trailingSpaceValues() {
        global $PDOX, $CFG;

        $empty = array();
        if ( ! is_object($PDOX) ) {
            return $empty;
        }
        $prefix = $CFG->dbprefix;
        $found = array();
        foreach ( self::identityFieldCopies() as $copy ) {
            $table = "{$prefix}{$copy['table']}";
            $described = $PDOX->describe($table);
            if ( ! is_array($described) ) continue;
            $names = array();
            foreach ( $described as $column ) {
                if ( is_array($column) && isset($column['Field']) ) $names[] = $column['Field'];
            }
            if ( ! in_array($copy['column'], $names, true) || ! in_array($copy['id_column'], $names, true) ) {
                continue;
            }
            if ( ! empty($copy['via_context']) && ! in_array('context_id', $names, true) ) {
                continue;
            }

            $expr = 't.'.$copy['column'];
            $redact = ! empty($copy['redact']);
            $key_sql = in_array('key_id', $names, true) ? 't.key_id' : '0';
            $join = '';
            if ( ! empty($copy['via_context']) ) {
                $key_sql = 'ctx.key_id';
                $join = "INNER JOIN {$prefix}lti_context AS ctx ON ctx.context_id = t.context_id";
            }
            $rows = self::diagnosticRows(
                "SELECT {$key_sql} AS key_id,
                        t.{$copy['id_column']} AS row_id,
                        CHAR_LENGTH({$expr}) AS value_length,
                        CHAR_LENGTH({$expr}) - CHAR_LENGTH(RTRIM({$expr})) AS trailing_spaces
                    FROM {$table} AS t
                    {$join}
                    WHERE {$expr} IS NOT NULL
                        AND CHAR_LENGTH({$expr}) <> CHAR_LENGTH(RTRIM({$expr}))
                    ORDER BY trailing_spaces DESC, t.{$copy['id_column']}"
            );
            foreach ( $rows as $row ) {
                $found[] = array(
                    'label' => $copy['label'],
                    'table' => $copy['table'],
                    'column' => $copy['column'],
                    'row_id' => (int) U::get($row, 'row_id', 0),
                    'key_id' => (int) U::get($row, 'key_id', 0),
                    'length' => (int) U::get($row, 'value_length', 0),
                    'trailing' => (int) U::get($row, 'trailing_spaces', 0),
                    'redacted' => $redact,
                );
            }
        }

        usort($found, function ($a, $b) {
            if ( $a['trailing'] !== $b['trailing'] ) return $b['trailing'] - $a['trailing'];
            if ( $a['key_id'] !== $b['key_id'] ) return $a['key_id'] - $b['key_id'];
            return $a['row_id'] - $b['row_id'];
        });
        return $found;
    }

    /**
     * A short window of a non-secret value, centered on the first character
     * outside ASCII when that character is in the fetched prefix.
     */
    private static function nonAsciiSample($raw, $truncated) {
        $raw = (string) $raw;
        $at_byte = null;
        if ( $raw !== '' && preg_match('/[^\x00-\x7F]/u', $raw, $match, PREG_OFFSET_CAPTURE) ) {
            $at_byte = $match[0][1];
        }
        if ( $at_byte === null ) {
            $sample = mb_substr($raw, 0, 80);
            if ( $truncated || mb_strlen($raw) > 80 ) $sample .= '...';
            return $sample;
        }
        $char_at = mb_strlen(substr($raw, 0, $at_byte));
        $start = max(0, $char_at - 20);
        $sample = mb_substr($raw, $start, 60);
        if ( $start > 0 ) $sample = '...'.$sample;
        if ( $truncated || mb_strlen($raw) > $start + 60 ) $sample .= '...';
        return $sample;
    }

    /**
     * Reject a new or updated tenant when another live key already has this
     * issuer, client id, and deployment id.
     *
     * A blank deployment id is its own value. Two blank deployment ids for the
     * same issuer and client id collide. A specific deployment id collides
     * only with that same deployment id.
     */
    public static function validate_issuer_client_unique($lms_issuer, $lms_client, $deploy_key, $exclude_key_id=null) {
        global $PDOX, $CFG;

        $pair = self::resolve_lti13_issuer_client($lms_issuer, $lms_client);
        if ( $pair === null ) {
            return true;
        }

        $deploy = self::normalize_deploy_key_input($deploy_key);
        $parms = array(
            ':issuer_sha256' => $pair['issuer_sha256'],
            ':issuer_client' => $pair['issuer_client'],
        );
        $exclude_sql = '';
        if ( ! empty($exclude_key_id) && (int) $exclude_key_id > 0 ) {
            $exclude_sql = ' AND k.key_id <> :exclude_key_id ';
            $parms[':exclude_key_id'] = (int) $exclude_key_id;
        }
        if ( $deploy === null ) {
            $deploy_sql = "AND (k.deploy_key IS NULL OR TRIM(k.deploy_key) = '' OR LOWER(TRIM(k.deploy_key)) = 'null')";
        } else {
            $deploy_sql = 'AND TRIM(k.deploy_key) = :deploy_key';
            $parms[':deploy_key'] = $deploy;
        }

        $row = $PDOX->rowDie(
            "SELECT k.key_id, k.key_title FROM {$CFG->dbprefix}lti_key AS k
                WHERE (k.deleted IS NULL OR k.deleted = 0)
                    $exclude_sql
                    AND k.lms_issuer_sha256 = :issuer_sha256
                    AND k.lms_client = :issuer_client
                    $deploy_sql
                LIMIT 1",
            $parms
        );

        if ( $row ) {
            $label = U::isNotEmpty($row['key_title']) ? $row['key_title'] : ('key_id '.$row['key_id']);
            if ( $deploy === null ) {
                U::flashError('A tenant/key with this LTI 1.3 issuer, client id, and a blank deployment id already exists ('.$label.')');
            } else {
                U::flashError('A tenant/key with this LTI 1.3 issuer, client id, and deployment id already exists ('.$label.')');
            }
            return false;
        }

        return true;
    }

    /**
     * Hand-entered inbound clients for the key admin screens.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function inboundClientChoices() {
        global $PDOX, $CFG;
        if ( ! \Tsugi\Core\LTIX::ltiClientTableExists($CFG->dbprefix) ) return array();
        $rows = $PDOX->allRowsDie(
            "SELECT lti_client_id, title, issuer, client_id
                FROM {$CFG->dbprefix}lti_client
                ORDER BY title, issuer, client_id, lti_client_id"
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param mixed $id
     * @return array<string, mixed>|null
     */
    public static function inboundClientById($id) {
        global $PDOX, $CFG;
        $id = (int) $id;
        if ( $id < 1 || ! \Tsugi\Core\LTIX::ltiClientTableExists($CFG->dbprefix) ) return null;
        $row = $PDOX->rowDie(
            "SELECT lti_client_id, title, user_id, notes, issuer, client_id, oidc_auth_url, keyset_url,
                    token_url, token_audience, unlock_code
                FROM {$CFG->dbprefix}lti_client
                WHERE lti_client_id = :id",
            array(':id' => $id)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * Keys that point at one inbound client.
     *
     * @param mixed $id
     * @return array<int, array<string, mixed>>
     */
    public static function keysForInboundClient($id) {
        global $PDOX, $CFG;
        $id = (int) $id;
        if ( $id < 1 || ! $PDOX->columnExists('lti_client_id', $CFG->dbprefix.'lti_key') ) return array();
        $rows = $PDOX->allRowsDie(
            "SELECT key_id, key_title, key_key, secret, lms_issuer, lms_client,
                    lms_oidc_auth, lms_keyset_url, lms_token_url, deploy_key, lti_client_id
                FROM {$CFG->dbprefix}lti_key
                WHERE lti_client_id = :id
                ORDER BY key_id",
            array(':id' => $id)
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Clients with this key's issuer, client id, and platform URLs.
     *
     * @param array<string, mixed> $key
     * @return array<int, array<string, mixed>>
     */
    public static function clientsMatchingKeyPlatform(array $key) {
        global $PDOX, $CFG;
        if ( ! \Tsugi\Core\LTIX::ltiClientTableExists($CFG->dbprefix) ) return array();
        $issuer = self::trimToNull(U::get($key, 'lms_issuer'));
        $client = self::trimToNull(U::get($key, 'lms_client'));
        if ( $issuer === null || $client === null ) return array();
        $rows = $PDOX->allRowsDie(
            "SELECT lti_client_id, title, issuer, client_id, oidc_auth_url, keyset_url, token_url
                FROM {$CFG->dbprefix}lti_client
                WHERE issuer = :issuer AND client_id = :client
                    AND oidc_auth_url <=> :oidc
                    AND keyset_url <=> :keyset
                    AND token_url <=> :token",
            array(
                ':issuer' => $issuer,
                ':client' => $client,
                ':oidc' => self::trimToNull(U::get($key, 'lms_oidc_auth')),
                ':keyset' => self::trimToNull(U::get($key, 'lms_keyset_url')),
                ':token' => self::trimToNull(U::get($key, 'lms_token_url')),
            )
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * Note when a saved key already lines up with a client.
     *
     * @param array<string, mixed> $key
     */
    public static function matchingClientNote(array $key) {
        $matches = self::clientsMatchingKeyPlatform($key);
        if ( count($matches) < 1 ) return '';
        $links = array();
        foreach ( $matches as $match ) {
            $id = (int) U::get($match, 'lti_client_id');
            $href = self::keyAdminUrl('client-detail?lti_client_id='.$id);
            $label = htmlspecialchars(self::inboundClientLabel($match), ENT_QUOTES, 'UTF-8');
            $links[] = '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">'.$label.'</a>';
        }
        $linked = (int) U::get($key, 'lti_client_id');
        $same = count($matches) === 1 && $linked > 0 && (int) U::get($matches[0], 'lti_client_id') === $linked;
        if ( $same ) {
            $text = 'This client matches this key\'s issuer, client id, and platform URLs: ';
        } else if ( count($matches) === 1 ) {
            $text = 'A client already matches this key\'s issuer, client id, and platform URLs: ';
        } else {
            $text = 'These clients match this key\'s issuer, client id, and platform URLs: ';
        }
        return '<p>'.$text.implode(', ', $links).'.</p>'."\n";
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function inboundClientLabel(array $row) {
        $issuer = (string) U::get($row, 'issuer', '');
        $client = U::get($row, 'client_id');
        $title = self::trimToNull(U::get($row, 'title'));
        $client_label = U::isNotEmpty($client) ? (string) $client : '(no client id)';
        $parts = array();
        if ( $title !== null ) $parts[] = $title;
        if ( $issuer !== '' ) $parts[] = $issuer;
        $parts[] = $client_label;
        return implode(' / ', $parts);
    }

    /**
     * Platform columns copied onto a key. Emptying a client link clears these.
     *
     * @return array<int, string>
     */
    public static function keyPlatformFields() {
        return array('lms_issuer', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience');
    }

    /**
     * Shown under the client control.
     *
     * @param bool $edit
     * @param int $selectedId
     */
    private static function clientPlatformFieldsNote($edit, $selectedId = 0) {
        $html = '';
        if ( ! $edit ) {
            $html .= "<p>The issuer, client id, and platform URLs below stay on this key unless you empty them. A blank issuer matches any issuer.</p>\n";
        }
        if ( $edit ) {
            $shown = ((int) $selectedId) > 0 ? '' : ' style="display:none"';
            $html .= "<div id=\"empty_key_lti13_fields_wrap\"{$shown}>\n"
                ."<p>After you have associated this key with a client, you can empty the issuer, client id, and platform URLs on this key.</p>\n"
                ."<div class=\"checkbox\">\n<label for=\"empty_key_lti13_fields\">\n"
                ."<input type=\"checkbox\" name=\"empty_key_lti13_fields\" id=\"empty_key_lti13_fields\" value=\"1\">\n"
                ."Empty the issuer, client id, and platform URLs on this key\n"
                ."</label>\n"
                ."<p class=\"help-block\">Checked, these fields are cleared when you save. Unchecked, they stay on the key. A blank issuer on the key matches any issuer.</p>\n"
                ."</div>\n"
                ."</div>\n"
                ."<script>\n"
                ."document.addEventListener('DOMContentLoaded', function() {\n"
                ."  var fields = ".json_encode(self::keyPlatformFields()).";\n"
                ."  var wrap = document.getElementById('empty_key_lti13_fields_wrap');\n"
                ."  var box = document.getElementById('empty_key_lti13_fields');\n"
                ."  var select = document.getElementById('lti_client_id');\n"
                ."  var snapshot = null;\n"
                ."  function chosen() { return !!(select && select.value !== ''); }\n"
                ."  function emptyFields() {\n"
                ."    if (snapshot === null) {\n"
                ."      snapshot = {};\n"
                ."      fields.forEach(function(name) {\n"
                ."        var el = document.getElementById(name);\n"
                ."        snapshot[name] = el ? el.value : '';\n"
                ."      });\n"
                ."    }\n"
                ."    fields.forEach(function(name) {\n"
                ."      var el = document.getElementById(name);\n"
                ."      if (el) el.value = '';\n"
                ."    });\n"
                ."  }\n"
                ."  function restoreFields() {\n"
                ."    if (!snapshot) return;\n"
                ."    fields.forEach(function(name) {\n"
                ."      var el = document.getElementById(name);\n"
                ."      if (el && Object.prototype.hasOwnProperty.call(snapshot, name)) el.value = snapshot[name];\n"
                ."    });\n"
                ."    snapshot = null;\n"
                ."  }\n"
                ."  function syncBox() {\n"
                ."    var on = chosen();\n"
                ."    if (wrap) wrap.style.display = on ? '' : 'none';\n"
                ."    if (!box) return;\n"
                ."    if (!on && box.checked) {\n"
                ."      box.checked = false;\n"
                ."      restoreFields();\n"
                ."    } else if (on && box.checked) {\n"
                ."      emptyFields();\n"
                ."    }\n"
                ."    if (window.tsugiRefreshCreateClient) window.tsugiRefreshCreateClient();\n"
                ."  }\n"
                ."  if (box) box.addEventListener('change', function() {\n"
                ."    if (box.checked) emptyFields();\n"
                ."    else restoreFields();\n"
                ."    if (window.tsugiRefreshCreateClient) window.tsugiRefreshCreateClient();\n"
                ."  });\n"
                ."  if (select) select.addEventListener('change', syncBox);\n"
                ."  window.tsugiEmptyKeyPlatformFields = emptyFields;\n"
                ."  window.tsugiSyncEmptyKeyPlatformFields = syncBox;\n"
                ."  syncBox();\n"
                ."});\n"
                ."</script>\n";
        }
        $html .= "<hr>\n";
        return $html;
    }

    /**
     * Select or read-only link for the key form.
     *
     * @param int $selectedId
     * @param bool $edit
     */
    public static function inboundClientControl($selectedId, $edit) {
        global $CFG;
        if ( ! \Tsugi\Core\LTIX::ltiClientTableExists($CFG->dbprefix) ) return '';
        $selectedId = (int) $selectedId;
        if ( ! $edit ) {
            if ( $selectedId < 1 ) {
                return "<p><strong>LTI 1.3 client</strong></p>\n<p>None</p>\n"
                    .self::clientPlatformFieldsNote(false);
            }
            $row = self::inboundClientById($selectedId);
            if ( ! is_array($row) ) {
                return "<p><strong>LTI 1.3 client</strong></p>\n<p>Missing client ".$selectedId."</p>\n"
                    .self::clientPlatformFieldsNote(false);
            }
            $href = self::keyAdminUrl('client-detail?lti_client_id='.$selectedId);
            $label = htmlspecialchars(self::inboundClientLabel($row), ENT_QUOTES, 'UTF-8');
            return "<p><strong>LTI 1.3 client</strong></p>\n<p><a href=\"{$href}\">{$label}</a></p>\n"
                .self::clientPlatformFieldsNote(false);
        }

        $choices = self::inboundClientChoices();
        $html = "<div class=\"form-group\">\n<label for=\"lti_client_id\">LTI 1.3 client<br/>\n";
        $html .= "<select id=\"lti_client_id\" name=\"lti_client_id\">\n";
        if ( count($choices) < 1 ) {
            $html .= "<option value=\"\">No Clients Available</option>\n";
        } else {
            $placeholder = $selectedId < 1 ? ' selected' : '';
            $html .= "<option value=\"\"{$placeholder}>Select</option>\n";
            foreach ( $choices as $choice ) {
                $id = (int) U::get($choice, 'lti_client_id');
                $selected = $id === $selectedId ? ' selected' : '';
                $label = htmlspecialchars(self::inboundClientLabel($choice), ENT_QUOTES, 'UTF-8');
                $html .= "<option value=\"{$id}\"{$selected}>{$label}</option>\n";
            }
        }
        $html .= "</select>\n</label>\n</div>\n";
        $html .= self::clientPlatformFieldsNote(true, $selectedId);
        return $html;
    }

    /**
     * Store the chosen client id. Empty the key's platform fields only when asked.
     *
     * @return bool False when the posted id is not a client.
     */
    public static function applyPostedClientToKey() {
        global $CFG;
        if ( ! \Tsugi\Core\LTIX::ltiClientTableExists($CFG->dbprefix) ) return true;
        if ( ! array_key_exists('lti_client_id', $_POST) ) {
            U::flashError('Choose a client from the list.');
            return false;
        }
        $raw = $_POST['lti_client_id'];
        if ( $raw === null || $raw === '' || $raw === '0' ) {
            $_POST['lti_client_id'] = null;
            return true;
        }
        $raw = trim((string) $raw);
        $client = ctype_digit($raw) && (int) $raw > 0 ? self::inboundClientById((int) $raw) : null;
        if ( ! is_array($client) ) {
            U::flashError('Choose a client from the list.');
            return false;
        }
        $_POST['lti_client_id'] = (int) $raw;
        if ( (string) U::get($_POST, 'empty_key_lti13_fields') === '1' ) {
            if ( ! self::clientCanReplaceKey($client) ) {
                U::flashError('This client needs an issuer, client id, and the platform URLs before the key fields can be emptied.');
                return false;
            }
            foreach ( self::keyPlatformFields() as $field ) {
                $_POST[$field] = null;
            }
        }
        return true;
    }

    /**
     * A client can stand in for the key only when the launch would use it.
     *
     * @param array<string, mixed> $row
     */
    private static function clientCanReplaceKey(array $row) {
        foreach ( array('issuer', 'client_id', 'oidc_auth_url', 'keyset_url', 'token_url') as $field ) {
            if ( ! U::isNotEmpty(U::get($row, $field)) ) return false;
        }
        return true;
    }

    /**
     * Insert or update a hand-entered client. Workflow columns stay at their defaults.
     *
     * @param int|null $id Null inserts.
     * @param array<string, mixed> $post
     * @return int|false
     */
    public static function saveInboundClient($id, array $post, $announce = true) {
        global $PDOX, $CFG;

        $issuer = self::trimToNull(U::get($post, 'issuer'));
        $client = self::trimToNull(U::get($post, 'client_id'));
        $values = array(
            ':issuer' => $issuer,
            ':client' => $client,
            ':oidc' => self::trimToNull(U::get($post, 'oidc_auth_url')),
            ':keyset' => self::trimToNull(U::get($post, 'keyset_url')),
            ':token' => self::trimToNull(U::get($post, 'token_url')),
            ':audience' => self::trimToNull(U::get($post, 'token_audience')),
            ':unlock' => self::trimToNull(U::get($post, 'unlock_code')),
            ':title' => self::trimToNull(U::get($post, 'title')),
            ':notes' => self::trimToNull(U::get($post, 'notes')),
            ':user' => self::postedClientUserId($post),
        );
        if ( $issuer === null ) {
            if ( $announce ) U::flashError('Issuer is required');
            return false;
        }
        $identity = \Tsugi\Util\LTI::ascii255First(array(
            'Issuer' => $issuer,
            'Client id' => $client,
        ));
        if ( is_string($identity) ) {
            if ( $announce ) U::flashError($identity);
            return false;
        }
        if ( $client !== null ) {
            $dup_sql = "SELECT lti_client_id FROM {$CFG->dbprefix}lti_client
                WHERE issuer = :issuer AND client_id = :client";
            $dup_parms = array(':issuer' => $issuer, ':client' => $client);
            if ( $id ) {
                $dup_sql .= " AND lti_client_id <> :id";
                $dup_parms[':id'] = (int) $id;
            }
            $existing = $PDOX->rowDie($dup_sql, $dup_parms);
            if ( is_array($existing) ) {
                if ( $announce ) U::flashError('A client with this issuer and client id already exists');
                return false;
            }
        }

        $table = "{$CFG->dbprefix}lti_client";
        if ( $id ) {
            $values[':id'] = (int) $id;
            $sql = "UPDATE {$table} SET
                    title = :title, user_id = :user, notes = :notes,
                    issuer = :issuer, client_id = :client, oidc_auth_url = :oidc,
                    keyset_url = :keyset, token_url = :token, token_audience = :audience,
                    unlock_code = :unlock, updated_at = NOW()
                WHERE lti_client_id = :id";
            $q = $PDOX->queryReturnError($sql, $values);
            if ( ! $q->success ) {
                if ( $announce ) U::flashError('Could not update client');
                return false;
            }
            \Tsugi\Core\LTIX::clearKeysetCacheForClient((int) $id);
            if ( $announce ) U::flashSuccess('Client updated');
            return (int) $id;
        }

        $created_by = null;
        if ( \Tsugi\Core\ReqScope::isLoggedInLegacy() ) {
            $logged_in = (int) \Tsugi\Core\ReqScope::loggedInUserIdLegacy();
            if ( $logged_in > 0 ) $created_by = $logged_in;
        }
        $values[':guid'] = \Tsugi\Core\Guid::uuid7();
        $values[':created_by'] = $created_by;
        $sql = "INSERT INTO {$table}
                (guid, title, user_id, notes, issuer, client_id, oidc_auth_url, keyset_url, token_url, token_audience,
                    unlock_code, client_method, created_by_user_id, created_at, updated_at)
            VALUES (UNHEX(:guid), :title, :user, :notes, :issuer, :client, :oidc, :keyset, :token, :audience,
                    :unlock, 'manual', :created_by, NOW(), NOW())";
        $q = $PDOX->queryReturnError($sql, $values);
        if ( ! $q->success ) {
            if ( $announce ) U::flashError('Could not insert client');
            return false;
        }
        if ( $announce ) U::flashSuccess('Client inserted');
        return (int) $PDOX->lastInsertId();
    }

    /**
     * Check or create a client from the LTI 1.3 fields on a key form.
     * Does not update the key. Audience and unlock code are copied when present.
     *
     * @param array<string, mixed> $source Key form fields (lms_*) or client fields.
     * @param bool $create
     * @return array<string, mixed>
     */
    public static function clientFromKeyRequest(array $source, $create) {
        global $PDOX, $CFG;

        $post = array(
            'issuer' => self::trimToNull(U::get($source, 'lms_issuer', U::get($source, 'issuer'))),
            'client_id' => self::trimToNull(U::get($source, 'lms_client', U::get($source, 'client_id'))),
            'oidc_auth_url' => self::trimToNull(U::get($source, 'lms_oidc_auth', U::get($source, 'oidc_auth_url'))),
            'keyset_url' => self::trimToNull(U::get($source, 'lms_keyset_url', U::get($source, 'keyset_url'))),
            'token_url' => self::trimToNull(U::get($source, 'lms_token_url', U::get($source, 'token_url'))),
            'token_audience' => self::trimToNull(U::get($source, 'lms_token_audience', U::get($source, 'token_audience'))),
            'unlock_code' => self::trimToNull(U::get($source, 'unlock_code')),
            'title' => self::trimToNull(U::get($source, 'key_title', U::get($source, 'title'))),
            'notes' => self::trimToNull(U::get($source, 'notes')),
            'user_id' => U::get($source, 'user_id'),
        );
        $required = array(
            'issuer' => 'Issuer',
            'client_id' => 'Client id',
            'oidc_auth_url' => 'OIDC login URL',
            'keyset_url' => 'Keyset URL',
            'token_url' => 'Token URL',
        );
        $missing = array();
        foreach ( $required as $key => $label ) {
            if ( $post[$key] === null ) $missing[] = $label;
        }
        $shown = array(
            'Title' => $post['title'],
            'User Id' => $post['user_id'],
            'Issuer' => $post['issuer'],
            'Client id' => $post['client_id'],
            'OIDC login URL' => $post['oidc_auth_url'],
            'Keyset URL' => $post['keyset_url'],
            'Token URL' => $post['token_url'],
            'Token audience' => $post['token_audience'],
            'Unlock code' => $post['unlock_code'],
        );
        if ( count($missing) > 0 ) {
            return array(
                'ok' => false,
                'error' => 'Required fields are missing: '.implode(', ', $missing).'.',
                'fields' => $shown,
            );
        }
        $identity = \Tsugi\Util\LTI::ascii255First(array(
            'Issuer' => $post['issuer'],
            'Client id' => $post['client_id'],
        ));
        if ( is_string($identity) ) {
            return array('ok' => false, 'error' => $identity, 'fields' => $shown);
        }
        $existing = $PDOX->rowDie(
            "SELECT lti_client_id FROM {$CFG->dbprefix}lti_client
                WHERE issuer = :issuer AND client_id = :client",
            array(':issuer' => $post['issuer'], ':client' => $post['client_id'])
        );
        if ( is_array($existing) ) {
            return array(
                'ok' => false,
                'error' => 'A client with this issuer and client id already exists.',
                'existing_id' => (int) $existing['lti_client_id'],
                'fields' => $shown,
            );
        }
        if ( ! $create ) {
            return array('ok' => true, 'fields' => $shown);
        }
        $id = self::saveInboundClient(null, $post, false);
        if ( ! $id ) {
            return array('ok' => false, 'error' => 'Could not create client.', 'fields' => $shown);
        }
        $row = self::inboundClientById($id);
        $label = is_array($row) ? self::inboundClientLabel($row) : (string) $id;
        return array('ok' => true, 'id' => (int) $id, 'label' => $label, 'fields' => $shown);
    }

    /**
     * Owner id for a client. A blank, on insert or update, is NULL.
     * Not checked against lti_user. user_id is not a foreign key.
     *
     * @param array<string, mixed> $post
     * @return int|null
     */
    private static function postedClientUserId(array $post) {
        $raw = array_key_exists('user_id', $post) ? $post['user_id'] : null;
        if ( is_int($raw) || (is_string($raw) && preg_match('/^[0-9]+$/', trim($raw)) === 1) ) {
            $id = (int) $raw;
            if ( $id > 0 ) return $id;
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function trimToNull($value) {
        if ( ! is_string($value) ) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    /**
     * Absolute key-admin URL. A relative link 404s from a trailing-slash route.
     *
     * @param string $path
     */
    private static function keyAdminUrl($path) {
        global $CFG;
        return $CFG->wwwroot.'/admin/key/'.ltrim((string) $path, '/');
    }

    /**
     * Status label for a tenant key on the admin list and detail page.
     *
     * A consumer key with a secret is LTI 1.1. A consumer key without a secret
     * is LTI 1.1 and Draft. An issuer or a client id makes the key LTI 1.3.
     * A blank issuer with a client id is a wildcard. Without a linked client
     * it stays Draft until the client id, OIDC login URL, keyset URL, and
     * token URL are set. A linked client adds the word Client.
     *
     * @param array<string, mixed> $row
     */
    public static function keyTypeLabel(array $row) {
        $parts = array();
        $draft = false;

        if ( U::isNotEmpty(U::get($row, 'key_key')) ) {
            $parts[] = 'LTI 1.1';
            if ( ! U::isNotEmpty(U::get($row, 'secret')) ) {
                $draft = true;
            }
        }

        $linked = (int) U::get($row, 'lti_client_id') > 0;
        $has_issuer = U::isNotEmpty(U::get($row, 'lms_issuer'));
        $has_client = U::isNotEmpty(U::get($row, 'lms_client'));
        if ( $has_issuer || $has_client || $linked ) {
            $parts[] = 'LTI 1.3';
            if ( ! $linked && ( ! $has_client
                || ! U::isNotEmpty(U::get($row, 'lms_oidc_auth'))
                || ! U::isNotEmpty(U::get($row, 'lms_keyset_url'))
                || ! U::isNotEmpty(U::get($row, 'lms_token_url')) ) ) {
                $draft = true;
            }
        }
        if ( $linked ) {
            $parts[] = 'Client';
        }

        if ( $draft || count($parts) < 1 ) {
            $parts[] = 'Draft';
        }
        return implode(' / ', $parts);
    }

    /**
     * keyTypeLabel with the Client word linked to that client.
     *
     * @param array<string, mixed> $row
     */
    public static function keyTypeHtml(array $row) {
        $label = htmlspecialchars(self::keyTypeLabel($row), ENT_QUOTES, 'UTF-8');
        $id = (int) U::get($row, 'lti_client_id');
        if ( $id < 1 ) return $label;
        $href = self::keyAdminUrl('client-detail?lti_client_id='.$id);
        $link = '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">Client</a>';
        return str_replace('Client', $link, $label);
    }

    /**
     * Pre-save checks for a tenant key.
     *
     * A draft (no OAuth consumer key, and not both an issuer and a client id)
     * is saved with no checks. A deployment id alone leaves the key a draft.
     * An OAuth consumer key must be unique. Issuer, client id, and deployment
     * id must be unique together. A blank deployment id collides with another
     * blank deployment id. A specific deployment id collides only with the
     * same deployment id. A linked client uses the same rule for
     * (lti_client_id, deployment id), including a blank deployment.
     * $old_deploy_key stays in the signature for existing callers.
     */
    public static function validate_key_details($key_key, $deploy_key, $lms_issuer, $old_key_key=null, $old_deploy_key=null, $lms_client=null, $exclude_key_id=null, $lti_client_id=null) {
        global $PDOX, $CFG;

        $have_oauth = U::isNotEmpty($key_key);
        $have_per_tenant_lti13 = U::isNotEmpty($lms_issuer) && U::isNotEmpty($lms_client);

        if ( $have_oauth && $key_key != $old_key_key ) {
            $key_sha256 = U::lti_sha256($key_key);
            $row = $PDOX->rowDie( "SELECT key_sha256 FROM {$CFG->dbprefix}lti_key
                    WHERE key_sha256 = :key_sha256",
                array(':key_sha256' => $key_sha256)
            );
            if ( $row ) {
                U::flashError("Cannot add the same OAuth Consumer Key more than once");
                return false;
            }
        }

        if ( $have_per_tenant_lti13 && ! self::validate_issuer_client_unique($lms_issuer, $lms_client, $deploy_key, $exclude_key_id) ) {
            return false;
        }

        if ( (int) $lti_client_id > 0 && ! self::validate_client_deploy_unique($lti_client_id, $deploy_key, $exclude_key_id) ) {
            return false;
        }

        return true;
    }

    /**
     * Two keys cannot share one client and the same deployment id.
     * A blank deployment id collides with another blank deployment id.
     * The SQL unique allows multiple NULL deployment ids, so this check
     * is what keeps that pair singular.
     *
     * @param mixed $lti_client_id
     * @param mixed $deploy_key
     * @param mixed $exclude_key_id
     */
    public static function validate_client_deploy_unique($lti_client_id, $deploy_key, $exclude_key_id=null) {
        global $PDOX, $CFG;

        $lti_client_id = (int) $lti_client_id;
        if ( $lti_client_id < 1 ) return true;
        if ( ! $PDOX->columnExists('lti_client_id', $CFG->dbprefix.'lti_key') ) return true;

        $deploy = self::normalize_deploy_key_input($deploy_key);
        $parms = array(':client' => $lti_client_id);
        $exclude_sql = '';
        if ( ! empty($exclude_key_id) && (int) $exclude_key_id > 0 ) {
            $exclude_sql = ' AND k.key_id <> :exclude_key_id ';
            $parms[':exclude_key_id'] = (int) $exclude_key_id;
        }
        if ( $deploy === null ) {
            $deploy_sql = "AND (k.deploy_key IS NULL OR TRIM(k.deploy_key) = '' OR LOWER(TRIM(k.deploy_key)) = 'null')";
        } else {
            $deploy_sql = 'AND TRIM(k.deploy_key) = :deploy_key';
            $parms[':deploy_key'] = $deploy;
        }

        $row = $PDOX->rowDie(
            "SELECT k.key_id, k.key_title FROM {$CFG->dbprefix}lti_key AS k
                WHERE (k.deleted IS NULL OR k.deleted = 0)
                    AND k.lti_client_id = :client
                    $exclude_sql
                    $deploy_sql
                LIMIT 1",
            $parms
        );
        if ( $row ) {
            $label = U::isNotEmpty($row['key_title']) ? $row['key_title'] : ('key_id '.$row['key_id']);
            if ( $deploy === null ) {
                U::flashError('A key with this client and a blank deployment id already exists ('.$label.')');
            } else {
                U::flashError('A key with this client and deployment id already exists ('.$label.')');
            }
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

        // Minimums shared with the admin/expire-maint batch scripts.
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            // fall through
        }
        try {
            $out = trim($repo->run('rev-parse --abbrev-ref HEAD'));
            if ( U::strlen($out) > 0 && $out !== 'HEAD' ) return $out;
        } catch (\Exception $e) {
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

    /**
     * Installed, available, and required modules for the admin install page.
     *
     * Also records each installed checkout in lms_tools. GitHub's tsugitools
     * list is cached for ten minutes.
     *
     * @return array{status:string,version:string,detail:string,available:array,installed:array,required:array,error?:string,errors?:array,available_error?:string,available_error_detail?:string}
     */
    public static function installModuleReport() {
        global $CFG, $PDOX;

        $available = array();
        $required = array();
        $installed = array();
        $retval = array(
            'status' => 'OK',
            'version' => '',
            'detail' => '',
            'available' => $available,
            'installed' => $installed,
            'required' => $required,
        );

        try {
            $PDOX = \Tsugi\Core\LTIX::getConnection();

            $l = false;
            if ( isset($CFG->lessons) && $CFG->lessons && file_exists($CFG->lessons) ) {
                $l = new \Tsugi\Services\Lessons\LessonsService($CFG->lessons);
            }

            $paths = array();
            $note = '';
            $repo_errors = array();

            if ( isset($CFG->git_command) && is_string($CFG->git_command) ) {
                \Tsugi\Util\Git::set_bin($CFG->git_command);
            }

            $repo = new \Tsugi\Util\GitRepo($CFG->dirroot);
            $git_version = $repo->run('--version');

            $origin = self::getRepoOrigin($repo);
            $tsugi = new \stdClass();
            $tsugi->clone_url = $origin;
            $tsugi->html_url = $origin;
            $tsugi->name = 'Tsugi Admin';
            $tsugi->description = 'Tsugi Adminstration, Management, and Development Console.';
            self::addRepoInfo($tsugi, $repo);
            if ( isset($tsugi->error) ) {
                $repo_errors[] = $CFG->dirroot.': '.$tsugi->error;
            }
            $install_writeable = $tsugi->writeable;
            $tsugi->tsugitools = false;
            $tsugi->index = count($installed) + 1;
            $tsugi->path = $CFG->dirroot;
            $tsugi->guid = md5($CFG->dirroot);
            $installed[] = $tsugi;
            $paths[$origin] = $CFG->dirroot;

            $path = U::remove_relative_path($CFG->install_folder);
            $folders = self::findAllFolders($path);

            $existing = array();
            foreach ( $folders as $folder ) {
                $git = $folder . '/.git';
                if ( ! is_dir($git) ) continue;

                try {
                    $repo = new \Tsugi\Util\GitRepo($folder);
                    $origin = self::getRepoOrigin($repo);
                    $existing[$origin] = $repo;
                    $paths[$origin] = $folder;
                } catch (\Exception $e) {
                    $repo_errors[] = $folder.': '.$e->getMessage();
                }
            }

            if ( $l && isset($l->lessons->required_modules) ) foreach ( $l->lessons->required_modules as $needed ) {
                if ( isset($existing[$needed]) || isset($existing[$needed.'.git']) ) continue;
                $detail = new \stdClass();
                $detail->html_url = $needed;
                $detail->clone_url = $needed;
                $detail->name = $needed;
                $detail->description = '';
                $detail->index = count($required) + 1;
                $detail->writeable = $install_writeable;
                $required[] = $detail;
            }

            $fail = false;
            $repos = \Tsugi\Core\Cache::check('repos', 1);
            if ( ( ! isset($_GET['force']) ) && $repos !== false ) {
                $expires = \Tsugi\Core\Cache::expires('repos', 1);
                $note = 'Retrieved from session. Cached for '.$expires.' more seconds to avoid rate limit. Add ?force=yes to force pull from github before cache expires.';
            } else {
                $url = 'https://api.github.com/users/tsugitools/repos?language=PHP';
                $headers = 'User-Agent: TsugiProject';
                $expiresec = 600;
                $repos_str = Net::doGet($url, $headers);
                $note = 'Retrieved from github API. Data is cached for '.$expiresec.' seconds to avoit github limit. Add ?force=yes to force pull from github before cache expires.';
                if ( U::strlen($repos_str) < 1 ) {
                    $retval['available_error'] = 'No data retrieved from '.$url;
                    $retval['available_error_detail'] = '';
                    $fail = true;
                } else {
                    $repos = json_decode($repos_str);
                    if ( $repos === null ) {
                        $retval['available_error'] = 'Unable to decode '.$url;
                        $retval['available_error_detail'] = $repos_str;
                        $fail = true;
                    } else if ( is_object($repos) ) {
                        $retval['available_error'] = 'Did not get list of repositories: '.$url;
                        $retval['available_error_detail'] = json_encode($repos, JSON_PRETTY_PRINT);
                        $fail = true;
                    }
                }
                if ( ! $fail ) \Tsugi\Core\Cache::set('repos', 1, $repos, $expiresec);
            }

            if ( ! $fail ) foreach ( $repos as $repo ) {
                $detail = new \stdClass();
                $detail->html_url = $repo->html_url;
                $detail->clone_url = $repo->clone_url;
                $detail->name = ucfirst($repo->name);
                $detail->description = $repo->description;
                $detail->tsugitools = true;
                if ( isset($existing[$detail->clone_url]) ) {
                    $detail->existing = true;
                    $detail->path = $paths[$detail->clone_url];
                    $detail->guid = md5($paths[$detail->clone_url]);
                    $repo = $existing[$detail->clone_url];
                    self::addRepoInfo($detail, $repo);
                    if ( isset($detail->error) ) {
                        $repo_errors[] = $detail->path.': '.$detail->error;
                    }
                    unset($existing[$detail->clone_url]);
                    $detail->index = count($installed) + 1;
                    $installed[] = $detail;
                } else {
                    $detail->writeable = $install_writeable;
                    $detail->index = count($available) + 1;
                    $available[] = $detail;
                }
            }

            foreach ( $existing as $clone_url => $repo ) {
                $detail = new \stdClass();
                $detail->clone_url = $clone_url;
                $detail->html_url = $clone_url;
                $detail->name = '';
                preg_match('/([^\/]+)\.git/', $clone_url, $match);
                if ( count($match) == 2 ) {
                    $detail->name = ucwords(preg_replace('/[^0-9a-zA-Z]/', ' ', $match[1]));
                }
                $detail->description = '';
                self::addRepoInfo($detail, $repo);
                $detail->tsugitools = false;
                $detail->writeable = $install_writeable;
                $detail->index = count($installed) + 1;
                if ( isset($paths[$clone_url]) ) {
                    $detail->path = $paths[$clone_url];
                    $detail->guid = md5($paths[$clone_url]);
                }
                if ( isset($detail->error) ) {
                    $path_note = isset($detail->path) ? $detail->path : $clone_url;
                    $repo_errors[] = $path_note.': '.$detail->error;
                }
                $installed[] = $detail;
            }

            foreach ( $installed as $tool ) {
                $sql = "INSERT INTO {$CFG->dbprefix}lms_tools
                    ( toolpath, name, description, clone_url, gitversion, created_at, updated_at ) VALUES
                    ( :toolpath, :name, :description, :clone_url, :gitversion, NOW(), NOW() )
                    ON DUPLICATE KEY
                    UPDATE name=:name, description=:description ";
                $values = array(
                    ':toolpath' => $tool->path,
                    ':name' => $tool->name,
                    ':description' => $tool->description,
                    ':clone_url' => $tool->clone_url,
                    ':gitversion' => (isset($tool->gitversion) ? $tool->gitversion : 'main'),
                );
                $PDOX->queryReturnError($sql, $values);
                self::updateToolStatus($tool->path, $tool);
            }

            $retval['status'] = count($repo_errors) > 0 ? 'ERROR' : 'OK';
            $retval['version'] = trim($git_version);
            $retval['detail'] = $note;
            $retval['available'] = $available;
            $retval['installed'] = $installed;
            $retval['required'] = $required;
            if ( count($repo_errors) > 0 ) {
                $retval['error'] = implode("\n", $repo_errors);
                $retval['errors'] = $repo_errors;
            }
        } catch (\Exception $e) {
            error_log('installModuleReport: '.$e->getMessage());
            $retval['status'] = 'ERROR';
            $retval['error'] = $e->getMessage();
            $retval['available'] = $available;
            $retval['installed'] = $installed;
            $retval['required'] = $required;
        }

        return $retval;
    }

    /**
     * Per-server tool status for the admin install cluster tab.
     *
     * @return array<int, \stdClass>
     */
    public static function installClusterServers() {
        global $CFG, $PDOX;

        $PDOX = \Tsugi\Core\LTIX::getConnection();
        $entries = self::getClusterInfo();
        if ( ! is_array($entries) ) {
            $entries = array();
        }

        $grouped = array();
        foreach ( $entries as $entry ) {
            $ip = $entry['ipaddr'];
            if ( ! isset($grouped[$ip]) ) {
                $grouped[$ip] = array();
            }
            $grouped[$ip][] = $entry;
        }

        $serverIP = Net::serverIP();
        $servers = array();
        foreach ( $grouped as $ip => $tools ) {
            $server = new \stdClass();
            $server->ipaddr = $ip;
            $server->ipaddrid = str_replace('.', '_', $ip);
            $server->local = ($ip == $serverIP);
            $server->tools = $tools;
            $install = $PDOX->allRowsDie(
                "SELECT clone_url, T.created_at AS created_at
                 FROM {$CFG->dbprefix}lms_tools AS T
                 LEFT JOIN {$CFG->dbprefix}lms_tools_status AS S
                    ON S.tool_id = T.tool_id AND S.ipaddr = :ipaddr
                 WHERE ISNULL(S.ipaddr)",
                array(':ipaddr' => $ip)
            );
            $server->install = is_array($install) ? $install : array();
            $servers[] = $server;
        }

        return $servers;
    }

    public static function doClone($remote, $folder, $record = true) {
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

            if ( $record ) {
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
            }

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
        } catch (\Exception $e) {
            $detail->writeable = false;
            $update = 'Caught exception: '.$e->getMessage(). "\n";
            $errors[] = 'remote update: '.$e->getMessage();
        }
        $detail->update_note = $update;
        try {
            $status = $repo->run('status -uno');
        } catch (\Exception $e) {
            $status = 'Caught exception: '.$e->getMessage(). "\n";
            $errors[] = 'status: '.$e->getMessage();
        }
        $detail->status_note = $status;
        $detail->updates = strpos($status, 'Your branch is behind') !== false;
        // Use -1 so single-commit / shallow repos work (HEAD^ fails there).
        try {
            $commit_log = $repo->run('log -1 --name-status');
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
