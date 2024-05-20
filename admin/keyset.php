<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
require_once("admin_util.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use \Tsugi\Core\Keyset;

if (U::isKeyNotEmpty($_POST, "maintain") ) {
    Keyset::maintain();
}

$sign_kid = null;
$sign_privkey = null;
$success = false;

if (U::isKeyNotEmpty($_POST, "getkey") ) {
    $success = Keyset::getSigning($sign_privkey, $sign_kid);
}

$keyset_url = $CFG->wwwroot . "/lti/keyset.php";

$apc_check = U::appCacheGet('keyset_last_check', 0);
$now = time();
$delta = -1;
if ( $apc_check > 0 ) $delta = abs($now-$apc_check);
$privkey = U::appCacheGet('keyset_privkey', null);
$kid = U::appCacheGet('keyset_kid', null);

?>
<html>
<head>
</head>
<body>
<h1>Keyset Detail</h1>
<p>Keyset URL:
<a href="<?= $keyset_url ?>" target="_blank"><?= $keyset_url ?></a>
</p>
<form method="POST">
<input type="submit" name="maintain" value="Maintain / Check for Key Rotation">
<input type="submit" name="getkey" value="Retrieve Signing Key">
</form>
<?php

if ( is_string($success) ) {
    echo("<p>Problem retrieving key: ".$success."</p>\n");
} else if ( $success == true ) {
      echo("<p>Current Signing KID: ".$sign_kid."\n");
      echo("<p>Current Signing Private Key:: ".substr($sign_privkey,0, 80)." ...\n");
      echo("<hr/>\n");
}

if ( U::apcuAvailable() ) {
   echo("<p>Cache is available</p>\n");
   echo("<p>Last apc check: ".$delta." seconds ago.\n");
   if ( $privkey != null && $kid != null ) {
      echo("<p>Cached KID: ".$kid."\n");
      echo("<p>Cached Private Key:: ".substr($privkey,0, 80)." ...\n");
    } else {
      echo("<p>There is no key in the cache. Cache is empty until a key is retrieved.  Keys in the cache expire after 10 minutes.</p>\n");
    }
} else {
    echo("<p>Cache is not available</p>\n");
}

        $stmt = $PDOX->queryDie(
        "SELECT * FROM {$CFG->dbprefix}lti_keyset
            WHERE deleted = 0 AND pubkey IS NOT NULL AND privkey IS NOT NULL
            ORDER BY created_at DESC LIMIT 10"
        );
        if ( $stmt->success ) {
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $rows = array();
        }

        if ( count($rows) > 0 ) {
            echo("<p>Key pairs in the database:</p>\n");
            echo("<table border=\"3\">\n");
?>
<thead><tr><td>Created_at</td><td>Public Key</td><td>Private Key</td></tr></thead>
<?php
            foreach($rows as $row) {
                $row['privkey'] = str_replace("\n", " ", substr($row['privkey'], 0, 40)) . " ...";
                $row['pubkey'] = str_replace("\n", " ", substr($row['pubkey'], 0, 40)) . " ...";
                echo("<tr>\n");
                echo("<td>".$row['created_at']."</td>\n");
                echo("<td>".$row['pubkey']."</td>\n");
                echo("<td>".$row['privkey']."</td>\n");
                echo("</tr>\n");
            }
            echo("</table>\n");
        } else {
            echo("<p>No key pairs in the database</p>\n");
        }

?>
</body>
</html>

