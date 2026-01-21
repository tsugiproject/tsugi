<?php

require_once("src/Core/Settings.php");
use \Tsugi\Core\Settings;

class SettingsTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $settings = new \Tsugi\Core\Settings();
        $this->assertInstanceOf(\Tsugi\Core\Settings::class, $settings, 'Settings should instantiate correctly');
    }

}
