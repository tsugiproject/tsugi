<?php

require_once "../config.php";

use \Tsugi\UI\Table;

$STUDENT_RETURN = Table::makeUrl('gallery.php', $_GET, Array('user_id'=>false));

require_once("student.php");
