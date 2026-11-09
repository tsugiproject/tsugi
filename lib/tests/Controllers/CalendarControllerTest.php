<?php

require_once "src/Controllers/Calendar.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumos/Application.php";
require_once "src/Lumos/Router.php";

use Tsugi\Controllers\Calendar;
use Tsugi\Lumos\Application;

class CalendarControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $mockLaunch;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost';
        $CFG->apphome = 'http://localhost/app';

        if (!isset($CFG->loader)) {
            $autoloaderPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                $CFG->loader = require_once $autoloaderPath;
            } else {
                $CFG->loader = new \stdClass();
            }
        }

        $this->mockLaunch = new \stdClass();
        $this->mockLaunch->output = new \stdClass();
        $this->mockLaunch->output->buffer = true;

        $this->mockApp = new Application($this->mockLaunch);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testRoutesRegistersCalendarRoute()
    {
        Calendar::routes($this->mockApp);
        $routes = $this->mockApp->router->getRoutes();
        $uris = [];
        foreach ($routes as $route) {
            $uris[] = $route['uri'];
        }
        $hasCal = false;
        $hasJson = false;
        foreach ($uris as $uri) {
            if ($uri === '/calendar' || $uri === '/calendar/') {
                $hasCal = true;
            }
            if ($uri === '/calendar/json' || $uri === '/calendar/json/') {
                $hasJson = true;
            }
        }
        $this->assertTrue($hasCal, 'Should register /calendar route');
        $this->assertTrue($hasJson, 'Should register /calendar/json route');
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/calendar', Calendar::ROUTE, 'ROUTE constant should be /calendar');
    }

    public function testDueRowsSortByDateThenTitle()
    {
        $rows = Calendar::sortDueRows(array(
            array('date' => '2026-05-02', 'title' => 'B'),
            array('date' => '2026-05-01', 'title' => 'Z'),
            array('date' => '2026-05-02', 'title' => 'a'),
        ));
        $this->assertSame(array('Z', 'a', 'B'), array_column($rows, 'title'));
    }

    public function testListMarkupGroupsByDay()
    {
        if (!function_exists('__')) {
            function __($s) { return $s; }
        }
        $html = Calendar::listMarkup(array(
            array(
                'date' => '2026-05-01',
                'title' => 'Quiz',
                'href' => '/lessons/week-1',
                'module_title' => 'Week 1',
                'due_mod' => 'tsugi-assignments-due-soon',
            ),
            array(
                'date' => '2026-05-01',
                'title' => 'Reading',
                'href' => '/lessons/week-1',
                'module_title' => 'Week 1',
                'due_mod' => 'tsugi-assignments-due-future',
            ),
        ), false);
        $this->assertStringContainsString('Due dates', $html);
        $this->assertStringContainsString('Up next', $html);
        $this->assertStringContainsString('May 1, 2026', $html);
        $this->assertSame(1, substr_count($html, 'class="tsugi-due-list-date"'));
        $quiz = strpos($html, 'Quiz');
        $reading = strpos($html, 'Reading');
        $this->assertNotFalse($quiz);
        $this->assertLessThan($reading, $quiz);
    }

    public function testHomeListKeepsTheMostRecentLateAndTwoUpcoming()
    {
        if (!function_exists('__')) {
            function __($s) { return $s; }
        }
        $html = Calendar::listMarkup(array(
            $this->dueRow('2026-01-01', 'Old late', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-02-01', 'Mid late', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-03-01', 'Recent late', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-03-15', 'Done', 'tsugi-assignments-due-completed'),
            $this->dueRow('2026-04-01', 'This week', 'tsugi-assignments-due-soon'),
            $this->dueRow('2026-05-01', 'Soonest upcoming', 'tsugi-assignments-due-future'),
            $this->dueRow('2026-06-01', 'Second upcoming', 'tsugi-assignments-due-future'),
            $this->dueRow('2026-07-01', 'Third upcoming', 'tsugi-assignments-due-future'),
        ), true);
        $this->assertStringNotContainsString('Old late', $html);
        $this->assertStringNotContainsString('Mid late', $html);
        $this->assertStringContainsString('Recent late', $html);
        $this->assertStringContainsString('View all (3 late)', $html);
        $this->assertStringContainsString('Done', $html);
        $this->assertStringContainsString('This week', $html);
        $this->assertStringContainsString('Soonest upcoming', $html);
        $this->assertStringContainsString('Second upcoming', $html);
        $this->assertStringNotContainsString('Third upcoming', $html);
        $this->assertStringContainsString('View all (3 upcoming)', $html);
        $lateLink = strpos($html, 'View all (3 late)');
        $recent = strpos($html, 'Recent late');
        $second = strpos($html, 'Second upcoming');
        $upcomingLink = strpos($html, 'View all (3 upcoming)');
        $this->assertNotFalse($lateLink);
        $this->assertNotFalse($recent);
        $this->assertLessThan($recent, $lateLink);
        $this->assertNotFalse($second);
        $this->assertNotFalse($upcomingLink);
        $this->assertLessThan($upcomingLink, $second);
    }

    public function testHomeListShowsTwoLateAndTwoUpcomingWithoutViewAll()
    {
        if (!function_exists('__')) {
            function __($s) { return $s; }
        }
        $html = Calendar::listMarkup(array(
            $this->dueRow('2026-01-01', 'Late one', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-02-01', 'Late two', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-05-01', 'Upcoming one', 'tsugi-assignments-due-future'),
            $this->dueRow('2026-06-01', 'Upcoming two', 'tsugi-assignments-due-future'),
        ), true);
        $this->assertStringContainsString('Late one', $html);
        $this->assertStringContainsString('Late two', $html);
        $this->assertStringContainsString('Upcoming one', $html);
        $this->assertStringContainsString('Upcoming two', $html);
        $this->assertStringNotContainsString('View all', $html);
    }

    public function testFullDueListDoesNotTrim()
    {
        if (!function_exists('__')) {
            function __($s) { return $s; }
        }
        $html = Calendar::listMarkup(array(
            $this->dueRow('2026-01-01', 'Old late', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-02-01', 'Mid late', 'tsugi-assignments-due-past'),
            $this->dueRow('2026-03-01', 'Recent late', 'tsugi-assignments-due-past'),
        ), false);
        $this->assertStringContainsString('Old late', $html);
        $this->assertStringContainsString('Mid late', $html);
        $this->assertStringContainsString('Recent late', $html);
        $this->assertStringNotContainsString('View all', $html);
    }

    /**
     * @return array{date:string,title:string,href:string,module_title:string,due_mod:string}
     */
    private function dueRow($date, $title, $dueMod)
    {
        return array(
            'date' => $date,
            'title' => $title,
            'href' => '/lessons/week',
            'module_title' => 'Week',
            'due_mod' => $dueMod,
        );
    }
}
