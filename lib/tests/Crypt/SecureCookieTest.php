<?php

use \Tsugi\Crypt\SecureCookie;

require_once('src/Crypt/AesOpenSSL.php');
require_once('src/Crypt/SecureCookie.php');
require_once "src/Config/ConfigInfo.php";

// From: http://www.movable-type.co.uk/scripts/aes-php.html

class SecureCookieTest extends \PHPUnit\Framework\TestCase
{
    public function testAESCookie() {
        global $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');
        $CFG->cookiepad = 'Helloworld';
        $CFG->cookiesecret = '42';
        $pt = 'HHGTTG';
        $ct = SecureCookie::encrypt($pt);
        $this->assertNotEquals($pt, $ct);
        $pt2 = SecureCookie::decrypt($ct);
        $this->assertEquals($pt, $pt2);
    }

    public function testSecureCookie() {
        global $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');
        $CFG->cookiepad = 'Helloworld';
        $CFG->cookiesecret = '42';
        $id = 1;
        $guid = 'xyzzy';
        $cid = '999';
        $ct = SecureCookie::create($id,$guid,$cid,false);
        $pieces = SecureCookie::extract($ct,false);
        $this->assertEquals(count($pieces), 3);
        $this->assertEquals($pieces[0], $id);
        $this->assertEquals($pieces[1], $guid);
        $this->assertEquals($pieces[2], $cid);
    }

}

?>
