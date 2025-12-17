<?php

use \Tsugi\Crypt\AesCtr;
use \Tsugi\Core\User;
use \Tsugi\Util\U;

if ( ! function_exists('hex2bin')) {
function hex2bin($hexString)
{
    $hexLength = strlen($hexString);
    // only hex numbers is allowed
    if ($hexLength % 2 != 0 || preg_match("/[^\da-fA-F]/",$hexString)) return FALSE;
    unset($binString);
    $binString = "";
    for ($x = 1; $x <= $hexLength/2; $x++)
    {
     $binString .= chr(hexdec(substr($hexString,2 * $x - 2,2)));
    }
    return $binString;
}
}

/**
 * Parse badge ID from encrypted string (reuses OB1 logic)
 */
function parse_assertion_id($encrypted, $lesson) {
    global $CFG, $PDOX;

    $decrypted = \Tsugi\Crypt\AesCtr::decrypt(hex2bin($encrypted), $CFG->badge_encrypt_password, 256);
    $pieces = explode(':',$decrypted);

    if ( count($pieces) != 3 || !is_numeric($pieces[0]) || !is_numeric($pieces[2])) {
        return 'Decryption failed';
    }
    $pattern = '/[^a-zA-Z0-9_]/';
    if ( preg_match($pattern, $pieces[1]) ) {
        return 'File pattern fail';
    }

    // Make sure this is a legit badge
    $legit = false;
    foreach($lesson->lessons->badges as $badge) {
        if ( $badge->image == $pieces[1].'.png') {
            $legit = true;
            break;
        }
    }
    if ( ! $legit ) {
        return 'Bad badge image';
    }

    // Load the badge
    $file = $CFG->badge_path . '/' . $pieces[1] . '.png';
    $png = file_get_contents($file);
    if ( $png === false ) return 'File contents fail';

    $row = $PDOX->rowDie(
            "SELECT displayname, email, user_key, U.login_at, title FROM {$CFG->dbprefix}lti_user AS U
            JOIN {$CFG->dbprefix}lti_membership AS M
            ON U.user_id = M.user_id 
            JOIN {$CFG->dbprefix}lti_context AS C
            ON M.context_id = C.context_id 
            WHERE U.user_id = :UID AND M.context_id = :CID",
            array(":UID" => $pieces[0], ":CID" => $pieces[2])
    );
    if ( $row === false ) return 'Metadata failed';

    return array($row, $png, $pieces, $badge);
}

/**
 * Get OB2 Assertion JSON
 */
function get_ob2_assertion($encrypted, $date, $code, $badge, $title, $email) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
    $assert_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".json";
    $badge_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/badge.json";
    $issuer_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json";
    
    // Evidence - same as OB1
    $evidence = $CFG->apphome;
    
    // Legacy OB1 assertion reference for traceability
    $legacy_assertion = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
    
    $assertion = array(
        "@context" => "https://purl.imsglobal.org/spec/ob/v2p1/context.json",
        "type" => "Assertion",
        "id" => $assert_id,
        "recipient" => array(
            "type" => "email",
            "hashed" => true,
            "salt" => $CFG->badge_assert_salt,
            "identity" => $recipient
        ),
        "issuedOn" => $date,
        "badge" => $badge_url,
        "image" => $image,
        "evidence" => array(
            array(
                "id" => $evidence,
                "type" => "Evidence",
                "narrative" => "Completed $badge->title in course $title at $CFG->servicename"
            )
        ),
        "verification" => array(
            "type" => "HostedBadge"
        )
    );
    
    // Add legacy assertion reference in extensions
    if (isset($CFG->badge_include_legacy) && $CFG->badge_include_legacy) {
        $assertion["extensions"] = array(
            "legacyAssertion" => $legacy_assertion
        );
    }
    
    return json_encode($assertion, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Get OB2 BadgeClass JSON
 */
function get_ob2_badge($encrypted, $code, $badge, $title) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $badge_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/badge.json";
    $issuer_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json";
    
    $badge_class = array(
        "@context" => "https://purl.imsglobal.org/spec/ob/v2p1/context.json",
        "id" => $badge_url,
        "type" => "BadgeClass",
        "name" => $badge->title,
        "image" => $image,
        "description" => "Completed $badge->title in course $title at $CFG->servicename",
        "criteria" => array(
            "id" => $CFG->apphome,
            "narrative" => "Completed $badge->title in course $title at $CFG->servicename"
        ),
        "issuer" => $issuer_url
    );
    
    return json_encode($badge_class, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Get OB2 Issuer JSON
 */
function get_ob2_issuer($encrypted, $code, $badge, $title) {
    global $CFG;

    $issuer_url = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json";
    
    $issuer = array(
        "@context" => "https://purl.imsglobal.org/spec/ob/v2p1/context.json",
        "id" => $issuer_url,
        "type" => "Profile",
        "url" => $CFG->apphome,
        "name" => $CFG->servicename,
        "email" => isset($CFG->admin_email) ? $CFG->admin_email : null
    );
    
    // Remove null email if not set
    if ($issuer["email"] === null) {
        unset($issuer["email"]);
    }
    
    return json_encode($issuer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Get OB3/VC Assertion JSON (Verifiable Credential)
 */
function get_ob3_assertion($encrypted, $date, $code, $badge, $title, $email) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
    $credential_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".vc.json";
    $achievement_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/achievement.json";
    $issuer_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json?format=ob3";
    
    // Evidence - same as OB1
    $evidence = $CFG->apphome;
    
    // Legacy OB1 assertion reference for traceability
    $legacy_assertion = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
    
    $credential = array(
        "@context" => array(
            "https://www.w3.org/ns/credentials/v2",
            "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json"
        ),
        "id" => $credential_id,
        "type" => array("VerifiableCredential", "OpenBadgeCredential"),
        "issuer" => array(
            "id" => $issuer_id,
            "type" => "Profile",
            "name" => $CFG->servicename,
            "url" => $CFG->apphome
        ),
        "issuanceDate" => $date,
        "credentialSubject" => array(
            "id" => "mailto:" . $email,
            "type" => "AchievementSubject",
            "achievement" => array(
                "id" => $achievement_id,
                "type" => "Achievement",
                "name" => $badge->title,
                "description" => "Completed $badge->title in course $title at $CFG->servicename",
                "image" => array(
                    "id" => $image,
                    "type" => "Image"
                ),
                "criteria" => array(
                    "id" => $evidence,
                    "narrative" => "Completed $badge->title in course $title at $CFG->servicename"
                )
            ),
            "evidence" => array(
                array(
                    "id" => $evidence,
                    "type" => "Evidence",
                    "narrative" => "Completed $badge->title in course $title at $CFG->servicename"
                )
            )
        )
    );
    
    // Add legacy assertion reference in extensions
    if (isset($CFG->badge_include_legacy) && $CFG->badge_include_legacy) {
        $credential["credentialSubject"]["extensions"] = array(
            "legacyAssertion" => $legacy_assertion
        );
    }
    
    // Note: In a production system, you would add a cryptographic proof here
    // For now, we're using hosted verification similar to OB2
    
    return json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Get OB3 Achievement JSON
 */
function get_ob3_achievement($encrypted, $code, $badge, $title) {
    global $CFG;

    $image = $CFG->badge_url.'/'.$code.'.png';
    $achievement_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/achievement.json";
    $issuer_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json?format=ob3";
    
    $achievement = array(
        "@context" => array(
            "https://www.w3.org/ns/credentials/v2",
            "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json"
        ),
        "id" => $achievement_id,
        "type" => "Achievement",
        "name" => $badge->title,
        "description" => "Completed $badge->title in course $title at $CFG->servicename",
        "image" => array(
            "id" => $image,
            "type" => "Image"
        ),
        "criteria" => array(
            "id" => $CFG->apphome,
            "narrative" => "Completed $badge->title in course $title at $CFG->servicename"
        ),
        "issuer" => array(
            "id" => $issuer_id,
            "type" => "Profile"
        )
    );
    
    return json_encode($achievement, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

/**
 * Get OB3 Issuer JSON
 */
function get_ob3_issuer($encrypted, $code, $badge, $title) {
    global $CFG;

    $issuer_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . "/issuer.json?format=ob3";
    
    $issuer = array(
        "@context" => array(
            "https://www.w3.org/ns/credentials/v2",
            "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json"
        ),
        "id" => $issuer_id,
        "type" => "Profile",
        "name" => $CFG->servicename,
        "url" => $CFG->apphome
    );
    
    if (isset($CFG->admin_email)) {
        $issuer["email"] = $CFG->admin_email;
    }
    
    return json_encode($issuer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
