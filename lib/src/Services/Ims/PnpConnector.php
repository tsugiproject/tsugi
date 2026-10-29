<?php

namespace Tsugi\Services\Ims;

/**
 * Personal Needs and Preferences claim on LTI 1.3 launches.
 *
 * Same behavior as Sakai's lti.pnp.baseurl and lti.pnp.use_email (SAK-50682).
 * When a base URL is configured and the launch has a user id, the launch
 * carries afapnp-endpoint-service. The @ in that URL is the user id, or the
 * email when use_email is on and this launch releases an email.
 */
class PnpConnector {

    public const CLAIM = 'https://purl.imsglobal.org/spec/lti-afapnp/claim/afapnp-endpoint-service';

    public const SCOPE_PNP_READONLY = 'https://purl.imsglobal.org/spec/lti-afapnp/scope/afapnprecord.readonly';

    /**
     * Claim body, or null when this platform has no PNP base URL or no user id.
     *
     * @param string $userId Launch user id. Sakai substitutes LTI user_id.
     * @param string|null $email Launch email. Used only when lti_pnp_use_email is on and this is non-empty.
     * @return array{afapnp_endpoint_url:string, scopes:array<int, string>}|null
     */
    public static function launchClaim($userId, $email = null): ?array {
        $base = self::baseUrl();
        if ( $base === null || $userId === null || $userId === '' ) {
            return null;
        }
        $token = (string) $userId;
        if ( self::useEmail() && is_string($email) && $email !== '' ) {
            $token = $email;
        }
        return array(
            'afapnp_endpoint_url' => str_replace('@', $token, $base),
            'scopes' => array(self::SCOPE_PNP_READONLY),
        );
    }

    /**
     * $CFG->lti_pnp_baseurl. Empty and false leave the claim off.
     */
    private static function baseUrl(): ?string {
        global $CFG;
        if ( ! is_object($CFG) || ! isset($CFG->lti_pnp_baseurl) || ! is_string($CFG->lti_pnp_baseurl) ) {
            return null;
        }
        $base = trim($CFG->lti_pnp_baseurl);
        if ( $base === '' ) {
            return null;
        }
        return $base;
    }

    /**
     * $CFG->lti_pnp_use_email. Default false. Ignored when the base URL is empty.
     */
    private static function useEmail(): bool {
        global $CFG;
        if ( ! is_object($CFG) || ! isset($CFG->lti_pnp_use_email) ) {
            return false;
        }
        $value = $CFG->lti_pnp_use_email;
        if ( $value === true ) {
            return true;
        }
        return is_string($value) && strcasecmp($value, 'true') === 0;
    }
}
