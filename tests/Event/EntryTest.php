<?php

require_once "src/Event/Entry.php";

use \Tsugi\Util\U;
use \Tsugi\Event\Entry;

class EntryTest extends PHPUnit_Framework_TestCase
{
    public function testY() {
        $x = 1502553699;
        $ent = new Entry($x);
        $ent->click($x);

        $this->assertEquals($ent->serialize(),'900:1669504:0=1');
        $this->assertEquals($ent->reconstruct(),array(1502553600 => 1));

        // Fil up some more
        for($month=0; $month<=2; $month++) {
        for($day=0;$day<=2;$day++) {
        for($sec=0; $sec<2000;$sec++) { 
            $now = $month*60*60*24*30 + $day*60*60*24 + $sec;
            $ent->click($x+$now);
        }}}

        // Look at the actual buckets
        $this->assertEquals(U::array_Integer_Serialize($ent->buckets),"0=802,1=900,2=299,96=801,97=900,98=299,192=801,193=900,194=299,2880=801,2881=900,2882=299,2976=801,2977=900,2978=299,3072=801,3073=900,3074=299,5760=801,5761=900,5762=299,5856=801,5857=900,5858=299,5952=801,5953=900,5954=299");

        // Call the serializer w/o the need to compress
        $ser =$ent->serialize(1000);
        $this->assertEquals($ser,"900:1669504:0=802,1=900,2=299,96=801,97=900,98=299,192=801,193=900,194=299,2880=801,2881=900,2882=299,2976=801,2977=900,2978=299,3072=801,3073=900,3074=299,5760=801,5761=900,5762=299,5856=801,5857=900,5858=299,5952=801,5953=900,5954=299");

        // Test rescaling...
        $newbuck = $ent->reScale();
        $this->assertEquals(U::array_Integer_Serialize($newbuck),"0=1702,1=299,48=1701,49=299,96=1701,97=299,1440=1701,1441=299,1488=1701,1489=299,1536=1701,1537=299,2880=1701,2881=299,2928=1701,2929=299,2976=1701,2977=299");

        $smaller = (int) (strlen($ser) * 0.75);
        $compress = false;
        $ser2 = $ent->serialize($smaller, $compress);
        $this->assertEquals($ser2,"1800:834752:0=1702,1=299,48=1701,49=299,96=1701,97=299,1440=1701,1441=299,1488=1701,1489=299,1536=1701,1537=299,2880=1701,2881=299,2928=1701,2929=299,2976=1701,2977=299");
        // var_dump($ser2);

        $smaller = (int) (strlen($ser) * 0.5);
        $ser3 = $ent->serialize($smaller, $compress);
        $this->assertEquals($ser3,"3600:417376:0=2001,24=2000,48=2000,720=2000,744=2000,768=2000,1440=2000,1464=2000,1488=2000");
    }

}
