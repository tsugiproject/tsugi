<?php

require_once "src/UI/SettingsDialog.php";

use \Tsugi\UI\SettingsDialog;

class SettingsDialogTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $settingsDialog = new SettingsDialog();
        $this->assertTrue(is_object($settingsDialog));
    }


}
