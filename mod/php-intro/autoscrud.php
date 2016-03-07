<?php

$reference_implementation = 'http://www.php-intro.com/solutions/autoscrud';

$assignment_type = "Assignment";
$assignment_type_lower = "assignment";

$title_singular = 'Automobile';
$title_plural = 'Automobiles';
$table_name = 'autos';
$table_name_lower = 'autos';

$fields = array(
    array('Make', 'make', false),
    array('Model', 'model', false),
    array('Year', 'year', 'i',false,10),
    array('Mileage', 'mileage', 'i',false,10)
);

require_once "crud.php";

