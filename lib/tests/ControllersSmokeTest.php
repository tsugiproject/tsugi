<?php
/**
 * Combined smoke tests for all Controllers
 * 
 * Runs smoke tests for Pages and Announcements controllers.
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../../tsugi/vendor/autoload.php';
}

require_once __DIR__ . '/PagesControllerTest.php';
require_once __DIR__ . '/AnnouncementsControllerTest.php';

class ControllersSmokeTest
{
    /**
     * Run all controller smoke tests
     */
    public function runAll()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "CONTROLLERS SMOKE TESTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $allPassed = true;
        
        // Test Pages Controller
        $pagesTest = new PagesControllerTest();
        if (!$pagesTest->runAll()) {
            $allPassed = false;
        }
        
        echo "\n" . str_repeat("-", 60) . "\n\n";
        
        // Test Announcements Controller
        $announcementsTest = new AnnouncementsControllerTest();
        if (!$announcementsTest->runAll()) {
            $allPassed = false;
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        if ($allPassed) {
            echo "✓ ALL CONTROLLER SMOKE TESTS PASSED!\n";
        } else {
            echo "✗ SOME TESTS FAILED\n";
        }
        echo str_repeat("=", 60) . "\n\n";
        
        return $allPassed;
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new ControllersSmokeTest();
    exit($test->runAll() ? 0 : 1);
}
