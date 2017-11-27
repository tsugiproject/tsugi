<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Crypt\AesCtr;

$request_headers = apache_request_headers();
$agent = U::get($request_headers,'User-Agent');
if ( $agent && stripos($agent,'Google Web Preview') !== false ) {
    echo('<center><img src="'.$CFG->apphome.'/logo.png"></center>'."\n");
    error_log("IMAGE THUMBNAIL!!");
    return;
}

$PDOX = LTIX::getConnection();

session_start();

$parts = U::parse_rest_path();
?>
<h1>I am a launch</h1>
<pre>
Path:
<?php
print_r($parts);
?>
Post:
<?php
print_r($_POST);
?>
<hr/>
Get:
<?php
print_r($_GET);
?>
<hr/>
Apache Request Headers:
<?php
print_r($request_headers);
error_log(\Tsugi\UI\Output::safe_var_dump($request_headers));
