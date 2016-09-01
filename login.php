<?php
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

define('COOKIE_SESSION', true);
require_once "config.php";
require_once 'lib/lightopenid/openid.php';

$PDOX = LTIX::getConnection();

session_start();
error_log('Session in login.php '.session_id());

$oauth_consumer_key = 'google.com';

// First we make sure that there is a google.com key
$stmt = $PDOX->queryDie(
    "SELECT key_id, secret FROM {$CFG->dbprefix}lti_key
        WHERE key_sha256 = :SHA LIMIT 1",
    array('SHA' => lti_sha256($oauth_consumer_key))
);
$key_row = $stmt->fetch(PDO::FETCH_ASSOC);
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
$glog = new \Tsugi\Google\GoogleLogin($CFG->google_client_id,$CFG->google_client_secret,
      $CFG->wwwroot.'/login.php',$CFG->wwwroot);

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
        $user_key = isset($user->openid_id) ? $user->openid_id :
            ( isset($user->id) ? $user->id : false );
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
        // echo("i=$user_key f=$firstName l=$lastName e=$userEmail a=$userAvatar h=$userHomePage\n");
        $doLogin = true;
    }
}

if ( $doLogin ) {
    if ( $firstName === false || $lastName === false || $userEmail === false ) {
        error_log('Google-Missing:'.$user_key.','.$firstName.','.$lastName.','.$userEmail);
        $_SESSION["error"] = "You do not have a first name, last name, and email in Google or you did not share it with us.";
        header('Location: '.$CFG->apphome.'/index.php');
        return;
    } else {
        $userSHA = lti_sha256($user_key);
        $displayName = $firstName . ' ' . $lastName;

        // Load the profile checking to see if everything
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
        $profile_row = $stmt->fetch(PDO::FETCH_ASSOC);
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
            header('Location: '.$CFG->apphome.'/index.php');
            return;
         }

        // Load user...
        if ( $user_id < 1 ) {
            $stmt = $PDOX->queryDie(
                "SELECT user_id FROM {$CFG->dbprefix}lti_user
                WHERE user_sha256 = :SHA AND key_id = :ID LIMIT 1",
                array('SHA' => $userSHA, ":ID" => $google_key_id)
            );
            $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = 0;
        }

        // Insert / update the user
        $didinsert = false;
        if ( $user_id > 0 ) {
            // The user data is fine...
        } else if ( $user_row === false ) { // Lets insert!
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$CFG->dbprefix}lti_user
                (user_sha256, user_key, key_id, profile_id,
                    email, displayname, created_at, updated_at, login_at) ".
                "VALUES ( :SHA, :UKEY, :KEY, :PROF, :EMAIL, :DN, NOW(), NOW(), NOW() )",
                 array('SHA' => $userSHA, ':UKEY' => $user_key, ':KEY' => $google_key_id,
                    ':PROF' => $profile_id, ':EMAIL' => $userEmail, ':DN' => $displayName)
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
                 SET email=:EMAIL, displayname=:DN, profile_id = :PRID, login_at=NOW()
                 WHERE user_id=:ID",
                array(':EMAIL' => $userEmail, ':DN' => $displayName,
                    ':ID' => $user_id, ':PRID' => $profile_id)
            );
            error_log('User-Update:'.$user_key.','.$displayName.','.$userEmail);
        }

        if ( $user_id < 1 ) {
             error_log('No User Entry:'.$user_key.','.$displayName.','.$userEmail);
             $_SESSION["error"] = "Internal database error, sorry";
             header('Location: '.$CFG->apphome.'/index.php');
             return;
        }

        // Add a membership record if needed
        if ( isset($context_id) ) {
            $sql = "INSERT IGNORE INTO {$CFG->dbprefix}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :context_id, :user_id, :role, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':context_id' => $context_id,
                ':user_id' => $user_id,
                ':role' => 0));
        }

        // We made a user and made a displayname
        $welcome = "Welcome ";
        if ( ! $didinsert ) $welcome .= "back ";
        $_SESSION["success"] = $welcome.($displayName)." (".$userEmail.")";
        $_SESSION["id"] = $user_id;
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_key"] = $user_key;
        $_SESSION["email"] = $userEmail;
        $_SESSION["displayname"] = $displayName;
        $_SESSION["profile_id"] = $profile_id;
        if ( isset($userAvatar) ) $_SESSION["avatar"] = $userAvatar;
        $_SESSION["oauth_consumer_key"] = $oauth_consumer_key;
        if ( isset($CFG->context_title) ) $_SESSION['context_title'] = $CFG->context_title;
        if ( isset($context_id) ) $_SESSION["context_id"] = $context_id;
        if ( isset($context_key) ) $_SESSION["context_key"] = $context_key;
        // TODO: Encrypt Secret
        if ( strlen($google_secret) ) {
            $_SESSION["secret"] = $google_secret;
        } else {
            unset($_SESSION["secret"]);
        }

        // Set the secure cookie
        SecureCookie::set($user_id,$userSHA);

        if ( isset($_SESSION['login_return']) ) {
            header('Location: '.$_SESSION['login_return']);
            unset($_SESSION['login_return']);
        } else if ( $didinsert ) {
            header('Location: '.$CFG->wwwroot.'/profile.php');
        } else {
            header('Location: '.$CFG->apphome.'/index.php');
        }
        return;
    }
}
// We need a login URL
$_SESSION['GOOGLE_STATE'] = md5(uniqid(rand(), TRUE));
$loginUrl = $glog->getLoginUrl($_SESSION['GOOGLE_STATE']);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<?php
$login_return = 'index.php';
if ( isset($_SESSION['login_return']) ) $login_return = $_SESSION['login_return'];
if ( $errormsg !== false ) {
    echo('<div style="margin-top: 10px;" class="alert alert-error">');
    echo($errormsg);
    echo("</div>\n");
}
if ( $success !== false ) {
    echo('<div style="margin-top: 10px;" class="alert alert-success">');
    echo($success);
    echo("</div>\n");
}
?>
<div style="margin: 30px">
<p>
We here at <?php echo($CFG->servicename); ?> use Google Accounts as our sole login.
We do not want to spend a lot of time verifying identity, resetting passwords,
detecting robot-login storms, and other issues so we let Google do that hard work.
</p>
<form method="post">
    <input class="btn btn-warning" type="button" onclick="location.href='<?php echo($login_return); ?>'; return false;" value="Cancel"/>
    <input class="btn btn-primary" type="button" onclick="location.href='<?= $loginUrl ?>'; return false;" value="Login with Google" />
</form>
<p>
So you must have a Google account and we will require your
name and email address to login.  We do not need and do not receive your password - only Google
will ask you for your password.  When you press login, you will be directed to the Google
authentication system where you will be given the option to share your
information with <?php echo($CFG->servicename); ?>.
</p>
</div>
<?php
$OUTPUT->footer();
