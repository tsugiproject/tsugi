<?php

use Tsugi\Core\LTIX;

if ( ! isset($CFG) ) {
    require_once __DIR__ . '/../config.php';
}

$rest = isset($TSUGI_REST_PATH_VALUES) ? $TSUGI_REST_PATH_VALUES : false;
$keyId = 0;
if ( is_string($rest) && preg_match('/^([0-9]+)\.json$/', $rest, $match) ) {
    $keyId = (int) $match[1];
}
if ( $keyId < 1 ) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Not found\n";
    return;
}

$PDOX = LTIX::getConnection();
$row = $PDOX->rowDie(
    "SELECT key_id, key_title
     FROM {$CFG->dbprefix}lti_key
     WHERE key_id = :KID AND (deleted IS NULL OR deleted = 0)",
    array(':KID' => $keyId)
);
if ( ! is_array($row) || (int) ($row['key_id'] ?? 0) < 1 ) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Not found\n";
    return;
}

$title = is_string($row['key_title']) ? trim($row['key_title']) : '';
if ( $title === '' ) {
    $title = $CFG->servicename . ' Plus';
}
$launch = $CFG->wwwroot . '/plus';
// Canvas registers the top-level target_link_uri as the OIDC redirect.
// The placement target is the course the instructor opens.
$redirect = $CFG->wwwroot . '/lti/oidc_launch';
$icon = $CFG->staticroot . '/img/logos/tsugi-logo-square.png';
$host = parse_url($CFG->wwwroot, PHP_URL_HOST);

$doc = array(
    'title' => $title,
    'description' => $title,
    'oidc_initiation_url' => $CFG->wwwroot . '/lti/oidc_login/' . $keyId,
    'target_link_uri' => $redirect,
    'redirect_uris' => $redirect,
    'oidc_redirect_uris' => $redirect,
    'public_jwk_url' => $CFG->wwwroot . '/lti/keyset',
    'scopes' => array(
        'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
        'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
        'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
        'https://purl.imsglobal.org/spec/lti-ags/scope/score',
        'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly',
    ),
    'extensions' => array(
        array(
            'platform' => 'canvas.instructure.com',
            'domain' => is_string($host) ? $host : '',
            'tool_id' => md5($CFG->wwwroot . '/plus/' . $keyId),
            'privacy_level' => 'public',
            'settings' => array(
                'text' => $title,
                'icon_url' => $icon,
                'placements' => array(
                    array(
                        'text' => $title,
                        'enabled' => true,
                        'icon_url' => $icon,
                        'placement' => 'course_navigation',
                        'message_type' => 'LtiResourceLinkRequest',
                        'target_link_uri' => $launch,
                        'windowTarget' => '_blank',
                    ),
                ),
            ),
        ),
    ),
);

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
