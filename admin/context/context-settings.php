<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

session_start();

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

if ( ! isset($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "No context_id provided";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$context_id = $_REQUEST['context_id'];

// Handle POST - save settings (must be done BEFORE any output)
if ( isset($_POST['settings_json']) ) {
    $settings_json = trim($_POST['settings_json']);
    
    // Validate JSON if not empty
    if ( !empty($settings_json) ) {
        $decoded = json_decode($settings_json, true);
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $_SESSION['error'] = "Invalid JSON: " . json_last_error_msg();
        } else {
            // Save compact JSON (no formatting)
            $compact_json = json_encode($decoded);
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_context 
                SET settings = :SETTINGS, updated_at = NOW() 
                WHERE context_id = :CID",
                array(
                    ':SETTINGS' => $compact_json,
                    ':CID' => $context_id
                )
            );
            
            if ( $stmt->success ) {
                $_SESSION['success'] = "Context settings updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update settings";
            }
        }
    } else {
        // Empty JSON - clear settings
        $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_context 
            SET settings = NULL, updated_at = NOW() 
            WHERE context_id = :CID",
            array(':CID' => $context_id)
        );
        
        if ( $stmt->success ) {
            $_SESSION['success'] = "Context settings cleared successfully";
        } else {
            $_SESSION['error'] = "Failed to clear settings";
        }
    }
    // Always redirect after POST to avoid unhandled POST error
    header("Location: ".U::addSession("context-settings.php?context_id=".$context_id));
    return;
}

// Load the context (only after POST is handled)
$context_row = $PDOX->rowDie("SELECT context_id, title, settings FROM {$CFG->dbprefix}lti_context
    WHERE context_id = :CID",
    array(':CID' => $context_id)
);

if ( $context_row === false ) {
    $_SESSION['error'] = "Context not found";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$context_title = $context_row['title'] ? $context_row['title'] : "Context ID: $context_id";
$current_settings = $context_row['settings'];

header('Content-Type: text/html; charset=utf-8');

// Format JSON for display (pretty print)
$settings_display = '';
if ( !empty($current_settings) ) {
    $decoded = json_decode($current_settings, true);
    if ( $decoded !== null ) {
        $settings_display = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $settings_display = $current_settings;
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Context Settings</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">View Contexts</a>
  <a href="membership?context_id=<?= htmlentities($context_id) ?>" class="btn btn-default">View Memberships</a>
</p>

<h2><?= htmlentities($context_title) ?></h2>

<form method="post">
    <div class="form-group">
        <label for="settings_json">Context Settings (JSON)</label>
        <textarea class="form-control" id="settings_json" name="settings_json" rows="20" 
            style="font-family: monospace; font-size: 12px;"><?= htmlentities($settings_display) ?></textarea>
        <small class="form-text text-muted">
            Enter valid JSON data. Leave empty to clear all settings. The settings are stored in the 
            <code>settings</code> column of the <code>lti_context</code> table.
        </small>
    </div>
    
    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Cancel</a>
</form>

<?php
$OUTPUT->footer();

