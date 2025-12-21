<?php

define('COOKIE_SESSION', true);
require_once('../config.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Badges;
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

// Handle fixed issuer route: /assertions/issuer.json
if ($first_part === 'issuer.json') {
    if ( ! isset($CFG->lessons) ) {
        die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
    }
    $format = isset($_GET['format']) ? $_GET['format'] : 'ob2';
    if ($format === 'ob3') {
        $text = Badges::getOb3Issuer(null, null, null, null);
    } else {
        $text = Badges::getOb2Issuer(null, null, null, null);
    }
    header('Content-Type: application/json');
    header('Cache-Control: public, max-age=3600');
    header('Access-Control-Allow-Origin: *');
    echo($text);
    return;
}

// Handle badge class route: /assertions/badge/{code}.json
if ($first_part === 'badge' && count($path_parts) === 2) {
    $badge_file = $path_parts[1];
    if (strpos($badge_file, '.json') !== false) {
        $code = urldecode(str_replace('.json', '', $badge_file));
        // Remove ?format=ob3 if present
        $code = str_replace('?format=ob3', '', $code);
        $code = str_replace('&format=ob3', '', $code);
        
        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }
        
        $PDOX = LTIX::getConnection();
        $l = new Lessons($CFG->lessons);
        
        // Find the badge by code
        $badge = null;
        foreach($l->lessons->badges as $b) {
            if ($b->image == $code.'.png') {
                $badge = $b;
                break;
            }
        }
        
        if ($badge === null) {
            http_response_code(404);
            $OUTPUT->header();
            $OUTPUT->bodyStart();
            $OUTPUT->topNav();
            echo("<h2>Badge not found.</h2>\n");
            $OUTPUT->footer();
            return;
        }
        
        // Get a default title (use first context title or servicename)
        $title = isset($CFG->servicename) ? $CFG->servicename : 'Course';
        
        $format = isset($_GET['format']) ? $_GET['format'] : 'ob2';
        if ($format === 'ob3') {
            $text = Badges::getOb3Achievement(null, $code, $badge, $title);
        } else {
            $text = Badges::getOb2Badge(null, $code, $badge, $title);
        }
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        return;
    }
}

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

$x = Badges::parseAssertionId($encrypted, $l);
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
        $text = Badges::getOb2Assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'assert-vc':
        // OB3/VC Assertion
        $text = Badges::getOb3Assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/vc+json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'badge':
        // OB2 BadgeClass
        $text = Badges::getOb2Badge($encrypted, $code, $badge, $title);
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'issuer':
        // OB2/OB3 Issuer (default to OB2, can use ?format=ob3)
        $format = isset($_GET['format']) ? $_GET['format'] : 'ob2';
        if ($format === 'ob3') {
            $text = Badges::getOb3Issuer($encrypted, $code, $badge, $title);
        } else {
            $text = Badges::getOb2Issuer($encrypted, $code, $badge, $title);
        }
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'achievement':
        // OB3 Achievement
        $text = Badges::getOb3Achievement($encrypted, $code, $badge, $title);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'index':
        // HTML display page
        $image = $CFG->badge_url.'/'.$code.'.png';
        
        $ob2_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".json";
        $ob3_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".vc.json";
        $badge_url = $CFG->wwwroot . "/assertions/badge/" . urlencode($code) . ".json";
        $issuer_url = $CFG->wwwroot . "/assertions/issuer.json";
        $achievement_url = $CFG->wwwroot . "/assertions/badge/" . urlencode($code) . ".json?format=ob3";
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
            
            <hr>
            
            <h3>Validate Badge</h3>
            <ul>
                <li><a href="https://openbadgesvalidator.imsglobal.org/?url=<?= urlencode($ob2_url) ?>" target="_blank">Validate OB2 Assertion (IMS Global Validator)</a></li>
                <li><a href="https://openbadgesvalidator.imsglobal.org/?url=<?= urlencode($badge_url) ?>" target="_blank">Validate OB2 Badge Class (IMS Global Validator)</a></li>
                <li><a href="https://openbadgesvalidator.imsglobal.org/?url=<?= urlencode($issuer_url) ?>" target="_blank">Validate OB2 Issuer (IMS Global Validator)</a></li>
            </ul>
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
