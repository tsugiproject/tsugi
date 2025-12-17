<?php

define('COOKIE_SESSION', true);
require_once('../config.php');
require_once('assertion-util.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;

// Parse the URL to extract encrypted ID and resource type
$url = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($url, PHP_URL_PATH), '/');
$path_parts = explode('/', $path);

// Find 'assertions' in the path and get everything after it
$assertions_index = array_search('assertions', $path_parts);
if ($assertions_index !== false) {
    // Get everything after 'assertions'
    $path_parts = array_slice($path_parts, $assertions_index + 1);
}

if (empty($path_parts)) {
    http_response_code(404);
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    echo("<h2>Page not found.</h2>\n");
    $OUTPUT->footer();
    return;
}

$first_part = $path_parts[0];

// Check if it ends with .json or .vc.json
if (strpos($first_part, '.vc.json') !== false) {
    // /assertions/{encrypted-id}.vc.json - OB3/VC Assertion (explicit)
    $encrypted = urldecode(str_replace('.vc.json', '', $first_part));
    $resource = 'assert-vc';
} elseif (strpos($first_part, '.json') !== false) {
    // /assertions/{encrypted-id}.json - Check Accept header for OB2 vs OB3
    $encrypted = urldecode(str_replace('.json', '', $first_part));
    
    // Check Accept header for OB3/VC request
    $accept_header = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    if (stripos($accept_header, 'application/vc+json') !== false || 
        stripos($accept_header, 'application/vc+ld+json') !== false) {
        $resource = 'assert-vc';
    } else {
        // Default to OB2 if Accept header missing or doesn't request OB3
        $resource = 'assert';
    }
} elseif (count($path_parts) === 1) {
    // /assertions/{encrypted-id} or /assertions/{encrypted-id}.html
    // Check Accept header to determine format
    $encrypted = urldecode($first_part);
    
    // Remove .html if present
    if (strpos($encrypted, '.html') !== false) {
        $encrypted = str_replace('.html', '', $encrypted);
    }
    
    // Check Accept header for content negotiation
    $accept_header = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    
    if (stripos($accept_header, 'application/vc+json') !== false || 
        stripos($accept_header, 'application/vc+ld+json') !== false) {
        // Requesting OB3/VC
        $resource = 'assert-vc';
    } elseif (stripos($accept_header, 'application/json') !== false) {
        // Requesting OB2 JSON
        $resource = 'assert';
    } else {
        // Default to HTML if no Accept header or Accept header requests HTML
        $resource = 'index';
    }
} elseif (count($path_parts) === 2) {
    // /assertions/{encrypted-id}/{resource}.json
    $encrypted = urldecode($first_part);
    $resource_file = $path_parts[1];
    
    if ($resource_file === 'badge.json') {
        $resource = 'badge';
    } elseif ($resource_file === 'issuer.json') {
        $resource = 'issuer';
    } elseif ($resource_file === 'achievement.json') {
        $resource = 'achievement';
    } else {
        http_response_code(404);
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        echo("<h2>Page not found.</h2>\n");
        $OUTPUT->footer();
        return;
    }
} else {
    http_response_code(404);
    $OUTPUT->header();
    $OUTPUT->bodyStart();
    $OUTPUT->topNav();
    echo("<h2>Page not found.</h2>\n");
    $OUTPUT->footer();
    return;
}

// Load lesson and parse assertion
if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

$PDOX = LTIX::getConnection();
$l = new Lessons($CFG->lessons);

$x = parse_assertion_id($encrypted, $l);
if ( is_string($x) ) {
    error_log("Assertion parse error: " . $x . " for encrypted: " . substr($encrypted, 0, 50) . "...");
    die_with_error_log($x);
}
$row = $x[0];
$pieces = $x[2];
$badge = $x[3];

$date = U::iso8601($row['login_at']);
$email = $row['email'];
$title = $row['title'];
$code = $pieces[1];

// Route to appropriate handler
switch ($resource) {
    case 'assert':
        // OB2 Assertion
        $text = get_ob2_assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'assert-vc':
        // OB3/VC Assertion
        $text = get_ob3_assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/vc+json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'badge':
        // OB2 BadgeClass
        $text = get_ob2_badge($encrypted, $code, $badge, $title);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'issuer':
        // OB2/OB3 Issuer (default to OB2, can use ?format=ob3)
        $format = isset($_GET['format']) ? $_GET['format'] : 'ob2';
        if ($format === 'ob3') {
            $text = get_ob3_issuer($encrypted, $code, $badge, $title);
        } else {
            $text = get_ob2_issuer($encrypted, $code, $badge, $title);
        }
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'achievement':
        // OB3 Achievement
        $text = get_ob3_achievement($encrypted, $code, $badge, $title);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'index':
        // HTML display page
        $image = $CFG->badge_url.'/'.$code.'.png';
        
        $ob2_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".json";
        $ob3_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".vc.json";
        $badge_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/badge.json";
        $issuer_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json";
        $achievement_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/achievement.json";
        $legacy_url = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
        $legacy_image_url = $CFG->wwwroot . "/badges/images/" . urlencode($encrypted) . ".png";
        
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
        <div class="container">
            <h1>Badge Assertion</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($badge->title) ?>" class="img-fluid" style="max-width: 200px;">
                </div>
                <div class="col-md-8">
                    <h2><?= htmlspecialchars($badge->title) ?></h2>
                    <p><strong>Course:</strong> <?= htmlspecialchars($title) ?></p>
                    <p><strong>Issued:</strong> <?= htmlspecialchars($date) ?></p>
                    <p><strong>Issuer:</strong> <?= htmlspecialchars($CFG->servicename) ?></p>
                </div>
            </div>
            
            <hr>
            
            <h3>Assertion Formats</h3>
            <ul>
                <li><a href="<?= htmlspecialchars($ob2_url) ?>" target="_blank">Open Badges 2.0 (OB2) JSON</a></li>
                <li><a href="<?= htmlspecialchars($ob3_url) ?>" target="_blank">Open Badges 3.0 / Verifiable Credential (OB3) JSON</a></li>
            </ul>
            
            <h3>Related Resources</h3>
            <ul>
                <li><a href="<?= htmlspecialchars($badge_url) ?>" target="_blank">Badge Class (OB2)</a></li>
                <li><a href="<?= htmlspecialchars($issuer_url) ?>" target="_blank">Issuer Profile</a></li>
                <li><a href="<?= htmlspecialchars($achievement_url) ?>" target="_blank">Achievement (OB3)</a></li>
                <li><a href="<?= htmlspecialchars($legacy_url) ?>" target="_blank">Legacy OB1 Assertion</a></li>
                <li><a href="<?= htmlspecialchars($legacy_image_url) ?>" target="_blank">Legacy Baked Badge Image (OB1)</a></li>
            </ul>
            
            <h3>Evidence</h3>
            <p><a href="<?= htmlspecialchars($CFG->apphome) ?>" target="_blank"><?= htmlspecialchars($CFG->apphome) ?></a></p>
        </div>
        <?php
        $OUTPUT->footer();
        break;
        
    default:
        http_response_code(404);
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        echo("<h2>Page not found.</h2>\n");
        $OUTPUT->footer();
        break;
}
