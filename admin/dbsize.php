<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
LTIX::getConnection();

$OUTPUT->header();
$OUTPUT->bodyStart();
// No Nav - this is in a frame

$dbname = false;
$m = array();
$retval = preg_match('/.*dbname=([^,\'"&?{}#$.]+)/',$CFG->pdo,$m);
if ( $retval && isset($m[1])) {
    $dbname = $m[1];
}

if ( ! $dbname ) {
    echo("Could not determine database name");
    $OUTPUT->footer();
    return;
}
echo("<h1>Database size for ".htmlentities($dbname)."</h1>\n");

$sql = "
SELECT 
    table_name AS `Table`, 
    table_rows AS `Rows`,
    round(((data_length + index_length) / 1024 / 1024), 2) AS `Size`
FROM information_schema.TABLES WHERE table_schema = :DBNAME";

$parms = array(':DBNAME' => $dbname);

if ( strlen($CFG->dbprefix) > 0 ) {
    $parms[':PREFIX'] = $CFG->dbprefix;
    $sql .= ' AND table_name like :PREFIX';
}

$rows = $PDOX->allRowsDie($sql, $parms);

?>
<table class="table">
<thead>
<th>Table</th><th>Rows</th><th>Size (MB)</th>
</thead>
<?php
foreach ( $rows as $row ) {
    echo('<tr scope="row"><td>');
    echo(htmlentities($row['Table']));
    echo("</td><td>");
    echo(htmlentities($row['Rows']));
    echo("</td><td>");
    echo(htmlentities($row['Size']));
    echo("</td></tr>\n");
}
echo("</table>\n");

$OUTPUT->footer();

