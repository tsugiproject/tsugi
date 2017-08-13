<?php

use \Tsugi\Util\U;

class UTest extends PHPUnit_Framework_TestCase
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
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro/'), '/py4e/lessons');
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro?x=2'), '/py4e/lessons');
        $this->assertEquals(U::get_rest_parent('/py4e/lessons/intro/?x=2'), '/py4e/lessons');
    }

    public function testRelative() {
        $this->assertEquals(U::remove_relative_path('/a/b/c'), '/a/b/c');
        $this->assertEquals(U::remove_relative_path('/a/b/c/'), '/a/b/c/');
        $this->assertEquals(U::remove_relative_path('/a/./c/'), '/a/c/');
        $this->assertEquals(U::remove_relative_path('/a/../c/'), '/c/');
        $this->assertEquals(U::remove_relative_path('/a/b/../../c/'), '/c/');
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


}
