<?php

require_once "src/UI/Lessons.php";

use \Tsugi\UI\Lessons;

class LessonsTest extends \PHPUnit\Framework\TestCase
{
    private $testJsonFile;

    protected function setUp(): void
    {
        parent::setUp();
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
        // Clean up temporary file
        if (file_exists($this->testJsonFile)) {
            unlink($this->testJsonFile);
        }
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

}
