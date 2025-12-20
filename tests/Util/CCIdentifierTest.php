<?php

require_once "src/Util/CCIdentifier.php";

use \Tsugi\Util\CCIdentifier;

/**
 * Test suite for CCIdentifier class
 * 
 * Tests deterministic identifier generation for Common Cartridge exports.
 * Ensures idempotency: same logical content produces same identifier.
 */
class CCIdentifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test basic identifier generation for different types
     */
    public function testBasicIdentifierGeneration() {
        $idgen = new CCIdentifier();
        
        // Test module identifier
        $id1 = $idgen->makeIdentifier('module', 'Week 1', '');
        $this->assertStringStartsWith('M_', $id1, 'Module identifier should start with M_');
        $this->assertEquals(18, strlen($id1), 'Identifier should be M_ + 16 char hash (18 total)');
        
        // Test submodule identifier
        $id2 = $idgen->makeIdentifier('submodule', 'Part 1', '');
        $this->assertStringStartsWith('S_', $id2, 'Submodule identifier should start with S_');
        
        // Test weblink identifier
        $id3 = $idgen->makeIdentifier('weblink', 'Video Link', '');
        $this->assertStringStartsWith('WL_', $id3, 'Weblink identifier should start with WL_');
        
        // Test LTI identifier
        $id4 = $idgen->makeIdentifier('lti', 'Assignment 1', '');
        $this->assertStringStartsWith('LT_', $id4, 'LTI identifier should start with LT_');
        
        // Test topic identifier
        $id5 = $idgen->makeIdentifier('topic', 'Discussion', '');
        $this->assertStringStartsWith('TO_', $id5, 'Topic identifier should start with TO_');
        
        // Test header identifier
        $id6 = $idgen->makeIdentifier('header', 'Section Header', '');
        $this->assertStringStartsWith('H_', $id6, 'Header identifier should start with H_');
        
        // Test unknown type (should use ID prefix)
        $id7 = $idgen->makeIdentifier('unknown', 'Test', '');
        $this->assertStringStartsWith('ID_', $id7, 'Unknown type identifier should start with ID_');
    }

    /**
     * Test deterministic behavior - same input produces same output
     */
    public function testDeterministicBehavior() {
        $idgen1 = new CCIdentifier();
        $idgen2 = new CCIdentifier();
        
        // Same inputs should produce same identifiers
        $id1a = $idgen1->makeIdentifier('module', 'Week 1', '');
        $id1b = $idgen2->makeIdentifier('module', 'Week 1', '');
        $this->assertEquals($id1a, $id1b, 'Same inputs should produce same identifier');
        
        // Different inputs should produce different identifiers
        $id2 = $idgen1->makeIdentifier('module', 'Week 2', '');
        $this->assertNotEquals($id1a, $id2, 'Different titles should produce different identifiers');
        
        // Same title but different parent path should produce different identifiers
        $id3 = $idgen1->makeIdentifier('module', 'Week 1', 'Parent Path');
        $this->assertNotEquals($id1a, $id3, 'Different parent paths should produce different identifiers');
    }

    /**
     * Test parent path handling
     */
    public function testParentPathHandling() {
        $idgen = new CCIdentifier();
        
        // Empty parent path
        $id1 = $idgen->makeIdentifier('module', 'Week 1', '');
        
        // With parent path
        $id2 = $idgen->makeIdentifier('module', 'Week 1', 'Course|Section');
        
        // Different parent paths should produce different identifiers
        $id3 = $idgen->makeIdentifier('module', 'Week 1', 'Course|Different');
        
        $this->assertNotEquals($id1, $id2, 'Parent path should affect identifier');
        $this->assertNotEquals($id2, $id3, 'Different parent paths should produce different identifiers');
    }

    /**
     * Test additional properties handling
     */
    public function testAdditionalProperties() {
        $idgen = new CCIdentifier();
        
        // Without additional properties
        $id1 = $idgen->makeIdentifier('lti', 'Assignment', '', array());
        
        // With URL property
        $id2 = $idgen->makeIdentifier('lti', 'Assignment', '', array('url' => 'https://example.com'));
        
        // With multiple properties
        $id3 = $idgen->makeIdentifier('lti', 'Assignment', '', array(
            'url' => 'https://example.com',
            'resource_link_id' => '12345'
        ));
        
        // Same properties in different order should produce same base identifier
        // Note: Since we're calling on the same instance, collision handling will append _1
        $id4 = $idgen->makeIdentifier('lti', 'Assignment', '', array(
            'resource_link_id' => '12345',
            'url' => 'https://example.com'
        ));
        
        $this->assertNotEquals($id1, $id2, 'Additional properties should affect identifier');
        $this->assertNotEquals($id2, $id3, 'Different properties should produce different identifiers');
        // Extract base ID (without collision counter) for comparison
        $baseId3 = preg_replace('/_\d+$/', '', $id3);
        $baseId4 = preg_replace('/_\d+$/', '', $id4);
        $this->assertEquals($baseId3, $baseId4, 'Same properties in different order should produce same base identifier');
    }

    /**
     * Test string normalization
     */
    public function testStringNormalization() {
        // Use separate instances to avoid collision handling
        $idgen1 = new CCIdentifier();
        $idgen2 = new CCIdentifier();
        $idgen3 = new CCIdentifier();
        $idgen4 = new CCIdentifier();
        
        // Test that whitespace variations produce same identifier
        $id1 = $idgen1->makeIdentifier('module', 'Week 1', '');
        $id2 = $idgen2->makeIdentifier('module', '  Week  1  ', '');
        $id3 = $idgen3->makeIdentifier('module', 'week 1', '');
        $id4 = $idgen4->makeIdentifier('module', 'WEEK 1', '');
        
        $this->assertEquals($id1, $id2, 'Extra whitespace should be normalized');
        $this->assertEquals($id1, $id3, 'Case should be normalized');
        $this->assertEquals($id1, $id4, 'Uppercase should be normalized');
    }

    /**
     * Test collision handling
     */
    public function testCollisionHandling() {
        $idgen = new CCIdentifier();
        
        // Generate first identifier
        $id1 = $idgen->makeIdentifier('module', 'Test', '');
        
        // Manually register a collision to test collision handling
        // We can't directly test this without reflection, but we can test
        // that the same input produces the same output (no collision)
        $id2 = $idgen->makeIdentifier('module', 'Test', '');
        
        // Since we're using the same input, it should produce the same identifier
        // but collision handling would append _1, _2, etc. if there was a collision
        // In practice, same input = same hash = same base ID, so collision detection
        // would kick in. But since we're using deterministic hashing, same input
        // should always produce same hash, so we'd get the same base ID.
        // The collision counter would only increment if we somehow got the same base ID
        // from different inputs (hash collision).
        
        // Test that we can generate multiple different identifiers
        $id3 = $idgen->makeIdentifier('module', 'Test 2', '');
        $id4 = $idgen->makeIdentifier('module', 'Test 3', '');
        
        $this->assertNotEquals($id1, $id3, 'Different inputs should produce different identifiers');
        $this->assertNotEquals($id3, $id4, 'Different inputs should produce different identifiers');
    }

    /**
     * Test numeric and array values in additional properties
     */
    public function testNumericAndArrayProperties() {
        // Use separate instances to avoid collision handling
        $idgen1 = new CCIdentifier();
        $idgen2 = new CCIdentifier();
        $idgen3 = new CCIdentifier();
        $idgen4 = new CCIdentifier();
        
        // Test with numeric value
        $id1 = $idgen1->makeIdentifier('lti', 'Assignment', '', array('id' => 12345));
        $id2 = $idgen2->makeIdentifier('lti', 'Assignment', '', array('id' => '12345'));
        $this->assertEquals($id1, $id2, 'Numeric and string numeric should produce same identifier');
        
        // Test with array value
        // Note: Arrays are JSON-encoded, so order matters for the identifier
        // Arrays with same values in different order will produce different identifiers
        // because JSON preserves array order
        $id3 = $idgen3->makeIdentifier('lti', 'Assignment', '', array('tags' => array('a', 'b')));
        $id4 = $idgen4->makeIdentifier('lti', 'Assignment', '', array('tags' => array('b', 'a')));
        $this->assertNotEquals($id3, $id4, 'Arrays with same values in different order produce different identifiers (JSON order matters)');
        
        // But arrays with same values in same order should produce same identifier
        $id5 = $idgen1->makeIdentifier('lti', 'Assignment', '', array('tags' => array('a', 'b')));
        $this->assertEquals($id3, $id5, 'Arrays with same values in same order should produce same identifier');
    }

    /**
     * Test reset functionality
     */
    public function testReset() {
        $idgen = new CCIdentifier();
        
        // Generate some identifiers
        $idgen->makeIdentifier('module', 'Week 1', '');
        $idgen->makeIdentifier('module', 'Week 2', '');
        
        $this->assertEquals(2, $idgen->getCount(), 'Should have 2 identifiers');
        
        // Reset
        $idgen->reset();
        
        $this->assertEquals(0, $idgen->getCount(), 'After reset, count should be 0');
        
        // After reset, same inputs should still produce same base identifiers (deterministic)
        // But collision handling will append _1 for the second call
        $id1 = $idgen->makeIdentifier('module', 'Week 1', '');
        $id2 = $idgen->makeIdentifier('module', 'Week 1', '');
        $baseId1 = preg_replace('/_\d+$/', '', $id1);
        $baseId2 = preg_replace('/_\d+$/', '', $id2);
        $this->assertEquals($baseId1, $baseId2, 'After reset, same inputs should still produce same base identifiers');
        $this->assertNotEquals($id1, $id2, 'Collision handling should append counter to second call');
    }

    /**
     * Test getCount functionality
     */
    public function testGetCount() {
        $idgen = new CCIdentifier();
        
        $this->assertEquals(0, $idgen->getCount(), 'Initial count should be 0');
        
        $idgen->makeIdentifier('module', 'Week 1', '');
        $this->assertEquals(1, $idgen->getCount(), 'After one identifier, count should be 1');
        
        $idgen->makeIdentifier('module', 'Week 2', '');
        $this->assertEquals(2, $idgen->getCount(), 'After two identifiers, count should be 2');
        
        // Same input should increment count (collision handling)
        $idgen->makeIdentifier('module', 'Week 1', '');
        $this->assertEquals(3, $idgen->getCount(), 'Same input should still increment count');
    }

    /**
     * Test that parent path normalization works correctly
     */
    public function testParentPathNormalization() {
        // Use separate instances to avoid collision handling
        $idgen1 = new CCIdentifier();
        $idgen2 = new CCIdentifier();
        
        // Test that parent path is normalized
        $id1 = $idgen1->makeIdentifier('submodule', 'Part 1', 'Week 1');
        $id2 = $idgen2->makeIdentifier('submodule', 'Part 1', '  week  1  ');
        
        $this->assertEquals($id1, $id2, 'Parent path should be normalized');
    }

    /**
     * Test empty title handling
     */
    public function testEmptyTitle() {
        // Use separate instances to avoid collision handling
        $idgen1 = new CCIdentifier();
        $idgen2 = new CCIdentifier();
        
        $id1 = $idgen1->makeIdentifier('module', '', '');
        $id2 = $idgen2->makeIdentifier('module', '', '');
        
        // Empty titles should still produce valid identifiers
        $this->assertStringStartsWith('M_', $id1, 'Empty title should still produce valid identifier');
        $this->assertEquals($id1, $id2, 'Same empty inputs should produce same identifier');
    }

    /**
     * Test that hash length is correct (16 characters)
     */
    public function testHashLength() {
        $idgen = new CCIdentifier();
        
        $id = $idgen->makeIdentifier('module', 'Test', '');
        
        // Extract hash part (after prefix and underscore)
        $parts = explode('_', $id);
        $hash = $parts[1];
        
        $this->assertEquals(16, strlen($hash), 'Hash should be 16 characters long');
    }
}

