<?php

use \Tsugi\Crypt\SecureCookie;

require_once('src/Crypt/Aes.php');
require_once('src/Crypt/AesCtr.php');
require_once('src/Crypt/SecureCookie.php');

// From: http://www.movable-type.co.uk/scripts/aes-php.html

class SecureCookieTest extends PHPUnit_Framework_TestCase
{
    public function testSecureCookie() {
        $id = 1;
        $guid = 'xyzzy';
        $ct = SecureCookie::createSecureCookie($id,$guid,false);
        $pieces = SecureCookie::extractSecureCookie($ct,false);
        $this->assertEquals(count($pieces), 2);
        $this->assertEquals($pieces[0], $id);
        $this->assertEquals($pieces[1], $guid);
    }

}

?>
