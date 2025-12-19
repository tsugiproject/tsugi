<?php

require_once "src/UI/Lessons2.php";
require_once "src/Config/ConfigInfo.php";

/**
 * Simple tests for Lessons2 utility class
 * 
 * Tests basic methods that don't require complex setup or database connections.
 */
class Lessons2Test extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    
    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';
    }
    
    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }
    
    /**
     * Test getSetting() method - retrieves settings from lessons object
     */
    public function testGetSetting() {
        // Create a minimal Lessons2 object without calling constructor
        $lessons2 = new class extends \Tsugi\UI\Lessons2 {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Test with no lessons set
        $result = $lessons2->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when lessons not set');
        
        // Test with lessons but no settings
        $lessons2->lessons = new \stdClass();
        $result = $lessons2->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when settings not set');
        
        // Test with settings but key not present
        $lessons2->lessons->settings = new \stdClass();
        $result = $lessons2->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when key not present');
        
        // Test with key present
        $lessons2->lessons->settings->test_key = 'test_value';
        $result = $lessons2->getSetting('test_key', 'default_value');
        $this->assertEquals('test_value', $result, 'getSetting should return the setting value when key exists');
    }
    
    /**
     * Test isSingle() method - checks if viewing a single lesson
     */
    public function testIsSingle() {
        $lessons2 = new class extends \Tsugi\UI\Lessons2 {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Test with no anchor or position
        $lessons2->anchor = null;
        $lessons2->position = null;
        $this->assertFalse($lessons2->isSingle(), 'isSingle should return false when anchor and position are null');
        
        // Test with anchor set
        $lessons2->anchor = 'test-anchor';
        $lessons2->position = null;
        $this->assertTrue($lessons2->isSingle(), 'isSingle should return true when anchor is set');
        
        // Test with position set
        $lessons2->anchor = null;
        $lessons2->position = 5;
        $this->assertTrue($lessons2->isSingle(), 'isSingle should return true when position is set');
        
        // Test with both set
        $lessons2->anchor = 'test-anchor';
        $lessons2->position = 5;
        $this->assertTrue($lessons2->isSingle(), 'isSingle should return true when both anchor and position are set');
    }
    
    /**
     * Test expandLink() static method - does macro substitution on URLs
     */
    public function testExpandLink() {
        global $CFG;
        
        // Test with {apphome} macro
        $url = '{apphome}/some/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/app/some/path', $result, 'expandLink should replace {apphome}');
        
        // Test with {wwwroot} macro
        $url = '{wwwroot}/other/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/other/path', $result, 'expandLink should replace {wwwroot}');
        
        // Test with both macros
        $url = '{apphome}/app and {wwwroot}/www';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/app/app and http://localhost/www', $result, 'expandLink should replace both macros');
        
        // Test with no macros
        $url = 'http://example.com/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://example.com/path', $result, 'expandLink should leave URLs without macros unchanged');
    }
    
    /**
     * Test makeUrlResource() static method - creates a URL resource object
     */
    public function testMakeUrlResource() {
        global $CFG;
        
        // Test with video type
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('video', 'My Video', 'http://example.com/video');
        $this->assertIsObject($resource);
        $this->assertEquals('video', $resource->type);
        $this->assertEquals('fa-video-camera', $resource->icon);
        $this->assertEquals('Video: My Video', $resource->title);
        $this->assertEquals('http://example.com/video', $resource->url);
        
        // Test with slides type
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('slides', 'My Slides', 'http://example.com/slides');
        $this->assertEquals('slides', $resource->type);
        $this->assertEquals('fa-file-powerpoint-o', $resource->icon);
        $this->assertEquals('Slides: My Slides', $resource->title);
        
        // Test with assignment type
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('assignment', 'Homework', 'http://example.com/assign');
        $this->assertEquals('assignment', $resource->type);
        $this->assertEquals('fa-lock', $resource->icon);
        $this->assertEquals('Assignment: Homework', $resource->title);
        
        // Test with solution type
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('solution', 'Answer Key', 'http://example.com/solution');
        $this->assertEquals('solution', $resource->type);
        $this->assertEquals('fa-unlock', $resource->icon);
        $this->assertEquals('Solution: Answer Key', $resource->title);
        
        // Test with reference type
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('reference', 'External Link', 'http://example.com/ref');
        $this->assertEquals('reference', $resource->type);
        $this->assertEquals('fa-external-link', $resource->icon);
        $this->assertEquals('Reference: External Link', $resource->title);
        
        // Test with unknown type (should default to fa-external-link)
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('unknown', 'Unknown Type', 'http://example.com/unknown');
        $this->assertEquals('unknown', $resource->type);
        $this->assertEquals('fa-external-link', $resource->icon);
        $this->assertEquals('Unknown: Unknown Type', $resource->title);
        
        // Test with title containing colon (should not add prefix)
        $resource = \Tsugi\UI\Lessons2::makeUrlResource('video', 'Video: Special Title', 'http://example.com/video');
        $this->assertEquals('Video: Special Title', $resource->title, 'Title with colon should not get prefix added');
    }
}

