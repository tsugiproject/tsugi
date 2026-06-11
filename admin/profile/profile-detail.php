<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Core\Launch;
use \Tsugi\Core\Profile as UserProfile;
use \Tsugi\Controllers\Profile as ProfileController;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['profile_id']) || ! is_numeric($_REQUEST['profile_id']) ) {
    die('No profile_id');
}

$profile_id = (int) $_REQUEST['profile_id'];
if ( $profile_id < 1 ) {
    die('Bad profile_id');
}

$row = $PDOX->rowDie(
    "SELECT * FROM {$CFG->dbprefix}profile
     WHERE profile_id = :PID",
    array(':PID' => $profile_id)
);

if ( $row === false ) {
    die('Profile not found');
}

$linked_users = $PDOX->allRowsDie(
    "SELECT user_id, displayname, email, login_at, login_count, created_at
     FROM {$CFG->dbprefix}lti_user
     WHERE profile_id = :PID
     ORDER BY user_id",
    array(':PID' => $profile_id)
);

$launch = new Launch();
$userProfile = UserProfile::fromLaunchRow(array('profile_id' => $profile_id), $launch);
$premium_until = ProfileController::supporterPremiumUntil($userProfile);
$supporter_active = false;
if ( $userProfile->isPremium() ) {
    if ( ! is_string($premium_until) || strlen($premium_until) < 1 ) {
        $supporter_active = true;
    } else {
        try {
            $until = new \DateTimeImmutable($premium_until);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $supporter_active = $until > $now;
        } catch (\Exception $e) {
            $supporter_active = true;
        }
    }
}

/**
 * @param mixed $raw
 */
function admin_profile_pretty_json($raw) {
    if ( ! is_string($raw) || strlen(trim($raw)) < 1 ) {
        return '(empty)';
    }
    try {
        $decoded = json_decode($raw, true);
    } catch (\Exception $e) {
        $decoded = null;
    }
    if ( ! is_array($decoded) ) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ( ! is_string($pretty) ) {
        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($pretty, ENT_QUOTES, 'UTF-8');
}

/**
 * @param mixed $value
 */
function admin_profile_cell($value) {
    if ( $value === null || $value === '' ) {
        return '<em>(empty)</em>';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<div class="container">
<p>
<a href="index" class="btn btn-default">Back</a>
<a href="<?= htmlspecialchars($CFG->wwwroot.'/admin') ?>" class="btn btn-default">Admin</a>
</p>

<h1>Profile Detail</h1>
<p><b>Read-only.</b> Profile rows hold login identity and billing state. Edit premium data through payment flows, not this screen.</p>

<h2>Identity</h2>
<table class="table table-striped table-condensed">
<tbody>
<tr><th>profile_id</th><td><?= admin_profile_cell(U::get($row, 'profile_id')) ?></td></tr>
<tr><th>displayname</th><td><?= admin_profile_cell(U::get($row, 'displayname')) ?></td></tr>
<tr><th>email</th><td><?= admin_profile_cell(U::get($row, 'email')) ?></td></tr>
<tr><th>profile_sha256</th><td><?= admin_profile_cell(U::get($row, 'profile_sha256')) ?></td></tr>
<tr><th>profile_key</th><td><em>(redacted)</em></td></tr>
<tr><th>key_id</th><td><?= admin_profile_cell(U::get($row, 'key_id')) ?></td></tr>
<tr><th>image</th><td><?= admin_profile_cell(U::get($row, 'image')) ?></td></tr>
<tr><th>locale</th><td><?= admin_profile_cell(U::get($row, 'locale')) ?></td></tr>
<tr><th>subscribe</th><td><?= admin_profile_cell(U::get($row, 'subscribe')) ?></td></tr>
<tr><th>google_translate</th><td><?= admin_profile_cell(U::get($row, 'google_translate')) ?></td></tr>
<tr><th>deleted</th><td><?= admin_profile_cell(U::get($row, 'deleted')) ?></td></tr>
<tr><th>entity_version</th><td><?= admin_profile_cell(U::get($row, 'entity_version')) ?></td></tr>
<tr><th>login_at</th><td><?= admin_profile_cell(U::get($row, 'login_at')) ?></td></tr>
<tr><th>created_at</th><td><?= admin_profile_cell(U::get($row, 'created_at')) ?></td></tr>
<tr><th>updated_at</th><td><?= admin_profile_cell(U::get($row, 'updated_at')) ?></td></tr>
<tr><th>deleted_at</th><td><?= admin_profile_cell(U::get($row, 'deleted_at')) ?></td></tr>
</tbody>
</table>

<h2>Premium</h2>
<table class="table table-striped table-condensed">
<tbody>
<tr><th>premium (tier)</th><td><?= admin_profile_cell(U::get($row, 'premium')) ?></td></tr>
<tr><th>premium_at</th><td><?= admin_profile_cell(U::get($row, 'premium_at')) ?></td></tr>
<tr><th>premium_until (derived)</th><td><?= admin_profile_cell($premium_until) ?></td></tr>
<tr><th>supporter active (derived)</th><td><?= $supporter_active ? 'yes' : 'no' ?></td></tr>
</tbody>
</table>

<h3>premium_json</h3>
<pre style="max-height: 24em; overflow: auto;"><?= admin_profile_pretty_json(U::get($row, 'premium_json')) ?></pre>

<h2>User preferences (json)</h2>
<pre style="max-height: 24em; overflow: auto;"><?= admin_profile_pretty_json(U::get($row, 'json')) ?></pre>

<h2>Linked lti_user rows</h2>
<?php if ( count($linked_users) < 1 ) { ?>
<p><em>No linked users.</em></p>
<?php } else { ?>
<table class="table table-striped table-condensed">
<thead>
<tr>
<th>user_id</th>
<th>displayname</th>
<th>email</th>
<th>login_at</th>
<th>login_count</th>
<th>created_at</th>
</tr>
</thead>
<tbody>
<?php foreach ( $linked_users as $user ) { ?>
<tr>
<td><a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/users/user-detail?user_id='.U::get($user, 'user_id')) ?>"><?= admin_profile_cell(U::get($user, 'user_id')) ?></a></td>
<td><?= admin_profile_cell(U::get($user, 'displayname')) ?></td>
<td><?= admin_profile_cell(U::get($user, 'email')) ?></td>
<td><?= admin_profile_cell(U::get($user, 'login_at')) ?></td>
<td><?= admin_profile_cell(U::get($user, 'login_count')) ?></td>
<td><?= admin_profile_cell(U::get($user, 'created_at')) ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
</div>
<?php
$OUTPUT->footer();
