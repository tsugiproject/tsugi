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
</li>
</ul>
</body>
</html>
