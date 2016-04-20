<?php

$folder = basename(__FILE__, ".php");

// Reference can be relative
$reference = "http://www.php-intro.com/solutions/$folder";

// These two must be absolute as they will be in peer.json
$reference_implementation = "http://www.php-intro.com/solutions/$folder";
$specification = "http://www.php-intro.com/assn/$folder";

$assignment_type = "Sample Exam";
$assignment_type_lower = "exam";

$title_singular = 'Pizza Store';
$title_plural = 'Pizza Stores';
$table_name = 'pizza';
$table_name_lower = 'pizza';

$fields = array(
    array('Store', 'store', false),
    array('Address', 'address', false),
    array('Rating', 'rating', 'i',false,10),
    array('Best Pizza', 'best', false)
);

require_once("crud.php");
