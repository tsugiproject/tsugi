<?php

require_once("src/Core/Link.php");
require_once("src/Core/Launch.php");
require_once("src/Core/Context.php");
require_once("src/Core/Key.php");
require_once("src/Config/ConfigInfo.php");
use \Tsugi\Core\Link;
use \Tsugi\Core\Launch;
use \Tsugi\Core\Context;
use \Tsugi\Core\Key;
use \Tsugi\Config\ConfigInfo;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $link = new \Tsugi\Core\Link();
        $this->assertInstanceOf(\Tsugi\Core\Link::class, $link, 'Link should instantiate correctly');
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $link, 'Link should extend Entity');
    }

    /**
     * Test that settingsGet() returns link-level setting when present
     */
    public function testSettingsGetReturnsLinkLevelSetting() {
        $link = $this->createLinkWithMockSettings(['test_key' => 'link_value']);
        
        $result = $link->settingsGet('test_key', 'default');
        $this->assertEquals('link_value', $result, 'Should return link-level setting when present');
    }

    /**
     * Test that settingsGet() bubbles up to context when link setting is not present
     */
    public function testSettingsGetBubblesToContext() {
        $link = $this->createLinkWithMockSettings(
            [], // No link settings
            ['test_key' => 'context_value'] // Context has the setting
        );
        
        $result = $link->settingsGet('test_key', 'default');
        $this->assertEquals('context_value', $result, 'Should return context-level setting when link setting not present');
    }

    /**
     * Test that settingsGet() bubbles up to key when link and context settings are not present
     */
    public function testSettingsGetBubblesToKey() {
        $link = $this->createLinkWithMockSettings(
            [], // No link settings
            [], // No context settings
            ['test_key' => 'key_value'] // Key has the setting
        );
        
        $result = $link->settingsGet('test_key', 'default');
        $this->assertEquals('key_value', $result, 'Should return key-level setting when link and context settings not present');
    }

    /**
     * Test that settingsGet() bubbles up to system-wide extension settings
     */
    public function testSettingsGetBubblesToSystemExtension() {
        global $CFG;
        
        // Create a ConfigInfo object with extension setting
        $originalCFG = $CFG;
        $CFG = new ConfigInfo(__DIR__, 'http://example.com');
        $CFG->setExtension('globalsetting_test_key', 'system_value');
        
        try {
            $link = $this->createLinkWithMockSettings(
                [], // No link settings
                [], // No context settings
                []  // No key settings
            );
            
            $result = $link->settingsGet('test_key', 'default');
            $this->assertEquals('system_value', $result, 'Should return system-wide extension setting when other levels not present');
        } finally {
            // Restore original CFG
            $CFG = $originalCFG;
        }
    }

    /**
     * Test that settingsGet() returns default when setting not found at any level
     */
    public function testSettingsGetReturnsDefaultWhenNotFound() {
        global $CFG;
        $originalCFG = $CFG;
        // Use ConfigInfo without the setting
        $CFG = new ConfigInfo(__DIR__, 'http://example.com');
        
        try {
            $link = $this->createLinkWithMockSettings(
                [], // No link settings
                [], // No context settings
                []  // No key settings
            );
            
            $result = $link->settingsGet('nonexistent_key', 'default_value');
            $this->assertEquals('default_value', $result, 'Should return default when setting not found at any level');
        } finally {
            $CFG = $originalCFG;
        }
    }

    /**
     * Test that settingsGet() skips null or empty string values and continues bubbling
     */
    public function testSettingsGetSkipsNullAndEmptyValues() {
        $link = $this->createLinkWithMockSettings(
            ['test_key' => null], // Link has null value
            ['test_key' => ''],   // Context has empty string
            ['test_key' => 'key_value'] // Key has actual value
        );
        
        $result = $link->settingsGet('test_key', 'default');
        $this->assertEquals('key_value', $result, 'Should skip null and empty string values and continue bubbling');
    }

    /**
     * Test that settingsGet() returns first non-null, non-empty value found
     */
    public function testSettingsGetReturnsFirstNonEmptyValue() {
        global $CFG;
        $originalCFG = $CFG;
        $CFG = new ConfigInfo(__DIR__, 'http://example.com');
        $CFG->setExtension('globalsetting_test_key', 'system_value');
        
        try {
            $link = $this->createLinkWithMockSettings(
                ['test_key' => ''],      // Link has empty string
                ['test_key' => null],     // Context has null
                ['test_key' => '']       // Key has empty string
            );
            
            $result = $link->settingsGet('test_key', 'default');
            $this->assertEquals('system_value', $result, 'Should return first non-null, non-empty value found');
        } finally {
            $CFG = $originalCFG;
        }
    }

    /**
     * Test that settingsGet() handles missing context gracefully
     */
    public function testSettingsGetHandlesMissingContext() {
        $link = $this->createLinkWithMockSettings(
            [], // No link settings
            null, // No context object
            ['test_key' => 'key_value'] // Key has the setting
        );
        
        $result = $link->settingsGet('test_key', 'default');
        $this->assertEquals('key_value', $result, 'Should work when context object is missing');
    }

    /**
     * Test that settingsGet() handles missing key gracefully
     */
    public function testSettingsGetHandlesMissingKey() {
        global $CFG;
        $originalCFG = $CFG;
        $CFG = new ConfigInfo(__DIR__, 'http://example.com');
        
        try {
            $link = $this->createLinkWithMockSettings(
                [], // No link settings
                ['test_key' => 'context_value'], // Context has setting
                null // No key object
            );
            
            $result = $link->settingsGet('test_key', 'default');
            $this->assertEquals('context_value', $result, 'Should work when key object is missing');
        } finally {
            $CFG = $originalCFG;
        }
    }

    /**
     * Test that settingsGet() handles CFG without getExtension method gracefully
     */
    public function testSettingsGetHandlesCFGWithoutGetExtension() {
        global $CFG;
        $originalCFG = $CFG;
        // Use stdClass which doesn't have getExtension method
        $CFG = new \stdClass();
        
        try {
            $link = $this->createLinkWithMockSettings(
                [], // No link settings
                [], // No context settings
                []  // No key settings
            );
            
            $result = $link->settingsGet('test_key', 'default_value');
            $this->assertEquals('default_value', $result, 'Should return default when CFG does not have getExtension method');
        } finally {
            $CFG = $originalCFG;
        }
    }

    /**
     * Helper method to create a Link with mocked settings at different levels
     */
    private function createLinkWithMockSettings(
        $linkSettings = [],
        $contextSettings = [],
        $keySettings = []
    ) {
        // Create a partial mock of Link that mocks settingsGetAll
        $linkMock = $this->getMockBuilder(Link::class)
            ->onlyMethods(['settingsGetAll'])
            ->getMock();
        
        $linkMock->id = 1;
        $linkMock->method('settingsGetAll')->willReturn($linkSettings);
        
        // Create Launch
        $launch = new Launch();
        $linkMock->launch = $launch;
        
        // Create and configure Context if settings provided
        if ($contextSettings !== null) {
            $context = $this->createMock(Context::class);
            $context->method('settingsGetAll')->willReturn($contextSettings);
            $launch->context = $context;
        } else {
            $launch->context = null;
        }
        
        // Create and configure Key if settings provided
        if ($keySettings !== null) {
            $key = $this->createMock(Key::class);
            $key->method('settingsGetAll')->willReturn($keySettings);
            $launch->key = $key;
        } else {
            $launch->key = null;
        }
        
        return $linkMock;
    }
}
