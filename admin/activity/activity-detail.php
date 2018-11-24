<?php

use \Tsugi\Util\U;

// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['link_id']) ) {
    die('No link_id');
}

$link_id = $_REQUEST['link_id'] / 10000;
$event = $_REQUEST['link_id'] % 10000;
$order_by = U::get($_REQUEST,'order_by');

$sql = "SELECT A.link_id AS link_id, L.title AS link_title, link_count, C.title AS context_title, A.created_at, A.updated_at, event, L.path
        FROM {$CFG->dbprefix}lti_link_activity AS A
        LEFT JOIN {$CFG->dbprefix}lti_link AS L ON A.link_id = L.link_id
        LEFT JOIN {$CFG->dbprefix}lti_context AS C ON L.context_id = C.context_id
        WHERE L.link_id = :LID AND event = :EID";

$row = $PDOX->rowDie($sql,
    array(':LID' => $link_id, ':EID' => $event)
);

if ( $row === false ) {
    die('Bad link_id');
}

$return_url = 'index';
if ( $order_by ) $return_url = U::add_url_parm($return_url, 'order_by', $order_by);
$desc = U::get($_REQUEST,'desc');
if ( $desc ) $return_url = U::add_url_parm($return_url, 'desc', $desc);


$OUTPUT->header();
?>
<link rel=import href="<?= $CFG->staticroot ?>/webcomponents/tsugi/tsugi-analytics-chart.html">
<link rel=import href="<?= $CFG->staticroot ?>/webcomponents/tsugi/tsugi-analytics-script.html">
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
<p>
<a href="<?= $return_url ?>" class="btn btn-default">Back</a>
</p>
Context: <?= htmlentities($row['context_title']); ?><br/>
Link: <?= htmlentities($row['link_title']); ?><br/>
<tsugi-analytics-chart chartid="chart_div"></tsugi-analytics-chart>
</div>
<?php

$OUTPUT->footerStart();
$analytics_url = 'analytics?link_id='.$link_id;
echo('<tsugi-analytics-script chartid="chart_div" chartdata="'.$analytics_url.'"></tsugi-analytics-script>');
$OUTPUT->footerEnd();
