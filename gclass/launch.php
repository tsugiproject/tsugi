<?php

// Only from within tsugi.php
if ( ! isset($CFG) ) return;

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Crypt\AesCtr;

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
$request_headers = apache_request_headers();
print_r($request_headers);
error_log(\Tsugi\UI\Output::safe_var_dump($request_headers));
