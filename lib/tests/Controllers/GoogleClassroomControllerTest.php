<?php

require_once "src/Controllers/GoogleClassroom.php";
require_once "src/Controllers/Tool.php";
require_once "src/Lumos/Application.php";
require_once "src/Lumos/Router.php";

use \Tsugi\Controllers\GoogleClassroom;
use \Tsugi\Lumos\Application;

class GoogleClassroomControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;

    protected function setUp(): void {
        global $CFG;
        $this->originalCFG = $CFG ?? null;
        $CFG = new \stdClass();
        $CFG->loader = new \stdClass();
    }

    protected function tearDown(): void {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testGclassRoutesStayOnTheSamePaths() {
        $launch = new \stdClass();
        $launch->output = new \stdClass();
        $launch->output->buffer = true;
        $app = new Application($launch);
        GoogleClassroom::routes($app);

        $uris = array();
        foreach ( $app->router->getRoutes() as $route ) {
            $uris[] = $route['uri'];
            $this->assertSame('GET', $route['method']);
        }

        $this->assertContains('/gclass/login', $uris);
        $this->assertContains('/gclass/assign', $uris);
        $this->assertContains('/gclass/share', $uris);
        $this->assertContains('/gclass/launch', $uris);
        $this->assertContains('/gclass/launch/{resource}', $uris);
    }
}
