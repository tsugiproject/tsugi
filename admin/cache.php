<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\UI\Output;

if (U::isKeyNotEmpty($_POST, "set") && U::isKeyNotEmpty($_POST, "set_value")) {
    U::appCacheSet('tsugi_test', U::get($_POST, "set_value"));
}

if (U::isKeyNotEmpty($_POST, "delete")) {
    U::appCacheDelete('tsugi_test');
}

?>
<html>
<head>
</head>
<body>
<h1>Cache Detail</h1>

<h2>APC server cache (APCu)</h2>
<p>This section shows PHP&rsquo;s APCu user cache: key/value data held in memory on this server and shared by PHP workers in this environment. Tsugi reads and writes application keys through that cache using the server prefix below. The dump at the bottom is the full <code>apcu_cache_info</code> output for the current PHP process.</p>
<p>Server prefix: <?= htmlentities($CFG->serverPrefix()) ?></p>
<?php
if ( U::apcuAvailable() ) {
    $value = U::appCacheGet('tsugi_test', false);
    if ( $value ) {
        echo("<p>Key tsugi_test=".htmlentities($value)."</p>\n");
    } else {
        echo("<p>Key tsugi_test not set</p>\n");
    }
?>
<form method="POST">
<input type="submit" name="set" value="Set tsugi_test cache entry"> = 
<input type="text" name="set_value"><br/>
<input type="submit" name="delete" value="Delete tsugi_test cache entry"><br/>
</form>
<?php
   $info = apcu_cache_info(false);
   $dump = Output::safe_print_r($info);
   echo("<pre>\n");
   echo(htmlentities($dump));
   echo("</pre>\n");
} else {
   echo("APCU Cache is not available");
}

?>

<h2>Session cache</h2>
<p>These entries live in your current PHP <code>$_SESSION</code> under keys that start with <code>cache_</code>. Tsugi&rsquo;s <code>Tsugi\Core\Cache</code> class stores per-session data there (for example cached LTI rows scoped to this login), separate from the shared APCu cache above.</p>
<?php
$session_cache_keys = array();
if ( isset($_SESSION) && is_array($_SESSION) ) {
    foreach ( array_keys($_SESSION) as $sk ) {
        if ( is_string($sk) && str_starts_with($sk, 'cache_') ) {
            $session_cache_keys[] = $sk;
        }
    }
}
sort($session_cache_keys);
if ( count($session_cache_keys) === 0 ) {
    echo("<p>No keys starting with <code>cache_</code> in this session.</p>\n");
} else {
    echo("<p>".count($session_cache_keys)." key(s).</p>\n");
    foreach ( $session_cache_keys as $sk ) {
        echo("<h3>".htmlentities($sk)."</h3>\n");
        $dump = Output::safe_print_r($_SESSION[$sk]);
        echo("<pre>\n".$dump."</pre>\n");
    }
}
?>
</body>
</html>
