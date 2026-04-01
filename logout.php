<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("config.php");
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Core\Cache;
session_start();
// Redundant with session_unset() below (which removes every $_SESSION key including cache_*).
// Kept explicitly so the security intent is obvious: Tsugi session caches must not outlive logout.
Cache::clearAllSessionCaches();
session_unset();
SecureCookie::delete();

if ( isset($CFG->logout_return_url) && is_string($CFG->logout_return_url) ) {
    header('Location: '.$CFG->logout_return_url);
} else if ( isset($CFG->apphome) && $CFG->apphome ) {
    header('Location: '.$CFG->apphome);
} else {
    header('Location: '.$CFG->wwwroot);
}
