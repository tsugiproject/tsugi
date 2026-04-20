<?php

require_once "../config.php";

use \Tsugi\UI\Table;

$STUDENT_RETURN = Table::makeUrl('gallery-detail.php', $_GET);
require_once("student.php");
