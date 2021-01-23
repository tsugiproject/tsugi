<?php

require_once "src/Event/Entry.php";

use \Tsugi\Util\U;
use \Tsugi\Event\Entry;

class EntryTest extends \PHPUnit\Framework\TestCase
{
    public function testClicking() {
        $x = 1502553699;
        $ent = new Entry($x);
        $ent->click($x);

        $ser0 = $ent->serialize();
        $this->assertEquals($ser0,'900:1669504:0=1');
        $this->assertEquals($ent->reconstruct(),array(1502553600 => 1));
        $mod0 = $ent->viewModel();
        // echo(json_encode($mod0,JSON_PRETTY_PRINT));
        $j0 = json_encode($mod0);
        $this->assertEquals($j0,'{"timestart":1502553600,"width":900,"rows":[[1502553600,1]],"n":1,"max":1,"min":1,"timeend":1502553600}');

        $ent2 = new Entry($x);
        $ent2->deserialize($ser0);
        $ser0a = $ent2->serialize();
        $this->assertEquals($ent->reconstruct(),$ent2->reconstruct());
        $this->assertEquals($ser0,$ser0a);

        // Make sure de-serialization handles compression
        $ent2 = new Entry($x);
        $ent2->deserialize(gzcompress($ser0));
        $ser0a = $ent2->serialize();
        $this->assertEquals($ent->reconstruct(),$ent2->reconstruct());
        $this->assertEquals($ser0,$ser0a);

        // Fill up some more
        for($month=0; $month<=2; $month++) {
        for($day=0;$day<=2;$day++) {
        for($sec=0; $sec<2000;$sec++) { 
            $now = $month*60*60*24*30 + $day*60*60*24 + $sec;
            $ent->click($x+$now);
        }}}

        // Look at the actual buckets
        $this->assertEquals(U::array_Integer_Serialize($ent->buckets),"0=802,1=900,2=299,96=801,97=900,98=299,192=801,193=900,194=299,2880=801,2881=900,2882=299,2976=801,2977=900,2978=299,3072=801,3073=900,3074=299,5760=801,5761=900,5762=299,5856=801,5857=900,5858=299,5952=801,5953=900,5954=299");

        // Call the serializer w/o the need to compress
        $ser1 =$ent->serialize(1000);
        $this->assertEquals($ser1,"900:1669504:0=802,1=900,2=299,96=801,97=900,98=299,192=801,193=900,194=299,2880=801,2881=900,2882=299,2976=801,2977=900,2978=299,3072=801,3073=900,3074=299,5760=801,5761=900,5762=299,5856=801,5857=900,5858=299,5952=801,5953=900,5954=299");

        // Test de-serialization
        $ent2 = new Entry($x);
        $ent2->deserialize($ser1);
        $ser1a = $ent2->serialize();
        $this->assertEquals($ent->reconstruct(),$ent2->reconstruct());
        $this->assertEquals($ser1,$ser1a);

        // Test de-serialization with compression
        $ent2 = new Entry($x);
        $ent2->deserialize(gzcompress($ser1));
        $ser1a = $ent2->serialize();
        $this->assertEquals($ent->reconstruct(),$ent2->reconstruct());
        $this->assertEquals($ser1,$ser1a);

        // Test rescaling...
        $newbuck = $ent->reScale();
        $this->assertEquals(U::array_Integer_Serialize($newbuck),"0=1702,1=299,48=1701,49=299,96=1701,97=299,1440=1701,1441=299,1488=1701,1489=299,1536=1701,1537=299,2880=1701,2881=299,2928=1701,2929=299,2976=1701,2977=299");

        // Trigger scale factor 2 and scale factor 4
        $smaller = (int) (strlen($ser1) * 0.75);
        $compress = false;
        $ser2 = $ent->serialize($smaller, $compress);
        $this->assertTrue(strlen($ser2)<=$smaller);
        $this->assertEquals($ser2,"1800:834752:0=1702,1=299,48=1701,49=299,96=1701,97=299,1440=1701,1441=299,1488=1701,1489=299,1536=1701,1537=299,2880=1701,2881=299,2928=1701,2929=299,2976=1701,2977=299");

        $smaller = (int) (strlen($ser1) * 0.5);
        $ser3 = $ent->serialize($smaller, $compress);
        $this->assertTrue(strlen($ser3)<=$smaller);
        $this->assertEquals($ser3,"3600:417376:0=2001,24=2000,48=2000,720=2000,744=2000,768=2000,1440=2000,1464=2000,1488=2000");
        $this->assertEquals($ser3,Entry::uncompressEntry($ser3));
        // Once uncompressed :)
        $this->assertEquals($ser3,Entry::uncompressEntry(Entry::uncompressEntry($ser3)));

        // Give this a try with compression
        $compress = true;
        $smaller = (int) (strlen($ser1) * 0.75);
        $ser4 = $ent->serialize($smaller, $compress);
        $this->assertTrue(strlen($ser4)<=$smaller);
        $this->assertEquals(bin2hex($ser4),"313830303a3833343735323a303d313730322c313d3239392c34383d313730312c34393d3239392c39363d313730312c39373d3239392c313434303d313730312c313434313d3239392c313438383d313730312c313438393d3239392c313533363d313730312c313533373d3239392c323838303d313730312c323838313d3239392c323932383d313730312c323932393d3239392c323937363d313730312c323937373d323939");
        $this->assertEquals(Entry::uncompressEntry($ser4),$ser2);

        $smaller = (int) (strlen($ser1) * 0.50);
        $ser5 = $ent->serialize($smaller, $compress);
        $this->assertTrue(strlen($ser5)<=$smaller);
        $this->assertEquals(bin2hex($ser5),"333630303a3431373337363a303d323030312c32343d323030302c34383d323030302c3732303d323030302c3734343d323030302c3736383d323030302c313434303d323030302c313436343d323030302c313438383d32303030");
        $this->assertEquals(Entry::uncompressEntry($ser5),$ser3);

        // Make it pitch data
        $compress = false;
        $smaller = (int) (strlen($ser1) * 0.25);
        $ser5 = $ent->serialize($smaller, $compress);
        $this->assertTrue(strlen($ser5)<=$smaller);
        $this->assertEquals($ser5,"900:1675360:0=801,1=900,2=299,96=801,97=900,98=299");

        // Make it pitch data with compression as option
        $compress = true;
        $smaller = (int) (strlen($ser1) * 0.20);
        $ser6 = $ent->serialize($smaller, $compress);
        // echo(bin2hex($ser6)); echo("\n");
        $this->assertTrue(strlen($ser6)<=$smaller);
        $this->assertEquals(bin2hex($ser6),"3930303a313637353336313a303d3930302c313d3239392c39353d3830312c39363d3930302c39373d323939");
        $this->assertEquals(Entry::uncompressEntry($ser6),"900:1675361:0=900,1=299,95=801,96=900,97=299");

        // Check if I never get less than 4 buckets :)
        $compress = false;
        $smaller = 10;
        $ser8 = $ent->serialize($smaller, $compress);
        $this->assertFalse(strlen($ser8)<=$smaller);
        $this->assertEquals($ser8,"900:1675362:0=299,94=801,95=900,96=299");
    }

}
