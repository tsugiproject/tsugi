<?php

use Tsugi\Services\Outbound\Lti13TestLaunch;

require_once __DIR__ . '/../config.php';

$source = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
if ( ! is_array($source) ) {
    $source = array();
}

try {
    $done = Lti13TestLaunch::complete($source);
} catch ( \InvalidArgumentException $e ) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    return;
} catch ( \Throwable $e ) {
    error_log('oidc_auth '.$e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Could not sign the launch.';
    return;
}

$action = htmlspecialchars($done['redirect_uri'], ENT_QUOTES, 'UTF-8');
$idToken = htmlspecialchars($done['id_token'], ENT_QUOTES, 'UTF-8');
$state = htmlspecialchars($done['state'], ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>LTI launch</title></head><body>';
echo '<form id="lti_launch" method="post" action="'.$action.'">';
echo '<input type="hidden" name="id_token" value="'.$idToken.'">';
echo '<input type="hidden" name="state" value="'.$state.'">';
echo '</form>';
echo '<script>document.getElementById("lti_launch").submit();</script>';
echo '</body></html>';
