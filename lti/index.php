<?php
require_once("../config.php");

$local_path = route_get_local_path(__DIR__);

if ( $local_path == "content-item.xml" ) {
    require_once("content-item-xml.php");
    return;
}
?>
<p>File not found</p>
