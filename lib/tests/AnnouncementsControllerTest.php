<?php
/**
 * Smoke tests for Announcements Controller
 * 
 * Simple unit tests to verify the controller can be parsed and loaded.
 * Functional tests can be added later.
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../../tsugi/vendor/autoload.php';
}

// Load Tsugi config and bootstrap if needed
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once __DIR__ . '/../../config.php';
}

// Load the controllers directly
require_once __DIR__ . '/../src/Controllers/Tool.php';
require_once __DIR__ . '/../src/Controllers/Announcements.php';

/**
 * Simple test case with basic assertions
 */
class AnnouncementsControllerTest
{
    /**
     * Simple assertion helper
     */
    protected function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new \Exception($message ?: 'Assertion failed');
        }
    }
    
    /**
     * Test that Announcements controller class can be loaded
     */
    public function testControllerClassExists()
    {
        $this->assertTrue(
            class_exists('Tsugi\Controllers\Announcements'),
            'Announcements controller class should exist'
        );
        
        echo "✓ Announcements controller class exists\n";
    }
    
    /**
     * Test that Announcements controller extends Tool base class
     */
    public function testControllerExtendsTool()
    {
        $this->assertTrue(
            is_subclass_of('Tsugi\Controllers\Announcements', 'Tsugi\Controllers\Tool'),
            'Announcements controller should extend Tool base class'
        );
        
        echo "✓ Announcements controller extends Tool\n";
    }
    
    /**
     * Test that Announcements controller has required constants
     */
    public function testControllerHasConstants()
    {
        $this->assertTrue(
            defined('Tsugi\Controllers\Announcements::ROUTE'),
            'Announcements controller should have ROUTE constant'
        );
        
        $this->assertTrue(
            defined('Tsugi\Controllers\Announcements::REDIRECT'),
            'Announcements controller should have REDIRECT constant'
        );
        
        $routeValue = constant('Tsugi\Controllers\Announcements::ROUTE');
        $this->assertTrue(
            $routeValue === '/announcements',
            'ROUTE constant should be /announcements, got: ' . $routeValue
        );
        
        echo "✓ Announcements controller has required constants\n";
    }
    
    /**
     * Test that Announcements controller has routes method
     */
    public function testControllerHasRoutesMethod()
    {
        $this->assertTrue(
            method_exists('Tsugi\Controllers\Announcements', 'routes'),
            'Announcements controller should have routes() method'
        );
        
        $this->assertTrue(
            is_callable(['Tsugi\Controllers\Announcements', 'routes']),
            'Announcements controller routes() method should be callable'
        );
        
        echo "✓ Announcements controller has routes() method\n";
    }
    
    /**
     * Test that Announcements controller has required action methods
     */
    public function testControllerHasActionMethods()
    {
        $requiredMethods = [
            'index',
            'json',
            'dismiss',
            'add',
            'addPost',
            'edit',
            'editPost',
            'manage',
            'managePost',
            'analytics'
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists('Tsugi\Controllers\Announcements', $method),
                "Announcements controller should have {$method}() method"
            );
        }
        
        echo "✓ Announcements controller has all required action methods\n";
    }
    
    /**
     * Test that Announcements controller routes method signature is correct
     * Note: We don't actually call routes() as it requires a real Application instance
     */
    public function testControllerRoutesMethodSignature()
    {
        $reflection = new \ReflectionMethod('Tsugi\Controllers\Announcements', 'routes');
        $parameters = $reflection->getParameters();
        
        $this->assertTrue(
            count($parameters) >= 1,
            'routes() method should have at least one parameter'
        );
        
        $this->assertTrue(
            $parameters[0]->getType() !== null,
            'routes() method first parameter should have a type hint'
        );
        
        echo "✓ Announcements controller routes() method signature is valid\n";
    }
    
    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "\n=== Announcements Controller Smoke Tests ===\n\n";
        
        try {
            $this->testControllerClassExists();
            $this->testControllerExtendsTool();
            $this->testControllerHasConstants();
            $this->testControllerHasRoutesMethod();
            $this->testControllerHasActionMethods();
            $this->testControllerRoutesMethodSignature();
            
            echo "\n✓ All Announcements controller smoke tests passed!\n\n";
            return true;
        } catch (\Exception $e) {
            echo "\n✗ Test failed: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new AnnouncementsControllerTest();
    exit($test->runAll() ? 0 : 1);
}
