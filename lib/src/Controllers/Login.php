<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\UI\GoogleLoginHandler;
use Tsugi\Lumen\Application;

class Login extends Controller {

    const ROUTE = '/login';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Login@get');
        $app->router->get($prefix.'/', 'Login@get');
    }

    public function get(Request $request)
    {
        global $CFG;

        error_log('Session in login.php '.session_id());

        // Determine callback URL
        $come_back = $CFG->wwwroot.'/login.php';
        if ( isset($CFG->google_login_new) && $CFG->google_login_new ) {
            $come_back = $CFG->wwwroot.'/login';
        }

        // Process login with redirect callback
        $result = GoogleLoginHandler::processLogin($come_back, function($result) {
            global $CFG;
            if ( isset($_SESSION['login_return']) ) {
                $url = $_SESSION['login_return'];
                unset($_SESSION['login_return']);
                return $url;
            } else if ( $result->did_insert ) {
                return $CFG->wwwroot.'/profile.php';
            } else {
                return isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
            }
        });

        // Handle errors
        if ( $result->error ) {
            $_SESSION["error"] = $result->error;
            header('Location: '.$CFG->apphome.'/');
            return "";
        }

        // Handle successful login redirect
        if ( $result->success && $result->redirect_url ) {
            header('Location: '.$result->redirect_url);
            return "";
        }

        // Display login form
        $loginUrl = $result->login_url ? $result->login_url : GoogleLoginHandler::getLoginUrl($come_back);

        $context = array();
        $login_return = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        if ( isset($_SESSION['login_return']) ) $login_return = $_SESSION['login_return'];
        $context['login_return'] = $login_return;
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
<div style="margin: 30px">
<p>
We here at <?= $CFG->servicename ?> use Google Accounts as our sole login.
We do not want to spend a lot of time verifying identity, resetting passwords,
detecting robot-login storms, and other issues so we let Google do that hard work.
</p>
<form method="post">
    <input class="btn btn-warning" type="button"
    onclick="location.href='<?= $context['login_return'] ?>'; return false;" value="Cancel"
        style="height: 2.5em;"/>
    <a href="<?= $context['loginUrl'] ?>"><img src="<?= $CFG->staticroot ?>/img/google_signin_buttons/2x/btn_google_signin_dark_normal_web@2x.png"
      title="<?= htmlentities(__('Sign in with Google')) ?>"
      style="height: 3em;"></a>
</form>
<p>
So you must have a Google account and we will require your
name and email address to login.  We do not need and do not receive your password - only Google
will ask you for your password.  When you press login, you will be directed to the Google
authentication system where you will be given the option to share your
information with <?= $CFG->servicename ?>.
</p>
</div>
<?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
    }


}
