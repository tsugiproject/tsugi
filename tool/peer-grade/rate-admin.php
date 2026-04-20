<?php
require_once "../config.php";

use \Tsugi\UI\Table;

$url_goback = Table::makeUrl('student.php', $_GET); // Keep the user_id variable
$url_stay = Table::makeUrl('rate-admin.php', $_GET); // Keep the user_id variable
require_once "rate.php";
