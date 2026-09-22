<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Util\U;
use \Tsugi\UI\DemoLogin;
use \Tsugi\UI\GoogleLoginHandler;
use Tsugi\Lumen\Application;

class Login extends Tool {

    const ROUTE = '/login';

    /**
     * URL of the login page for redirects and links.
     * Uses apphome/login when apphome is set, otherwise wwwroot/login.
     */
    public static function loginUrl() {
        global $CFG;
        $base = (isset($CFG->apphome) && is_string($CFG->apphome) && $CFG->apphome)
            ? $CFG->apphome
            : $CFG->wwwroot;
        return rtrim($base, '/') . self::ROUTE;
    }

    /**
     * Google OAuth redirect_uri after authentication.
     * Honors google_login_redirect, then loginUrl() when google_login_new, else login.php.
     */
    public static function oauthRedirectUri() {
        global $CFG;
        if ( isset($CFG->google_login_redirect) && $CFG->google_login_redirect ) {
            return $CFG->google_login_redirect;
        }
        if ( isset($CFG->google_login_new) && $CFG->google_login_new ) {
            return self::loginUrl();
        }
        return rtrim($CFG->wwwroot, '/') . '/login.php';
    }

    /**
     * Remember where to send the user after login.
     */
    public static function setReturnUrl($url) {
        $_SESSION['login_return'] = $url;
    }

    /**
     * Saved post-login URL without consuming it.
     */
    public static function peekReturnUrl() {
        $url = U::get($_SESSION, 'login_return');
        return ( is_string($url) && $url !== '' ) ? $url : null;
    }

    /**
     * Saved post-login URL, cleared from the session.
     */
    public static function takeReturnUrl() {
        $url = self::peekReturnUrl();
        if ( $url ) {
            unset($_SESSION['login_return']);
        }
        return $url;
    }

    /**
     * Default site home when no saved return URL exists.
     */
    public static function defaultHomeUrl() {
        global $CFG;
        if ( isset($CFG->apphome) && is_string($CFG->apphome) && $CFG->apphome ) {
            return $CFG->apphome;
        }
        return $CFG->wwwroot;
    }

    /**
     * Fixed post-login URL from $CFG->login_return_url when configured.
     */
    public static function configuredReturnUrl() {
        global $CFG;
        if ( isset($CFG->login_return_url) && is_string($CFG->login_return_url) && $CFG->login_return_url ) {
            return $CFG->login_return_url;
        }
        return null;
    }

    /**
     * Cancel target on the login form: saved return or site home.
     */
    public static function cancelUrl() {
        return self::peekReturnUrl() ?? self::defaultHomeUrl();
    }

    /**
     * Post-login redirect URL after Google authentication.
     *
     * @param object $result GoogleLoginHandler result (needs did_insert)
     * @param string|null $newUserUrl first-time user destination
     * @param bool $fallbackHome when false, return null instead of home for legacy callers
     */
    public static function returnAfterLogin($result, $newUserUrl = null, $fallbackHome = true) {
        $url = self::takeReturnUrl();
        if ( $url ) {
            return $url;
        }
        if ( $result->did_insert ) {
            if ( $newUserUrl ) {
                return $newUserUrl;
            }
            $configured = self::configuredReturnUrl();
            if ( $configured ) {
                return $configured;
            }
            return $fallbackHome ? self::defaultHomeUrl() : null;
        }
        return $fallbackHome ? self::defaultHomeUrl() : null;
    }

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/simulate', 'Login@simulate');
        $app->router->post($prefix.'/simulate', 'Login@simulate');
        $app->router->get($prefix.'/simulate/', 'Login@simulate');
        $app->router->post($prefix.'/simulate/', 'Login@simulate');
        $app->router->get($prefix, 'Login@get');
        $app->router->get($prefix.'/', 'Login@get');
        // Legacy Google / bookmark URLs still hit login.php after the script was removed.
        $app->router->get($prefix.'.php', 'Login@get');
    }

    public function get(Request $request)
    {
        global $CFG;

        $come_back = self::oauthRedirectUri();

        // Process login with redirect callback
        // Capture parent path before closure so we can use it inside
        $parentPath = $this->toolParent(self::ROUTE);
        $result = GoogleLoginHandler::processLogin($come_back, function($result) use ($parentPath) {
            return self::returnAfterLogin($result, $parentPath . '/profile');
        });

        // Handle errors
        if ( $result->error ) {
            error_log('Login.get() error: '.$result->error.' session_id='.session_id());
            U::flashError($result->error);
            return new RedirectResponse(self::defaultHomeUrl());
        }

        // Handle successful login redirect
        if ( $result->success && $result->redirect_url ) {
            error_log('Login.get() successful redirect to: '.$result->redirect_url.' session_id='.session_id());
            return new RedirectResponse($result->redirect_url);
        }

        // Display login form
        $loginUrl = $result->login_url ? $result->login_url : GoogleLoginHandler::getLoginUrl($come_back);

        $context = array();
        $context['login_return'] = self::cancelUrl();
        $context['loginUrl'] = $loginUrl;

        return $this->viewLogin($context);
    }

    public function viewLogin($context)
    {
        global $OUTPUT, $CFG;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
?>
<main class="container" id="main-content">
<h1><?= __('Login') ?></h1>
<div style="margin: 30px">
<p>
We here at <?= htmlspecialchars($CFG->servicename) ?> use Google Accounts as our sole login.
We do not want to spend a lot of time verifying identity, resetting passwords,
detecting robot-login storms, and other issues so we let Google do that hard work.
</p>
<form method="post">
    <button type="button" class="btn btn-warning" onclick="location.href=<?= htmlspecialchars(json_encode($context['login_return'])) ?>; return false;" aria-label="<?= htmlspecialchars(__('Cancel login')) ?>" style="height: 2.5em;">Cancel</button>
    <a href="<?= htmlspecialchars($context['loginUrl']) ?>" aria-label="<?= htmlspecialchars(__('Sign in with Google')) ?>"><img src="<?= htmlspecialchars($CFG->staticroot) ?>/img/google_signin_buttons/2x/btn_google_signin_dark_normal_web@2x.png" alt="<?= htmlspecialchars(__('Sign in with Google')) ?>" title="<?= htmlspecialchars(__('Sign in with Google')) ?>" style="height: 3em;"></a>
</form>
<?php if ( DemoLogin::isEnabled() ) { ?>
<p style="margin-top: 1.5em;">
<a href="<?= htmlspecialchars(DemoLogin::simulateUrl()) ?>"><?= htmlspecialchars(__('Demo login')) ?></a>
</p>
<?php } ?>
<p>
So you must have a Google account and we will require your
name and email address to login.  We do not need and do not receive your password - only Google
will ask you for your password.  When you press login, you will be directed to the Google
authentication system where you will be given the option to share your
information with <?= htmlspecialchars($CFG->servicename) ?>.
</p>
</div>
</main>
<?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
    }

    /**
     * Config-gated simulated Google login. Always 403 unless demo_login + demo_secret.
     */
    public function simulate()
    {
        if ( ! DemoLogin::isEnabled() ) {
            return new Response('Forbidden', 403);
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ( $method === 'POST' ) {
            return $this->simulatePost();
        }

        return $this->viewSimulate(array(
            'personas' => DemoLogin::personas(),
            'selected' => '',
        ));
    }

    /**
     * @return Response|RedirectResponse
     */
    private function simulatePost()
    {
        $personas = DemoLogin::personas();
        $persona_id = U::get($_POST, 'persona', '');
        $selected = is_string($persona_id) ? $persona_id : '';

        if ( ! self::csrfOk() ) {
            U::flashError(__('Missing or invalid CSRF token'));
            return $this->viewSimulate(array('personas' => $personas, 'selected' => $selected));
        }

        $secret = U::get($_POST, 'secret', '');
        $persona = DemoLogin::persona($selected);
        if ( ! DemoLogin::secretMatches(is_string($secret) ? $secret : '') || $persona === null ) {
            U::flashError(__('Could not log you in.'));
            return $this->viewSimulate(array('personas' => $personas, 'selected' => $selected));
        }

        $displayName = $persona['firstName'].' '.$persona['lastName'];
        $parentPath = $this->toolParent(self::ROUTE);
        $result = GoogleLoginHandler::establishGoogleSiteSession(
            $persona['user_key'],
            $persona['email'],
            $displayName,
            false,
            function($result) use ($parentPath) {
                return self::returnAfterLogin($result, $parentPath . '/profile');
            },
            DemoLogin::sessionOptions($persona)
        );

        if ( $result->error ) {
            error_log('Login.simulate() error: '.$result->error);
            U::flashError($result->error);
            return $this->viewSimulate(array('personas' => $personas, 'selected' => $selected));
        }

        if ( $result->success ) {
            session_regenerate_id(true);
            $url = $result->redirect_url ? $result->redirect_url : self::defaultHomeUrl();
            error_log('Login.simulate() '.$persona['id'].' user_id='.$result->user_id);
            return new RedirectResponse($url);
        }

        U::flashError(__('Could not log you in.'));
        return $this->viewSimulate(array('personas' => $personas, 'selected' => $selected));
    }

    public function viewSimulate($context)
    {
        global $OUTPUT;

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $selected = $context['selected'] ?? '';
?>
<main class="container" id="main-content">
<h1><?= __('Demo login') ?></h1>
<div style="margin: 30px">
<p>
<?= htmlspecialchars(__('This page simulates a Google site login for local testing. It does not contact Google.')) ?>
</p>
<form method="post" action="<?= htmlspecialchars(DemoLogin::simulateUrl()) ?>">
<?= self::csrfField() ?>
<p>
<label for="persona"><?= htmlspecialchars(__('Persona')) ?></label><br>
<select id="persona" name="persona" required>
<?php foreach ( $context['personas'] as $persona ) {
    $sel = ($persona['id'] === $selected) ? ' selected' : '';
?>
    <option value="<?= htmlspecialchars($persona['id']) ?>"<?= $sel ?>><?= htmlspecialchars($persona['label']) ?></option>
<?php } ?>
</select>
</p>
<p>
<label for="secret"><?= htmlspecialchars(__('Secret')) ?></label><br>
<input type="password" id="secret" name="secret" required autocomplete="off">
</p>
<p>
<button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Log in')) ?></button>
<a class="btn btn-warning" href="<?= htmlspecialchars(self::loginUrl()) ?>"><?= htmlspecialchars(__('Cancel')) ?></a>
</p>
</form>
</div>
</main>
<?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
    }

}
