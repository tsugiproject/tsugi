<?php

require_once 'setup.php';
require_once 'config.php';
session_start();

print "<pre>\n";
print "Welcome to Auto...\n";
print_r($_SESSION['lti']);
print "</pre>\n";

