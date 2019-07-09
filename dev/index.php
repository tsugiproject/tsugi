<?php

require_once "../config.php";
$OUTPUT->header();
$OUTPUT->bodyStart();
?>
<h1>Moved...</h1>
<p>
The ability to test a locally installed Tsugi tool has been moved to the 
<a href="<?= $CFG->wwwroot ?>/store">app store</a>.
<p>
The Simple LTI 1.x testing tool has moved to 
<a href="https://www.tsugi.org/lti-test/">https://www.tsugi.org/lti-test/</a>.
</p>
<?php

$OUTPUT->footer();

