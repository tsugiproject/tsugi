<?php

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Shared demo-login and course-create steps for Panther tests.
 */
trait DemoCourseSteps
{
    private string $lastWatchedUrl = '';

    /**
     * @return array{home: string, title: string}
     */
    protected function startInstructorCourse(\Symfony\Component\Panther\Client $client): array
    {
        $secret = getenv('TSUGI_DEMO_SECRET');
        $this->assertNotFalse($secret, 'TSUGI_DEMO_SECRET must be set.');
        $this->assertNotSame('', $secret, 'TSUGI_DEMO_SECRET must not be empty.');

        $adminPw = getenv('TSUGI_ADMIN_PW');
        $this->assertNotFalse($adminPw, 'TSUGI_ADMIN_PW must be set.');
        $this->assertNotSame('', $adminPw, 'TSUGI_ADMIN_PW must not be empty.');

        $driver = $client->getWebDriver();
        $this->loginInstructor($client, $secret);
        $this->saveProfileIfShown($client);
        $this->grantCreateCourses($client, $adminPw);

        $driver->get($this->uri('logout'));
        $this->loginInstructor($client, $secret);

        $title = 'Panther Demo ' . date('YmdHis');
        $driver->get($this->uri('courses/create'));
        $this->waitForPageText($client, 'Add course');
        $driver->findElement(WebDriverBy::id('course_title'))->sendKeys($title);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] button[type="submit"]'))->click();

        $this->waitForPageText($client, 'Course created.');
        $this->waitForPageText($client, $title);

        return [
            'home' => $this->courseHomeFromUrl($driver->getCurrentURL()),
            'title' => $title,
        ];
    }

    protected function loginInstructor(\Symfony\Component\Panther\Client $client, string $secret): void
    {
        $driver = $client->getWebDriver();
        $simulate = $this->uri('login/simulate');
        $driver->get($simulate);
        $this->waitForPageText($client, 'Demo login');

        $driver->executeScript(
            'document.querySelector("form[method=\'post\']").setAttribute("action", arguments[0]);',
            [$simulate]
        );
        $this->submitDemoPersona($client, $secret, 'instructor-01', 'Instructor 01');
    }

    protected function loginStudent(\Symfony\Component\Panther\Client $client, string $secret): void
    {
        $driver = $client->getWebDriver();
        $simulate = $this->uri('login/simulate');
        $driver->get($simulate);
        $this->waitForPageText($client, 'Demo login');
        $driver->executeScript(
            'document.querySelector("form[method=\'post\']").setAttribute("action", arguments[0]);',
            [$simulate]
        );
        $this->submitDemoPersona($client, $secret, 'student-01', 'Student 01');
    }

    private function submitDemoPersona(\Symfony\Component\Panther\Client $client, string $secret, string $personaId, string $displayName): void
    {
        $driver = $client->getWebDriver();
        $persona = new WebDriverSelect($driver->findElement(WebDriverBy::id('persona')));
        $persona->selectByValue($personaId);
        $driver->findElement(WebDriverBy::id('secret'))->sendKeys($secret);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] button[type="submit"]'))->click();
        $deadline = microtime(true) + 15;
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (!str_contains($page, 'name="persona"') && str_contains($page, $displayName)) {
                return;
            }
            usleep(200000);
        }
        $this->fail('Demo login did not leave the persona form.');
    }

    protected function saveProfileIfShown(\Symfony\Component\Panther\Client $client): void
    {
        $driver = $client->getWebDriver();
        $page = $client->getPageSource();
        if (!str_contains($page, 'How much mail would you like us to send?')) {
            $href = $driver->executeScript(
                'var a = document.querySelector("a[href*=\'/profile\']"); return a ? a.href : "";'
            );
            if ($href === '') {
                return;
            }
            $driver->get($href);
            $this->waitForPageText($client, 'How much mail would you like us to send?');
        }

        $driver->executeScript(
            'document.querySelectorAll("button").forEach(function (b) { if ((b.innerText || "").trim() === "OK") { b.click(); } });'
        );
        $driver->findElement(WebDriverBy::cssSelector('button.btn-primary'))->click();
        $this->waitForPageText($client, 'Profile updated.');
    }

    protected function grantCreateCourses(\Symfony\Component\Panther\Client $client, string $adminPw): void
    {
        $driver = $client->getWebDriver();
        $driver->get($this->uri('admin/'));
        $this->waitForPageText($client, 'Admin Unlock');
        $driver->findElement(WebDriverBy::name('passphrase'))->sendKeys($adminPw);
        $driver->findElement(WebDriverBy::cssSelector('form[method="post"] input[type="submit"]'))->click();
        $this->waitForPageText($client, 'Administration Console');

        $driver->get($this->uri('admin/users/?search_text=' . rawurlencode('instructor01@notgoogle.com')));
        $this->waitForPageText($client, 'instructor01@notgoogle.com');
        $driver->findElement(WebDriverBy::cssSelector('tbody a'))->click();
        $this->waitForPageText($client, 'Create courses (0 or 1)');

        $driver->findElement(WebDriverBy::linkText('Edit'))->click();
        $this->waitForPageText($client, 'name="create_courses"');
        $field = $driver->findElement(WebDriverBy::id('create_courses'));
        $field->clear();
        $field->sendKeys('1');
        $driver->findElement(WebDriverBy::name('doUpdate'))->click();
        $this->waitForPageText($client, 'instructor01@notgoogle.com');
    }

    protected function courseHomeFromUrl(string $url): string
    {
        if (!preg_match('#^(https?://.+?/courses/\d+)#', $url, $match)) {
            $this->fail('Course home URL was not found after create. URL: '.$url);
        }

        return $match[1];
    }

    protected function waitForPageText(\Symfony\Component\Panther\Client $client, string $expected, int $timeoutSeconds = 15): void
    {
        $deadline = microtime(true) + $timeoutSeconds;
        $page = '';
        while (microtime(true) < $deadline) {
            $page = $client->getPageSource();
            if (str_contains($page, $expected)) {
                $this->pauseAfterPageChange($client);
                return;
            }
            usleep(200000);
        }

        $this->captureScreenshot($client, 'debug-demo-course');
        $excerpt = substr(preg_replace('/\s+/u', ' ', $page) ?? $page, 0, 400);
        $this->fail("Timed out waiting for '{$expected}'. Page excerpt: {$excerpt}");
    }

    protected function pauseAfterPageChange(\Symfony\Component\Panther\Client $client): void
    {
        $url = $client->getCurrentURL();
        if ($url === $this->lastWatchedUrl) {
            return;
        }
        $this->lastWatchedUrl = $url;
        sleep(1);
    }
}
