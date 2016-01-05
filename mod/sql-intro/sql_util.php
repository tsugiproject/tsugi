<?php

use \Tsugi\Util\Mersenne_Twister;

require_once "names.php";
require_once "courses.php";

function makeRoster($code,$course_count=false,$name_count=false) {
    global $names, $courses;
    $MT = new Mersenne_Twister($code);
    $retval = array();
    $cc = 0;
    foreach($courses as $k => $course) {
    $cc = $cc + 1;
    if ( $course_count && $cc > $course_count ) break;
        $new = $MT->shuffle($names);
        $new = array_slice($new,0,$MT->getNext(17,53));
        $inst = 1;
        $nc = 0;
        foreach($new as $k2 => $name) {
            $nc = $nc + 1;
            if ( $name_count && $nc > $name_count ) break;
            $retval[] = array($name, $course, $inst);
            $inst = 0;
        }
    }
    return $retval;
}


// Load the export to JSON format from MySQL
function load_mysql_json_export($data) {

    $pos = 0;
    $retval = array();
    $errors = array();
    echo("\n<pre>\n");
    while ( $pos < strlen($data) ) {
        $nxt = strpos($data,'// ',$pos);
        if ( $nxt === false ) break;
        $com = strpos($data,'// ',$nxt+3);
        $start = strpos($data,'[{"',$nxt+3);
        if ( $start === false ) break;
        if ( $com !== false && $com < $start ) {
            $pos = $com; // Skip to the next comment
            continue;
        }
        $name = trim(substr($data, $nxt+3,$start-$nxt-3));
        $pieces = explode('.',$name);
        if ( count($pieces) > 1 ) {
            $name = $pieces[count($pieces)-1];
        }
        $end = strpos($data,'"}]',$start);
        if ( $end === false ) break;

        $json_str = substr($data, $start, 3+$end-$start);
        $json = json_decode($json_str, true);
        if ( $json === NULL ) {
            $errors[] = "Unable to parse the $name JSON ".json_last_error();
            $pos = $end;
            continue;
        }

        $retval[$name] = $json;
        if ( count($json) < 1 ) {
            $pos = $end;
            continue;
        }

        $key = strtolower($name).'_id';
        if ( !isset($json[0][$key]) ) {
            $pos = $end;
            continue;
        }

        $table = array();
        foreach($json as $row) {
            if ( isset($row[$key]) && is_numeric($row[$key]) ) {
                $table[$row[$key]+0] = $row;
            }
        }
        $retval[$name."_table"] = $table;
        $pos = $end;
    }
    // echo("<pre>\n"); print_r($retval); echo("</pre>\n");
    return $retval;
}
