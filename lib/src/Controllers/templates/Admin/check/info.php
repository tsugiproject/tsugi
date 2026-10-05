<?php

use \Tsugi\Util\U;


ob_start();
phpinfo();
$output = ob_get_clean();
$btn = '<p style="margin:1em;overflow:auto;"><a href="index.php" style="float:right;display:inline-block;padding:.5em 1em;background:#337ab7;color:#fff;text-decoration:none;border-radius:4px;font-family:sans-serif;font-size:14px;">← Back to Admin</a></p>';
echo str_replace('<body>', '<body>' . $btn, $output);
