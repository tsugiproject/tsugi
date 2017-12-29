<?php

session_start();

$_SESSION['login_return'] = $CFG->wwwroot . '/store';
header('Location: '.$CFG->wwwroot . '/gclass/login');
