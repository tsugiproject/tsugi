<?php

require_once "src/UI/SettingsForm.php";

use \Tsugi\UI\SettingsForm;

class SettingsFormTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $settingsForm = new SettingsForm();
        $this->assertTrue(is_object($settingsForm));
    }


}
