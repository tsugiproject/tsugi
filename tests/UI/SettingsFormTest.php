<?php

require_once "src/UI/SettingsForm.php";

use \Tsugi\UI\SettingsForm;

class SettingsFormTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $settingsForm = new SettingsForm();
        $this->assertInstanceOf(\Tsugi\UI\SettingsForm::class, $settingsForm, 'SettingsForm should instantiate correctly');
    }

}
