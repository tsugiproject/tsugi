<?php

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

// TODO: deal with headers sent...
function requireLogin() {
    global $CFG, $OUTPUT;
    if ( $CFG->google_glient_id && ! isset($_SESSION['user_id']) ) {
        U::flashError('Login required');
        $OUTPUT->doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function isAdmin() {
    return isset( $_SESSION['admin']) && $_SESSION['admin'] == 'yes';
}

function requireAdmin() {
    global $CFG, $OUTPUT;
    if ( $CFG->google_glient_id && $_SESSION['admin'] != 'yes' ) {
        U::flashError('Login required');
        $OUTPUT->doRedirect($CFG->wwwroot.'/login.php') ;
        exit();
    }
}

function findTools($dir, &$retval, $filenames=array("index.php", "tsugi.php")) {
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

function findAllFolders($paths)
{
    $folders = array();
    if ( is_string($paths) ) $paths = array($paths);
    foreach( $paths as $path) {
       if ( ! is_dir($path) ) continue;
       $files = scandir($path);
        foreach($files as $file) {
            if ( strpos($file,'.') === 0 ) continue;
            if ( strpos($file,'_') === 0 ) continue;
            $abs = addSlash($path).$file;
            if ( ! is_dir($abs) ) continue;
            $folders[] = $abs;
        }
    }
    return $folders;
}

function addSlash($path)
{
    if ( U::strlen($path) < 1 ) return $path;
    if ( substr($path,strlen($path)-1) == DIRECTORY_SEPARATOR ) return $path;
    return $path . DIRECTORY_SEPARATOR;
}

function findAllTools()
{
    global $CFG;
    // Load tools from various folders
    $tools = array();
    foreach( $CFG->tool_folders AS $tool_folder) {
        if ( $tool_folder == 'core' ) continue;
        if ( $tool_folder == 'admin' ) continue;
        findTools($CFG->dirroot.'/'.$tool_folder,$tools);
    }
    return $tools;
}

function findAllRegistrations($folders=false, $appStore=false)
{
    global $CFG, $PDOX;

    $regCode = $CFG->dirroot . '/../load_registrations.php';
    if ( file_exists($regCode) ) {
        require_once($regCode);
        return loadRegistrations();
    }
    return findAllRegistrationsInternal($folders, $appStore);
}

function findAllRegistrationsInternal($folders=false, $appStore=false)
{
    global $CFG, $PDOX;

    // Scan the tools folders for registration settings
    if ( $folders == false ) $folders = $CFG->tool_folders;
    if ( is_string($folders) ) $folders = array($folders);
    $tools = array();
    foreach( $folders AS $tool_folder) {
        if ( $tool_folder == 'core' ) continue;
        if ( $tool_folder == 'admin' ) continue;
        $some = findFiles('register.php', $CFG->dirroot . '/');
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

function findFiles($filename="index.php", $reldir=false) {
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

function findToolFiles($filename="index.php", $reldir=false) {
    global $CFG;
    $retval = array();
    foreach ( $CFG->tool_folders as $dir ) {
        if ( $reldir !== false ) $dir = $reldir . '/' . $dir;
        $files = searchTwoLevels($filename, $dir);
        $retval = array_merge($retval, $files);
    }
    return $retval;
}

function searchTwoLevels($filename, $dir) {
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
function trimAsMuchAsYouCan($path, $root) {
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

/**
 * Count tenant keys linked to live Tsugi Issuers; detect many-keys-to-one-issuer cases.
 *
 * Orphan issuer_id values are ignored. Returns ok=false if lti_issuer is unavailable.
 *
 * @return array{ok:bool,linked_key_count:int,multi_issuer_count:int,multi_linked_key_count:int}
 */
function tsugi_linked_issuer_deprecation_stats() {
    global $CFG, $PDOX;

    $empty = array(
        'ok' => false,
        'linked_key_count' => 0,
        'multi_issuer_count' => 0,
        'multi_linked_key_count' => 0,
    );
    if ( ! isset($PDOX) || $PDOX === false || ! isset($CFG) ) {
        return $empty;
    }

    $sql = "SELECT i.issuer_id, COUNT(*) AS keys_per_issuer
        FROM {$CFG->dbprefix}lti_key AS k
        INNER JOIN {$CFG->dbprefix}lti_issuer AS i ON k.issuer_id = i.issuer_id
        WHERE k.issuer_id IS NOT NULL AND k.issuer_id > 0
        AND (k.deleted IS NULL OR k.deleted = 0)
        AND (i.deleted IS NULL OR i.deleted = 0)
        GROUP BY i.issuer_id";
    $stmt = $PDOX->queryReturnError($sql, false, false);
    if ( ! $stmt || ! $stmt->success ) {
        return $empty;
    }

    $linked_key_count = 0;
    $multi_issuer_count = 0;
    $multi_linked_key_count = 0;
    while ( $row = $stmt->fetch(\PDO::FETCH_ASSOC) ) {
        $keys_per_issuer = (int) $row['keys_per_issuer'];
        $linked_key_count += $keys_per_issuer;
        if ( $keys_per_issuer > 1 ) {
            $multi_issuer_count++;
            $multi_linked_key_count += $keys_per_issuer;
        }
    }

    return array(
        'ok' => true,
        'linked_key_count' => $linked_key_count,
        'multi_issuer_count' => $multi_issuer_count,
        'multi_linked_key_count' => $multi_linked_key_count,
    );
}

/**
 * Build user-facing Tsugi Issuer deprecation warning lines from stats.
 *
 * @param array $stats Result from tsugi_linked_issuer_deprecation_stats()
 * @return string[]
 */
function tsugi_linked_issuer_deprecation_messages($stats) {
    if ( ! is_array($stats) || empty($stats['ok']) || (int) $stats['linked_key_count'] < 1 ) {
        return array();
    }

    $linked_key_count = (int) $stats['linked_key_count'];
    $multi_issuer_count = (int) $stats['multi_issuer_count'];
    $multi_linked_key_count = (int) $stats['multi_linked_key_count'];

    $key_word = ($linked_key_count === 1) ? 'tenant key is' : 'tenant keys are';
    $issuer_word = ($linked_key_count === 1) ? 'a Tsugi Issuer' : 'Tsugi Issuers';
    $messages = array(
        "{$linked_key_count} {$key_word} linked to {$issuer_word}. "
        . "The separate Issuer feature in Tsugi will be removed soon. "
        . "A conversion path will be provided, but you should contact Dr. Chuck "
        . "(drchuck@learnxp.com) to make sure the conversion works well for your installation.",
    );

    if ( $multi_issuer_count > 0 ) {
        if ( $multi_issuer_count === 1 ) {
            $intro = 'A Tsugi Issuer has more than one tenant key linked';
        } else {
            $intro = "{$multi_issuer_count} Tsugi Issuers each have more than one tenant key linked";
        }
        $key_total_label = ($multi_linked_key_count === 1) ? 'tenant key' : 'tenant keys';
        $messages[] = "WARNING: {$intro} ({$multi_linked_key_count} {$key_total_label} total). "
            . "Automatic conversion will likely fail for these arrangements; "
            . "the affected keys are probably currently failing to launch. One issuer with one tenant key converts easily; "
            . "multiple keys per issuer need manual review.";
    }

    return $messages;
}

/**
 * @param string[] $messages
 */
function tsugi_echo_issuer_deprecation_asterisk_box($messages) {
    if ( ! is_array($messages) || count($messages) < 1 ) {
        return;
    }

    foreach ( $messages as $message ) {
        error_log("Tsugi Issuer deprecation: ".$message);
    }

    $width = 78;
    $border = str_repeat('*', $width);
    echo("<pre style=\"color:#b30000;font-weight:bold;margin:1em 0;\">\n");
    echo(htmlentities($border)."\n");
    foreach ( $messages as $message ) {
        $lines = explode("\n", wordwrap($message, $width - 4, "\n", true));
        foreach ( $lines as $line ) {
            echo('* '.htmlentities($line)."\n");
        }
    }
    echo(htmlentities($border)."\n");
    echo("</pre>\n");
}

/**
 * @param string[] $messages
 */
function tsugi_echo_issuer_deprecation_alert($messages) {
    if ( ! is_array($messages) || count($messages) < 1 ) {
        return;
    }

    foreach ( $messages as $message ) {
        error_log("Tsugi Issuer deprecation: ".$message);
    }

    echo('<div class="alert alert-danger" role="alert" style="margin: 10px 0;">'."\n");
    foreach ( $messages as $message ) {
        $is_warning = (strpos($message, 'WARNING:') === 0);
        echo('<p>');
        if ( $is_warning ) {
            echo('<strong>');
        }
        $html = htmlentities($message);
        $html = str_replace(
            htmlentities('Dr. Chuck (drchuck@learnxp.com)'),
            '<a href="mailto:drchuck@learnxp.com">Dr. Chuck</a>',
            $html
        );
        echo($html);
        if ( $is_warning ) {
            echo('</strong>');
        }
        echo("</p>\n");
    }
    echo("</div>\n");
}
