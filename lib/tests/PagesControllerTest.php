<?php
/**
 * Smoke tests for Pages Controller
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
require_once __DIR__ . '/../src/Controllers/Pages.php';

/**
 * Simple test case with basic assertions
 */
class PagesControllerTest
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
     * Test that Pages controller class can be loaded
     */
    public function testControllerClassExists()
    {
        $this->assertTrue(
            class_exists('Tsugi\Controllers\Pages'),
            'Pages controller class should exist'
        );
        
        echo "✓ Pages controller class exists\n";
    }
    
    /**
     * Test that Pages controller extends Tool base class
     */
    public function testControllerExtendsTool()
    {
        $this->assertTrue(
            is_subclass_of('Tsugi\Controllers\Pages', 'Tsugi\Controllers\Tool'),
            'Pages controller should extend Tool base class'
        );
        
        echo "✓ Pages controller extends Tool\n";
    }
    
    /**
     * Test that Pages controller has required constants
     */
    public function testControllerHasConstants()
    {
        $this->assertTrue(
            defined('Tsugi\Controllers\Pages::ROUTE'),
            'Pages controller should have ROUTE constant'
        );
        
        $this->assertTrue(
            defined('Tsugi\Controllers\Pages::REDIRECT'),
            'Pages controller should have REDIRECT constant'
        );
        
        $routeValue = constant('Tsugi\Controllers\Pages::ROUTE');
        $this->assertTrue(
            $routeValue === '/pages',
            'ROUTE constant should be /pages, got: ' . $routeValue
        );
        
        echo "✓ Pages controller has required constants\n";
    }
    
    /**
     * Test that Pages controller has routes method
     */
    public function testControllerHasRoutesMethod()
    {
        $this->assertTrue(
            method_exists('Tsugi\Controllers\Pages', 'routes'),
            'Pages controller should have routes() method'
        );
        
        $this->assertTrue(
            is_callable(['Tsugi\Controllers\Pages', 'routes']),
            'Pages controller routes() method should be callable'
        );
        
        echo "✓ Pages controller has routes() method\n";
    }
    
    /**
     * Test that Pages controller has required action methods
     */
    public function testControllerHasActionMethods()
    {
        $requiredMethods = [
            'index',
            'json',
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
                method_exists('Tsugi\Controllers\Pages', $method),
                "Pages controller should have {$method}() method"
            );
        }
        
        echo "✓ Pages controller has all required action methods\n";
    }
    
    /**
     * Test that Pages controller routes method signature is correct
     * Note: We don't actually call routes() as it requires a real Application instance
     */
    public function testControllerRoutesMethodSignature()
    {
        $reflection = new \ReflectionMethod('Tsugi\Controllers\Pages', 'routes');
        $parameters = $reflection->getParameters();
        
        $this->assertTrue(
            count($parameters) >= 1,
            'routes() method should have at least one parameter'
        );
        
        $this->assertTrue(
            $parameters[0]->getType() !== null,
            'routes() method first parameter should have a type hint'
        );
        
        echo "✓ Pages controller routes() method signature is valid\n";
    }
    
    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "\n=== Pages Controller Smoke Tests ===\n\n";
        
        try {
            $this->testControllerClassExists();
            $this->testControllerExtendsTool();
            $this->testControllerHasConstants();
            $this->testControllerHasRoutesMethod();
            $this->testControllerHasActionMethods();
            $this->testControllerRoutesMethodSignature();
            
            echo "\n✓ All Pages controller smoke tests passed!\n\n";
            return true;
        } catch (\Exception $e) {
            echo "\n✗ Test failed: " . $e->getMessage() . "\n\n";
            return false;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new PagesControllerTest();
    exit($test->runAll() ? 0 : 1);
}
