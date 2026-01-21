<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CanvasModuleMeta.php";

use \Tsugi\Util\CC;

/**
 * Test suite for CC class integration with CCIdentifier
 * 
 * Tests the deterministic identifier generation integrated into CC methods.
 */
class CCIdentifierIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that add_module uses deterministic identifiers
     */
    public function testAddModuleDeterministicIdentifiers() {
        $cc1 = new CC();
        $cc1->set_title('Test Course');
        $cc1->set_description('Test Description');
        
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        // Same inputs should produce same identifiers
        $module1 = $cc1->add_module('Week 1');
        $module2 = $cc2->add_module('Week 1');
        
        $this->assertEquals($cc1->last_identifier, $cc2->last_identifier, 
            'Same module title should produce same identifier');
        $this->assertStringStartsWith('M_', $cc1->last_identifier, 
            'Module identifier should start with M_');
    }

    /**
     * Test that add_sub_module uses deterministic identifiers and tracks parent paths
     */
    public function testAddSubModuleDeterministicIdentifiers() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        $moduleId = $cc->last_identifier;
        
        // Add submodule - should auto-detect parent path
        $submodule = $cc->add_sub_module($module, 'Part 1');
        $submoduleId = $cc->last_identifier;
        
        $this->assertStringStartsWith('S_', $submoduleId, 
            'Submodule identifier should start with S_');
        $this->assertNotEquals($moduleId, $submoduleId, 
            'Module and submodule should have different identifiers');
        
        // Create another CC with same structure - should produce same identifiers
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $module2Id = $cc2->last_identifier;
        $submodule2 = $cc2->add_sub_module($module2, 'Part 1');
        $submodule2Id = $cc2->last_identifier;
        
        $this->assertEquals($moduleId, $module2Id, 
            'Same module structure should produce same identifier');
        $this->assertEquals($submoduleId, $submodule2Id, 
            'Same submodule structure should produce same identifier');
    }

    /**
     * Test that add_web_link uses deterministic identifiers with URL
     */
    public function testAddWebLinkDeterministicIdentifiers() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add web link with URL
        $file1 = $cc->add_web_link($module, 'Video Link', 'https://example.com/video');
        $id1 = $cc->last_identifier;
        
        $this->assertStringStartsWith('WL_', $id1, 
            'Weblink identifier should start with WL_');
        $this->assertStringContainsString('xml/WL_', $file1, 
            'File path should contain WL_ prefix');
        
        // Same URL should produce same identifier
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $file2 = $cc2->add_web_link($module2, 'Video Link', 'https://example.com/video');
        
        $this->assertEquals($id1, $cc2->last_identifier, 
            'Same web link with same URL should produce same identifier');
    }

    /**
     * Test that add_lti_link uses deterministic identifiers with URL and resource_link_id
     */
    public function testAddLtiLinkDeterministicIdentifiers() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add LTI link with URL and resource_link_id
        $file1 = $cc->add_lti_link($module, 'Assignment 1', 'https://example.com/lti', 'link123');
        $id1 = $cc->last_identifier;
        
        $this->assertStringStartsWith('LT_', $id1, 
            'LTI identifier should start with LT_');
        $this->assertStringContainsString('xml/LT_', $file1, 
            'File path should contain LT_ prefix');
        
        // Same properties should produce same identifier
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $file2 = $cc2->add_lti_link($module2, 'Assignment 1', 'https://example.com/lti', 'link123');
        
        $this->assertEquals($id1, $cc2->last_identifier, 
            'Same LTI link with same properties should produce same identifier');
    }

    /**
     * Test that add_topic uses deterministic identifiers with text
     */
    public function testAddTopicDeterministicIdentifiers() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add topic with text
        $file1 = $cc->add_topic($module, 'Discussion', 'Discussion text content');
        $id1 = $cc->last_identifier;
        
        $this->assertStringStartsWith('TO_', $id1, 
            'Topic identifier should start with TO_');
        $this->assertStringContainsString('xml/TO_', $file1, 
            'File path should contain TO_ prefix');
        
        // Same text should produce same identifier
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $file2 = $cc2->add_topic($module2, 'Discussion', 'Discussion text content');
        
        $this->assertEquals($id1, $cc2->last_identifier, 
            'Same topic with same text should produce same identifier');
    }

    /**
     * Test that add_header_item uses deterministic identifiers
     */
    public function testAddHeaderItemDeterministicIdentifiers() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add header item
        $header = $cc->add_header_item($module, 'Section Header');
        $id1 = $cc->last_identifier;
        
        $this->assertStringStartsWith('H_', $id1, 
            'Header identifier should start with H_');
        
        // Same header should produce same identifier
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $header2 = $cc2->add_header_item($module2, 'Section Header');
        
        $this->assertEquals($id1, $cc2->last_identifier, 
            'Same header should produce same identifier');
    }

    /**
     * Test parent path tracking across nested modules
     */
    public function testParentPathTracking() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        // Create nested structure
        $module1 = $cc->add_module('Week 1');
        $submodule1 = $cc->add_sub_module($module1, 'Part 1');
        
        // Add item to submodule - should auto-detect parent path
        $file1 = $cc->add_web_link($submodule1, 'Video', 'https://example.com');
        $id1 = $cc->last_identifier;
        
        // Create same structure with explicit parent path
        $cc2 = new CC();
        $cc2->set_title('Test Course');
        $cc2->set_description('Test Description');
        
        $module2 = $cc2->add_module('Week 1');
        $submodule2 = $cc2->add_sub_module($module2, 'Part 1');
        $file2 = $cc2->add_web_link($submodule2, 'Video', 'https://example.com');
        
        $this->assertEquals($id1, $cc2->last_identifier, 
            'Auto-detected and explicit parent paths should produce same identifier');
    }

    /**
     * Test that different parent paths produce different identifiers
     */
    public function testDifferentParentPaths() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module1 = $cc->add_module('Week 1');
        $file1 = $cc->add_web_link($module1, 'Video', 'https://example.com');
        $id1 = $cc->last_identifier;
        
        $module2 = $cc->add_module('Week 2');
        $file2 = $cc->add_web_link($module2, 'Video', 'https://example.com');
        $id2 = $cc->last_identifier;
        
        $this->assertNotEquals($id1, $id2, 
            'Same item in different modules should produce different identifiers');
    }

    /**
     * Test that file names are based on hash, not sequential numbers
     */
    public function testFileNamingBasedOnHash() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add multiple items - file names should be based on hash
        $file1 = $cc->add_web_link($module, 'Link 1', 'https://example.com/1');
        $file2 = $cc->add_web_link($module, 'Link 2', 'https://example.com/2');
        $file3 = $cc->add_web_link($module, 'Link 3', 'https://example.com/3');
        
        // Extract hash parts from file names
        preg_match('/WL_([a-f0-9]+)\.xml$/', $file1, $matches1);
        preg_match('/WL_([a-f0-9]+)\.xml$/', $file2, $matches2);
        preg_match('/WL_([a-f0-9]+)\.xml$/', $file3, $matches3);
        
        $hash1 = $matches1[1];
        $hash2 = $matches2[1];
        $hash3 = $matches3[1];
        
        $this->assertEquals(16, strlen($hash1), 'Hash should be 16 characters');
        $this->assertNotEquals($hash1, $hash2, 'Different items should have different hashes');
        $this->assertNotEquals($hash2, $hash3, 'Different items should have different hashes');
    }

    /**
     * Test that identifierref is correctly appended
     */
    public function testIdentifierRefAppending() {
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        $file = $cc->add_web_link($module, 'Video', 'https://example.com');
        
        $this->assertEquals($cc->last_identifier . '_R', $cc->last_identifierref, 
            'identifierref should be identifier + _R');
    }

    /**
     * Test zip_add_lti_to_module with resource_link_id parameter
     */
    public function testZipAddLtiWithResourceLinkId() {
        $filename = tempnam(sys_get_temp_dir(), 'cc.zip');
        unlink($filename);
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            $this->fail("Cannot open $filename");
        }
        
        $cc = new CC();
        $cc->set_title('Test Course');
        $cc->set_description('Test Description');
        
        $module = $cc->add_module('Week 1');
        
        // Add LTI with resource_link_id
        $cc->zip_add_lti_to_module($zip, $module, 'Assignment', 'https://example.com/lti', 
            array(), array(), 'link123');
        
        $this->assertStringStartsWith('LT_', $cc->last_identifier, 
            'LTI identifier should start with LT_');
        
        $zip->close();
    }
}

