<?php

define('COOKIE_SESSION', true);
require_once('../config.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Badges;
use \Tsugi\UI\Lessons;
use \Tsugi\LinkedIn\LinkedIn;

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
    header('Content-Type: application/ld+json');
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
        header('Content-Type: application/ld+json');
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
$completion_badge = isset($badge->completion) ? $badge->completion : false;

// Route to appropriate handler
switch ($resource) {
    case 'assert':
        // OB2 Assertion
        $text = Badges::getOb2Assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/ld+json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'assert-vc':
        // OB3/VC Assertion
        $text = Badges::getOb3Assertion($encrypted, $date, $code, $badge, $title, $email);
        header('Content-Type: application/ld+json');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'badge':
        // OB2 BadgeClass
        $text = Badges::getOb2Badge($encrypted, $code, $badge, $title);
        header('Content-Type: application/ld+json');
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
        header('Content-Type: application/ld+json');
        header('Cache-Control: public, max-age=3600');
        header('Access-Control-Allow-Origin: *');
        echo($text);
        break;
        
    case 'achievement':
        // OB3 Achievement
        $text = Badges::getOb3Achievement($encrypted, $code, $badge, $title);
        header('Content-Type: application/ld+json');
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
        
        // Build landing URL for this badge assertion page (used as certUrl for LinkedIn)
        $landing_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted);
        
        // Compute digital signature (5 hex digits) - same algorithm as assignments page
        // Uses badge owner's displayname and email from $row
        $badge_owner_displayname = isset($row['displayname']) ? $row['displayname'] : '';
        $badge_owner_email = isset($row['email']) ? $row['email'] : '';
        $credential_id = null;
        if (is_string($badge_owner_displayname) || is_string($badge_owner_email)) {
            // Build output string matching assignments format: "displayname email" or just one if missing
            $output = "";
            if (is_string($badge_owner_displayname) && strlen($badge_owner_displayname) > 0) {
                $output .= $badge_owner_displayname;
            }
            if (is_string($badge_owner_displayname) && strlen($badge_owner_displayname) > 0 && 
                is_string($badge_owner_email) && strlen($badge_owner_email) > 0) {
                $output .= ' ';
            }
            if (is_string($badge_owner_email) && strlen($badge_owner_email) > 0) {
                $output .= $badge_owner_email;
            }
            // Compute signature: md5("42 " + output), then take first 5 hex digits
            $sig = md5("42 " . $output);
            $credential_id = substr($sig, 0, 5);
        }
        
        // Check if user is logged in and owns this badge
        // Start session if not already started (needed to check login status)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get current logged-in user ID from session
        $current_user_id = U::get($_SESSION, 'id');
        $logged_in = !empty($current_user_id);
        
        // Get badge owner user ID from the encrypted assertion ID
        // $pieces[0] contains the user_id of the badge owner
        $badge_owner_user_id = isset($pieces[0]) ? intval($pieces[0]) : null;
        
        // Only show LinkedIn button if user is logged in AND owns this badge
        $show_linkedin_button = $logged_in && ($current_user_id == $badge_owner_user_id);
        
        // Determine issuer organization name using badge_organization with fallback
        // Use getBadgeOrganization() method if available, otherwise fall back to manual logic
        if (method_exists($CFG, 'getBadgeOrganization')) {
            $issuer_org_name = htmlspecialchars($CFG->getBadgeOrganization(), ENT_QUOTES, 'UTF-8');
        } else {
            // Fallback for older ConfigInfo versions without the method
            if (isset($CFG->badge_organization) && !empty($CFG->badge_organization)) {
                $issuer_org_name = htmlspecialchars($CFG->badge_organization, ENT_QUOTES, 'UTF-8');
            } else {
                if (isset($CFG->servicedesc) && !empty($CFG->servicedesc)) {
                    $issuer_org_name = htmlspecialchars($CFG->servicedesc, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($CFG->servicename, ENT_QUOTES, 'UTF-8') . ')';
                } else {
                    $issuer_org_name = htmlspecialchars($CFG->servicename, ENT_QUOTES, 'UTF-8');
                }
            }
        }
        
        // Create LinkedIn helper from config (used for both URL building and link tags)
        $linkedin = LinkedIn::fromConfig($CFG);
        
        // Build LinkedIn "Add to Profile" URL if user owns the badge
        $linkedin_url = null;
        if ($show_linkedin_button && $completion_badge) {
            // Prepare badge details for LinkedIn
            $badge_name = htmlspecialchars($badge->title, ENT_QUOTES, 'UTF-8');
            $issued_on = $date; // Already in ISO 8601 format from U::iso8601()
            
            // Parse issued_on date to extract year and month for LinkedIn
            $issueYear = null;
            $issueMonth = null;
            if (!empty($issued_on)) {
                try {
                    // Try parsing ISO 8601 date (e.g., "2025-12-21T04:11:56Z")
                    $date_obj = new DateTime($issued_on);
                    if ($date_obj !== false) {
                        $issueYear = (int)$date_obj->format('Y');
                        $issueMonth = (int)$date_obj->format('m');
                    }
                } catch (Exception $e) {
                    // If date parsing fails, just skip date parameters
                    // LinkedIn will still accept the certification without dates
                }
            }
            
            // Build LinkedIn certification URL using LinkedIn class
            $linkedin_url = $linkedin->buildAddCertificationUrl(
                $badge_name,
                $landing_url,
                $credential_id,
                $issueYear,
                $issueMonth
            );
        }
        
        $OUTPUT->header();
        // Add alternate link tags to head for badge assertion JSON using LinkedIn class
        $linkTags = $linkedin->buildOb2HeadLinkTags($ob2_url, $landing_url);
        foreach ($linkTags as $tag) {
            echo($tag . "\n");
        }
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
        <div class="container">
            <h1>Badge Assertion</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <img id="badge-image" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($badge->title) ?>" class="img-fluid" style="max-width: 200px;">
                </div>
                <div class="col-md-8">
                    <h2><?= htmlspecialchars($badge->title) ?></h2>
                    <p><strong>Course:</strong> <?= htmlspecialchars($title) ?></p>
                    <p><strong>Issued:</strong> <?= htmlspecialchars($date) ?></p>
                    <p><strong>Issuer:</strong> <?= $issuer_org_name ?>
                    <?php
                    // Show LinkedIn organization link if configured
                    if (isset($CFG->linkedin_url) && !empty($CFG->linkedin_url)): ?>
                        <a href="<?= htmlspecialchars($CFG->linkedin_url) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 10px;">
                            <span class="glyphicon glyphicon-link"></span> LinkedIn
                        </a>
                    <?php endif; ?>
                    </p>
                    
                    <?php
                    // Show action buttons if user is logged in and owns this badge
                    if ($show_linkedin_button): ?>
                        <div style="margin-top: 15px;">
                            <button onclick="copyBadgeUrlToClipboard(this)" class="btn btn-default" style="margin-right: 10px;" title="Copy badge URL to clipboard">
                                <span class="glyphicon glyphicon-link"></span> Copy Badge URL
                            </button>
                            <button onclick="toggleQRCode()" class="btn btn-default" style="margin-right: 10px;" title="Show QR code">
                                <span class="glyphicon glyphicon-qrcode"></span> Show QR Code
                            </button>
                            <?php if ($completion_badge && $linkedin_url): ?>
                                <a href="<?= htmlspecialchars($linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" title="Add to LinkedIn">
                                    Add to LinkedIn
                                </a>
                            <?php endif; ?>
                        </div>
                        <div id="qr-code-container" style="display: none; margin-top: 15px; padding: 5px; background: white; border: 1px solid #ddd;"></div>
                        <?php if (!$completion_badge): ?>
                            <p style="margin-top: 15px; color: #666;">
                            This badge marks a learning milestone within the course. It represents progress toward a final, externally shareable credential.
                            </p>
                        <?php endif;
                    endif; ?>
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
            <p><a href="https://www.imsglobal.org/sites/default/files/Badges/OBv2p0Final/index.html" target="_blank">Official OB2 Specification</a> | <a href="https://www.imsglobal.org/spec/ob/v3p0" target="_blank">Official OB3 Specification</a> | <a href="#" data-toggle="modal" data-target="#validators-modal">About Other Badge Validators</a></p>
            
            <?php
            // Display digital signature (credential ID) near the bottom of the page
            if (!empty($credential_id)): ?>
                <hr>
                <p><strong>Credential ID:</strong> <code><?= htmlspecialchars($credential_id) ?></code></p>
            <?php endif; ?>
        </div>
        
        <!-- Bootstrap Modal for Badge Validators Info -->
        <div class="modal fade" id="validators-modal" tabindex="-1" role="dialog" aria-labelledby="validators-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="validators-modal-label">About Credly, Badgr, and Other Badge Platforms</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>PY4E badges follow the open, specification-based Open Badges 2.0 format and are
                        independently verifiable through the public assertion links we provide. Some
                        commercial badge platforms—such as Credly, Badgr, and others—use proprietary
                        verification systems and require additional vendor-specific metadata that is not
                        part of the official OB2 specification. Because of this, don't be surprised if PY4E
                        badges do not validate on those services. This is not an indication that anything
                        is wrong with the badge; it simply reflects that those platforms expect badges to
                        be created and stored inside their own ecosystems and data formats. In some cases,
                        these vendors originally implemented early draft versions of the Open Badges
                        specification but never fully updated to the final standard. PY4E implements the
                        official, final specification directly to keep credentials portable, transparent,
                        technically correct, and fully under the learner's control—without locking badges
                        into a single vendor. We hope that, by adhering to the official specification,
                        pressure will build for vendors to update their ecosystems to support the final
                        standards. <!-- goblins tried to bake OB2 PNGs. ChatGPT and Dr. Chuck said no. -->
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
        // Function to toggle QR code visibility
        function toggleQRCode() {
            const qrContainer = document.getElementById('qr-code-container');
            const button = event.target.closest('button');
            
            if (qrContainer.style.display === 'none') {
                qrContainer.style.display = 'block';
                button.innerHTML = '<span class="glyphicon glyphicon-qrcode"></span> Hide QR Code';
                // Generate QR code if not already generated
                if (!qrContainer.hasChildNodes()) {
                    new QRCode(qrContainer, {
                        text: '<?= htmlspecialchars($landing_url, ENT_QUOTES) ?>',
                        width: 150,
                        height: 150,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            } else {
                qrContainer.style.display = 'none';
                button.innerHTML = '<span class="glyphicon glyphicon-qrcode"></span> Show QR Code';
            }
        }
        
        // Function to copy badge URL to clipboard using tsugiscripts.js library
        function copyBadgeUrlToClipboard(button) {
            const badgeUrl = '<?= htmlspecialchars($landing_url, ENT_QUOTES, 'UTF-8') ?>';
            const originalText = button.innerHTML;
            
            // Use the copyToClipboardNoScroll function from tsugiscripts.js
            copyToClipboardNoScroll(button, badgeUrl);
            
            // Show success feedback
            button.innerHTML = '<span class="glyphicon glyphicon-ok"></span> Copied!';
            button.classList.remove('btn-default');
            button.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(function() {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-default');
            }, 2000);
        }
        </script>
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
