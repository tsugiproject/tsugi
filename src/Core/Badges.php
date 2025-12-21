<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Crypt\AesCtr;

/**
 * Open Badges 2.0 and 3.0 JSON generation utilities
 * 
 * This class provides methods for generating Open Badges assertion, badge class,
 * and issuer JSON in both OB2 and OB3 formats.
 */
class Badges {

    // OB2 Context URL (canonical)
    const OB2_CONTEXT = "https://w3id.org/openbadges/v2";
    
    // OB3 Context URLs
    const OB3_CREDENTIALS_CONTEXT = "https://www.w3.org/ns/credentials/v2";
    const OB3_OPENBADGES_CONTEXT = "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json";
    
    // Default issuer email if not configured
    const DEFAULT_ISSUER_EMAIL = "badge_issuer_email_not_set@example.com";
    
    /**
     * Parse badge ID from encrypted string (reuses OB1 logic)
     * 
     * @param string $encrypted The encrypted assertion ID
     * @param object $lesson The Lessons object
     * @return array|string Array of [row, png, pieces, badge] on success, error string on failure
     */
    public static function parseAssertionId($encrypted, $lesson) {
        global $CFG, $PDOX;
        
        if ( ! function_exists('hex2bin')) {
            function hex2bin($hexString) {
                $hexLength = strlen($hexString);
                if ($hexLength % 2 != 0 || preg_match("/[^\da-fA-F]/",$hexString)) return FALSE;
                $binString = "";
                for ($x = 1; $x <= $hexLength/2; $x++) {
                    $binString .= chr(hexdec(substr($hexString,2 * $x - 2,2)));
                }
                return $binString;
            }
        }

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
     * Get the issuer email from config or return default
     * 
     * @return string The issuer email address
     */
    private static function getIssuerEmail() {
        global $CFG;
        return (isset($CFG->badge_issuer_email) && !empty($CFG->badge_issuer_email)) 
            ? $CFG->badge_issuer_email 
            : self::DEFAULT_ISSUER_EMAIL;
    }
    
    /**
     * Build assertion URL
     * 
     * @param string $encrypted The encrypted assertion ID
     * @return string The assertion URL
     */
    private static function buildAssertionUrl($encrypted) {
        global $CFG;
        return $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".json";
    }
    
    /**
     * Build badge class URL
     * 
     * @param string $code The badge code
     * @param string|null $format Optional format parameter (e.g., 'ob3')
     * @return string The badge class URL
     */
    private static function buildBadgeUrl($code, $format = null) {
        global $CFG;
        $url = $CFG->wwwroot . "/assertions/badge/" . urlencode($code) . ".json";
        if ($format) {
            $url .= "?format=" . urlencode($format);
        }
        return $url;
    }
    
    /**
     * Build issuer URL
     * 
     * @param string|null $format Optional format parameter (e.g., 'ob3')
     * @return string The issuer URL
     */
    private static function buildIssuerUrl($format = null) {
        global $CFG;
        $url = $CFG->wwwroot . "/assertions/issuer.json";
        if ($format) {
            $url .= "?format=" . urlencode($format);
        }
        return $url;
    }
    
    /**
     * Build evidence narrative
     * 
     * @param object $badge The badge object
     * @param string $title The course title
     * @return string The evidence narrative
     */
    private static function buildEvidenceNarrative($badge, $title) {
        global $CFG;
        return "Completed {$badge->title} in course $title at {$CFG->servicename}";
    }
    
    /**
     * Get OB2 Assertion JSON
     * 
     * @param string $encrypted The encrypted assertion ID
     * @param string $date The issuance date (ISO 8601)
     * @param string $code The badge code
     * @param object $badge The badge object
     * @param string $title The course title
     * @param string $email The recipient email
     * @return string JSON-encoded assertion
     */
    public static function getOb2Assertion($encrypted, $date, $code, $badge, $title, $email) {
        global $CFG;

        $image = $CFG->badge_url.'/'.$code.'.png';
        $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
        $assert_id = self::buildAssertionUrl($encrypted);
        $badge_url = self::buildBadgeUrl($code);
        $evidence = $CFG->apphome;
        $narrative = self::buildEvidenceNarrative($badge, $title);
        
        // Legacy OB1 assertion reference for traceability
        $legacy_assertion = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
        
        $assertion = array(
            "@context" => self::OB2_CONTEXT,
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
                    "narrative" => $narrative
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
     * 
     * @param string|null $encrypted The encrypted assertion ID (optional, for per-badge issuer)
     * @param string $code The badge code
     * @param object $badge The badge object
     * @param string $title The course title
     * @return string JSON-encoded badge class
     */
    public static function getOb2Badge($encrypted, $code, $badge, $title) {
        global $CFG;

        $image = $CFG->badge_url.'/'.$code.'.png';
        $badge_url = self::buildBadgeUrl($code);
        $issuer_url = self::buildIssuerUrl();
        $narrative = self::buildEvidenceNarrative($badge, $title);
        
        $badge_class = array(
            "@context" => self::OB2_CONTEXT,
            "id" => $badge_url,
            "type" => "BadgeClass",
            "name" => $badge->title,
            "image" => $image,
            "description" => $narrative,
            "criteria" => array(
                "id" => $CFG->apphome,
                "narrative" => $narrative
            ),
            "issuer" => $issuer_url
        );
        
        return json_encode($badge_class, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get OB2 Issuer JSON
     * 
     * @param string|null $encrypted The encrypted assertion ID (optional, for per-badge issuer)
     * @param string|null $code The badge code (optional)
     * @param object|null $badge The badge object (optional)
     * @param string|null $title The course title (optional)
     * @return string JSON-encoded issuer
     */
    public static function getOb2Issuer($encrypted = null, $code = null, $badge = null, $title = null) {
        global $CFG;

        $issuer_url = self::buildIssuerUrl();
        $issuer_email = self::getIssuerEmail();
        
        $issuer = array(
            "@context" => self::OB2_CONTEXT,
            "id" => $issuer_url,
            "type" => "Issuer",
            "name" => $CFG->servicename,
            "url" => $CFG->apphome,
            "email" => $issuer_email
        );
        
        return json_encode($issuer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get OB3/VC Assertion JSON (Verifiable Credential)
     * 
     * @param string $encrypted The encrypted assertion ID
     * @param string $date The issuance date (ISO 8601)
     * @param string $code The badge code
     * @param object $badge The badge object
     * @param string $title The course title
     * @param string $email The recipient email
     * @return string JSON-encoded credential
     */
    public static function getOb3Assertion($encrypted, $date, $code, $badge, $title, $email) {
        global $CFG;

        $image = $CFG->badge_url.'/'.$code.'.png';
        $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
        $credential_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".vc.json";
        $achievement_id = self::buildBadgeUrl($code, 'ob3');
        $issuer_id = self::buildIssuerUrl('ob3');
        $evidence = $CFG->apphome;
        $narrative = self::buildEvidenceNarrative($badge, $title);
        
        // Legacy OB1 assertion reference for traceability
        $legacy_assertion = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
        
        $credential = array(
            "@context" => array(
                self::OB3_CREDENTIALS_CONTEXT,
                self::OB3_OPENBADGES_CONTEXT
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
                    "description" => $narrative,
                    "image" => array(
                        "id" => $image,
                        "type" => "Image"
                    ),
                    "criteria" => array(
                        "id" => $evidence,
                        "narrative" => $narrative
                    )
                ),
                "evidence" => array(
                    array(
                        "id" => $evidence,
                        "type" => "Evidence",
                        "narrative" => $narrative
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
        
        return json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get OB3 Achievement JSON
     * 
     * @param string|null $encrypted The encrypted assertion ID (optional)
     * @param string $code The badge code
     * @param object $badge The badge object
     * @param string $title The course title
     * @return string JSON-encoded achievement
     */
    public static function getOb3Achievement($encrypted, $code, $badge, $title) {
        global $CFG;

        $image = $CFG->badge_url.'/'.$code.'.png';
        $achievement_id = self::buildBadgeUrl($code, 'ob3');
        $issuer_id = self::buildIssuerUrl('ob3');
        $narrative = self::buildEvidenceNarrative($badge, $title);
        
        $achievement = array(
            "@context" => array(
                self::OB3_CREDENTIALS_CONTEXT,
                self::OB3_OPENBADGES_CONTEXT
            ),
            "id" => $achievement_id,
            "type" => "Achievement",
            "name" => $badge->title,
            "description" => $narrative,
            "image" => array(
                "id" => $image,
                "type" => "Image"
            ),
            "criteria" => array(
                "id" => $CFG->apphome,
                "narrative" => $narrative
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
     * 
     * @param string|null $encrypted The encrypted assertion ID (optional)
     * @param string|null $code The badge code (optional)
     * @param object|null $badge The badge object (optional)
     * @param string|null $title The course title (optional)
     * @return string JSON-encoded issuer
     */
    public static function getOb3Issuer($encrypted = null, $code = null, $badge = null, $title = null) {
        global $CFG;

        $issuer_id = self::buildIssuerUrl('ob3');
        $issuer_email = self::getIssuerEmail();
        
        $issuer = array(
            "@context" => array(
                self::OB3_CREDENTIALS_CONTEXT,
                self::OB3_OPENBADGES_CONTEXT
            ),
            "id" => $issuer_id,
            "type" => "Profile",
            "name" => $CFG->servicename,
            "url" => $CFG->apphome,
            "email" => $issuer_email
        );
        
        return json_encode($issuer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
