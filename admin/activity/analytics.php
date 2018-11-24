<?php

use \Tsugi\Util\U;

if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

session_start();

\Tsugi\Core\LTIX::getConnection();

use \Tsugi\Event\Entry;

if ( ! isAdmin() ) die('Must be admin');
$link_id = U::get($_GET, 'link_id');

if ( ! $link_id ) die('No link_id');

$sql = "SELECT link_count, activity FROM {$CFG->dbprefix}lti_link_activity
    WHERE link_id = :link_id";
$values = array(':link_id' => $link_id);
$row = $PDOX->rowDie($sql, $values);

$ent = new Entry();
$ent->deSerialize($row['activity']);
$ent->total = $row['link_count']+0;
$retval = $ent->viewModel();

echo(json_encode($retval,JSON_PRETTY_PRINT));
