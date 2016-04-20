<?php

$title_singular = 'Address';
$title_plural = 'Addresses';
$table_name = 'Address';
$table_name_lower = 'track';
$reference_implementation = 'http://www.php-intro.com/exams/fin-f15-address';

$fields = array(
    array('Name', 'name', false),
    array('Address', 'addr', false),
    array('City', 'city', false),
    array('State', 'state', 2, 2,10),
    array('Zip', 'zip', 'i',false,10)
);


require_once "final.php";

