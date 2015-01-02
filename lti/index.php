<?php
require_once("../config.php");

$local_path = route_get_local_path(__DIR__);

if ( $local_path == "content_item.xml" ) {
    require_once("content_item_xml.php");
    return;
}
?>
<p>TBD</p>
