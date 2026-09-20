<?php

/**
 * Dedicated Settings cartridge upload (thick .imscc files).
 *
 * This folder is a real executed PHP script so it can have its own
 * post_max_size / upload_max_filesize. Do not route it through tsugi.php.
 * POST here from Settings → Import only — not the legacy /cc/export path.
 */

use Tsugi\Controllers\Settings;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\RedirectResponse;

define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../../../../config.php';

LTIX::session_start();

$settings = new Settings();
if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
    $to = $settings->cartridgeImportReturnUrl();
    header('Location: '.$to);
    exit;
}

$response = $settings->handleCartridgeUploadPost();
if ( $response instanceof RedirectResponse ) {
    header('Location: '.$response->getTargetUrl(), true, $response->getStatusCode());
    exit;
}

$fallback = U::addSession(Settings::settingsUrl('import'));
header('Location: '.$fallback);
exit;
