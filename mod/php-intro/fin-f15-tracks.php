<?php

$title_singular = 'Music Track';
$title_plural = 'Tracks';
$reference_implementation = 'http://localhost:8888/php-intro/php-solutions/exams/fin-f15-tracks';

$fields = array(
    array('Title', 'title', false),
    array('Album', 'album', false),
    array('Artist', 'artist', false),
    array('Seconds', 'seconds', 'i',false,10),
    array('Rating', 'rating', 'i',false,10)
);

require_once "final.php";

