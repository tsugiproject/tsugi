<?php

require_once($CFG->dirroot."/vendor/autoload.php");

// When true, LMS ID SANITY mismatches are written to the PHP error log. Default false; set true on a server while debugging, then turn off.
$DETAIL_LOG = false;

function lmsDie($message=false) {
    global $CFG, $DEBUG_STRING;
    if($message !== false) {
        echo($message);
        error_log($message);
    }
    if ( $CFG->DEVELOPER === TRUE ) {
        if ( strlen($DEBUG_STRING) > 0 ) {
            echo("\n<pre>\n");
            echo(htmlentities($DEBUG_STRING));
            echo("\n</pre>\n");
        }
    }
    die();  // lmsDie
}

function line_out($output) {
    echo(htmlent_utf8($output)."<br/>\n");
    flush();
}

function error_out($output) {
    echo('<span role="status" aria-live="polite" style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

function success_out($output) {
    echo('<span role="status" aria-live="polite" style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

// Output also needs a trivial Launch to make the session calls work.
$OUTPUT = new \Tsugi\UI\Output();
$LAUNCH = new \Tsugi\Core\Launch();
$OUTPUT->launch = $LAUNCH;

// http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle)
    {
        if (is_string($haystack) && is_string($needle)) {
            // search backwards starting from haystack length characters from the end
            return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
        } else {
            return false;
        }
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        if (is_string($haystack) && is_string($needle)) {
            return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
        } else {
            return false;
        }
    }
}

// Quick test - make sure we don't regress these.
if ( startsWith("Hello", "H") && endsWith("Hello", "o") &&
     startsWith(null, null) == false && endsWith(null, null) == false &&
     startsWith("Hello", null) == false && endsWith("Hello", null) == false &&
     startsWith(null, "H") == false && endsWith(null, "o") == false ) {
    // all good.
} else {
    die('startsWith or endsWith fail');
}

if (!function_exists('_tsugiNormalizePositiveId')) {
    /** @return int 0 for missing/invalid/non-positive values. */
    function _tsugiNormalizePositiveId($value) {
        if ($value === null || $value === false || $value === '' || is_bool($value)) {
            return 0;
        }
        if (!is_numeric($value)) {
            return 0;
        }
        $intval = (int) $value;
        if ($intval <= 0) {
            return 0;
        }
        return $intval;
    }
}

if (!function_exists('_tsugiIdentitySnapshot')) {
    /**
     * Resolve SID/SCID and UID/CID once per request with sanity checks.
     *
     * Returns:
     * - source: 'session' | 'global' | null
     * - user_id: chosen current user id (or 0)
     * - context_id: chosen current context id (or 0)
     * - sid/scid/uid/cid: normalized source values (or 0)
     * - errors: sanity mismatch messages
     *
     * Selection rule (no mixing):
     * - If SID exists, choose session pair (SID, SCID)
     * - Else if UID exists, choose global pair (UID, CID)
     * - Else none
     *
     * @return array<string,mixed>
     */
    function _tsugiIdentitySnapshot() {
        global $USER, $CONTEXT, $DETAIL_LOG;
        static $snapshot = null;
        static $logged = false;
        if (is_array($snapshot)) {
            return $snapshot;
        }

        $sid = 0;
        $scid = 0;
        $uid = 0;
        $cid = 0;

        if (isset($_SESSION) && is_array($_SESSION)) {
            if (array_key_exists('id', $_SESSION)) {
                $sid = _tsugiNormalizePositiveId($_SESSION['id']);
            }
            if (array_key_exists('context_id', $_SESSION)) {
                $scid = _tsugiNormalizePositiveId($_SESSION['context_id']);
            }
        }

        if (isset($USER) && $USER !== false && $USER !== null && is_object($USER)
            && property_exists($USER, 'id')) {
            $uid = _tsugiNormalizePositiveId($USER->id);
        }
        if (isset($CONTEXT) && $CONTEXT !== false && $CONTEXT !== null && is_object($CONTEXT)
            && property_exists($CONTEXT, 'id')) {
            $cid = _tsugiNormalizePositiveId($CONTEXT->id);
        }

        $errors = array();
        if ($sid > 0 && $uid > 0 && $sid !== $uid) {
            $errors[] = 'SID/UID mismatch sid='.$sid.' uid='.$uid;
        }
        if ($scid > 0 && $cid > 0 && $scid !== $cid) {
            $errors[] = 'SCID/CID mismatch scid='.$scid.' cid='.$cid;
        }

        $source = null;
        $user_id = 0;
        $context_id = 0;
        if ($sid > 0) {
            $source = 'session';
            $user_id = $sid;
            $context_id = $scid;
        } else if ($uid > 0) {
            $source = 'global';
            $user_id = $uid;
            $context_id = $cid;
        }

        $snapshot = array(
            'source' => $source,
            'user_id' => $user_id,
            'context_id' => $context_id,
            'sid' => $sid,
            'scid' => $scid,
            'uid' => $uid,
            'cid' => $cid,
            'errors' => $errors,
        );

        if (!$logged && !empty($errors) && $DETAIL_LOG) {
            foreach ($errors as $error) {
                error_log('LMS ID SANITY: '.$error);
            }
            $logged = true;
        }

        return $snapshot;
    }
}

if (!function_exists('loggedInUserId')) {
    /**
     * Current Tsugi user id, or 0.
     *
     * Source pair is selected by _tsugiIdentitySnapshot():
     * session SID/SCID pair first, otherwise global UID/CID pair.
     *
     * @return int
     */
    function loggedInUserId() {
        $identity = _tsugiIdentitySnapshot();
        return (int) $identity['user_id'];
    }
}

if (!function_exists('currentContextId')) {
    /**
     * Current course/context id, or 0, from the same selected source as loggedInUserId().
     *
     * @return int
     */
    function currentContextId() {
        $identity = _tsugiIdentitySnapshot();
        return (int) $identity['context_id'];
    }
}

if (!function_exists('isLoggedIn')) {
    /**
     * True when loggedInUserId() is non-zero.
     * Loaded via config.php → setup.php and lms_lib.php (see config-dist.php).
     */
    function isLoggedIn() {
        return loggedInUserId() !== 0;
    }
}

// No trailer
