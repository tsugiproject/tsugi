<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
?>
<h1>Welcome</h1>
<p>
Yada
<p>
<?php

$OUTPUT->footer();
