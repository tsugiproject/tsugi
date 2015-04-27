<?php
define('COOKIE_SESSION', true);
require_once "config.php";
require_once "pdo.php";
require_once 'lib/lms_lib.php';
require_once 'lib/lightopenid/openid.php';

session_start();
error_log('Session in login.php '.session_id());

// First we make sure that there is a google.com key
$stmt = $PDOX->queryDie(
    "SELECT key_id FROM {$CFG->dbprefix}lti_key
        WHERE key_sha256 = :SHA LIMIT 1",
    array('SHA' => lti_sha256('google.com'))
);
$key_row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $key_row === false ) {
    die_with_error_log('Error: No key defined for accounts from google.com');
}
$google_key_id = $key_row['key_id']+0;
if ( $google_key_id < 1 ) {
    die_with_error_log('Error: No key for accounts from google.com');
}

// Google Login Object
$glog = new \Tsugi\Google\GoogleLogin($CFG->google_client_id,$CFG->google_client_secret,
      $CFG->wwwroot.'/login.php',$CFG->wwwroot);

$errormsg = false;
$success = false;

$doLogin = false;
$identity = false;
$firstName = false;
$lastName = false;
$userEmail = false;

if ( $CFG->DEVELOPER && $CFG->OFFLINE ) {
    $identity = 'http://notgoogle.com/1234567';
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
        $identity = isset($user->openid_id) ? $user->openid_id :
            ( isset($user->id) ? $user->id : false );
        $firstName = isset($user->given_name) ? $user->given_name : false;
        $lastName = isset($user->family_name) ? $user->family_name : false;
        $userEmail = isset($user->email) ? $user->email : false;
        $userAvatar = isset($user->picture) ? $user->picture : false;
        $userHomePage = isset($user->link) ? $user->link : false;
        // echo("i=$identity f=$firstName l=$lastName e=$userEmail a=$userAvatar h=$userHomePage\n");
        $doLogin = true;
    }
}

if ( $doLogin ) {
    if ( $firstName === false || $lastName === false || $userEmail === false ) {
        error_log('Google-Missing:'.$identity.','.$firstName.','.$lastName.','.$userEmail);
        $_SESSION["error"] = "You do not have a first name, last name, and email in Google or you did not share it with us.";
        header('Location: index.php');
        return;
    } else {
        $userSHA = lti_sha256($identity);
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
                 array('SHA' => $userSHA, ':UKEY' => $identity, ':KEY' => $google_key_id,
                    ':EMAIL' => $userEmail, ':DN' => $displayName)
            );

            if ( $stmt->success) $profile_id = $PDOX->lastInsertId();

            error_log('Profile-Insert:'.$identity.','.$displayName.','.$userEmail.','.$profile_id);
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
            error_log('Fail-SQL-Profile:'.$identity.','.$displayName.','.$userEmail.','.$stmt->errorImplode);
            $_SESSION["error"] = "Internal database error, sorry";
            header('Location: index.php');
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
                 array('SHA' => $userSHA, ':UKEY' => $identity, ':KEY' => $google_key_id,
                    ':PROF' => $profile_id, ':EMAIL' => $userEmail, ':DN' => $displayName)
            );

            if ( $stmt->success ) {
                $user_id = $PDOX->lastInsertId();
                error_log('User-Insert:'.$identity.','.$displayName.','.$userEmail.','.$user_id);
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
            error_log('User-Update:'.$identity.','.$displayName.','.$userEmail);
        }

        if ( $user_id < 1 ) {
             error_log('No User Entry:'.$identity.','.$displayName.','.$userEmail);
             $_SESSION["error"] = "Internal database error, sorry";
             header('Location: index.php');
             return;
         }

        // We made a user and made a displayname
        $welcome = "Welcome ";
        if ( ! $didinsert ) $welcome .= "back ";
        $_SESSION["success"] = $welcome.($displayName)." (".$userEmail.")";
        $_SESSION["id"] = $user_id;
        $_SESSION["email"] = $userEmail;
        $_SESSION["displayname"] = $displayName;
        $_SESSION["profile_id"] = $profile_id;

        // Set the secure cookie
        setSecureCookie($user_id,$userSHA);

        if ( isset($_SESSION['login_return']) ) {
            header('Location: '.$_SESSION['login_return']);
            unset($_SESSION['login_return']);
        } else if ( $didinsert ) {
            header('Location: profile.php');
        } else {
            header('Location: index.php');
        }
        return;
    }
}
// We need a login URL
$_SESSION['GOOGLE_STATE'] = md5(uniqid(rand(), TRUE));
$loginUrl = $glog->getLoginUrl($_SESSION['GOOGLE_STATE']);

$OUTPUT->header();
$OUTPUT->bodyStart();
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
if ( $CFG->DEVELOPER ) {
    echo '<div class="alert alert-danger" style="margin-top: 10px;">'.
        'Note: Currently this server is running in developer mode.'.
        "\n</div>\n";
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
