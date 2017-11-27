<?php

use \Tsugi\Util\U;

if ( ! isset($CFG) ) return;

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
