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
            $uris[$route['method'].' '.$route['uri']] = true;
        }

        $this->assertArrayHasKey('GET /gclass/login', $uris);
        $this->assertArrayHasKey('GET /gclass/assign', $uris);
        $this->assertArrayHasKey('GET /gclass/share', $uris);
        $this->assertArrayHasKey('GET /gclass/launch', $uris);
        $this->assertArrayHasKey('GET /gclass/launch/{resource}', $uris);
        $this->assertArrayHasKey('GET /gclass/courses', $uris);
        $this->assertArrayHasKey('GET /gclass/course/{context}', $uris);
        $this->assertArrayHasKey('POST /gclass/course/{context}/send', $uris);
        $this->assertArrayHasKey('POST /gclass/course/{context}/reset', $uris);
        $this->assertArrayHasKey('GET /gclass/course/{context}/reconnect', $uris);
        $this->assertArrayHasKey('GET /gclass/use-account', $uris);
    }
}
