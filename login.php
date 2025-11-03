<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\SecureCookie;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "config.php";

function login_redirect($path=false) {
    global $CFG;
    $login_return = U::get($_SESSION, 'login_return');
    if ( $login_return ) {
        unset($_SESSION['login_return']);
        header('Location: '.$login_return);
    } else if ( isset($CFG->login_return_url) && is_string($CFG->login_return_url) ) {
        header('Location: '.$CFG->login_return_url);
    } else if ( isset($CFG->apphome) && is_string($CFG->apphome) ) {
        header('Location: '.$CFG->apphome.'/'.$path);
    } else {
        header('Location: '.$CFG->wwwroot.'/'.$path);
    }
}

/**
 * Authenticate user against Active Directory LDAP
 * Returns array with user info on success, false on failure
 */
function ldap_authenticate($username, $password) {
    global $CFG;

    // Validate LDAP configuration
    if ( !isset($CFG->ldap_host) || !$CFG->ldap_host ) {
        error_log('LDAP Error: LDAP host not configured');
        return false;
    }

    if ( !isset($CFG->ldap_basedn) || !$CFG->ldap_basedn ) {
        error_log('LDAP Error: LDAP base DN not configured');
        return false;
    }

    // Sanitize username to prevent LDAP injection
    $username = ldap_escape($username, '', LDAP_ESCAPE_FILTER);

    // Build LDAP connection string
    $ldap_url = $CFG->ldap_host;
    if ( isset($CFG->ldap_port) && $CFG->ldap_port ) {
        $ldap_url .= ':' . $CFG->ldap_port;
    }

    // Connect to LDAP server
    $ldap_conn = ldap_connect($ldap_url);

    if ( !$ldap_conn ) {
        error_log('LDAP Error: Could not connect to LDAP server: ' . $ldap_url);
        return false;
    }

    error_log('LDAP SUCCESS: Connected to LDAP server: ' . $ldap_url);

    // Set LDAP options
    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $CFG->ldap_protocol_version ?? 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // Use TLS if configured
    if ( isset($CFG->ldap_use_tls) && $CFG->ldap_use_tls ) {
        if ( !ldap_start_tls($ldap_conn) ) {
            error_log('LDAP Error: Could not start TLS');
            ldap_close($ldap_conn);
            return false;
        }
    }

    // Bind with service account if configured, otherwise try anonymous bind
    if ( isset($CFG->ldap_bind_dn) && $CFG->ldap_bind_dn ) {
        $bind_result = @ldap_bind($ldap_conn, $CFG->ldap_bind_dn, $CFG->ldap_bind_password);
    } else {
        $bind_result = @ldap_bind($ldap_conn);
    }

    if ( !$bind_result ) {
        error_log('LDAP Error: Could not bind to LDAP server: ' . ldap_error($ldap_conn));
        ldap_close($ldap_conn);
        return false;
    }

    error_log('LDAP SUCCESS: Successfully bound to LDAP server');

    // Build search filter
    $search_filter = $CFG->ldap_search_filter ?? '(&(objectClass=user)(sAMAccountName={USERNAME}))';
    $search_filter = str_replace('{USERNAME}', $username, $search_filter);

    // Search for user
    $search_result = @ldap_search($ldap_conn, $CFG->ldap_basedn, $search_filter);

    if ( !$search_result ) {
        error_log('LDAP Error: Search failed: ' . ldap_error($ldap_conn));
        ldap_close($ldap_conn);
        return false;
    }

    $entries = ldap_get_entries($ldap_conn, $search_result);

    if ( $entries['count'] == 0 ) {
        error_log('LDAP Error: User not found: ' . $username);
        ldap_close($ldap_conn);
        return false;
    }

    if ( $entries['count'] > 1 ) {
        error_log('LDAP Error: Multiple users found for: ' . $username);
        ldap_close($ldap_conn);
        return false;
    }

    error_log('LDAP SUCCESS: Found user in directory: ' . $username);

    // Get user DN
    $user_dn = $entries[0]['dn'];
    error_log('LDAP SUCCESS: User DN: ' . $user_dn);

    // Try to bind as the user to verify password
    $user_bind = @ldap_bind($ldap_conn, $user_dn, $password);

    if ( !$user_bind ) {
        error_log('LDAP Error: Invalid credentials for user: ' . $username);
        ldap_close($ldap_conn);
        return false;
    }

    error_log('LDAP SUCCESS: User authenticated successfully: ' . $username);

    // Extract user attributes
    $user_entry = $entries[0];

    $firstName = false;
    $lastName = false;
    $userEmail = false;
    $displayName = false;

    // Get attribute names from config or use defaults
    $attr_firstname = $CFG->ldap_attr_firstname ?? 'givenName';
    $attr_lastname = $CFG->ldap_attr_lastname ?? 'sn';
    $attr_email = $CFG->ldap_attr_email ?? 'mail';
    $attr_displayname = $CFG->ldap_attr_displayname ?? 'displayName';

    // Extract attributes (LDAP attributes are case-insensitive, stored lowercase in array)
    if ( isset($user_entry[strtolower($attr_firstname)][0]) ) {
        $firstName = $user_entry[strtolower($attr_firstname)][0];
    }

    if ( isset($user_entry[strtolower($attr_lastname)][0]) ) {
        $lastName = $user_entry[strtolower($attr_lastname)][0];
    }

    if ( isset($user_entry[strtolower($attr_email)][0]) ) {
        $userEmail = $user_entry[strtolower($attr_email)][0];
    }

    if ( isset($user_entry[strtolower($attr_displayname)][0]) ) {
        $displayName = $user_entry[strtolower($attr_displayname)][0];
    }

    ldap_close($ldap_conn);

    // Return user information
    return array(
        'username' => $username,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $userEmail,
        'displayName' => $displayName
    );
}

$PDOX = LTIX::getConnection();

session_start();
// Only regenerate session ID on GET requests, not POST (to preserve CSRF token)
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    session_regenerate_id(true);
}
error_log('Session in login '.session_id());

// Use LDAP domain as the consumer key
$oauth_consumer_key = 'ldap.local';
if ( isset($CFG->ldap_basedn) && $CFG->ldap_basedn ) {
    // Extract domain from base DN (e.g., DC=example,DC=com -> example.com)
    $base_parts = explode(',', $CFG->ldap_basedn);
    $domain_parts = array();
    foreach ( $base_parts as $part ) {
        $part = trim($part);
        if ( stripos($part, 'DC=') === 0 ) {
            $domain_parts[] = substr($part, 3);
        }
    }
    if ( count($domain_parts) > 0 ) {
        $oauth_consumer_key = implode('.', $domain_parts);
    }
}

// First we make sure that there is a key for LDAP authentication
$stmt = $PDOX->queryDie(
    "SELECT key_id, secret FROM {$CFG->dbprefix}lti_key
        WHERE key_sha256 = :SHA LIMIT 1",
    array('SHA' => lti_sha256($oauth_consumer_key))
);
$key_row = $stmt->fetch(PDO::FETCH_ASSOC);

// Auto-create the key if it doesn't exist
if ( $key_row === false ) {
    error_log('LDAP key not found for domain: ' . $oauth_consumer_key . ', creating automatically');

    // Generate a random secret for the key
    $ldap_secret = bin2hex(random_bytes(32));

    $sql = "INSERT INTO {$CFG->dbprefix}lti_key
            (key_sha256, key_key, secret, created_at, updated_at)
            VALUES (:SHA, :KEY, :SECRET, NOW(), NOW())";

    $PDOX->queryDie($sql, array(
        ':SHA' => lti_sha256($oauth_consumer_key),
        ':KEY' => $oauth_consumer_key,
        ':SECRET' => $ldap_secret
    ));

    $ldap_key_id = $PDOX->lastInsertId();
    error_log('Created LDAP key with key_id: ' . $ldap_key_id . ' for domain: ' . $oauth_consumer_key);
} else {
    $ldap_key_id = $key_row['key_id']+0;
    $ldap_secret = $key_row['secret'];
}

if ( $ldap_key_id < 1 ) {
    die_with_error_log('Error: Failed to create or retrieve key for LDAP authentication domain: ' . $oauth_consumer_key);
}

$context_key = false;
$context_id = false;
// If there is a global course, grab it or make it
if ( isset($CFG->context_title) ) {
    $context_key = 'course:'.md5($CFG->context_title);

    $row = $PDOX->rowDie(
        "SELECT context_id FROM {$CFG->dbprefix}lti_context
            WHERE context_sha256 = :SHA AND key_id = :KID LIMIT 1",
        array(':SHA' => lti_sha256($context_key), ':KID' => $ldap_key_id)
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
                ':key_id' => $ldap_key_id));
        $context_id = $PDOX->lastInsertId();
    }
}

$errormsg = false;
$success = false;

$doLogin = false;
$user_key = false;
$firstName = false;
$lastName = false;
$userEmail = false;
$userName = false;

// Handle developer offline mode
if ( $CFG->DEVELOPER && $CFG->OFFLINE ) {
    $user_key = 'ldap:fake_person';
    $firstName = 'Fake';
    $lastName = 'Person';
    $userEmail = 'fake_person@example.com';
    $userName = 'fake_person';
    $doLogin = true;
} else {
    // Check if LDAP is configured
    if ( ! isset($CFG->ldap_host) || ! $CFG->ldap_host ) {
        echo("<p>"._m('LDAP authentication is not configured. Please set LDAP configuration in config.php')."</p>\n");
        if ( strpos($CFG->wwwroot, '//localhost') !== false ) {
            echo("<p>"._m('There is no need to log in to do local administration or local development')."</p>\n");
        }
        die();
    }

    // Handle login form submission
    if ( isset($_POST['username']) && isset($_POST['password']) ) {
        // Verify CSRF token
        if ( !isset($_POST['session_id']) || $_POST['session_id'] != session_id() ) {
            $errormsg = "Session error - please try logging in again.";
            error_log("LDAP Login CSRF check failed");
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if ( empty($username) || empty($password) ) {
                $errormsg = "Please enter both username and password.";
            } else {
                // Authenticate against LDAP
                error_log('LDAP authentication attempt for user: ' . $username);
                error_log('LDAP config - host: ' . (isset($CFG->ldap_host) ? $CFG->ldap_host : 'NOT SET'));
                error_log('LDAP config - basedn: ' . (isset($CFG->ldap_basedn) ? $CFG->ldap_basedn : 'NOT SET'));

                $ldap_user = ldap_authenticate($username, $password);

                if ( $ldap_user === false ) {
                    $errormsg = "Invalid username or password. Check error logs for details.";
                    error_log('LDAP Login failed for user: ' . $username);
                } else {
                    // Successful authentication
                    $userName = $ldap_user['username'];
                    $firstName = $ldap_user['firstName'];
                    $lastName = $ldap_user['lastName'];
                    $userEmail = $ldap_user['email'];

                    // Create user key based on username and domain
                    $user_key = 'ldap:' . $oauth_consumer_key . ':' . $userName;

                    $doLogin = true;
                }
            }
        }
    }
}

if ( $doLogin ) {
    if ( $firstName === false || $lastName === false || $userEmail === false ) {
        error_log('LDAP-Missing:'.$user_key.','.$firstName.','.$lastName.','.$userEmail);
        $_SESSION["error"] = "Could not retrieve your first name, last name, or email from LDAP directory.";
        login_redirect();
        return;
    } else {

        $userSHA = lti_sha256($user_key);
        $displayName = $firstName . ' ' . $lastName;

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
            array('SHA' => $userSHA, ":ID" => $ldap_key_id)
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
                 array('SHA' => $userSHA, ':UKEY' => $user_key, ':KEY' => $ldap_key_id,
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
            login_redirect();
            return;
         }

        // Load user...
        if ( $user_id < 1 ) {
            $stmt = $PDOX->queryDie(
                "SELECT user_id FROM {$CFG->dbprefix}lti_user
                WHERE user_sha256 = :SHA AND key_id = :ID LIMIT 1",
                array('SHA' => $userSHA, ":ID" => $ldap_key_id)
            );
            $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = 0;
        }

        // Insert / update the user
        $didinsert = false;
        if ( $user_id > 0 ) {
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_user
                 SET displayname=:DN, email=:EMAIL, login_at=NOW(), ipaddr=:IP
                 WHERE user_id=:ID",
                array(':DN' => $displayName,':IP' => Net::getIP(),
                    ':ID' => $user_id, ':EMAIL' => $userEmail)
            );
            error_log('User-Update:'.$user_key.','.$displayName.','.$userEmail);
        } else if ( $user_row === false ) { // Lets insert!
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$CFG->dbprefix}lti_user
                (user_sha256, user_key, key_id, profile_id,
                    email, displayname, created_at, updated_at, login_at, ipaddr) ".
                "VALUES ( :SHA, :UKEY, :KEY, :PROF, :EMAIL, :DN, NOW(), NOW(), NOW(), :IP )",
                 array('SHA' => $userSHA, ':UKEY' => $user_key, ':KEY' => $ldap_key_id,
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
            login_redirect();
            return;
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
        $lti['key_id'] = $ldap_key_id;

        $_SESSION["oauth_consumer_key"] = $oauth_consumer_key;
        $lti['key_key'] = $oauth_consumer_key;

        if ( is_string($ldap_secret) && strlen($ldap_secret) > 1 ) {
            $_SESSION['secret'] = LTIX::encrypt_secret($ldap_secret);
            $lti['secret'] = LTIX::encrypt_secret($ldap_secret);
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
        $lti["email"] = $userEmail;

        $_SESSION["displayname"] = $displayName;
        $lti["displayname"] = $displayName;

        $_SESSION["profile_id"] = $profile_id;
        $lti["profile_id"] = $profile_id;

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

        LTIX::noteLoggedIn($lti);

        // Set the secure cookie
        SecureCookie::set($user_id,$userEmail,$context_id);

        if ( $didinsert ) {
            login_redirect('profile');
        } else {
            login_redirect();
        }
        return;
    }
}

// Display login form
$OUTPUT->header();
$OUTPUT->bodyStart(false);  // false = POST already handled
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<?php
$login_return = 'index';
if ( isset($_SESSION['login_return']) ) $login_return = $_SESSION['login_return'];
if ( $errormsg !== false ) {
    echo('<div style="margin-top: 10px;" class="alert alert-error">');
    echo(htmlentities($errormsg));
    echo("</div>\n");
}
if ( $success !== false ) {
    echo('<div style="margin-top: 10px;" class="alert alert-success">');
    echo(htmlentities($success));
    echo("</div>\n");
}
?>
<div style="margin: 30px">
<h2><?= htmlentities(__('Sign in to ' . $CFG->servicename)) ?></h2>
<p>
<?= _m('Please enter your Active Directory credentials to log in.') ?>
</p>
<form method="post" style="max-width: 400px;">
    <input type="hidden" name="session_id" value="<?= htmlentities(session_id()) ?>">

    <div class="form-group">
        <label for="username"><?= htmlentities(__('Username')) ?>:</label>
        <input type="text" class="form-control" id="username" name="username"
               placeholder="<?= htmlentities(__('Enter your username')) ?>"
               required autofocus autocomplete="username">
    </div>

    <div class="form-group">
        <label for="password"><?= htmlentities(__('Password')) ?>:</label>
        <input type="password" class="form-control" id="password" name="password"
               placeholder="<?= htmlentities(__('Enter your password')) ?>"
               required autocomplete="current-password">
    </div>

    <div class="form-group" style="margin-top: 15px;">
        <button type="submit" class="btn btn-primary"><?= htmlentities(__('Sign In')) ?></button>
        <input class="btn btn-warning" type="button"
               onclick="location.href='<?= htmlentities($login_return) ?>'; return false;"
               value="<?= htmlentities(__('Cancel')) ?>"/>
    </div>
</form>
<p style="margin-top: 20px; color: #666; font-size: 0.9em;">
<?= _m('Your credentials are authenticated against the Active Directory server. Your password is not stored by this application.') ?>
</p>
</div>
<?php
$OUTPUT->footer();
