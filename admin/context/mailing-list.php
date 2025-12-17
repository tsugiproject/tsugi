<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "No context_id provided";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

if ( ! is_numeric($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "Invalid context_id";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$context_id = $_REQUEST['context_id'] + 0;

// Handle form submission - POST-Redirect-GET pattern
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['days']) ) {
    $days = $_POST['days'] + 0;
    if ( !is_numeric($_POST['days']) || $days < 1 || $days > 365 ) {
        $_SESSION['error'] = "Days must be between 1 and 365";
        header('Location: mailing-list.php?context_id='.$context_id);
        return;
    }
    $include_opted_out = isset($_POST['include_opted_out']) ? 1 : 0;
    // Redirect to GET to avoid resubmission
    header('Location: mailing-list.php?context_id='.$context_id.'&days='.$days.'&include_opted_out='.$include_opted_out);
    return;
}

// Handle GET parameters
$days = null;
$include_opted_out = false;
if ( isset($_REQUEST['days']) && is_numeric($_REQUEST['days']) ) {
    $days = $_REQUEST['days'] + 0;
    if ( $days < 1 || $days > 365 ) {
        $_SESSION['error'] = "Days must be between 1 and 365";
        $days = null;
    }
}
if ( isset($_REQUEST['include_opted_out']) && $_REQUEST['include_opted_out'] == '1' ) {
    $include_opted_out = true;
}

// Check if user is site admin OR instructor/admin for this context
$is_context_admin = false;
if ( isAdmin() ) {
    $is_context_admin = true;
} else if ( U::get($_SESSION, 'id') ) {
    // Check if user is instructor/admin for this context
    $membership = $PDOX->rowDie(
        "SELECT role FROM {$CFG->dbprefix}lti_membership 
         WHERE context_id = :CID AND user_id = :UID",
        array(':CID' => $context_id, ':UID' => $_SESSION['id'])
    );
    if ( $membership && isset($membership['role']) ) {
        $role = $membership['role'] + 0;
        // ROLE_INSTRUCTOR = 1000, ROLE_ADMINISTRATOR = 5000
        if ( $role >= LTIX::ROLE_INSTRUCTOR ) {
            $is_context_admin = true;
        }
    }
    // Also check if user owns the context or its key
    if ( ! $is_context_admin ) {
        $context_check = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (
                 key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                 OR user_id = :UID
             )",
            array(':CID' => $context_id, ':UID' => $_SESSION['id'])
        );
        if ( $context_check ) {
            $is_context_admin = true;
        }
    }
}

if ( ! $is_context_admin ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

// Get context title
$context_row = $PDOX->rowDie(
    "SELECT title FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
    array(':CID' => $context_id)
);
$context_title = $context_row ? $context_row['title'] : "Context #$context_id";

// Query for users only if days is provided
$rows = array();
if ( $days !== null ) {
    // Query for users who:
    // 1. Are members of this context
    // 2. Logged in within the specified number of days
    // 3. Have not opted out (subscribe != -1 in either lti_user or profile)
    // 4. Have an email address
    // Note: We calculate the cutoff date in PHP since MySQL INTERVAL doesn't support parameters
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

    $sql = "SELECT DISTINCT U.email, U.displayname, U.login_at, U.user_id
            FROM {$CFG->dbprefix}lti_membership AS M
            JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
            LEFT JOIN {$CFG->dbprefix}profile AS P ON U.profile_id = P.profile_id
            WHERE M.context_id = :CID
              AND U.email IS NOT NULL
              AND U.email != ''
              AND U.login_at IS NOT NULL
              AND U.login_at >= :CUTOFF";
    
    // Only filter out opted-out users if checkbox is not checked
    if ( !$include_opted_out ) {
        $sql .= " AND (U.subscribe IS NULL OR U.subscribe != -1)
                  AND (P.subscribe IS NULL OR P.subscribe != -1)";
    }
    
    $sql .= " ORDER BY SUBSTRING_INDEX(U.email, '@', -1), U.email";

    $rows = $PDOX->allRowsDie($sql, array(':CID' => $context_id, ':CUTOFF' => $cutoff_date));
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>

<h2>Mailing List for: <?= htmlentities($context_title) ?></h2>
<p>
  <a href="membership?context_id=<?= htmlentities($context_id) ?>" class="btn btn-default">Back to Membership</a>
</p>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">Generate Mailing List</h3>
  </div>
  <div class="panel-body">
    <p>Generate a mailing list of users who have logged in within a specified number of days.</p>
    <form method="post" action="mailing-list.php">
      <input type="hidden" name="context_id" value="<?= htmlentities($context_id) ?>">
      <div class="form-group" style="margin-bottom: 15px;">
        <label for="days">Users who logged in within the last:</label>
        <input type="number" class="form-control" id="days" name="days" value="<?= htmlentities($days !== null ? $days : 30) ?>" min="1" max="365" style="width: 80px; margin: 0 10px; display: inline-block;">
        <label for="days">days</label>
      </div>
      <div class="form-group" style="margin-bottom: 15px;">
        <label>
          <input type="checkbox" name="include_opted_out" value="1" <?= $include_opted_out ? 'checked' : '' ?>>
          Include all users including those that have opted out of all email
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Generate Mailing List</button>
    </form>
  </div>
</div>

<?php if ( $days !== null ): ?>
  <p>Users who logged in within the last <?= htmlentities($days) ?> days<?= $include_opted_out ? '' : ' (excluding users who opted out of email)' ?></p>
  
  <?php if ( count($rows) == 0 ): ?>
  <div class="alert alert-info">
    <p>No users found matching the criteria.</p>
  </div>
<?php else: 
  // Build emails array once
  $emails = array();
  foreach ( $rows as $row ) {
      if ( !empty($row['email']) ) {
          $emails[] = trim($row['email']);
      }
  }
?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Comma-separated list (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('comma-list-body', this)" style="margin-left: 10px;">Hide</button>
    </div>
    <div class="panel-body" id="comma-list-body">
      <textarea class="form-control" rows="5" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode(', ', $emails));
      ?></textarea>
    </div>
  </div>
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Semicolon-separated list (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('semicolon-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="semicolon-list-body" style="display: none;">
      <textarea class="form-control" rows="5" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode('; ', $emails));
      ?></textarea>
    </div>
  </div>
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">One per line (<?= count($rows) ?> total)</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('line-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="line-list-body" style="display: none;">
      <textarea class="form-control" rows="10" readonly style="font-family: monospace;"><?php
        echo htmlentities(implode("\n", $emails));
      ?></textarea>
    </div>
  </div>
  
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title" style="display: inline-block;">Detailed List</h3>
      <button type="button" class="btn btn-sm btn-default" onclick="toggleSection('detailed-list-body', this)" style="margin-left: 10px;">Show</button>
    </div>
    <div class="panel-body" id="detailed-list-body" style="display: none;">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Email</th>
            <th>Display Name</th>
            <th>Last Login</th>
            <th>Days Since Login</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $now = new DateTime();
          foreach ( $rows as $row ): 
            $days_since_login = 'N/A';
            if ( $row['login_at'] ) {
              try {
                $login_date = new DateTime($row['login_at']);
                $diff = $now->diff($login_date);
                $days_since_login = $diff->days;
              } catch (Exception $e) {
                $days_since_login = 'N/A';
              }
            }
          ?>
            <tr>
              <td><?= htmlentities($row['email']) ?></td>
              <td><?= htmlentities($row['displayname'] ? $row['displayname'] : 'N/A') ?></td>
              <td><?= htmlentities($row['login_at'] ? $row['login_at'] : 'Never') ?></td>
              <td><?= htmlentities($days_since_login) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php endif; ?>

<script>
function toggleSection(sectionId, button) {
    var section = document.getElementById(sectionId);
    if (section.style.display === 'none' || section.style.display === '') {
        section.style.display = 'block';
        button.textContent = 'Hide';
    } else {
        section.style.display = 'none';
        button.textContent = 'Show';
    }
}
</script>

<?php
$OUTPUT->footer();
?>
