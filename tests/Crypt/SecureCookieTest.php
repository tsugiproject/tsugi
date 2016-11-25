<?php

use \Tsugi\Crypt\SecureCookie;

require_once('src/Crypt/Aes.php');
require_once('src/Crypt/AesCtr.php');
require_once('src/Crypt/SecureCookie.php');
require_once "src/Config/ConfigInfo.php";

// From: http://www.movable-type.co.uk/scripts/aes-php.html

class SecureCookieTest extends PHPUnit_Framework_TestCase
{
    public function testSecureCookie() {
        global $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');
        $CFG->cookiepad = 'Helloworld';
        $id = 1;
        $guid = 'xyzzy';
        $ct = SecureCookie::create($id,$guid,false);
        $pieces = SecureCookie::extract($ct,false);
        $this->assertEquals(count($pieces), 2);
        $this->assertEquals($pieces[0], $id);
        $this->assertEquals($pieces[1], $guid);
    }

}

?>
