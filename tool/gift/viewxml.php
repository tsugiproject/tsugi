<?php
session_start();
if ( !isset($_SESSION['quiz']) ) die('Missing quiz data');
header('Content-type: application/xml; charset=utf-8');

echo($_SESSION['quiz']);

