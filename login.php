<?php
define('COOKIE_SESSION', true);
require_once "config.php";
require_once "db.php";
require_once 'lib/lms_lib.php';
require_once 'lib/lightopenid/openid.php';

session_start();

// First we make sure that there is a google.com key
$stmt = pdoQueryDie($db,
    "SELECT key_id FROM {$CFG->dbprefix}lti_key 
        WHERE key_sha256 = :SHA LIMIT 1",
    array('SHA' => lti_sha256('google.com'))
);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    die('Error: No key defined for accounts from google.com');
}
$google_key_id = $row['key_id']+0;
if ( $google_key_id < 1 ) {
    die('Error: No key for accounts from google.com');
}

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
    try {
        $openid = new LightOpenID($CFG->wwwroot);
        if(!$openid->mode) {
            if(isset($_GET['login'])) {
                $openid->identity = 'https://www.google.com/accounts/o8/id';
                $openid->required = array('contact/email', 'namePerson/first', 'namePerson/last');
                $openid->optional = array('namePerson/friendly');
                header('Location: ' . $openid->authUrl());
                return;
            }
        } else {
            if($openid->mode == 'cancel') {
                $errormsg = "You have canceled authentication. That's OK but we cannot log you in.  Sorry.";
                error_log('Google-Cancel');
            } else if ( ! $openid->validate() ) {
                $errormsg = 'You were not logged in by Google.  It may be due to a technical problem.';
                error_log('Google-Fail');
            } else {
                $identity = $openid->identity;
                $userAttributes = $openid->getAttributes();
                // echo("\n<pre>\n");print_r($userAttributes);echo("\n</pre>\n");
                $firstName = isset($userAttributes['namePerson/first']) ? $userAttributes['namePerson/first'] : false;
                $lastName = isset($userAttributes['namePerson/last']) ? $userAttributes['namePerson/last'] : false;
                $userEmail = isset($userAttributes['contact/email']) ? $userAttributes['contact/email'] : false;
                $doLogin = true;
            }
        }
    } catch(ErrorException $e) {
        $errormsg = $e->getMessage();
    }
}

if ( $doLogin ) {
    if ( $firstName === false || $lastName === false || $userEmail === false ) {
        error_log('Google-Missing:'.$identity.','.$firstName.','.$lastName.','.$userEmail);
        $_SESSION["error"] = "You do not have a first name, last name, and email in Google or you did not share it with us.";
        header('Location: index.php');
        return;
    } else {
        $user_sha = lti_sha256($identity);
        $displayName = $firstName . ' ' . $lastName;
        $stmt = pdoQueryDie($db,
            "SELECT user_id, profile_id, displayname, email
                 FROM {$CFG->dbprefix}lti_user 
                WHERE user_sha256 = :SHA AND key_id = :ID LIMIT 1",
            array('SHA' => $user_sha, ":ID" => $google_key_id)
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $theid = false;
        $didinsert = false;
        if ( $row !== false ) { // Lets update!
            $theid = $row['user_id'];
            if ( $row['email'] != $userEmail || $row['displayname'] != $displayName ) {
                $stmt = pdoQuery($db,
                    "UPDATE {$CFG->dbprefix}lti_user
                     SET email=:EMAIL, displayname=:DN, modified_at=NOW(), login_at=NOW()
                     WHERE user_id=:ID",
                    array(':EMAIL' => $userEmail, ':DN' => $displayName, ':ID' => $theid)
                );
            } else { 
                $stmt = pdoQuery($db,
                    "UPDATE {$CFG->dbprefix}lti_user SET login_at=NOW() WHERE id=:ID",
                    array(':ID' => $theid)
                );
            }
            if ( $stmt-> success ) {
                error_log('User-Update:'.$identity.','.$firstName.','.$lastName.','.$userEmail);
            } else {
                error_log('Fail-SQL:'.$identity.','.$firstName.','.$lastName.','.$userEmail.','.mysql_error().','.$sql);
                $_SESSION["error"] = "Internal database error, sorry";
                header('Location: index.php');
                return;
            }
            $stmt = pdoQuery($db,
                "INSERT INTO {$CFG->dbprefix}lti_user  
                (user_sha256, key_id, email, displayname, created_at, modified_at, login_at) ".
                    "VALUES ( :SHA, :KEY, :EMAIL, :DN, NOW(), NOW(), NOW() )",
                 array('SHA' => $userSHA, ':KEY' => $google_key_id,
                    ':EMAIL' => $userEmail, ':DN' => $displayName)
            );

            if ( ! $stmt->success ) {
                $theid = mysql_insert_id();
                error_log('User-Insert:'.$identity.','.$displayName.','.$userEmail.','.$theid);
                $didinsert = true;
            } else {
                error_log('Fail-SQL:'.$identity.','.$dispayName.','.$userEmail.','.mysql_error().','.$sql);
                $_SESSION["error"] = "Internal database error, sorry";
                header('Location: index.php');
                return;
            }
        }

        $welcome = "Welcome ";
        if ( ! $didinsert ) $welcome .= "back ";
        $_SESSION["success"] = $welcome.($displayName)." (".$userEmail.")";
        $_SESSION["id"] = $theid;
        $_SESSION["email"] = $userEmail;
        $_SESSION["displayname"] = $displayName;
        // Set the secure cookie
        $guid = MD5($identity);
        $ct = create_secure_cookie($theid,$guid);
        setcookie($CFG->cookiename,$ct,time() + (86400 * 45)); // 86400 = 1 day

        if ( $didinsert ) {
            header('Location: profile.php');
        } else {
            header('Location: index.php');
        }
        return;
    }
}
headerContent();
startBody();
?>
<?php
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
<form action="?login" method="post">
    <input class="btn btn-warning" type="button" onclick="location.href='index.php'; return false;" value="Cancel"/>
    <button class="btn btn-primary">Login with Google</button>
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
footerContent();
