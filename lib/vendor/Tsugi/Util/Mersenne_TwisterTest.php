<?php
require_once "Mersenne_Twister.php";

$should_return = array( 
2, 3, 3, 6, 5, 1, 5, 3, 4, 2, 1, 6, 5, 4, 0, 
6, 0, 2, 0, 3, 5, 6, 6, 3, 1, 3, 2, 6, 3, 3 );

$code = 12345;
$MT = new \Tsugi\Util\Mersenne_Twister($code);
for($i=0; $i < 30; $i++ ) {
    $ran = $MT->getNext(0,6);
    if ( $should_return[$i] != $ran ) {
        echo("\n");
        echo("Mismatch i=$i ran=$ran, should be=".$should_return[$i]."\n");
    }
    echo $ran." ";
}

echo "\n";
