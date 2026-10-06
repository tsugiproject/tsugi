<?php

use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
use Facebook\WebDriver\JavaScriptExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverNavigationInterface;
use Facebook\WebDriver\WebDriverOptions;
use Facebook\WebDriver\WebDriverTargetLocator;
use Facebook\WebDriver\WebDriverWait;

/**
 * Forwards WebDriver calls and scans every returned page for PHP errors.
 */
final class ScanningWebDriver implements WebDriver, JavaScriptExecutor
{
    private string $lastPausedUrl = '';

    public function __construct(
        private readonly RemoteWebDriver $inner,
        private readonly TsugiPantherTestCase $test,
    ) {
    }

    public function getPageSource()
    {
        $html = $this->readPageSource();
        $this->pauseOnNewScreen();
        $this->test->assertPageHasNoPhpError($html, $this->currentUrl());

        return $html;
    }

    /**
     * qa/panther-watch.sh sets PANTHER_WATCH_PAUSE. Headless CI leaves it unset.
     */
    private function pauseOnNewScreen(): void
    {
        $raw = getenv('PANTHER_WATCH_PAUSE');
        if ($raw === false || $raw === '' || !is_numeric($raw) || (float) $raw <= 0) {
            return;
        }
        $url = $this->currentUrl();
        if ($url === '' || $url === $this->lastPausedUrl) {
            return;
        }
        $this->lastPausedUrl = $url;
        fwrite(STDERR, sprintf("\nWatching %s for %ss\n", $url, $raw));
        usleep((int) round(((float) $raw) * 1000000));
    }

    /**
     * Chrome can drop the page source while a navigation is still finishing.
     */
    private function readPageSource(): string
    {
        $last = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->inner->getPageSource();
            } catch (UnrecognizedExceptionException $exception) {
                $message = $exception->getMessage();
                if (!str_contains($message, 'aborted by navigation') && !str_contains($message, 'Not attached')) {
                    throw $exception;
                }
                $last = $exception;
                usleep(200000);
            }
        }

        throw $last;
    }

    private function currentUrl(): string
    {
        try {
            return (string) $this->inner->getCurrentURL();
        } catch (\Throwable $exception) {
            return '';
        }
    }

    public function close()
    {
        $this->inner->close();

        return $this;
    }

    public function get($url)
    {
        $this->inner->get($url);

        return $this;
    }

    public function getCurrentURL()
    {
        return $this->inner->getCurrentURL();
    }

    public function getTitle()
    {
        return $this->inner->getTitle();
    }

    public function getWindowHandle()
    {
        return $this->inner->getWindowHandle();
    }

    public function getWindowHandles()
    {
        return $this->inner->getWindowHandles();
    }

    public function quit()
    {
        $this->inner->quit();
    }

    public function takeScreenshot($save_as = null)
    {
        return $this->inner->takeScreenshot($save_as);
    }

    public function wait($timeout_in_second = 30, $interval_in_millisecond = 250)
    {
        return new WebDriverWait($this, $timeout_in_second, $interval_in_millisecond);
    }

    public function manage(): WebDriverOptions
    {
        return $this->inner->manage();
    }

    public function navigate(): WebDriverNavigationInterface
    {
        return $this->inner->navigate();
    }

    public function switchTo(): WebDriverTargetLocator
    {
        return $this->inner->switchTo();
    }

    public function execute($name, $params)
    {
        return $this->inner->execute($name, $params);
    }

    public function findElement(WebDriverBy $locator): WebDriverElement
    {
        return $this->inner->findElement($locator);
    }

    public function findElements(WebDriverBy $locator)
    {
        return $this->inner->findElements($locator);
    }

    public function executeScript($script, array $arguments = [])
    {
        return $this->inner->executeScript($script, $arguments);
    }

    public function executeAsyncScript($script, array $arguments = [])
    {
        return $this->inner->executeAsyncScript($script, $arguments);
    }

    public function getCapabilities()
    {
        return $this->inner->getCapabilities();
    }

    public function getKeyboard()
    {
        return $this->inner->getKeyboard();
    }

    public function getMouse()
    {
        return $this->inner->getMouse();
    }
}
