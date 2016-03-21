<?php

$reference_implementation = 'http://www.php-intro.com/solutions/res-profile';

$assignment_type = "Assignment";
$assignment_type_lower = "assignment";

$title_singular = 'Profile';
$title_plural = 'Profles';
$table_name = 'Profile';
$table_name_lower = 'profile';

$fields = array(
    array('First Name', 'first_name', false),
    array('Last Name', 'last_name', false),
    array('Email', 'email', 'e'),
    array('Headline', 'headline', false),
    array('Summary', 'summary', false),
);

require_once "crud.php";

