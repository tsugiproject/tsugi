<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\Ob3DataIntegrity;
use \Tsugi\Crypt\AesCtr;
use \Tsugi\Services\Badges\CourseBadges;

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
    /**
     * Course id stored in an unpublished assertion id, or 0.
     *
     * @param mixed $encrypted
     */
    public static function contextIdFromAssertion($encrypted): int {
        global $CFG;

        if ( ! is_string($encrypted) || $encrypted === '' || (strlen($encrypted) % 2) !== 0 || ! ctype_xdigit($encrypted) ) {
            return 0;
        }
        if ( self::checkBadgeConfig(false) !== false ) {
            return 0;
        }
        $bin = hex2bin($encrypted);
        if ( ! is_string($bin) || $bin === '' ) {
            return 0;
        }
        $plain = \Tsugi\Crypt\AesCtr::decrypt($bin, $CFG->badge_encrypt_password, 256);
        if ( ! is_string($plain) ) {
            return 0;
        }
        $pieces = explode(':', $plain);
        if ( count($pieces) !== 3 || ! is_numeric($pieces[2]) ) {
            return 0;
        }
        return (int) $pieces[2];
    }

    public static function parseAssertionId($encrypted, $lesson) {
        global $CFG, $PDOX;
        
        // Encrypt password and salt. The site image folder is checked only
        // when this badge is not a Files Public picture.
        $config_error = self::checkBadgeConfig(false);
        if ( $config_error !== false ) {
            return $config_error;
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

        $badge = self::badgeFromLessons($lesson, $pieces[1]);
        $minted = \Tsugi\Services\Badges\BadgeService::mintedDisplayRow(
            (int) $pieces[0],
            (string) $pieces[1],
            (int) $pieces[2]
        );
        if ( $badge === null && $minted !== null ) {
            $badge = \Tsugi\Services\Badges\BadgeService::syntheticBadge(
                (string) $pieces[1],
                $minted['badge_title']
            );
        }
        if ( $badge === null ) {
            return 'Bad badge image';
        }

        $png = self::pngBytes($badge, $pieces[1], (int) $pieces[2]);
        if ( ! is_string($png) ) {
            $image = (is_object($badge) && isset($badge->image)) ? $badge->image : '';
            if ( ! CourseBadges::isPublicImage($image) ) {
                $file_config = self::checkBadgeConfig(true);
                if ( $file_config !== false ) {
                    return $file_config;
                }
            }
            return 'File contents fail';
        }

        // Join the course even when it is soft-deleted. Published badges stay valid.
        $row = $PDOX->rowDie(
                "SELECT displayname, email, user_key, U.login_at, title FROM {$CFG->dbprefix}lti_user AS U
                JOIN {$CFG->dbprefix}lti_membership AS M
                ON U.user_id = M.user_id 
                JOIN {$CFG->dbprefix}lti_context AS C
                ON M.context_id = C.context_id 
                WHERE U.user_id = :UID AND M.context_id = :CID",
                array(":UID" => $pieces[0], ":CID" => $pieces[2])
        );
        if ( $row === false ) {
            if ( $minted === null ) {
                return 'Metadata failed';
            }
            $row = $minted;
        }

        return array($row, $png, $pieces, $badge);
    }

    /**
     * Badge definition from the current lessons list, or null.
     *
     * @param object $lesson
     * @param string $code
     * @return object|null
     */
    private static function badgeFromLessons($lesson, $code) {
        if ( ! is_object($lesson) || ! isset($lesson->lessons->badges) || ! is_iterable($lesson->lessons->badges) ) {
            return null;
        }
        foreach ( $lesson->lessons->badges as $badge ) {
            if ( isset($badge->image) && CourseBadges::imageMatchesCode($badge->image, $code) ) {
                return $badge;
            }
        }
        return null;
    }

    /**
     * Check if badge configuration is properly set up.
     *
     * Identity (encrypt password and salt) is always required. The site badge
     * folder is required for file-based badge images. Manifest badges that
     * use Files Public do not need it.
     *
     * Returns an error message string if configuration is missing, or false if OK.
     *
     * @param bool $requireFiles When false, badge_path and badge_url are not required
     * @return string|false Error message string or false if OK
     */
    public static function checkBadgeConfig($requireFiles = true) {
        global $CFG;
        
        $missing = array();
        
        if ( !isset($CFG->badge_encrypt_password) || empty($CFG->badge_encrypt_password) || $CFG->badge_encrypt_password === false ) {
            $missing[] = '$CFG->badge_encrypt_password';
        }
        
        if ( !isset($CFG->badge_assert_salt) || empty($CFG->badge_assert_salt) || $CFG->badge_assert_salt === false ) {
            $missing[] = '$CFG->badge_assert_salt';
        }
        
        if ( $requireFiles ) {
            if ( !isset($CFG->badge_path) || empty($CFG->badge_path) ) {
                $missing[] = '$CFG->badge_path';
            }
            
            if ( !isset($CFG->badge_url) || empty($CFG->badge_url) ) {
                $missing[] = '$CFG->badge_url';
            }
        }
        
        if ( count($missing) > 0 ) {
            $msg = "<h2>Badge Configuration Required</h2>\n";
            $msg .= "<p>The following badge configuration parameters are missing or not set in your <code>config.php</code>:</p>\n";
            $msg .= "<ul>\n";
            foreach($missing as $param) {
                $msg .= "<li><code>" . htmlspecialchars($param) . "</code></li>\n";
            }
            $msg .= "</ul>\n";
            $msg .= "<h3>How to Configure Badges</h3>\n";
            $msg .= "<p>To enable badge functionality, add the following to your <code>config.php</code> file:</p>\n";
            $msg .= "<pre>\n";
            $msg .= "// Badge generation settings - once you set these values to something\n";
            $msg .= "// other than false and start issuing badges - don't change these or\n";
            $msg .= "// existing badge images that have been downloaded from the system\n";
            $msg .= "// will be invalidated.\n";
            $msg .= "\$CFG->badge_encrypt_password = \"somethinglongwithhex387438758974987\";\n";
            $msg .= "\$CFG->badge_assert_salt = \"mediumlengthhexstring\";\n";
            if ( $requireFiles ) {
                $msg .= "\n";
                $msg .= "// This folder contains the badge images\n";
                $msg .= "\$CFG->badge_path = \$CFG->dirroot . '/../bimages';\n";
                $msg .= "\$CFG->badge_url = \$CFG->apphome . '/bimages';\n";
            }
            $msg .= "</pre>\n";
            $msg .= "<p>For more information, see <code>config-dist.php</code> in your Tsugi installation.</p>\n";
            return $msg;
        }
        
        return false;
    }

    /**
     * Live image URL for a badge that has not been published yet.
     *
     * Public files win. Otherwise the site badge folder.
     *
     * @param object|null $badge
     * @param string $code
     */
    public static function liveImageUrl($badge, $code, int $contextId = 0): string {
        global $CFG;
        if ( is_object($badge) ) {
            $url = CourseBadges::displayUrl($badge, $contextId);
            if ( $url !== '' ) {
                return $url;
            }
        }
        $code = is_string($code) ? $code : '';
        if ( $code !== '' && isset($CFG->badge_url) && is_string($CFG->badge_url) && $CFG->badge_url !== '' ) {
            return rtrim($CFG->badge_url, '/') . '/' . $code . '.png';
        }
        return '';
    }

    /**
     * Site badge folder is required only for an unpublished file-based image.
     *
     * @param object|null $badge
     * @param array<string,mixed>|null $frozen
     */
    private static function needsBadgeFiles($badge, $frozen): bool {
        if ( self::useFrozen($frozen) ) {
            return false;
        }
        if ( is_object($badge) && isset($badge->image) && CourseBadges::isPublicImage($badge->image) ) {
            return false;
        }
        return true;
    }

    /**
     * @param object|null $badge
     * @param array<string,mixed>|null $frozen
     */
    private static function claimImage($badge, $code, $frozen, $encrypted = null): string {
        $contextId = is_string($encrypted) ? self::contextIdFromAssertion($encrypted) : 0;
        $live = self::liveImageUrl($badge, $code, $contextId);
        if ( self::useFrozen($frozen) ) {
            return self::frozenString($frozen, 'image_url', $live);
        }
        return $live;
    }

    /**
     * PNG bytes for baking. Public files come from the course. Otherwise badge_path.
     *
     * @param object|null $badge
     * @return string|null
     */
    private static function pngBytes($badge, string $code, int $contextId): ?string {
        global $CFG;
        $image = (is_object($badge) && isset($badge->image) && is_string($badge->image)) ? $badge->image : '';
        if ( CourseBadges::isPublicImage($image) ) {
            return CourseBadges::bytes($image, $contextId);
        }
        if ( $code === '' || ! isset($CFG->badge_path) || ! is_string($CFG->badge_path) || $CFG->badge_path === '' ) {
            return null;
        }
        $file = rtrim($CFG->badge_path, '/') . '/' . $code . '.png';
        $png = @file_get_contents($file);
        return is_string($png) ? $png : null;
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
     * Build badge class URL (OB2 - no query params)
     * 
     * @param string $code The badge code
     * @return string The badge class URL
     */
    private static function buildBadgeUrl($code) {
        global $CFG;
        return $CFG->wwwroot . "/assertions/badge/" . urlencode($code) . ".json";
    }
    
    /**
     * Build badge class URL with format (OB3 only)
     * 
     * @param string $code The badge code
     * @param string $format Format parameter (e.g., 'ob3')
     * @return string The badge class URL with format
     */
    private static function buildBadgeUrlWithFormat($code, $format) {
        global $CFG;
        return $CFG->wwwroot . "/assertions/badge/" . urlencode($code) . ".json?format=" . urlencode($format);
    }
    
    /**
     * Build issuer URL (OB2 - no query params)
     * 
     * @return string The issuer URL
     */
    private static function buildIssuerUrl() {
        global $CFG;
        return $CFG->wwwroot . "/assertions/issuer.json";
    }
    
    /**
     * Build issuer URL with format (OB3 only)
     * 
     * @param string $format Format parameter (e.g., 'ob3')
     * @return string The issuer URL with format
     */
    private static function buildIssuerUrlWithFormat($format) {
        global $CFG;
        return $CFG->wwwroot . "/assertions/issuer.json?format=" . urlencode($format);
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
     * A published badge carries its claims. Unminted badges keep reading config.
     *
     * @param array<string,mixed>|null $frozen
     */
    private static function useFrozen($frozen) {
        return is_array($frozen)
            && isset($frozen['guid'])
            && is_string($frozen['guid'])
            && $frozen['guid'] !== '';
    }

    /**
     * @param array<string,mixed>|null $frozen
     */
    private static function frozenString($frozen, $key, $default) {
        if ( ! is_array($frozen) || ! isset($frozen[$key]) || ! is_string($frozen[$key]) || $frozen[$key] === '' ) {
            return $default;
        }
        return $frozen[$key];
    }

    /**
     * @param array<string,mixed>|null $frozen
     */
    private static function claimNarrative($badge, $title, $frozen) {
        if ( self::useFrozen($frozen) ) {
            return self::frozenString($frozen, 'narrative', self::buildEvidenceNarrative($badge, $title));
        }
        return self::buildEvidenceNarrative($badge, $title);
    }

    /**
     * @param array<string,mixed> $frozen
     */
    private static function assertionResourceUrl($guid, $resource) {
        global $CFG;
        return rtrim($CFG->wwwroot, '/') . '/assertions/' . rawurlencode($guid) . '/' . $resource;
    }

    /**
     * Manifest badges are fetched from the assertion id, which names the course.
     *
     * @param object|null $badge
     */
    private static function courseScopedResource($encrypted, $badge, string $resource): ?string {
        if ( ! is_string($encrypted) || $encrypted === '' ) {
            return null;
        }
        if ( ! is_object($badge) || ! isset($badge->image) || ! CourseBadges::isPublicImage($badge->image) ) {
            return null;
        }
        return self::assertionResourceUrl($encrypted, $resource);
    }

    /**
     * Issuer profile fields. Frozen values win for a published badge.
     *
     * @param array<string,mixed>|null $frozen
     * @return array{name:string,url:string,email:string,logo:string,linkedin_url:string}
     */
    private static function issuerFields($frozen) {
        global $CFG;

        if ( self::useFrozen($frozen) && isset($frozen['issuer']) && is_array($frozen['issuer']) ) {
            $src = $frozen['issuer'];
            $email = isset($src['email']) && is_string($src['email']) ? $src['email'] : '';
            if ( $email === '' ) {
                $email = self::getIssuerEmail();
            }
            return array(
                'name' => isset($src['name']) && is_string($src['name']) ? $src['name'] : '',
                'url' => isset($src['url']) && is_string($src['url']) ? $src['url'] : '',
                'email' => $email,
                'logo' => isset($src['logo']) && is_string($src['logo']) ? $src['logo'] : '',
                'linkedin_url' => isset($src['linkedin_url']) && is_string($src['linkedin_url']) ? $src['linkedin_url'] : '',
            );
        }

        $email = (isset($CFG->badge_issuer_email) && !empty($CFG->badge_issuer_email))
            ? $CFG->badge_issuer_email
            : self::getIssuerEmail();

        if (isset($CFG->badge_organization) && !empty($CFG->badge_organization)) {
            $name = $CFG->badge_organization;
        } elseif (method_exists($CFG, 'getBadgeOrganization')) {
            $name = $CFG->getBadgeOrganization();
        } else {
            $name = $CFG->servicename;
        }

        $url = (isset($CFG->badge_organization_url) && !empty($CFG->badge_organization_url))
            ? $CFG->badge_organization_url
            : $CFG->apphome;

        return array(
            'name' => is_string($name) ? $name : '',
            'url' => is_string($url) ? $url : '',
            'email' => is_string($email) ? $email : '',
            'logo' => (isset($CFG->badge_organization_logo) && is_string($CFG->badge_organization_logo)) ? $CFG->badge_organization_logo : '',
            'linkedin_url' => (isset($CFG->linkedin_url) && is_string($CFG->linkedin_url)) ? $CFG->linkedin_url : '',
        );
    }
    
    /**
     * Normalize ISO8601 date to Z format (required by Badgr/Credly)
     * Converts +00:00 to Z
     * 
     * @param string $date ISO8601 date string
     * @return string Date string ending with Z
     */
    private static function normalizeDateToZ($date) {
        // Replace +00:00 with Z
        $date = str_replace('+00:00', 'Z', $date);
        // Also handle other timezone offsets that might be UTC
        if (preg_match('/\+00:00$/', $date)) {
            $date = str_replace('+00:00', 'Z', $date);
        }
        return $date;
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
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded assertion
     */
    public static function getOb2Assertion($encrypted, $date, $code, $badge, $title, $email, $frozen = null) {
        global $CFG;

        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $image = self::claimImage($badge, $code, $frozen, $encrypted);
        $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
        $assert_id = self::buildAssertionUrl($encrypted);
        $badge_url = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'badge.json')
            : (self::courseScopedResource($encrypted, $badge, 'badge.json') ?? self::buildBadgeUrl($code));
        $evidence = self::useFrozen($frozen)
            ? self::frozenString($frozen, 'evidence_url', $CFG->apphome)
            : $CFG->apphome;
        $narrative = self::claimNarrative($badge, $title, $frozen);
        
        // Normalize date to Z format (required by Badgr/Credly)
        $issuedOn = self::normalizeDateToZ($date);
        
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
            "issuedOn" => $issuedOn,
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
                "type" => "hosted"
            )
        );
        
        return json_encode($assertion, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get OB2 BadgeClass JSON
     * 
     * @param string|null $encrypted The encrypted assertion ID (optional, for per-badge issuer)
     * @param string $code The badge code
     * @param object $badge The badge object
     * @param string $title The course title
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded badge class
     */
    public static function getOb2Badge($encrypted, $code, $badge, $title, $frozen = null) {
        global $CFG;

        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $image = self::claimImage($badge, $code, $frozen, $encrypted);
        $badge_url = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'badge.json')
            : (self::courseScopedResource($encrypted, $badge, 'badge.json') ?? self::buildBadgeUrl($code));
        $issuer_url = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'issuer.json')
            : (self::courseScopedResource($encrypted, $badge, 'issuer.json') ?? self::buildIssuerUrl());
        $narrative = self::claimNarrative($badge, $title, $frozen);
        $criteria_id = self::useFrozen($frozen)
            ? self::frozenString($frozen, 'evidence_url', $CFG->apphome)
            : $CFG->apphome;
        
        $badge_class = array(
            "@context" => self::OB2_CONTEXT,
            "id" => $badge_url,
            "type" => "BadgeClass",
            "name" => $badge->title,
            "image" => $image,
            "description" => $narrative,
            "criteria" => array(
                "id" => $criteria_id,
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
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded issuer
     */
    public static function getOb2Issuer($encrypted = null, $code = null, $badge = null, $title = null, $frozen = null) {
        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $fields = self::issuerFields($frozen);
        $issuer_url = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'issuer.json')
            : (self::courseScopedResource($encrypted, $badge, 'issuer.json') ?? self::buildIssuerUrl());
        
        $issuer = array(
            "@context" => self::OB2_CONTEXT,
            "id" => $issuer_url,
            "type" => "Issuer",
            "name" => $fields['name'],
            "url" => $fields['url'],
            "email" => $fields['email']
        );
        
        // Add logo if configured (OB2 uses simple string URL)
        if ($fields['logo'] !== '') {
            $issuer["image"] = $fields['logo'];
        }
        
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
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded credential
     */
    public static function getOb3Assertion($encrypted, $date, $code, $badge, $title, $email, $frozen = null) {
        global $CFG;

        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $image = self::claimImage($badge, $code, $frozen, $encrypted);
        $recipient = 'sha256$' . hash('sha256', $email . $CFG->badge_assert_salt);
        $credential_id = $CFG->wwwroot . "/assertions/" . urlencode($encrypted) . ".vc.json";
        $achievement_id = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'achievement.json')
            : (self::courseScopedResource($encrypted, $badge, 'achievement.json') ?? self::buildBadgeUrlWithFormat($code, 'ob3'));
        $scoped_issuer = self::courseScopedResource($encrypted, $badge, 'issuer.json');
        $issuer_id = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'issuer.json') . '?format=ob3'
            : ($scoped_issuer !== null ? $scoped_issuer . '?format=ob3' : self::buildIssuerUrlWithFormat('ob3'));
        $evidence = self::useFrozen($frozen)
            ? self::frozenString($frozen, 'evidence_url', $CFG->apphome)
            : $CFG->apphome;
        $narrative = self::claimNarrative($badge, $title, $frozen);
        $issuer_fields = self::issuerFields($frozen);
        
        // Legacy OB1 assertion reference for traceability
        $legacy_assertion = $CFG->wwwroot . "/badges/assert.php?id=" . urlencode($encrypted);
        
        // VC 2.0 uses validFrom (not issuanceDate); evidence at credential level (not credentialSubject)
        $normalized_date = self::normalizeDateToZ($date);
        $credential = array(
            "@context" => array(
                self::OB3_CREDENTIALS_CONTEXT,
                self::OB3_OPENBADGES_CONTEXT
            ),
            "id" => $credential_id,
            "type" => array("VerifiableCredential", "OpenBadgeCredential"),
            "name" => $badge->title,
            "issuer" => array(
                "id" => $issuer_id,
                "type" => "Profile",
                "name" => self::useFrozen($frozen) ? $issuer_fields['name'] : $CFG->servicename,
                "url" => self::useFrozen($frozen) ? $issuer_fields['url'] : $CFG->apphome
            ),
            "validFrom" => $normalized_date,
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
                )
            ),
            "evidence" => array(
                array(
                    "id" => $evidence,
                    "type" => "Evidence",
                    "narrative" => $narrative
                )
            )
        );
        
        // Add legacy assertion reference in extensions
        if (isset($CFG->badge_include_legacy) && $CFG->badge_include_legacy) {
            $credential["credentialSubject"]["extensions"] = array(
                "legacyAssertion" => $legacy_assertion
            );
        }
        
        // Add DataIntegrityProof when OB3 signing is configured
        if (!empty($CFG->badge_ob3_secret_key) && !empty($CFG->badge_ob3_verification_method_id)) {
            $secretKey = base64_decode($CFG->badge_ob3_secret_key, true);
            if ($secretKey !== false && strlen($secretKey) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
                $proof = Ob3DataIntegrity::createProof(
                    $credential,
                    $CFG->badge_ob3_verification_method_id,
                    $secretKey
                );
                if ($proof !== null) {
                    $credential["proof"] = $proof;
                }
            }
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
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded achievement
     */
    public static function getOb3Achievement($encrypted, $code, $badge, $title, $frozen = null) {
        global $CFG;

        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $image = self::claimImage($badge, $code, $frozen, $encrypted);
        $achievement_id = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'achievement.json')
            : (self::courseScopedResource($encrypted, $badge, 'achievement.json') ?? self::buildBadgeUrlWithFormat($code, 'ob3'));
        $scoped_issuer = self::courseScopedResource($encrypted, $badge, 'issuer.json');
        $issuer_id = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'issuer.json') . '?format=ob3'
            : ($scoped_issuer !== null ? $scoped_issuer . '?format=ob3' : self::buildIssuerUrlWithFormat('ob3'));
        $narrative = self::claimNarrative($badge, $title, $frozen);
        $criteria_id = self::useFrozen($frozen)
            ? self::frozenString($frozen, 'evidence_url', $CFG->apphome)
            : $CFG->apphome;
        
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
                "id" => $criteria_id,
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
     * @param array<string,mixed>|null $frozen Published-badge snapshot. Null reads live config.
     * @return string JSON-encoded issuer
     */
    public static function getOb3Issuer($encrypted = null, $code = null, $badge = null, $title = null, $frozen = null) {
        global $CFG;

        $config_error = self::checkBadgeConfig(self::needsBadgeFiles($badge, $frozen));
        if ( $config_error !== false ) {
            return $config_error;
        }

        $fields = self::issuerFields($frozen);
        $scoped_issuer = self::courseScopedResource($encrypted, $badge, 'issuer.json');
        $issuer_id = self::useFrozen($frozen)
            ? self::assertionResourceUrl($frozen['guid'], 'issuer.json') . '?format=ob3'
            : ($scoped_issuer !== null ? $scoped_issuer . '?format=ob3' : self::buildIssuerUrlWithFormat('ob3'));
        
        $issuer = array(
            "@context" => array(
                self::OB3_CREDENTIALS_CONTEXT,
                self::OB3_OPENBADGES_CONTEXT
            ),
            "id" => $issuer_id,
            "type" => "Profile",
            "name" => $fields['name'],
            "url" => $fields['url'],
            "email" => $fields['email']
        );
        
        // Add logo if configured
        if ($fields['logo'] !== '') {
            $issuer["image"] = array(
                "id" => $fields['logo'],
                "type" => "Image"
            );
        }
        
        // Add LinkedIn URL if configured (as extension or additional property)
        if ($fields['linkedin_url'] !== '') {
            if (!isset($issuer["extensions"])) {
                $issuer["extensions"] = array();
            }
            $issuer["extensions"]["linkedIn"] = $fields['linkedin_url'];
        }
        
        // Add verificationMethod for OB3 proof verification when signing is configured
        if (!empty($CFG->badge_ob3_secret_key) && !empty($CFG->badge_ob3_verification_method_id)) {
            $secretKey = base64_decode($CFG->badge_ob3_secret_key, true);
            if ($secretKey !== false && strlen($secretKey) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
                $publicKey = Ob3DataIntegrity::secretKeyToPublicKey($secretKey);
                $publicKeyMultibase = Ob3DataIntegrity::publicKeyToMultibase($publicKey);
                if ($publicKeyMultibase !== '') {
                    $issuer["verificationMethod"] = array(
                        array(
                            "id" => $CFG->badge_ob3_verification_method_id,
                            "type" => "Multikey",
                            "controller" => $issuer_id,
                            "publicKeyMultibase" => $publicKeyMultibase
                        )
                    );
                }
            }
        }
        
        return json_encode($issuer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
