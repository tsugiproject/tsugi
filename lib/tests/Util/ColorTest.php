<?php

require_once "src/Util/Color.php";

use \Tsugi\Util\Color;

class ColorTest extends \PHPUnit\Framework\TestCase
{
    public function testOne() {

        // Just don't blow up :)
        $white = "#FFFFFF";
        for($b=0; $b<=255; $b=$b+16) {
            $r=0;
            $g = 0;
            $rgb = [ $r, $g, $b];
            $hex = Color::hex($rgb);
            $lum = Color::luminance ($rgb);
            $rel = Color::relativeLuminance($hex, $white);
            // echo("$hex, $lum, $white, $rel\n");
        }
        
        $rel = Color::relativeLuminance('#000000', '#FFFFFF');
        $this->assertEqualsWithDelta($rel, 21, 0.0001);
        $rel = Color::relativeLuminance('#111111', '#EEEEEE');
        $this->assertEqualsWithDelta($rel, 17.28932767242968, 0.0001);
        $rel = Color::relativeLuminance('#000010', '#FFFFFF');
        $this->assertEqualsWithDelta($rel, 20.931601713733, 0.0001);
        $rel = Color::relativeLuminance('#EE1111', '#1111EE');
        $this->assertEqualsWithDelta($rel, 2.050977751330241, 0.0001);
        $rel = Color::relativeLuminance('#11EE11', '#1111EE');
        $this->assertEqualsWithDelta($rel, 5.813238562003881, 0.0001);

        $expected = array(18, 52, 86);
        $fix = Color::fixRgb('#123456');
        $this->assertEquals($fix, $expected);
        $fix = Color::fixRgb($fix);
        $this->assertEquals($fix, $expected);
        $fix = Color::fixRgb(18, 52, 86);
        $this->assertEquals($fix, $expected);

    }


}
