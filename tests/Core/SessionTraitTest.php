<?php

require_once("src/Core/SessionTrait.php");
use \Tsugi\Core\SessionTrait;

class SessionTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTraitExists() {
        $this->assertTrue(trait_exists(\Tsugi\Core\SessionTrait::class), 'SessionTrait should exist');
    }

}
