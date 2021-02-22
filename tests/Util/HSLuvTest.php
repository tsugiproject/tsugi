<?php

require_once "src/Util/HSLuv.php";

use \Tsugi\Util\HSLuv;

class HSLuvTest extends \PHPUnit\Framework\TestCase
{
    public function testOne() {

        $rgb = \Tsugi\Util\HSLuv::fromHex("#000000");
        $luv = \Tsugi\Util\HSLuv::rgbToHpluv($rgb);
        $this->assertEquals($luv, array(0,0,0));
        $rgb = \Tsugi\Util\HSLuv::fromHex("#FFFFFF");
        $luv = \Tsugi\Util\HSLuv::rgbToHpluv($rgb);
        $this->assertEqualsWithDelta($luv[0], 265.87432021818, 0.0001);
        $this->assertEqualsWithDelta($luv[1], 0.0, 0.0001);
        $this->assertEqualsWithDelta($luv[2], 1827.0794159617, 0.0001);
        $rgb = \Tsugi\Util\HSLuv::fromHex("#0000FF");
        $luv = \Tsugi\Util\HSLuv::rgbToHpluv($rgb);
        $this->assertEqualsWithDelta($luv[0], 19.227370041966, 0.0001);
        $this->assertEqualsWithDelta($luv[1], 0.0, 0.0001);
        $this->assertEqualsWithDelta($luv[2], 6325.6287379323, 0.0001);
    }


}
