<?php

use \Tsugi\Util\U;
use \Tsugi\Config\ConfigInfo;

class UTest extends \PHPUnit\Framework\TestCase
{
    public function testGet() {
        $this->assertFalse(U::goodFolder(' '));
        $this->assertFalse(U::goodFolder('a b'));
        $this->assertTrue(U::goodFolder('ab'));
        $this->assertFalse(U::goodFolder('1a'));
        $this->assertFalse(U::goodFolder('ai!'));
        $this->assertFalse(U::goodFolder('ASJHJGAai!'));
        $this->assertTrue(U::goodFolder('ASJHJGAai'));
        $this->assertTrue(U::goodFolder('ASJ-JGAai'));
        $this->assertTrue(U::goodFolder('ASJ_JGAai'));
        $this->assertFalse(U::goodFolder('-ASJ_JGAai'));
        $this->assertFalse(U::goodFolder('_ASJ_JGAai'));
    }

    public function testRest() {
        // Note this is normally $_SERVER['REQUEST_URI'] so there is not http:// ...
        $this->assertEquals(U::get_rest_path('/py4e/lessons/intro'), '/py4e/lessons/intro');
        $this->assertEquals(U::get_rest_path('/py4e/lessons/intro/'), '/py4e/lessons/intro');
        $this->assertEquals(U::get_rest_path('/py4e/lessons/intro?x=2'), '/py4e/lessons/intro');
        $this->assertEquals(U::get_rest_path('/py4e/lessons/intro/?x=2'), '/py4e/lessons/intro');

        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro'), '/py4e/lessons');
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro?x=2'), '/py4e/lessons');
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro/'), '/py4e/lessons/intro');
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro/?x=2'), '/py4e/lessons/intro');
    }

    public function testRelative() {
        $this->assertEquals(U::remove_relative_path('/a/b/c'), '/a/b/c');
        $this->assertEquals(U::remove_relative_path('/a/b/c/'), '/a/b/c/');
        $this->assertEquals(U::remove_relative_path('/a/./c/'), '/a/c/');
        $this->assertEquals(U::remove_relative_path('/a/../c/'), '/c/');
        $this->assertEquals(U::remove_relative_path('/a/b/../../c/'), '/c/');
    }

    public function testParseController() {
        global $CFG;
        if ( ! isset($CFG) ) $CFG = new \stdclass();
        $this->assertEquals(U::parse_rest_path('/a/b/c','/a/koseu.php'), array('/a','b','c'));
        $this->assertEquals(U::parse_rest_path('/py4e/lessons/intro?x=2','/py4e/koseu.php'), array('/py4e','lessons','intro'));
        $this->assertEquals(U::parse_rest_path('/py4e/lessons/intro/fred/sarah?x=2','/py4e/koseu.php'), array('/py4e','lessons','intro/fred/sarah'));
        $this->assertEquals(U::parse_rest_path('/lessons/intro?x=2','/koseu.php'), array('','lessons','intro'));
        $this->assertEquals(U::parse_rest_path('/lessons/intro/fred/sarah?x=2','/koseu.php'), array('','lessons','intro/fred/sarah'));

        // Running from the wrong directory
        $this->assertEquals(U::parse_rest_path('/a/b/c','/x/koseu.php'), false);

        // Object version...
        $CFG->wwwroot = "http://www.example.com:8888/tsugi";
        $path = U::rest_path('/py4e/lessons/intro?x=2','/py4e/koseu.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'/py4e');
        $this->assertEquals($path->controller,'lessons');
        $this->assertEquals($path->extra,'intro');
        $path = U::rest_path('/py4e/lessons/intro/fred/sarah?x=2','/py4e/koseu.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'/py4e');
        $this->assertEquals($path->controller,'lessons');
        $this->assertEquals($path->extra,'intro/fred/sarah');
        $path = U::rest_path('/lessons/intro?x=2','/koseu.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'');
        $this->assertEquals($path->controller,'lessons');
        $this->assertEquals($path->extra,'intro');
        $path = U::rest_path('/lessons/intro/fred/sarah?x=2','/koseu.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'');
        $this->assertEquals($path->controller,'lessons');
        $this->assertEquals($path->extra,'intro/fred/sarah');
        $this->assertEquals($path->action,'intro');
        $this->assertEquals($path->parameters,array('fred', 'sarah'));

        // When there is no controller and we fall into index.php directly
        $path = U::rest_path('/a','/a/index.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'/a');
        $this->assertEquals($path->controller,'');
        $this->assertEquals($path->extra,'');
        $this->assertEquals($path->full,'/a');
        $this->assertEquals($path->current,'/a');
        $this->assertEquals($path->action,'');
        $this->assertEquals($path->parameters,array());

        // When there is no controller and we go to index.php
        $path = U::rest_path('/a/','/a/index.php');
        $this->assertEquals($path->base_url,'http://www.example.com:8888');
        $this->assertEquals($path->parent,'/a');
        $this->assertEquals($path->controller,'');
        $this->assertEquals($path->extra,'');
        $this->assertEquals($path->full,'/a');
        $this->assertEquals($path->current,'/a');
        $this->assertEquals($path->action,'');
        $this->assertEquals($path->parameters,array());
    }

    // https://stackoverflow.com/questions/30231476/i-want-to-array-key-and-array-value-comma-separated-string
    // https://stackoverflow.com/questions/4923951/php-split-string-in-key-value-pairs
    public function testSerialization() {
        $arar = Array ( 1 => 42 ,2 => 43, 3 => 44 );
        $str = U::array_Integer_Serialize($arar);
        $this->assertEquals($str, '1=42,2=43,3=44');
        $newa = U::array_Integer_Deserialize($str);
        $this->assertEquals($arar, $newa);
    }

    // http://php.net/manual/en/function.array-shift.php#84179
    public function testKShift() {
        $arr = array('x'=>'ball','y'=>'hat','z'=>'apple');
        $thing = U::array_kshift($arr);
        $this->assertEquals($thing,array('x'=>'ball'));
        $this->assertEquals($arr,array('y'=>'hat','z'=>'apple'));
    }

    public function testgetServerBase() {
        $a = array(
            'https://www.py4e.com' => 'https://www.py4e.com',
            'https://www.py4e.com/abc' => 'https://www.py4e.com',
            'https://www.py4e.com:443/abc' => 'https://www.py4e.com:443',
            'http://localhost:8888/abc' => 'http://localhost:8888',
            'http://localhost:8888' => 'http://localhost:8888',
        );

        foreach($a as $k => $v ) {
            $retval = U::getServerBase($k);
            $this->assertEquals($retval, $v);
        }
    }

    public function testhtmlspec_utf8() {
        $t1 = U::htmlspec_utf8("zap");
        $this->assertEquals($t1, "zap");
        $t1 = U::htmlspec_utf8(42);
        $this->assertEquals($t1, 42);
        $t1 = U::htmlspec_utf8("zap'zap");
        $this->assertEquals($t1, "zap&#039;zap");
        $t1 = U::htmlspec_utf8("zap&zap");
        $this->assertEquals($t1, "zap&amp;zap");
        $t1 = U::htmlspec_utf8("zap?zap");
        $this->assertEquals($t1, "zap?zap");
        $t1 = U::htmlspec_utf8("zap<zap");
        $this->assertEquals($t1, "zap&lt;zap");
        // PHP 8.1
        $t1 = U::htmlspec_utf8(null);
        $this->assertEquals($t1, "");
        // Arrary is just a guardian test
        $t1 = U::htmlspec_utf8(array('bob'));
        $this->assertEquals($t1, array('bob'));
    }


    public function testhtmlent_utf8() {
        $t1 = U::htmlent_utf8("zap");
        $this->assertEquals($t1, "zap");
        $t1 = U::htmlent_utf8(42);
        $this->assertEquals($t1, 42);
        $t1 = U::htmlent_utf8("zap'zap");
        $this->assertEquals($t1, "zap&#039;zap");
        $t1 = U::htmlent_utf8("zap&zap");
        $this->assertEquals($t1, "zap&amp;zap");
        $t1 = U::htmlent_utf8("zap?zap");
        $this->assertEquals($t1, "zap?zap");
        $t1 = U::htmlent_utf8("zap<zap");
        $this->assertEquals($t1, "zap&lt;zap");
        // PHP 8.1
        $t1 = U::htmlent_utf8(null);
        $this->assertEquals($t1, "");
    }

    public function testStrlen() {
        // Non string parameters in the old days were treated as "stringy" - if
        // something can be converted to a string, what would its length be?
        // PHP beyond 8.2 might get cranky with these illegal calls.
        // Test these in case PHP changes its mind in the future
        $this->assertEquals(strlen(''), 0);
        $this->assertEquals(strlen('0'), 1);
        $this->assertEquals(strlen('bob'), 3);
        $this->assertEquals(strlen(42), 2);
        $this->assertEquals(strlen(4.2), 3);
        $this->assertEquals(strlen(false), 0);
        // $this->assertEquals(strlen(null), 0); // Breaks on 8.2 
        $this->assertEquals(strlen(true), 1);  // Why oh why?

        // Make a sane strlen
        $this->assertEquals(U::strlen(''), 0);
        $this->assertEquals(U::strlen('0'), 1);
        $this->assertEquals(U::strlen('bob'), 3);
        $this->assertEquals(U::strlen(42), 2);
        $this->assertEquals(U::strlen(4.2), 3);
        $this->assertEquals(U::strlen(false), 0);
        $this->assertEquals(U::strlen(null), 0);
        $this->assertEquals(U::strlen(true), 0);  // Depart from PHP here
    }

    public function testEmpty() {
        // Sane stuff
        $this->assertTrue(U::isEmpty(''));
        $this->assertTrue(U::isEmpty(null));
        $this->assertTrue(U::isEmpty(false));
        $this->assertTrue(U::isNotEmpty('bob'));
        $this->assertTrue(U::isNotEmpty('0'));

        // Weird but like 7.4 strlen
        $this->assertTrue(U::isNotEmpty(42));
        $this->assertTrue(U::isNotEmpty(4.2));


        // We are not going to follow PHP down these rabbit holes :)
        $this->assertTrue(U::isEmpty(false));
        $this->assertFalse(U::isNotEmpty(true));

        // Lets just run the PHP empty() through its paces as of 8.2
        // sane and otherwise - to check if PHP changes its mind in a
        // future version.
        $this->assertTrue(empty(''));
        $this->assertTrue(empty(null));
        $this->assertTrue(empty(false));
        $this->assertFalse(empty(42));
        $this->assertFalse(empty(4.2));

        $this->assertFalse(empty('false'));
        $this->assertFalse(empty('bob'));

        // Rasmus, this should be false - what are they thinking
        $this->assertTrue(empty('0'));

        /*  strlen(42) = 2
            strlen(4.2) = 3
            strlen(false) = 0
            strlen(true) = 1
            empty(42) = false
            empty(4.2) = false
            empty(false) = true
            empty(true) = false
        */

    }

    public function testJsonDecode() {
        $this->assertIsObject(U::json_decode(null));
        $this->assertIsObject(U::json_decode(false));
        $this->assertIsObject(U::json_decode(42));
        $this->assertIsObject(U::json_decode('funky'));
        $this->assertIsObject(U::json_decode('{ "zap }'));

        $json = '{ "key" : 42 }';
        $js = U::json_decode($json);
        $this->assertIsObject($js);

        // $this->assertObjectHasProperty('key', $js);
        $this->assertTrue(property_exists($js, 'key'));
        // $this->assertObjectHasNotProperty('bob', $js);
        $this->assertFalse(property_exists($js, 'bob'));

        // Bad json
        $json = '{ "key : 42 }';
        $js = U::json_decode($json);
        $this->assertIsObject($js);

        $this->assertFalse(property_exists($js, 'key'));
        $this->assertFalse(property_exists($js, 'bob'));
    }

    public function testUrlParm() {
        $url = "http://www.tsugi.org";
        $this->assertEquals(U::add_url_parm(null, null, null), null);
        $this->assertEquals(U::add_url_parm('bob', null, null), 'bob');
        $this->assertEquals(U::add_url_parm('bob', 'x', null), 'bob');
        $this->assertEquals(U::add_url_parm('bob', null, 'y'), 'bob');
        $this->assertEquals(U::add_url_parm('bob', 'x', 'y'), 'bob?x=y');
        $this->assertEquals(U::add_url_parm('bob?a=b', 'x', 'y'), 'bob?a=b&x=y');
    }

    public function testIsKeyNotEmpty() {
        $arr = array("bob" => "42", "sam" => 43, "sarah" => null, "sue" => false);
        $this->assertTrue(U::isKeyNotEmpty($arr, "bob"));
        $this->assertTrue(U::isKeyNotEmpty($arr, "sam"));
        $this->assertFalse(U::isKeyNotEmpty($arr, "sarah"));
        $this->assertFalse(U::isKeyNotEmpty($arr, "sue"));
        $this->assertFalse(U::isKeyNotEmpty($arr, ""));
        $this->assertFalse(U::isKeyNotEmpty($arr, "zap"));
    }

    public function testServerPrefix() {
        $CFG = new ConfigInfo("bob", "http://www.zap.com/tsugi");
        $this->assertEquals($CFG->serverPrefix(), "www.zap.com/tsugi");
        $CFG->apphome = "https://www.zap.com";
        $this->assertEquals($CFG->serverPrefix(), "www.zap.com");
        $CFG->apphome = "www.zap.com";
        $this->assertEquals($CFG->serverPrefix(), "www.zap.com");
        // Use MD5 if too long
        $CFG->apphome = "dlsjsdflkjdfljdljdflkjflkjlkjfdklja;ljdfsl;jgfl;jgfl;jgl;jgfds;ljgfl;jgfd;ljdfl;kjdsljalwskjaskhfkdjhdfgkjhgkhkdshkghdfkhgfkhfgkh";
        $this->assertEquals($CFG->serverPrefix(), "227b19f82bca5eb23f9cd02cfe34dbbe");
    }

    public function testSha256() {
        $out = lti_sha256("hello world");
        $this->assertEquals($out, "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9");
        $out = lti_sha256(null);
        $this->assertEquals($out, null);
    }

    public function testStartsWith() {
        $this->assertTrue(U::startsWith("csev@umich.edu", "csev"));
        $this->assertTrue(U::startsWith("csev@umich.edu", ""));
        $this->assertFalse(U::startsWith("csev@umich.edu", "cxev"));
        $this->assertFalse(U::startsWith("edu", "@umich.edu"));
        $this->assertFalse(U::startsWith(null, "@umich.edu"));
        $this->assertFalse(U::startsWith(null, null));
        $this->assertFalse(U::startsWith("edu", null));
    }


    public function testEndsWith() {
        $this->assertTrue(U::endsWith("csev@umich.edu", "@umich.edu"));
        $this->assertTrue(U::endsWith("csev@umich.edu", ""));
        $this->assertFalse(U::endsWith("csev@umich.edu", "@xmich.edu"));
        $this->assertFalse(U::endsWith("edu", "@umich.edu"));
        $this->assertFalse(U::endsWith(null, "@umich.edu"));
        $this->assertFalse(U::endsWith(null, null));
        $this->assertFalse(U::endsWith("edu", null));
    }

}
