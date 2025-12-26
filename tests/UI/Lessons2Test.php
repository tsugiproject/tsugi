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
     * Test expandLink() with URLs that already start with http:// or https://
     */
    public function testExpandLinkSkipsHttpUrls() {
        global $CFG;
        
        // Test with http:// URL (should skip expansion)
        $url = 'http://example.com/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://example.com/path', $result, 'expandLink should skip URLs starting with http://');
        
        // Test with https:// URL (should skip expansion)
        $url = 'https://example.com/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('https://example.com/path', $result, 'expandLink should skip URLs starting with https://');
        
        // Test with http:// URL containing macros (should still skip)
        $url = 'http://example.com/{apphome}/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://example.com/{apphome}/path', $result, 'expandLink should skip URLs starting with http:// even if they contain macros');
    }
    
    /**
     * Test expandLink() with duplicate placeholders cleanup
     */
    public function testExpandLinkCleansDuplicatePlaceholders() {
        global $CFG;
        
        // Test with duplicate {apphome} placeholders
        $url = '{apphome}/{apphome}/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/app/path', $result, 'expandLink should clean up duplicate {apphome} placeholders');
        
        // Test with duplicate {wwwroot} placeholders
        $url = '{wwwroot}/{wwwroot}/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/path', $result, 'expandLink should clean up duplicate {wwwroot} placeholders');
        
        // Test with multiple slashes between duplicates
        $url = '{apphome}//{apphome}/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/app/path', $result, 'expandLink should clean up duplicate placeholders with multiple slashes');
    }
    
    /**
     * Test expandLink() prevents double expansion
     * 
     * Note: URLs starting with http:// or https:// are returned as-is without processing.
     * Double expansion prevention only works for URLs that don't start with http:// but
     * contain the full expanded value (including protocol).
     */
    public function testExpandLinkPreventsDoubleExpansion() {
        global $CFG;
        
        // Test that URLs starting with http:// are returned as-is (even with placeholders)
        $url = 'http://localhost/app/some/path/{apphome}';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals('http://localhost/app/some/path/{apphome}', $result, 'expandLink should return http:// URLs as-is without processing');
        
        // Test double expansion prevention with a URL that contains full expanded apphome
        // but doesn't start with http:// (edge case - unlikely in practice)
        // Create a URL that has the full apphome value embedded
        $url = 'someprefix' . $CFG->apphome . '/path/{apphome}';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        // Should detect that apphome is already present and remove placeholder
        $this->assertStringNotContainsString('{apphome}', $result, 'expandLink should remove placeholders when URL already contains full expanded apphome');
        $this->assertStringContainsString($CFG->apphome, $result, 'expandLink should preserve existing expanded apphome');
        
        // Test with wwwroot
        $url = 'someprefix' . $CFG->wwwroot . '/path/{wwwroot}';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertStringNotContainsString('{wwwroot}', $result, 'expandLink should remove placeholders when URL already contains full expanded wwwroot');
    }
    
    /**
     * Test expandLink() cleans up double slashes
     */
    public function testExpandLinkCleansDoubleSlashes() {
        global $CFG;
        
        // Test that double slashes are cleaned up after placeholder removal
        // Use a URL that contains full expanded apphome (not starting with http://)
        $url = 'prefix' . $CFG->apphome . '/path//{apphome}';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        // After removing placeholder, should clean up double slashes
        // The result should not have // after the apphome part
        $pos = strpos($result, $CFG->apphome);
        if ($pos !== false) {
            $after_apphome = substr($result, $pos + strlen($CFG->apphome));
            // Should not have double slashes (except http:// at the start)
            $this->assertStringNotContainsString('//', $after_apphome, 'expandLink should clean up double slashes after placeholder removal');
        }
        
        // Test normal expansion doesn't create double slashes
        $url = '{apphome}/path';
        $result = \Tsugi\UI\Lessons2::expandLink($url);
        $this->assertEquals($CFG->apphome . '/path', $result, 'Normal expansion should create clean URLs');
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
    
    /**
     * Test getLtiByRlid() method with items array support
     */
    public function testGetLtiByRlidWithItemsArray() {
        $lessons2 = new class extends \Tsugi\UI\Lessons2 {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Set up lessons with items array containing LTI
        $lessons2->lessons = new \stdClass();
        $lessons2->lessons->modules = [
            (object)[
                'title' => 'Test Module',
                'anchor' => 'test1',
                'items' => [
                    (object)[
                        'type' => 'lti',
                        'title' => 'Test LTI',
                        'resource_link_id' => 'rlid123'
                    ],
                    (object)[
                        'type' => 'discussion',
                        'title' => 'Test Discussion',
                        'resource_link_id' => 'rlid456'
                    ]
                ]
            ]
        ];
        
        // Test finding LTI in items array
        $lti = $lessons2->getLtiByRlid('rlid123');
        $this->assertNotNull($lti, 'getLtiByRlid should find LTI in items array');
        $this->assertEquals('lti', $lti->type);
        $this->assertEquals('Test LTI', $lti->title);
        
        // Test finding discussion in items array
        $discussion = $lessons2->getLtiByRlid('rlid456');
        $this->assertNotNull($discussion, 'getLtiByRlid should find discussion in items array');
        $this->assertEquals('discussion', $discussion->type);
        
        // Test not found
        $notFound = $lessons2->getLtiByRlid('nonexistent');
        $this->assertNull($notFound, 'getLtiByRlid should return null for nonexistent resource_link_id');
    }
    
    /**
     * Test getModuleByRlid() method with items array support
     */
    public function testGetModuleByRlidWithItemsArray() {
        $lessons2 = new class extends \Tsugi\UI\Lessons2 {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Set up lessons with items array
        $module1 = (object)[
            'title' => 'Module 1',
            'anchor' => 'mod1',
            'items' => [
                (object)[
                    'type' => 'lti',
                    'title' => 'LTI 1',
                    'resource_link_id' => 'rlid1'
                ]
            ]
        ];
        
        $module2 = (object)[
            'title' => 'Module 2',
            'anchor' => 'mod2',
            'items' => [
                (object)[
                    'type' => 'discussion',
                    'title' => 'Discussion 1',
                    'resource_link_id' => 'rlid2'
                ]
            ]
        ];
        
        $lessons2->lessons = new \stdClass();
        $lessons2->lessons->modules = [$module1, $module2];
        
        // Test finding module by LTI resource_link_id in items array
        $foundModule = $lessons2->getModuleByRlid('rlid1');
        $this->assertNotNull($foundModule, 'getModuleByRlid should find module with LTI in items array');
        $this->assertEquals('Module 1', $foundModule->title);
        
        // Test finding module by discussion resource_link_id in items array
        $foundModule = $lessons2->getModuleByRlid('rlid2');
        $this->assertNotNull($foundModule, 'getModuleByRlid should find module with discussion in items array');
        $this->assertEquals('Module 2', $foundModule->title);
        
        // Test not found
        $notFound = $lessons2->getModuleByRlid('nonexistent');
        $this->assertNull($notFound, 'getModuleByRlid should return null for nonexistent resource_link_id');
    }
    
    /**
     * Test getModuleByAnchor() method
     */
    public function testGetModuleByAnchor() {
        $lessons2 = new class extends \Tsugi\UI\Lessons2 {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $lessons2->lessons = new \stdClass();
        $lessons2->lessons->modules = [
            (object)['title' => 'Module 1', 'anchor' => 'mod1'],
            (object)['title' => 'Module 2', 'anchor' => 'mod2']
        ];
        
        // Test finding module by anchor
        $module = $lessons2->getModuleByAnchor('mod1');
        $this->assertNotNull($module, 'getModuleByAnchor should find module by anchor');
        $this->assertEquals('Module 1', $module->title);
        
        // Test not found
        $notFound = $lessons2->getModuleByAnchor('nonexistent');
        $this->assertNull($notFound, 'getModuleByAnchor should return null for nonexistent anchor');
    }
}

