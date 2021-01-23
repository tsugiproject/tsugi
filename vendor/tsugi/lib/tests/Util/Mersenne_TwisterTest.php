<?php

require_once('src/Util/Mersenne_Twister.php');


$should_return = array( 
2, 3, 3, 6, 5, 1, 5, 3, 4, 2, 1, 6, 5, 4, 0, 
6, 0, 2, 0, 3, 5, 6, 6, 3, 1, 3, 2, 6, 3, 3 );

echo("Max Integer:".PHP_INT_MAX."\n");
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

// Truncated Gaussian
$hist = array();
for($i=0;$i<200;$i++) $hist[$i] = 0;
$tot = 0;
$cnt = 0;
for($i=0;$i<10000; $i++) {
    $tao = 100;
    $lambda = 0.2;
    $ran = $MT->gaussian($tao);
    $hist[$ran]++;
    $cnt++;
    $tot = $tot + $ran;
}
for($i=0;$i<100;$i++) echo($hist[$i].' ');
echo("\n");
$ave = $tot / $cnt;
echo("lambda=$lambda tao=$tao ave=$ave cnt=$cnt\n");

if ( PHP_INT_MAX == 2147483647 ) { 
    echo("\n*****\n");
    echo("This test will fail on 32-bit PHP-Systems.\n");
    echo("*****\n");
}

