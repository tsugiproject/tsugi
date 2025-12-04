<?php

require_once("src/Core/SettingsTrait.php");
use \Tsugi\Core\SettingsTrait;

class SettingsTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTraitExists() {
        $this->assertTrue(trait_exists(\Tsugi\Core\SettingsTrait::class), 'SettingsTrait should exist');
    }

}
