<?php
require_once "../config.php";

use \Tsugi\UI\Table;

$url_goback = Table::makeUrl('gallery.php', $_GET, Array('user_id'=>false));
$url_stay = Table::makeUrl('gallery-detail.php', $_GET, Array('user_id'=>false));
require_once "rate.php";
