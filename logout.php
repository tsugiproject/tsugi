<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("config.php");
use \Tsugi\Crypt\SecureCookie;
session_start();
session_unset();
SecureCookie::delete();

if ( isset($CFG->logout_return_url) && $CFG->logout_return_url ) {
    header('Location: '.$CFG->logout_return_url);
} else if ( isset($CFG->apphome) && $CFG->apphome ) {
    header('Location: '.$CFG->apphome);
} else {
    header('Location: '.$CFG->wwwroot);
}
