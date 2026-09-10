<?php
require_once __DIR__ . "/../config.php";
\Tsugi\Core\LTIX::getConnection();

use \Tsugi\Grades\UI;

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back'), 'index.php');

$GRADE_DETAIL_CLASS = new \Tsugi\Grades\SimpleGradeDetail();

UI::GradeTable($GRADE_DETAIL_CLASS, 'none', /* text */ false, $menu);
