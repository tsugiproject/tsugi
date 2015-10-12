<?php
require_once("../../../config.php");
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

$LTI = LTIX::requireData();

$local_path = route_get_local_path(__DIR__);
$pos = strpos($local_path,'?');
if ( $pos > 0 ) $local_path = substr($local_path,0,$pos);

if ( $local_path == "assn_11_actual.txt" || $local_path == "assn_11_sample.txt" ) {
    $code = $USER->id+$LINK->id+$CONTEXT->id;
    if ( $local_path == "assn_11_sample.txt" ) {
        echo("This file contains the sample data\n\n");
        $code = 42;
    } else {
        echo("This file contains the actual data for your assignment - good luck!\n\n");
    }

    $handle = fopen("../static/intro.txt", "r");
    if ($handle) {
        $count = 0;
        $MT = new Mersenne_Twister($code);
        header('Content-Type: text/plain');
        // header('Content-Disposition: attachment; filename='.$local_path.';');
        while (($line = fgets($handle)) !== false) {
            $count++;
            $choose = ($count < 400 ) ? $MT->getNext(0,9) : 1 ;
            if ( $choose != 0 ) {
                echo($line);
                continue;
            }
            $howmany = $MT->getNext(1,3);
            if ( $howmany == 1 ) {
                echo($MT->getNext(1,10000).' '.$line);
            } else if ( $howmany == 2 ) {
                echo($MT->getNext(1,10000).' '.rtrim($line).' '.$MT->getNext(1,10000)."\n");
            } else if ( $howmany == 3 ) {
                $words = explode(' ',$line);
                if ( count($words) > 3 ) {
                    for($i=0; $i<count($words);$i++) {
                        echo($words[$i].' ');
                        if ( $i < 3 ) {
                            echo($MT->getNext(1,10000).' ');
                        }
                    }
                } else {
                    echo($MT->getNext(1,10000).' '.$MT->getNext(1,10000).' '.$MT->getNext(1,10000)."\n");
                }
            }

            // process the line read.
        }
        echo("42\n");
        echo("The end\n");

        fclose($handle);
    } else {
        echo("<p>File not found: intro.txt</p>\n");
        return;
    } 

    return;
}
?>
<p>File not found <?= htmlentities($local_path) ?></p>
