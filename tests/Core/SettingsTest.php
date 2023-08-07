<?php

require_once("src/Core/Settings.php");
use \Tsugi\Core\Settings;

class SettingsTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Settings();
        $this->assertTrue(true);
    }

}
