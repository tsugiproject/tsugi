<?php

require_once("src/Core/JsonTrait.php");
use \Tsugi\Core\JsonTrait;

class JsonTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTraitExists() {
        $this->assertTrue(trait_exists(\Tsugi\Core\JsonTrait::class), 'JsonTrait should exist');
    }

}
