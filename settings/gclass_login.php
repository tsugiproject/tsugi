<?php

session_start();

\Tsugi\Controllers\Login::setReturnUrl($CFG->wwwroot . '/settings');
header('Location: '.$CFG->wwwroot . '/gclass/login');
