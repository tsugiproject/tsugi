<?php

use \Tsugi\Core\LTIX;
LTIX::getConnection();

?>
<html>
<head>
</head>
<body>
<h1>Launch Activity</h1>
<ul>
<li>Launch activity:
<?= $CFG->launchactivity ? "ON" : "OFF" ?>
<ul>
<li>Link activity records:
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_link_activity");
echo( $row ? $row['count'] : '0'  );
?>
</li>
<li>User activity records:
<?php
$row = $PDOX->rowDie("SELECT COUNT(*) AS count FROM {$CFG->dbprefix}lti_link_user_activity");
echo( $row ? $row['count'] : '0'  );
?>
</li>
</ul>
</li>
</ul>
</body>
</html>
