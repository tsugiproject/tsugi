<?php

use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "courses.php";

function makeRoster($code) {
    global $names, $courses;
    $MT = new Mersenne_Twister($code);
    $retval = array();
    foreach($courses as $k => $course) {
        $new = $MT->shuffle($names);
        $new = array_slice($new,0,$MT->getNext(17,53));
        $inst = 1;
        foreach($new as $k2 => $name) {
            $retval[] = array($name, $course, $inst);
            $inst = 0;
        }
    }
    return $retval;
}
