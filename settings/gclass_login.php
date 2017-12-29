<?php

session_start();

$_SESSION['login_return'] = $CFG->wwwroot . '/settings';
header('Location: '.$CFG->wwwroot . '/gclass/login');
