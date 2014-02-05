<?php 

// From: http://www.movable-type.co.uk/scripts/aes-php.html

  require 'aes.class.php';     // AES PHP implementation
  require 'aesctr.class.php';  // AES Counter Mode implementation
  
  $timer = microtime(true);
  
  // initialise password & plaintesxt if not set in post array (shouldn't need stripslashes if magic_quotes is off)
  $pw = 'L0ck it up saf3';
  $pt = 'pssst ... đon’t tell anyøne!';
  $encr = AesCtr::encrypt($pt, $pw, 256) ;
  $decr = AesCtr::decrypt($encr, $pw, 256);
  echo("E: ".$encr."\n");
  echo("D: ".$decr."\n");

?> 
