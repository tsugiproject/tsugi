<?php

namespace Tsugi\Controllers;

use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\Request;

use Tsugi\Lumen\Application;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

class Login extends Controller {

    const ROUTE = '/login';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Login@get');
        $app->router->get($prefix.'/', 'Login@get');
    }

    public function get(Request $request)
    {
        global $CFG;

        $PDOX = LTIX::getConnection();

        error_log('Session in login.php '.session_id());

        $oauth_consumer_key = 'google.com';

        // First we make sure that there is a google.com key
        $stmt = $PDOX->queryDie(
                "SELECT key_id, secret FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :SHA LIMIT 1",
                array('SHA' => lti_sha256($oauth_consumer_key))
                );
        $key_row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( $key_row === false ) {
            die_with_error_log('Error: No key defined for accounts from google.com');
        }
        $google_key_id = $key_row['key_id']+0;
        $google_secret = $key_row['secret'];
        if ( $google_key_id < 1 ) {
            die_with_error_log('Error: No key for accounts from google.com');
        }

        $context_key = false;
        $context_id = false;
        // If there is a global course, grab it or make it
        if ( isset($CFG->context_title) ) {
            $context_key = 'course:'.md5($CFG->context_title);

            $row = $PDOX->rowDie(
                    "SELECT context_id FROM {$CFG->dbprefix}lti_context
                    WHERE context_sha256 = :SHA AND key_id = :KID LIMIT 1",
                    array(':SHA' => lti_sha256($context_key), ':KID' => $google_key_id)
                    );

            if ( $row != false ) {
                $context_id = $row['context_id'];
            } else {
                $sql = "INSERT INTO {$CFG->dbprefix}lti_context
                    ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
                    ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )";
                $PDOX->queryDie($sql, array(
                            ':context_key' => $context_key,
                            ':context_sha256' => lti_sha256($context_key),
                            ':title' => $CFG->context_title,
                            ':key_id' => $google_key_id));
                $context_id = $PDOX->lastInsertId();
            }
        }

        // Google Login Object
        $come_back = $CFG->wwwroot.'/login.php';
        if ( isset($CFG->google_login_new) && $CFG->google_login_new ) {
            $come_back = $CFG->wwwroot.'/login';
        }
        $glog = new \Tsugi\Google\GoogleLogin($CFG->google_client_id,$CFG->google_client_secret,
                $come_back,$CFG->wwwroot);

        $errormsg = false;
        $success = false;

        $doLogin = false;
        $user_key = false;
        $firstName = false;
        $lastName = false;
        $userEmail = false;

        if ( $CFG->DEVELOPER && $CFG->OFFLINE ) {
            $user_key = 'http://notgoogle.com/1234567';
            $firstName = 'Fake';
            $lastName = 'Person';
            $userEmail = 'fake_person@notgoogle.com';
            $doLogin = true;
        } else {

            if ( isset($_GET['code']) ) {
                if ( isset($_SESSION['GOOGLE_STATE']) && isset($_GET['state']) ) {
                    if ( $_SESSION['GOOGLE_STATE'] != $_GET['state'] ) {
                        $errormsg = "Missing important session data - could not log you in.  Sorry.";
                        error_log("Google Login state mismatch");
                        unset($_SESSION['GOOGLE_STATE']);
                    }
                } else {
                    $errormsg = "Missing important session data info- could not log you in.  Sorry.";
                    error_log("Error missing state");
                    unset($_SESSION['GOOGLE_STATE']);
                }

                $google_code = $_GET['code'];
                $authObj = $glog->getAccessToken($google_code);
                $user = $glog->getUserInfo();
                // echo("<pre>\nUser\n");print_r($user);echo("</pre>\n");

                $firstName = isset($user->given_name) ? $user->given_name : false;
                $lastName = isset($user->family_name) ? $user->family_name : false;
                $userEmail = isset($user->email) ? $user->email : false;
                $userAvatar = isset($user->picture) ? $user->picture : false;
                // If we can derive a gravatar URL - lets check now
                if ( $userAvatar === false && $userEmail !== false ) {
                    $gravatarurl = 'http://www.gravatar.com/avatar/';
                    $gravatarurl .= md5( strtolower( trim( $email ) ) );
                    $url = $gravatarurl . '?d=404';
                    $x =  get_headers($url);
                    if ( is_array($x) && strpos($x[0]," 200 ") > 0 ) {
                        $userAvatar = $gravatarurl;
                    }
                }
                $userHomePage = isset($user->link) ? $user->link : false;

                // flawed $user_key computation prior to 11-Jan-2016
                // Note that $user->openid_id can be false (oops)
                // Note that $user->openid_id and $user->id change over time
                // So we just trust the email from google as our user_key (in this tenant)
                // $user_key = isset($user->openid_id) ? $user->openid_id :
                // ( isset($user->id) ? $user->id : false );

                // New user_key is just based on the Google email which
                // (a) we demand and (b) is consistent over time
                $user_key = 'googlemail:'.$userEmail;

                // echo("i=$user_key f=$firstName l=$lastName e=$userEmail a=$userAvatar h=$userHomePage\n");
                $doLogin = true;
            }
        }

        if ( $doLogin ) {
            if ( $firstName === false || $lastName === false || $userEmail === false ) {
                error_log('Google-Missing:'.$user_key.','.$firstName.','.$lastName.','.$userEmail);
                $_SESSION["error"] = "You do not have a first name, last name, and email in Google or you did not share it with us.";
                header('Location: '.$CFG->apphome.'/');
                return "";
            } else {
                $userSHA = lti_sha256($user_key);
                $displayName = $firstName . ' ' . $lastName;

                // Compensating/updating old lti_user records that are broken
                // First we find the most recently added account with matching email
                // if it exists
                $stmt = $PDOX->queryDie(
                        "SELECT user_id, user_key, user_sha256, user_key FROM {$CFG->dbprefix}lti_user
                        WHERE key_id = :KEY AND email = :EM 
                        ORDER BY updated_at DESC, created_at DESC
                        LIMIT 1;",
                        array(':EM' => $userEmail, ':KEY' => $google_key_id)
                        );
                $user_row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ( $user_row !== false ) {
                    $old_user_key = $user_row['user_key'];
                    $old_user_sha = $user_row['user_sha256'];
                    $selected_user_id = $user_row['user_id'];
                    if ( $old_user_key != $user_key || $old_user_sha != $userSHA ) {
                        $stmt = $PDOX->queryDie(
                                "UPDATE {$CFG->dbprefix}lti_user
                                SET user_key=:NEWK, user_sha256=:NEWSHA
                                WHERE user_key=:OLDK AND user_sha256=:OLDSHA AND key_id = :KEY",
                                array(':NEWK' => $user_key, ':NEWSHA' => $userSHA,
                                    ':OLDK' => $old_user_key, ':OLDSHA' => $old_user_sha,
                                    ':KEY' => $google_key_id)
                                );
                        $stmt = $PDOX->queryDie(
                                "UPDATE {$CFG->dbprefix}profile
                                SET profile_sha256=:NEWSHA, profile_key=:NEWK
                                WHERE profile_sha256=:OLDSHA AND key_id = :KEY",
                                array(':NEWK' => $user_key, ':NEWSHA' => $userSHA,
                                    ':KEY' => $google_key_id, ':OLDSHA' => $old_user_sha)
                                );
                        error_log("User record adjusted old_key=$old_user_key, old_sha=$old_user_sha, new_key=$user_key, new_sha=$userSHA");
                    }
                }

                // Load the profile checking to see if everything matches
                $stmt = $PDOX->queryDie(
                        "SELECT P.profile_id AS profile_id, P.displayname AS displayname,
                        P.email as email, U.user_id as user_id
                        FROM {$CFG->dbprefix}profile AS P
                        LEFT JOIN {$CFG->dbprefix}lti_user AS U
                        ON P.profile_id = U.profile_id AND P.email = U.email AND
                        P.displayname = U.displayname AND user_sha256 = profile_sha256 AND
                        P.key_id = U.key_id
                        WHERE profile_sha256 = :SHA AND P.key_id = :ID LIMIT 1",
                        array('SHA' => $userSHA, ":ID" => $google_key_id)
                        );
                $profile_row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $profile_id = 0;
                $user_id = 0;

                // Make sure we have a profile for this person
                if ( $profile_row === false ) {
                    $stmt = $PDOX->queryDie(
                            "INSERT INTO {$CFG->dbprefix}profile
                            (profile_sha256, profile_key, key_id, email, displayname, created_at, updated_at, login_at) ".
                            "VALUES ( :SHA, :UKEY, :KEY, :EMAIL, :DN, NOW(), NOW(), NOW() )",
                            array('SHA' => $userSHA, ':UKEY' => $user_key, ':KEY' => $google_key_id,
                                ':EMAIL' => $userEmail, ':DN' => $displayName)
                            );

                    if ( $stmt->success) $profile_id = $PDOX->lastInsertId();

                    error_log('Profile-Insert:'.$user_key.','.$displayName.','.$userEmail.','.$profile_id);
                } else {
                    $profile_id = $profile_row['profile_id']+0;
                    // Check to see if everything is already fine
                    if ( $profile_row['email'] == $userEmail && $profile_row['displayname']  ) {
                        $user_id = $profile_row['user_id']+0;
                    }
                    $stmt = $PDOX->queryDie(
                            "UPDATE {$CFG->dbprefix}profile
                            SET email = :EMAIL, displayname = :DN, login_at = NOW()
                            WHERE profile_id = :PRID",
                            array('PRID' => $profile_id,
                                ':EMAIL' => $userEmail, ':DN' => $displayName)
                            );
                }

                // Must have profile...
                if ( $profile_id < 1 ) {
                    error_log('Fail-SQL-Profile:'.$user_key.','.$displayName.','.$userEmail.','.$stmt->errorImplode);
                    $_SESSION["error"] = "Internal database error, sorry";
                    header('Location: '.$CFG->apphome.'/');
                    return "";
                }

                // Load user...
                if ( $user_id < 1 ) {
                    $stmt = $PDOX->queryDie(
                            "SELECT user_id FROM {$CFG->dbprefix}lti_user
                            WHERE user_sha256 = :SHA AND key_id = :ID LIMIT 1",
                            array('SHA' => $userSHA, ":ID" => $google_key_id)
                            );
                    $user_row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $user_id = 0;
                }

                // Insert / update the user
                $didinsert = false;
                if ( $user_id > 0 ) {
                    $stmt = $PDOX->queryDie(
                            "UPDATE {$CFG->dbprefix}lti_user
                            SET displayname=:DN, login_at=NOW(), ipaddr=:IP
                            WHERE user_id=:ID",
                            array(':DN' => $displayName,':IP' => Net::getIP(), 
                                ':ID' => $user_id)
                            );
                } else if ( $user_row === false ) { // Lets insert!
                    $stmt = $PDOX->queryReturnError(
                            "INSERT INTO {$CFG->dbprefix}lti_user
                            (user_sha256, user_key, key_id, profile_id,
                             email, displayname, created_at, updated_at, login_at, ipaddr) ".
                            "VALUES ( :SHA, :UKEY, :KEY, :PROF, :EMAIL, :DN, NOW(), NOW(), NOW(), :IP )",
                            array('SHA' => $userSHA, ':UKEY' => $user_key, ':KEY' => $google_key_id,
                                ':PROF' => $profile_id, ':EMAIL' => $userEmail, ':DN' => $displayName,
                                ':IP' => Net::getIP())
                            );

                    if ( $stmt->success ) {
                        $user_id = $PDOX->lastInsertId();
                        error_log('User-Insert:'.$user_key.','.$displayName.','.$userEmail.','.$user_id);
                        $didinsert = true;
                    }
                } else {  // Lets update!
                    $user_id = $user_row['user_id']+0;
                    $stmt = $PDOX->queryDie(
                            "UPDATE {$CFG->dbprefix}lti_user
                            SET email=:EMAIL, displayname=:DN, profile_id = :PRID, login_at=NOW(), ipaddr=:IP
                            WHERE user_id=:ID",
                            array(':EMAIL' => $userEmail, ':DN' => $displayName,':IP' => Net::getIP(), 
                                ':ID' => $user_id, ':PRID' => $profile_id)
                            );
                    error_log('User-Update:'.$user_key.','.$displayName.','.$userEmail);
                }

                if ( $user_id < 1 ) {
                    error_log('No User Entry:'.$user_key.','.$displayName.','.$userEmail);
                    $_SESSION["error"] = "Internal database error, sorry";
                    header('Location: '.$CFG->apphome.'/');
                    return "";
                }

                // Add a membership record if needed
                if ( $context_id !== false ) {
                    $sql = "INSERT IGNORE INTO {$CFG->dbprefix}lti_membership
                        ( context_id, user_id, role, created_at, updated_at ) VALUES
                        ( :context_id, :user_id, :role, NOW(), NOW() )";
                    $PDOX->queryDie($sql, array(
                                ':context_id' => $context_id,
                                ':user_id' => $user_id,
                                ':role' => 0));
                }

                // We made a user and made a displayname
                // Set up the session and fake an LTI launch
                $welcome = "Welcome ";
                if ( ! $didinsert ) $welcome .= "back ";
                $_SESSION["success"] = $welcome.($displayName)." (".$userEmail.")";

                // Also set up a fake LTI launch
                $lti = array();
                $lti['key_id'] = $google_key_id;

                $_SESSION["oauth_consumer_key"] = $oauth_consumer_key;
                $lti['key_key'] = $oauth_consumer_key;

                if ( strlen($google_secret) ) {
                    $_SESSION['secret'] = LTIX::encrypt_secret($google_secret);
                    $lti['secret'] = LTIX::encrypt_secret($google_secret);
                } else {
                    unset($_SESSION['secret']);
                }

                $_SESSION["id"] = $user_id;
                $lti["user_id"] = $user_id;

                $_SESSION["user_id"] = $user_id;
                $lti["user_id"] = $user_id;

                $_SESSION["user_key"] = $user_key;
                $lti["user_key"] = $user_key;

                $_SESSION["email"] = $userEmail;
                $lti["user_email"] = $userEmail;

                $_SESSION["displayname"] = $displayName;
                $lti["user_displayname"] = $displayName;

                $_SESSION["profile_id"] = $profile_id;
                $lti["profile_id"] = $profile_id;

                if ( isset($userAvatar) ) {
                    $_SESSION["avatar"] = $userAvatar;
                    $lti["user_image"] = $userAvatar;
                }

                if ( isset($CFG->context_title) ) {
                    $_SESSION['context_title'] = $CFG->context_title;
                    $lti['context_title'] = $CFG->context_title;
                    $lti['resource_title'] = $CFG->context_title;
                }
                if ( isset($context_id) ) {
                    $_SESSION["context_id"] = $context_id;
                    $lti["context_id"] = $context_id;
                }
                if ( isset($context_key) ) {
                    $_SESSION["context_key"] = $context_key;
                    $lti["context_key"] = $context_key;
                }

                // Set that data in the session.
                $_SESSION['lti'] = $lti;

                // Set the secure cookie
                SecureCookie::set($user_id,$userEmail,$context_id);

                if ( isset($_SESSION['login_return']) ) {
                    header('Location: '.$_SESSION['login_return']);
                    unset($_SESSION['login_return']);
                } else if ( $didinsert ) {
                    header('Location: '.$CFG->wwwroot.'/profile.php');
                } else {
                    header('Location: '.$CFG->apphome.'/');
                }
                return "";
            }
        }

        // We need a login URL
        $_SESSION['GOOGLE_STATE'] = md5(uniqid(rand(), TRUE));
        $loginUrl = $glog->getLoginUrl($_SESSION['GOOGLE_STATE']);

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
