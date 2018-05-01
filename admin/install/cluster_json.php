<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Git;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Cache;
use \Tsugi\UI\Lessons;

if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
session_start();
if ( ! isset($_SESSION["admin"]) ) {
    Net::send403();
    die_with_error_log('Must be admin to list repositories');
}

$retval = array();

require_once("../admin_util.php");
require_once("install_util.php");

$PDOX = LTIX::getConnection();
if ( isset($CFG->git_command) ) {
    Git::set_bin($CFG->git_command);
}

$entries = getClusterInfo();
$ipaddr = false;
$servers = array();
$tools = array();
$serverIP = Net::serverIP();
foreach($entries as $entry) {
    if ( $entry['ipaddr'] != $ipaddr && $ipaddr && count($tools) > 0 ) {
        $server = new \stdClass();
        $server->ipaddr = $ipaddr;
        $server->ipaddrid = str_replace('.','_',$ipaddr);
        $server->local = $ipaddr == $serverIP;
        $server->tools = $tools;
        $install = $PDOX->allRowsDie("SELECT clone_url, T.created_at AS created_at
            FROM lms_tools AS T LEFT JOIN lms_tools_status AS S
                ON S.tool_id = T.tool_id AND S.ipaddr = :ipaddr
            WHERE ISNULL(S.ipaddr)",
            array(':ipaddr' => $ipaddr)
        );
        $server->install = $install;
        $servers[] = $server;
        $tools = array();
    }
    $ipaddr = $entry['ipaddr'];
    $tools[] = $entry;
}

if ( $ipaddr && count($tools) > 0 ) {
    $server = new \stdClass();
    $server->ipaddr = $ipaddr;
    $server->local = $ipaddr == $serverIP;
    $server->tools = $tools;
    $install = $PDOX->allRowsDie("SELECT clone_url, T.created_at AS created_at
        FROM lms_tools AS T LEFT JOIN lms_tools_status AS S
            ON S.tool_id = T.tool_id AND S.ipaddr = :ipaddr
        WHERE ISNULL(S.ipaddr)",
        array(':ipaddr' => $ipaddr)
    );
    $server->install = $install;
    $servers[] = $server;
}

$retval['servers'] = $servers;

echo(json_encode($retval, JSON_PRETTY_PRINT));
