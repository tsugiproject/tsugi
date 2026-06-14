<?php

session_start();

\Tsugi\Controllers\Login::setReturnUrl($CFG->wwwroot . '/store');
header('Location: '.$CFG->wwwroot . '/gclass/login');
