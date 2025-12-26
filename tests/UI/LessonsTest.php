<?php

require_once "src/UI/Lessons.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\UI\Lessons;

class LessonsTest extends \PHPUnit\Framework\TestCase
{
    private $testJsonFile;
    private $originalCFG;

    protected function setUp(): void
    {
        parent::setUp();
        global $CFG;
        $this->originalCFG = $CFG;
        
        // Set up test CFG
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        
        // Create a temporary JSON file for testing
        $this->testJsonFile = sys_get_temp_dir() . '/test_lessons_' . uniqid() . '.json';
        $testData = [
            'modules' => [
                [
                    'title' => 'Test Module 1',
                    'anchor' => 'test1'
                ],
                [
                    'title' => 'Test Module 2',
                    'anchor' => 'test2'
                ]
            ],
            'settings' => [
                'test_key' => 'test_value',
                'another_key' => 'another_value'
            ]
        ];
        file_put_contents($this->testJsonFile, json_encode($testData));
    }

    protected function tearDown(): void
    {
        global $CFG;
        // Clean up temporary file
        if (file_exists($this->testJsonFile)) {
            unlink($this->testJsonFile);
        }
        // Restore original CFG
        $CFG = $this->originalCFG;
        parent::tearDown();
    }

    public function testGetSetting() {
        // Create a minimal Lessons object without calling constructor
        $lessons = new class extends Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Test with no lessons set
        $result = $lessons->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when lessons not set');
        
        // Test with lessons but no settings
        $lessons->lessons = new \stdClass();
        $result = $lessons->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when settings not set');
        
        // Test with settings but key not present
        $lessons->lessons->settings = new \stdClass();
        $result = $lessons->getSetting('test_key', 'default_value');
        $this->assertEquals('default_value', $result, 'getSetting should return default when key not present');
        
        // Test with key present
        $lessons->lessons->settings->test_key = 'test_value';
        $result = $lessons->getSetting('test_key', 'default_value');
        $this->assertEquals('test_value', $result, 'getSetting should return the setting value when key exists');
    }

    public function testInstantiation() {
        $lessons = new Lessons($this->testJsonFile);
        $this->assertInstanceOf(\Tsugi\UI\Lessons::class, $lessons, 'Lessons should instantiate correctly');
        $this->assertNotNull($lessons->lessons, 'Lessons should load JSON data');
        $this->assertCount(2, $lessons->lessons->modules, 'Lessons should load 2 modules');
        $this->assertEquals('Test Module 1', $lessons->lessons->modules[0]->title, 'First module should have correct title');
        $this->assertEquals('test1', $lessons->lessons->modules[0]->anchor, 'First module should have correct anchor');
    }

    public function testGetSettingFromLoadedJson() {
        $lessons = new Lessons($this->testJsonFile);
        $result = $lessons->getSetting('test_key', 'default');
        $this->assertEquals('test_value', $result, 'getSetting should return value from loaded JSON');
        
        $result = $lessons->getSetting('nonexistent', 'default');
        $this->assertEquals('default', $result, 'getSetting should return default for nonexistent key');
    }
    
    /**
     * Test expandLink() static method - does macro substitution on URLs
     */
    public function testExpandLink() {
        global $CFG;
        
        // Test with {apphome} macro
        $url = '{apphome}/some/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/app/some/path', $result, 'expandLink should replace {apphome}');
        
        // Test with {wwwroot} macro
        $url = '{wwwroot}/other/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/other/path', $result, 'expandLink should replace {wwwroot}');
        
        // Test with both macros
        $url = '{apphome}/app and {wwwroot}/www';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/app/app and http://localhost/www', $result, 'expandLink should replace both macros');
        
        // Test with no macros
        $url = 'http://example.com/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://example.com/path', $result, 'expandLink should leave URLs without macros unchanged');
    }
    
    /**
     * Test expandLink() with URLs that already start with http:// or https://
     */
    public function testExpandLinkSkipsHttpUrls() {
        global $CFG;
        
        // Test with http:// URL (should skip expansion)
        $url = 'http://example.com/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://example.com/path', $result, 'expandLink should skip URLs starting with http://');
        
        // Test with https:// URL (should skip expansion)
        $url = 'https://example.com/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('https://example.com/path', $result, 'expandLink should skip URLs starting with https://');
        
        // Test with http:// URL containing macros (should still skip)
        $url = 'http://example.com/{apphome}/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://example.com/{apphome}/path', $result, 'expandLink should skip URLs starting with http:// even if they contain macros');
    }
    
    /**
     * Test expandLink() with duplicate placeholders cleanup
     */
    public function testExpandLinkCleansDuplicatePlaceholders() {
        global $CFG;
        
        // Test with duplicate {apphome} placeholders
        $url = '{apphome}/{apphome}/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/app/path', $result, 'expandLink should clean up duplicate {apphome} placeholders');
        
        // Test with duplicate {wwwroot} placeholders
        $url = '{wwwroot}/{wwwroot}/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/path', $result, 'expandLink should clean up duplicate {wwwroot} placeholders');
        
        // Test with multiple slashes between duplicates
        $url = '{apphome}//{apphome}/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/app/path', $result, 'expandLink should clean up duplicate placeholders with multiple slashes');
    }
    
    /**
     * Test expandLink() prevents double expansion
     */
    public function testExpandLinkPreventsDoubleExpansion() {
        global $CFG;
        
        // Test that URLs starting with http:// are returned as-is (even with placeholders)
        $url = 'http://localhost/app/some/path/{apphome}';
        $result = Lessons::expandLink($url);
        $this->assertEquals('http://localhost/app/some/path/{apphome}', $result, 'expandLink should return http:// URLs as-is without processing');
        
        // Test double expansion prevention with a URL that contains full expanded apphome
        // but doesn't start with http:// (edge case - unlikely in practice)
        $url = 'someprefix' . $CFG->apphome . '/path/{apphome}';
        $result = Lessons::expandLink($url);
        // Should detect that apphome is already present and remove placeholder
        $this->assertStringNotContainsString('{apphome}', $result, 'expandLink should remove placeholders when URL already contains full expanded apphome');
        $this->assertStringContainsString($CFG->apphome, $result, 'expandLink should preserve existing expanded apphome');
        
        // Test with wwwroot
        $url = 'someprefix' . $CFG->wwwroot . '/path/{wwwroot}';
        $result = Lessons::expandLink($url);
        $this->assertStringNotContainsString('{wwwroot}', $result, 'expandLink should remove placeholders when URL already contains full expanded wwwroot');
    }
    
    /**
     * Test expandLink() cleans up double slashes
     */
    public function testExpandLinkCleansDoubleSlashes() {
        global $CFG;
        
        // Test that double slashes are cleaned up after placeholder removal
        $url = 'prefix' . $CFG->apphome . '/path//{apphome}';
        $result = Lessons::expandLink($url);
        // After removing placeholder, should clean up double slashes
        $pos = strpos($result, $CFG->apphome);
        if ($pos !== false) {
            $after_apphome = substr($result, $pos + strlen($CFG->apphome));
            // Should not have double slashes (except http:// at the start)
            $this->assertStringNotContainsString('//', $after_apphome, 'expandLink should clean up double slashes after placeholder removal');
        }
        
        // Test normal expansion doesn't create double slashes
        $url = '{apphome}/path';
        $result = Lessons::expandLink($url);
        $this->assertEquals($CFG->apphome . '/path', $result, 'Normal expansion should create clean URLs');
    }
    
    /**
     * Test isSingle() method
     */
    public function testIsSingle() {
        $lessons = new class extends Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Test with no anchor or position
        $lessons->anchor = null;
        $lessons->position = null;
        $this->assertFalse($lessons->isSingle(), 'isSingle should return false when anchor and position are null');
        
        // Test with anchor set
        $lessons->anchor = 'test-anchor';
        $lessons->position = null;
        $this->assertTrue($lessons->isSingle(), 'isSingle should return true when anchor is set');
        
        // Test with position set
        $lessons->anchor = null;
        $lessons->position = 5;
        $this->assertTrue($lessons->isSingle(), 'isSingle should return true when position is set');
        
        // Test with both set
        $lessons->anchor = 'test-anchor';
        $lessons->position = 5;
        $this->assertTrue($lessons->isSingle(), 'isSingle should return true when both anchor and position are set');
    }
    
    /**
     * Test getLtiByRlid() method with items array support
     */
    public function testGetLtiByRlidWithItemsArray() {
        $lessons = new class extends Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        // Set up lessons with items array containing LTI
        $lessons->lessons = new \stdClass();
        $lessons->lessons->modules = [
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
        $lti = $lessons->getLtiByRlid('rlid123');
        $this->assertNotNull($lti, 'getLtiByRlid should find LTI in items array');
        $this->assertEquals('lti', $lti->type);
        $this->assertEquals('Test LTI', $lti->title);
        
        // Test finding discussion in items array
        $discussion = $lessons->getLtiByRlid('rlid456');
        $this->assertNotNull($discussion, 'getLtiByRlid should find discussion in items array');
        $this->assertEquals('discussion', $discussion->type);
        
        // Test not found
        $notFound = $lessons->getLtiByRlid('nonexistent');
        $this->assertNull($notFound, 'getLtiByRlid should return null for nonexistent resource_link_id');
    }
    
    /**
     * Test getModuleByRlid() method with items array support
     */
    public function testGetModuleByRlidWithItemsArray() {
        $lessons = new class extends Lessons {
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
        
        $lessons->lessons = new \stdClass();
        $lessons->lessons->modules = [$module1, $module2];
        
        // Test finding module by LTI resource_link_id in items array
        $foundModule = $lessons->getModuleByRlid('rlid1');
        $this->assertNotNull($foundModule, 'getModuleByRlid should find module with LTI in items array');
        $this->assertEquals('Module 1', $foundModule->title);
        
        // Test finding module by discussion resource_link_id in items array
        $foundModule = $lessons->getModuleByRlid('rlid2');
        $this->assertNotNull($foundModule, 'getModuleByRlid should find module with discussion in items array');
        $this->assertEquals('Module 2', $foundModule->title);
        
        // Test not found
        $notFound = $lessons->getModuleByRlid('nonexistent');
        $this->assertNull($notFound, 'getModuleByRlid should return null for nonexistent resource_link_id');
    }
    
    /**
     * Test getModuleByAnchor() method
     */
    public function testGetModuleByAnchor() {
        $lessons = new class extends Lessons {
            public function __construct() {
                // Skip parent constructor
            }
        };
        
        $lessons->lessons = new \stdClass();
        $lessons->lessons->modules = [
            (object)['title' => 'Module 1', 'anchor' => 'mod1'],
            (object)['title' => 'Module 2', 'anchor' => 'mod2']
        ];
        
        // Test finding module by anchor
        $module = $lessons->getModuleByAnchor('mod1');
        $this->assertNotNull($module, 'getModuleByAnchor should find module by anchor');
        $this->assertEquals('Module 1', $module->title);
        
        // Test not found
        $notFound = $lessons->getModuleByAnchor('nonexistent');
        $this->assertNull($notFound, 'getModuleByAnchor should return null for nonexistent anchor');
    }

}
