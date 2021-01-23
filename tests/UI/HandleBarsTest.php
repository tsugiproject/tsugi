<?php

$toppath = dirname(__FILE__).'/../..';

require_once "src/Core/I18N.php";
require_once "src/UI/HandleBars.php";

use \Tsugi\UI\HandleBars;

class HandleBarsTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess() {
        $input = "one {{ two }} {{__ 'three' }}{{__ 'four'}}";
        $output = HandleBars::templateProcess($input);
        $this->assertEquals($output,'one {{ two }} threefour');
    }


}
