<?php

require_once("src/Core/I18N.php");
use \Tsugi\Core\I18N;

class I18NTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $i18n = new \Tsugi\Core\I18N();
        $this->assertInstanceOf(\Tsugi\Core\I18N::class, $i18n, 'I18N should instantiate correctly');
    }

}
