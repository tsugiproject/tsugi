<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

if ( ! isset($_REQUEST['key_id']) ) {
    $_SESSION['error'] = "No key_id provided";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$key_id = $_REQUEST['key_id'];

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
            $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_key 
                SET settings = :SETTINGS, updated_at = NOW() 
                WHERE key_id = :KID",
                array(
                    ':SETTINGS' => $compact_json,
                    ':KID' => $key_id
                )
            );
            
            if ( $stmt->success ) {
                $_SESSION['success'] = "Key settings updated successfully";
            } else {
                $_SESSION['error'] = "Failed to update settings";
            }
        }
    } else {
        // Empty JSON - clear settings
        $stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_key 
            SET settings = NULL, updated_at = NOW() 
            WHERE key_id = :KID",
            array(':KID' => $key_id)
        );
        
        if ( $stmt->success ) {
            $_SESSION['success'] = "Key settings cleared successfully";
        } else {
            $_SESSION['error'] = "Failed to clear settings";
        }
    }
    // Always redirect after POST to avoid unhandled POST error
    header("Location: ".U::addSession("key-settings.php?key_id=".$key_id));
    return;
}

// Load the key (only after POST is handled)
$key_row = $PDOX->rowDie("SELECT key_id, key_title, settings FROM {$CFG->dbprefix}lti_key
    WHERE key_id = :KID",
    array(':KID' => $key_id)
);

if ( $key_row === false ) {
    $_SESSION['error'] = "Key not found";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$key_title = $key_row['key_title'] ? $key_row['key_title'] : "Key ID: $key_id";
$current_settings = $key_row['settings'];

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
<h1>Key Settings</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">View Keys</a>
  <a href="key-detail.php?key_id=<?= htmlentities($key_id) ?>" class="btn btn-default">View Key Details</a>
</p>

<h2><?= htmlentities($key_title) ?></h2>

<form method="post">
    <div class="form-group">
        <label for="settings_json">Key Settings (JSON)</label>
        <textarea class="form-control" id="settings_json" name="settings_json" rows="20" 
            style="font-family: monospace; font-size: 12px;"><?= htmlentities($settings_display) ?></textarea>
        <small class="form-text text-muted">
            Enter valid JSON data. Leave empty to clear all settings. The settings are stored in the 
            <code>settings</code> column of the <code>lti_key</code> table.
        </small>
    </div>
    
    <button type="submit" class="btn btn-primary">Save Settings</button>
    <a href="key-detail.php?key_id=<?= htmlentities($key_id) ?>" class="btn btn-default">Cancel</a>
</form>

<?php
$OUTPUT->footer();

