<?php

use \Tsugi\Util\FakeName;

class FakeNameTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that getname() returns a string
     */
    public function testGetnameReturnsString() {
        $name = FakeName::getname('test');
        $this->assertIsString($name, 'getname() should return a string');
    }

    /**
     * Test that getname() is deterministic - same seed produces same name
     */
    public function testGetnameIsDeterministic() {
        $seed = 'test-seed-123';
        $name1 = FakeName::getname($seed);
        $name2 = FakeName::getname($seed);
        $name3 = FakeName::getname($seed);
        
        $this->assertEquals($name1, $name2, 'Same seed should produce same name');
        $this->assertEquals($name2, $name3, 'Same seed should produce same name consistently');
    }

    /**
     * Test that different seeds produce different names
     */
    public function testGetnameDifferentSeeds() {
        $name1 = FakeName::getname('seed1');
        $name2 = FakeName::getname('seed2');
        $name3 = FakeName::getname('seed3');
        
        $this->assertNotEquals($name1, $name2, 'Different seeds should produce different names');
        $this->assertNotEquals($name2, $name3, 'Different seeds should produce different names');
        $this->assertNotEquals($name1, $name3, 'Different seeds should produce different names');
    }

    /**
     * Test that the name format is "Adjective Animal"
     */
    public function testGetnameFormat() {
        $name = FakeName::getname('format-test');
        
        // Should contain exactly one space
        $parts = explode(' ', $name);
        $this->assertCount(2, $parts, 'Name should consist of two words separated by a space');
        
        $adjective = $parts[0];
        $animal = $parts[1];
        
        $this->assertNotEmpty($adjective, 'Adjective should not be empty');
        $this->assertNotEmpty($animal, 'Animal should not be empty');
    }

    /**
     * Test that the adjective is from the ADJECTIVES constant
     */
    public function testGetnameAdjectiveFromConstants() {
        $name = FakeName::getname('adjective-test');
        $parts = explode(' ', $name);
        $adjective = $parts[0];
        
        $this->assertContains($adjective, FakeName::ADJECTIVES, 
            'Adjective should be from the ADJECTIVES constant');
    }

    /**
     * Test that the animal is from the ANIMALS constant
     */
    public function testGetnameAnimalFromConstants() {
        $name = FakeName::getname('animal-test');
        $parts = explode(' ', $name);
        $animal = $parts[1];
        
        $this->assertContains($animal, FakeName::ANIMALS, 
            'Animal should be from the ANIMALS constant');
    }

    /**
     * Test with different input types
     */
    public function testGetnameWithDifferentInputTypes() {
        // String input
        $name1 = FakeName::getname('string-seed');
        $this->assertIsString($name1, 'String seed should produce a string name');
        
        // Integer input
        $name2 = FakeName::getname(12345);
        $this->assertIsString($name2, 'Integer seed should produce a string name');
        
        // Different integer should produce different name
        $name3 = FakeName::getname(67890);
        $this->assertNotEquals($name2, $name3, 'Different integer seeds should produce different names');
        
        // Float input
        $name4 = FakeName::getname(123.45);
        $this->assertIsString($name4, 'Float seed should produce a string name');
    }

    /**
     * Test that empty string seed works
     */
    public function testGetnameWithEmptyString() {
        $name = FakeName::getname('');
        $this->assertIsString($name, 'Empty string seed should produce a string name');
        $this->assertNotEmpty($name, 'Empty string seed should produce a non-empty name');
        
        // Should be deterministic even with empty string
        $name2 = FakeName::getname('');
        $this->assertEquals($name, $name2, 'Empty string seed should be deterministic');
    }

    /**
     * Test multiple calls produce valid names
     */
    public function testGetnameMultipleCalls() {
        $names = [];
        for ($i = 0; $i < 10; $i++) {
            $name = FakeName::getname("seed-$i");
            $names[] = $name;
            
            // Verify format
            $parts = explode(' ', $name);
            $this->assertCount(2, $parts, "Name $i should have two parts");
            $this->assertContains($parts[0], FakeName::ADJECTIVES, 
                "Adjective in name $i should be from constants");
            $this->assertContains($parts[1], FakeName::ANIMALS, 
                "Animal in name $i should be from constants");
        }
        
        // All names should be unique (very unlikely to have duplicates with different seeds)
        $uniqueNames = array_unique($names);
        $this->assertGreaterThanOrEqual(8, count($uniqueNames), 
            'Most names should be unique with different seeds');
    }
}

