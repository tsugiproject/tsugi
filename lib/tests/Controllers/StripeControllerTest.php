<?php

require_once "src/Controllers/Stripe.php";
require_once "src/Controllers/Login.php";
require_once "src/Controllers/Tool.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Lumen/Application.php";
require_once "src/Lumen/Router.php";

use Tsugi\Controllers\Stripe;
use Tsugi\Controllers\Login;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StripeControllerTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;
    private $mockLaunch;
    private $mockApp;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = $_SESSION ?? [];

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
        $CFG->servicename = 'PY4E';

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
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
    }

    public function testRouteConstant()
    {
        $this->assertEquals('/stripe', Stripe::ROUTE);
    }

    public function testCheckoutUrl()
    {
        global $CFG;
        $this->assertEquals('http://localhost/app/stripe', Stripe::checkoutUrl($CFG));
    }

    public function testRoutesRegistersStripeEndpoints()
    {
        Stripe::routes($this->mockApp);

        $routes = $this->mockApp->router->getRoutes();
        $byMethodUri = [];
        foreach ($routes as $route) {
            $byMethodUri[$route['method'] . ' ' . $route['uri']] = true;
        }

        $expected = [
            'GET /stripe',
            'POST /stripe',
            'POST /stripe/webhook',
            'GET /stripe/success',
            'GET /stripe/cancel',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $byMethodUri, 'Missing route: ' . $key);
        }
    }

    public function testGetCheckoutRedirectsToLoginWhenNotLoggedIn()
    {
        global $CFG;

        $CFG->dirroot = realpath(__DIR__ . '/../../..');
        require_once $CFG->dirroot . '/lib/include/lms_lib.php';

        $response = Stripe::getCheckout($this->mockApp);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringEndsWith('/login', $response->getTargetUrl());
        $this->assertEquals(Stripe::checkoutUrl($CFG), Login::peekReturnUrl());
    }
}
