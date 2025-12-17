<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

$path = U::rest_path();
$base_path = $path->parent; // e.g., /announce

// Generate URLs using rest_path and addSession
$json_url = addSession($base_path . '/json.php');
$dismiss_url = addSession($base_path . '/dismiss.php');
$view_url = addSession($base_path . '/index.php');
$component_js_url = addSession($base_path . '/tsugi-announce.js');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tsugi Announce Component Example</title>
</head>
<body>
    <h1>Announcements Component Example</h1>
    
    <p>Place the component anywhere:</p>
    <span style="font-size: 2em;">
        Notify
        <tsugi-announce 
            json-url="<?= htmlspecialchars($json_url) ?>" 
            dismiss-url="<?= htmlspecialchars($dismiss_url) ?>" 
            view-url="<?= htmlspecialchars($view_url) ?>">
        </tsugi-announce>
        Notify
    </span>
    
    <p>Or in a navigation bar:</p>
    <nav style="text-align: right; padding: 10px;">
        <span style="font-size: 2em;">
            Notify
            <tsugi-announce 
                json-url="<?= htmlspecialchars($json_url) ?>" 
                dismiss-url="<?= htmlspecialchars($dismiss_url) ?>" 
                view-url="<?= htmlspecialchars($view_url) ?>">
            </tsugi-announce>
            Notify
        </span>
    </nav>
    
    <p>75% size (1.5em):</p>
    <span style="font-size: 1.5em;">
        Notify
        <tsugi-announce 
            json-url="<?= htmlspecialchars($json_url) ?>" 
            dismiss-url="<?= htmlspecialchars($dismiss_url) ?>" 
            view-url="<?= htmlspecialchars($view_url) ?>">
        </tsugi-announce>
        Notify
    </span>
    
    <p>50% size (1em):</p>
    <span style="font-size: 1em;">
        Notify
        <tsugi-announce 
            json-url="<?= htmlspecialchars($json_url) ?>" 
            dismiss-url="<?= htmlspecialchars($dismiss_url) ?>" 
            view-url="<?= htmlspecialchars($view_url) ?>">
        </tsugi-announce>
        Notify
    </span>
    
    <!-- Load Lit Element from CDN -->
    <script type="module" src="<?= htmlspecialchars($component_js_url) ?>"></script>
</body>
</html>
