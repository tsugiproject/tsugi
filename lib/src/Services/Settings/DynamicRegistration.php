<?php

namespace Tsugi\Services\Settings;

use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\SakaiCustom;
use Tsugi\Util\LTI;
use Tsugi\Util\U;
use Tsugi\Util\Net;
use Tsugi\Core\LTIX;

/**
 * LTI Advantage dynamic registration (LMS OpenID config + client registration).
 *
 * Used by Settings (instructor) and admin/key/auto.php (super user).
 * Caller must already have sent $OUTPUT->header() / bodyStart().
 */
class DynamicRegistration {

    /**
     * @param int|string|null $user_id 0 is the admin/super-user path
     * @param mixed $tsugi_key
     * @param mixed $unlock_code
     * @param mixed $openid_configuration
     * @param mixed $registration_token
     * @return string Empty string after HTML is printed
     */
    public static function run($user_id, $tsugi_key, $unlock_code, $openid_configuration, $registration_token) {
        global $OUTPUT, $CFG, $PDOX;

        if ( ! isset($user_id) ) {
            echo("<p><b>You are not logged in.</b></p>\n");
            $OUTPUT->footer();
            return '';
        }

        if ( ! isset($tsugi_key) || ! isset($unlock_code) || ! is_string($unlock_code) ) {
            echo("<p><b>Missing tsugi_key and/or unlock_code.</b></p>\n");
            $OUTPUT->footer();
            return '';
        }

        LTIX::getConnection();

        if ( $user_id == 0 ) {
            $row = $PDOX->rowDie(
                "SELECT key_title, key_key, unlock_code, user_id, plus, deploy_key,
                    lms_client, lms_oidc_auth, lms_keyset_url, lms_token_url
                FROM {$CFG->dbprefix}lti_key
                    WHERE key_id = :KID AND unlock_code = :UNLOCK",
                array(":KID" => $tsugi_key, ":UNLOCK" => $unlock_code)
            );
            if ( $row && isset($row['user_id']) ) $user_id = $row['user_id']+0;
        } else {
            $row = $PDOX->rowDie(
                "SELECT key_title, key_key, unlock_code, user_id, plus, deploy_key,
                    lms_client, lms_oidc_auth, lms_keyset_url, lms_token_url
                FROM {$CFG->dbprefix}lti_key
                    WHERE key_id = :KID AND user_id = :UID AND unlock_code = :UNLOCK",
                array(":KID" => $tsugi_key, ":UID" => $user_id, ":UNLOCK" => $unlock_code)
            );
        }

        if ( ! $row ) {
            echo("<pre>\n");
            echo "Could not load your key, perhaps you did not set the one-time unlock code\n";
            echo("</pre>\n");
            return '';
        }

        echo("<p><b>Retrieving OpenId configuration from LMS</b><br>\n");
        echo(htmlentities(self::redactSecrets($openid_configuration ?? ''))."\n</p>\n");

        // A . or .. segment is removed by the fetch, so the path that is
        // requested can leave the issuer prefix this check will apply later.
        if ( is_string($openid_configuration) && self::pathUsesDotSegment(parse_url($openid_configuration, PHP_URL_PATH)) ) {
            error_log('Dynamic registration: OpenID configuration URL uses a . or .. path segment');
            echo("<pre>\n");
            echo "OpenID configuration URL must not use . or .. path segments.\n";
            echo 'openid_configuration: ', htmlentities(self::redactSecrets($openid_configuration)), "\n";
            echo("</pre>\n");
            return '';
        }

        $response = Net::doGet($openid_configuration );
        $code = Net::getLastHttpResponse();
        if ( ! $response || U::strlen($response) < 1 ) {
            echo("<pre>\n");
            echo("Unable to retrieve:\n".htmlentities(self::redactSecrets($openid_configuration ?? ''))."\n");
            echo("Error code:".htmlentities((string)$code)."\n");
            echo("</pre>\n");
            return '';
        }

        $OUTPUT->togglePre("LMS-Provided OpenId Configuration", self::prettyJson($response));

        $platform_configuration = json_decode($response);
        if ( ! $platform_configuration || ! is_object($platform_configuration) ) {
            echo("<pre>\n");
            echo("Unable to parse JSON retrieved from:\n".htmlentities($openid_configuration??'')."\n\n");
            echo(htmlentities($response));
            echo("</pre>\n");
            return '';
        }

        $required_fields = [
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'jwks_uri',
            'registration_endpoint',
        ];

        $missing = [];
        $not_string = [];
        $invalid = [];
        $insecure = [];
        $values  = [];
        foreach ($required_fields as $field) {
            if ( ! isset($platform_configuration->$field) ) {
                $missing[] = $field;
                continue;
            }
            if ( ! is_string($platform_configuration->$field) ) {
                $not_string[] = $field;
                continue;
            }
            $value = trim($platform_configuration->$field);
            if ( $value === '' ) {
                $missing[] = $field;
                continue;
            }
            if ( ! filter_var($value, FILTER_VALIDATE_URL) ) {
                $invalid[] = $field;
                continue;
            }
            // LTI Dynamic Registration 1.0 section 2.1.1 requires https.
            // http is only for a local LMS on localhost or loopback.
            if ( ! self::allowsOpenIdEndpoint($value) ) {
                $insecure[] = $field;
                continue;
            }
            $values[$field] = $value;
        }

        if ( $missing || $not_string || $invalid || $insecure ) {
            $problems = [];
            if ( $missing ) { $problems[] = 'missing or empty: ' . implode(', ', $missing); }
            if ( $not_string ) { $problems[] = 'must be a URL string: ' . implode(', ', $not_string); }
            if ( $invalid ) { $problems[] = 'not a valid URL: ' . implode(', ', $invalid); }
            if ( $insecure ) { $problems[] = 'must use https (http is allowed only for localhost and loopback): ' . implode(', ', $insecure); }

            error_log('Dynamic registration: OpenID configuration field(s) ' . implode('; ', $problems));

            echo("<pre>\n");
            echo "OpenID configuration field(s) problem:\n" . htmlentities('  - ' . implode("\n  - ", $problems)) . "\n";
            echo("</pre>\n");
            return '';
        }

        $issuer = $values['issuer'];
        $authorization_endpoint = $values['authorization_endpoint'];
        $token_endpoint = $values['token_endpoint'];
        $jwks_uri = $values['jwks_uri'];
        $registration_endpoint = $values['registration_endpoint'];

        // LTI Dynamic Registration 1.0 sections 3.4 and 3.5.1. Stop before the
        // registration POST, and before this issuer is stored.
        $issuer_message = LTI::ascii255Message('Issuer', $issuer);
        if ( is_string($issuer_message) ) {
            error_log('Dynamic registration: '.$issuer_message);
            echo("<p><b>".htmlentities($issuer_message)."</b></p>\n");
            echo("<p>The key was not updated.</p>\n");
            $OUTPUT->footer();
            return '';
        }

        if ( ! self::openidConfigurationMatchesIssuer($openid_configuration, $issuer) ) {
            error_log('Dynamic registration: OpenID configuration URL is not under the declared issuer');
            echo("<pre>\n");
            echo "OpenID configuration URL must be the issuer, or a path under the issuer.\n";
            echo "The host must match exactly. A subdomain does not count.\n";
            echo 'openid_configuration: ', htmlentities(self::redactSecrets((string) $openid_configuration)), "\n";
            echo 'issuer: ', htmlentities(self::redactSecrets((string) $issuer)), "\n";
            echo("</pre>\n");
            return '';
        }

        $authorization_server = isset($platform_configuration->authorization_server) ? $platform_configuration->authorization_server : null;

        $json = new \stdClass();
        $tool = new \stdClass();

        $json->application_type = "web";
        $json->response_types = array("id_token");
        $json->grant_types = array("implicit", "client_credentials");
        $json->initiate_login_uri = $CFG->wwwroot . '/lti/oidc_login/' . urlencode((string)$tsugi_key);
        $json->redirect_uris = array($CFG->wwwroot . '/lti/oidc_launch');
        if ( isset($CFG->servicename) && $CFG->servicename ) {
            $json->client_name = $CFG->servicename;
        }
        $json->jwks_uri = $CFG->wwwroot . '/lti/keyset/';
        if ( isset($CFG->privacy_url) && $CFG->privacy_url ) {
            $json->policy_uri = $CFG->privacy_url;
        }
        if ( isset($CFG->sla_url) && $CFG->sla_url ) {
            $json->tos_uri = $CFG->sla_url;
        }
        $json->token_endpoint_auth_method = "private_key_jwt";

        if ( isset($CFG->owneremail) && $CFG->owneremail ) {
            $json->contacts = array($CFG->owneremail);
            $contact = new \stdClass();
            $contact->email = $CFG->owneremail;
            if ( isset($CFG->ownername) && $CFG->ownername ) $contact->display_name = $CFG->ownername;
            $tool->better_contacts = array($contact);
        }

        $json->scope = implode(' ', ToolRegistrationDocument::scopeVocabulary());

        $tool->product_family_code = "tsugi.org";
        $tool->target_link_uri = $CFG->wwwroot . '/lti/store/';

        // Determine the tool domain for LTI 1.3 registration.
        $tool_domain = '';
        if ( !empty($CFG->apphome) && is_string($CFG->apphome) ) {
            $tool_domain = parse_url($CFG->apphome, PHP_URL_HOST) ?: '';
        }

        if ( empty($tool_domain) && !empty($CFG->wwwroot) && is_string($CFG->wwwroot) ) {
            $tool_domain = parse_url($CFG->wwwroot, PHP_URL_HOST) ?: '';
        }

        if ( empty($tool_domain) ) {
            error_log('LTI13: unable to determine tool registration domain from apphome or wwwroot');

            echo("<pre>\n");
            echo "Unable to determine LTI tool registration domain from apphome or wwwroot.\n";
            echo("</pre>\n");
            return '';
        }

        $tool->domain = strtolower($tool_domain);

        if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
            $tool->description = $CFG->servicedesc;
        }

        $tool->claims = array( "iss", "sub", "name", "given_name", "family_name", "email" );

        $custom_parameters = array_merge(
            array("tsugi_from" => "www.tsugi.org"),
            SakaiCustom::deepLinkCustom(false)
        );
        $privacy_launch = $CFG->wwwroot;
        if ( isset($CFG->privacy_url) && is_string($CFG->privacy_url) && $CFG->privacy_url !== '' ) {
            $privacy_launch = $CFG->privacy_url;
        }
        $tool->messages = array(
            array(
                "type" => "LtiDeepLinkingRequest",
                "label" => $json->client_name . ' (' . __('Deep Link') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/',
                // One deep link opens the store from a module or an assignment.
                // ContentArea and link_selection are the module picker. Canvas
                // treats ContentArea as link_selection and keeps the first.
                // assignment_selection is the assignment picker. lessons is the
                // Sakai content area and stays on this same store URL.
                "placements" => array("ContentArea", "link_selection", "assignment_selection", "lessons"),
                "custom_parameters" => $custom_parameters,
            ),
            array(
                "type" => "LtiDataPrivacyLaunchRequest",
                "label" => $json->client_name . ' (' . __('Privacy') . ')',
                "target_link_uri" => $privacy_launch,
                "custom_parameters" => $custom_parameters,
            ),
        );
        if ( (int) ($row['plus'] ?? 0) === 1 ) {
            // Course navigation uses the tool name. Canvas shows client_name
            // under Apps and Admin. The message label is the course menu text.
            $plusLabel = isset($json->client_name) && is_string($json->client_name) ? trim($json->client_name) : '';
            if ( $plusLabel === '' ) {
                $plusLabel = __('Tsugi Plus');
            }
            $tool->messages[] = array(
                "type" => "LtiResourceLinkRequest",
                "label" => $plusLabel,
                "target_link_uri" => $CFG->wwwroot . '/plus',
                // Course navigation launches a resource link. The module picker
                // is a deep link (link_selection, ContentArea, and the rest).
                // new_window matches the Canvas Plus developer-key document:
                // Plus keeps its session in a cookie and does not start inside
                // the course-navigation iframe.
                "placements" => array("course_navigation"),
                "https://canvas.instructure.com/lti/display_type" => "new_window",
                // Canvas course navigation is visible unless this is false.
                // False checks "Hide by default" on the registration screen.
                "https://canvas.instructure.com/lti/course_navigation/default_enabled" => false,
                "custom_parameters" => $custom_parameters,
            );
        }
        $tool->messages = array_merge($tool->messages, array(
            array(
                "type" => "LtiDeepLinkingRequest",
                "label" => $json->client_name . ' (' . __('Migration Selection') . ')',
                "target_link_uri" => $CFG->wwwroot . '/cc/export',
                "placements" => array( "migration_selection"),
                "custom_parameters" => $custom_parameters,
            ),
            array(
                "type" => "LtiDeepLinkingRequest",
                "label" => $json->client_name . ' (' . __('Editor Button') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=editor_button',
                // RichTextEditor is the standard name and comes first.
                // editor_button is Canvas. content_editor is Tsugi/Sakai.
                // All three go out. Import into Tsugi keeps RichTextEditor only.
                "placements" => array("RichTextEditor", "editor_button", "content_editor"),
                "custom_parameters" => $custom_parameters,
            ),
            array(
                "type" => "LtiDeepLinkingRequest",
                "label" => $json->client_name . ' (' . __('Deep Link') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/',
                "custom_parameters" => $custom_parameters,
            ),
        ));
        // A platform that publishes messages_supported keeps only those types.
        // When it also lists placements, only advertised placements are sent.
        // A platform that omits the list receives every message.
        // Sakai keeps one launch, then the privacy message. Plus is a resource
        // link. Otherwise the launch is the store deep link.
        $tool->messages = self::messagesForPlatform($tool->messages, $platform_configuration);
        if ( self::isSakai($platform_configuration) && isset($tool->messages[0]['target_link_uri']) && is_string($tool->messages[0]['target_link_uri']) ) {
            $tool->target_link_uri = $tool->messages[0]['target_link_uri'];
        }

        $json->{"https://purl.imsglobal.org/spec/lti-tool-configuration"} = $tool;

        $body = json_encode($json, JSON_PRETTY_PRINT);

        $method = "POST";
        $header = "Content-type: application/json;" ;
        if ( is_string($registration_token) && strlen($registration_token) > 0 ) {
            $header = $header . "\n" .
                    "Authorization: Bearer ".$registration_token;
        }
        $url = $registration_endpoint;

        echo("\n<p><b>Sending registration to LMS</b><br>\n");
        echo(htmlentities($url)."\n</p>\n");
        $OUTPUT->togglePre("Registration data sent to LMS", self::prettyJson($body));

        $response = Net::doBody($url, $method, $body, $header);

        $response_code = Net::getLastHttpResponse();
        $curl_error = Net::getLastCurlError();
        $transfer_failed = is_string($curl_error) && $curl_error !== '';

        // A dropped connection can still leave a 201 in the status line.
        if ( $transfer_failed || ( $response_code != 200 && $response_code != 201 ) ) {
            // Redact the whole LMS response before the log truncates it.
            $report = self::registrationFailureReport(
                $response_code,
                is_string($response) ? $response : '',
                Net::getLastHeadersReceived(),
                $curl_error,
                $registration_token
            );

            error_log('LTI13: dynamic registration failed: '.substr($report, 0, 2000));

            echo("<pre>\n");
            echo(htmlentities($report, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            echo("</pre>\n");
            if ( $transfer_failed && ( $response_code == 200 || $response_code == 201 ) ) {
                echo("<p>The LMS returned a success status but the response did not finish transferring. Check the LMS before trying again. It may already have created the client.</p>\n");
            }
            echo("<p>The one-time unlock code is still set, so you can try this registration again.</p>\n");
            return '';
        }

        $resp = is_string($response) ? json_decode($response) : null;
        $shown_response = self::redactSecrets(
            self::prettyJson(is_string($response) ? $response : ''),
            $registration_token
        );
        $OUTPUT->togglePre("Registration response from LMS", $shown_response);
        if ( ! $resp || ! is_object($resp) ) {
            echo("Unable to parse JSON retrieved from:\n".htmlentities($registration_endpoint)."\n\n");
            echo(htmlentities($shown_response));
            echo("</pre>\n");
            return '';
        }

        if ( !isset($resp->client_id) ) {
            echo("Did not find client_id in response\n");
            echo htmlentities($shown_response, ENT_QUOTES, 'UTF-8');
            echo("</pre>\n");
            return '';
        }

        $client_id = $resp->client_id;

        // Sakai puts deployment_id inside the tool configuration. Canvas puts it
        // on the registration response itself.
        $deployment_id = self::deploymentIdFromResponse($resp);

        $identity_message = LTI::ascii255First(array(
            'Issuer' => $issuer,
            'Client id' => $client_id,
            'Deployment id' => $deployment_id,
        ));
        if ( is_string($identity_message) ) {
            error_log('Dynamic registration: '.$identity_message);
            echo("<p><b>".htmlentities($identity_message)."</b></p>\n");
            echo("<p>The key was not updated. The one-time unlock code is still set, so you can try this registration again.</p>\n");
            $OUTPUT->footer();
            return '';
        }

        // The LMS has created the client. A second POST with the same unlock
        // would register the tool again.
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_key SET unlock_code = NULL WHERE key_id = :KID",
            array(":KID" => $tsugi_key)
        );

        // A missing deployment id leaves the stored deploy_key unchanged.
        $deploy_for_check = $deployment_id ? $deployment_id : U::get($row, 'deploy_key');
        if ( ! \Tsugi\Services\Admin\AdminService::validate_issuer_client_unique($issuer, $client_id, $deploy_for_check, $tsugi_key) ) {
            $message = isset($_SESSION[U::FLASH_ERROR]) ? $_SESSION[U::FLASH_ERROR] : 'This issuer, client id, and deployment id are already in use';
            unset($_SESSION[U::FLASH_ERROR]);
            error_log($message);
            echo("<p><b>".htmlentities($message)."</b></p>\n");
            echo("<p>The key was not updated.</p>\n");
            $OUTPUT->footer();
            return '';
        }

        $issuer_sha256 = hash('sha256', trim($issuer));

        $sql = "UPDATE {$CFG->dbprefix}lti_key SET
                lms_issuer = :lms_issuer,
                lms_issuer_sha256 = :lms_issuer_sha256,
                lms_client = :lms_client,
                lms_oidc_auth = :lms_oidc_auth,
                lms_keyset_url = :lms_keyset_url,
                lms_token_url = :lms_token_url,
                lms_token_audience = :lms_token_audience
            WHERE key_id = :ID AND (user_id = :UID OR :UID = 0 )
        ";

        $values = array(
            ":ID" => $tsugi_key,
            ":UID" => $user_id,
            ":lms_issuer" => $issuer,
            ":lms_issuer_sha256" => $issuer_sha256,
            ":lms_client" => $client_id,
            ":lms_oidc_auth" => $authorization_endpoint,
            ":lms_keyset_url" => $jwks_uri,
            ":lms_token_url" => $token_endpoint,
            ":lms_token_audience" => $authorization_server,
        );

        $stmt = $PDOX->queryReturnError($sql, $values);
        if ( ! $stmt->success ) {
            echo("Unable to store LMS platform configuration on the key\n");
            return '';
        }

        error_log("Stored issuer $issuer / $client_id in lti_key table");

        if ( $deployment_id ) {
            $deployment_sha256 = hash('sha256', trim($deployment_id));
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_key SET deploy_key = :DID, deploy_sha256 = :D256
                    WHERE key_id = :KID AND (user_id = :UID OR :UID = 0 )",
                array(":DID" => $deployment_id, ":D256" => $deployment_sha256, ":KID" => $tsugi_key, ":UID" => $user_id)
            );

            if ( ! $stmt->success ) {
                echo("Unable to update key entry to set deployment_id\n");
                return '';
            }
        }

        echo('<button onclick="(window.opener || window.parent).postMessage({subject:\'org.imsglobal.lti.close\'}, \'*\')">Continue Registration in the LMS</button>'."\n");
        $OUTPUT->footer();
        return '';
    }

    /**
     * Deployment id from a dynamic-registration response.
     *
     * The tool configuration is checked first. Canvas returns the id beside
     * that object, as deployment_id on the response.
     *
     * @param object|null $response
     * @return string|null
     */
    public static function deploymentIdFromResponse($response) {
        if ( ! is_object($response) ) {
            return null;
        }
        $tc_key = 'https://purl.imsglobal.org/spec/lti-tool-configuration';
        if ( isset($response->{$tc_key}) && is_object($response->{$tc_key}) ) {
            $from_tool = self::deploymentIdValue($response->{$tc_key}->deployment_id ?? null);
            if ( $from_tool !== null ) {
                return $from_tool;
            }
        }
        return self::deploymentIdValue($response->deployment_id ?? null);
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function deploymentIdValue($value) {
        if ( is_int($value) || is_float($value) ) {
            $value = (string) $value;
        }
        if ( ! is_string($value) ) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    /**
     * Text for a failed registration POST. The body is the LMS response.
     *
     * Secrets are removed from the cURL error, headers, and body before any
     * truncation, so a cut cannot leave a token behind without its marker.
     *
     * @param mixed $status
     * @param mixed $body
     * @param mixed $headers
     * @param mixed $curl_error
     * @param mixed $token Registration token to remove wherever the LMS echoed it
     * @return string
     */
    public static function registrationFailureReport($status, $body, $headers, $curl_error = false, $token = null) {
        $curl_text = (is_string($curl_error) && $curl_error !== '') ? self::redactSecrets($curl_error, $token) : '(none)';
        $report = "cURL error:\n".$curl_text."\n\n";
        $report .= "Http response code: ".(string) $status."\n\n";
        $report .= "Response headers:\n";
        $header_text = is_string($headers) ? trim(self::redactSecrets($headers, $token)) : '';
        $report .= ($header_text === '' ? "(none)" : $header_text)."\n\n";
        $report .= "Response body:\n";
        // Pretty-print first so a JSON \/ becomes / before the token is removed.
        $shown = is_string($body) ? self::redactSecrets(self::prettyJson($body), $token) : '';
        if ( ! is_string($shown) || $shown === '' ) {
            $report .= "(empty)\n";
        } else {
            $limit = 20000;
            if ( strlen($shown) > $limit ) {
                $report .= substr($shown, 0, $limit)."\n\n(truncated)\n";
            } else {
                $report .= $shown."\n";
            }
        }
        return $report;
    }

    /**
     * https is required for an OpenID endpoint.
     *
     * LTI Dynamic Registration 1.0 section 2.1.1 requires an https issuer.
     * http is allowed only for localhost, 127.0.0.1, or ::1, so a local LMS
     * can still be registered.
     *
     * @param string $url
     * @return bool
     */
    public static function allowsOpenIdEndpoint($url) {
        if ( ! is_string($url) || $url === '' ) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ( $scheme === 'https' ) {
            return true;
        }
        if ( $scheme !== 'http' ) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = trim($host, '[]');
        return $host === 'localhost' || $host === '127.0.0.1' || $host === '::1';
    }

    /**
     * The configuration URL must be the issuer, or a path concatenated onto it.
     *
     * LTI Dynamic Registration 1.0 section 3.4. Query parameters are allowed.
     * A fragment is not. The host must match exactly, so a subdomain of the
     * issuer is rejected. Section 3.5.1 requires this check before registration,
     * to guard against impersonation.
     * Dot segments are removed first. A fetch resolves /tenant/../outside to
     * /outside, and that path is what the prefix is compared to.
     * An empty segment stays, so /tenant//blue is not /tenant/blue.
     * A percent-encoded reserved character stays encoded. RFC 3986 section
     * 6.2.2.2 does not treat %3B as the same character as ";".
     * https://www.imsglobal.org/spec/lti-dr/v1p0
     *
     * @param mixed $configuration_url
     * @param mixed $issuer
     * @return bool
     */
    public static function openidConfigurationMatchesIssuer($configuration_url, $issuer) {
        if ( ! is_string($configuration_url) || ! is_string($issuer) ) {
            return false;
        }
        $config = parse_url($configuration_url);
        $iss = parse_url($issuer);
        if ( ! is_array($config) || ! is_array($iss) ) {
            return false;
        }
        if ( isset($config['fragment']) || isset($iss['fragment']) || isset($iss['query']) ) {
            return false;
        }
        $config_scheme = strtolower((string) ($config['scheme'] ?? ''));
        $issuer_scheme = strtolower((string) ($iss['scheme'] ?? ''));
        if ( $config_scheme === '' || $config_scheme !== $issuer_scheme ) {
            return false;
        }
        $config_host = strtolower(trim((string) ($config['host'] ?? ''), '[]'));
        $issuer_host = strtolower(trim((string) ($iss['host'] ?? ''), '[]'));
        if ( $config_host === '' || $config_host !== $issuer_host ) {
            return false;
        }
        if ( self::urlPort($config, $config_scheme) !== self::urlPort($iss, $issuer_scheme) ) {
            return false;
        }
        $issuer_path = self::resolvedPath((string) ($iss['path'] ?? ''));
        $config_path = self::resolvedPath((string) ($config['path'] ?? ''));
        if ( $issuer_path === false || $config_path === false ) {
            return false;
        }
        if ( $issuer_path === '' ) {
            return true;
        }
        return $config_path === $issuer_path || str_starts_with($config_path, $issuer_path.'/');
    }

    /**
     * @param array<string, mixed> $parts
     * @param string $scheme
     * @return int
     */
    private static function urlPort($parts, $scheme) {
        if ( isset($parts['port']) ) {
            return (int) $parts['port'];
        }
        if ( $scheme === 'https' ) {
            return 443;
        }
        if ( $scheme === 'http' ) {
            return 80;
        }
        return 0;
    }

    /**
     * Path after . and .. segments are removed, including a once-decoded
     * segment such as %2e%2e. Interior empty segments are kept. A trailing
     * slash is not. False when the path climbs above the root or a segment
     * hides a slash.
     *
     * @param string|null $path
     * @return string|false
     */
    private static function resolvedPath($path) {
        if ( ! is_string($path) || $path === '' || $path === '/' ) {
            return '';
        }
        $absolute = str_starts_with($path, '/');
        $out = array();
        $first = true;
        foreach ( explode('/', $path) as $segment ) {
            if ( $first ) {
                $first = false;
                if ( $segment === '' && $absolute ) {
                    continue;
                }
            }
            if ( $segment === '' ) {
                $out[] = '';
                continue;
            }
            $decoded = rawurldecode($segment);
            if ( $segment === '.' || $decoded === '.' ) {
                continue;
            }
            if ( str_contains($decoded, '/') || str_contains($decoded, '\\') ) {
                return false;
            }
            if ( $segment === '..' || $decoded === '..' ) {
                if ( count($out) === 0 ) {
                    return false;
                }
                array_pop($out);
                continue;
            }
            $out[] = self::comparisonSegment($segment);
        }
        while ( count($out) > 0 && $out[count($out) - 1] === '' ) {
            array_pop($out);
        }
        if ( count($out) === 0 ) {
            return '';
        }
        return '/'.implode('/', $out);
    }

    /**
     * Decode percent-encoded unreserved characters. Leave a reserved
     * escape, such as %3B, in the path so it does not compare equal to ";".
     *
     * @param string $segment
     * @return string
     */
    private static function comparisonSegment($segment) {
        $out = '';
        $length = strlen($segment);
        for ( $i = 0; $i < $length; $i++ ) {
            $hex = substr($segment, $i + 1, 2);
            if ( $segment[$i] === '%' && strlen($hex) === 2 && ctype_xdigit($hex) ) {
                $hex = strtoupper($hex);
                $char = chr(hexdec($hex));
                if ( preg_match('/^[A-Za-z0-9._~-]$/', $char) === 1 ) {
                    $out .= $char;
                } else {
                    $out .= '%'.$hex;
                }
                $i += 2;
                continue;
            }
            $out .= $segment[$i];
        }
        return $out;
    }

    /**
     * @param string|null|false $path
     * @return bool
     */
    private static function pathUsesDotSegment($path) {
        if ( ! is_string($path) || $path === '' ) {
            return false;
        }
        foreach ( explode('/', $path) as $segment ) {
            if ( $segment === '' ) {
                continue;
            }
            $decoded = rawurldecode($segment);
            if ( $segment === '.' || $segment === '..' || $decoded === '.' || $decoded === '..' ) {
                return true;
            }
            if ( str_contains($decoded, '/') || str_contains($decoded, '\\') ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hide a registration token or bearer token in text shown to the browser.
     *
     * The known token is removed first, before any caller truncates the text.
     * A token shorter than 8 characters is left alone so a tiny value cannot
     * wipe ordinary words out of the error.
     *
     * @param mixed $text
     * @param mixed $token
     * @return string
     */
    public static function redactSecrets($text, $token = null) {
        if ( ! is_string($text) || $text === '' ) {
            return '';
        }
        if ( is_string($token) && strlen($token) >= 8 && strpos($text, $token) !== false ) {
            $text = str_replace($token, '[redacted]', $text);
        }
        $text = preg_replace('/(registration_token=)[^&\s]*/i', '$1[redacted]', $text);
        $text = preg_replace('/("registration_token"\s*:\s*")[^"]*/i', '$1[redacted]', $text);
        $text = preg_replace("/('registration_token'\\s*:\\s*')[^']*/i", '$1[redacted]', $text);
        $text = preg_replace('/(Authorization:\s*Bearer\s+)\S+/i', '$1[redacted]', $text);
        $text = preg_replace('/("Authorization"\s*:\s*"Bearer\s+)[^"]*/i', '$1[redacted]', $text);
        $text = preg_replace('/("registration_access_token"\s*:\s*")[^"]*/i', '$1[redacted]', $text);
        $text = preg_replace('/("client_secret"\s*:\s*")[^"]*/i', '$1[redacted]', $text);
        return is_string($text) ? $text : '';
    }

    /**
     * Sakai's product family is sakai or sakailms.org.
     *
     * @param object|null $platform
     * @return bool
     */
    private static function isSakai($platform) {
        $family = self::platformFamily($platform);
        if ( $family === '' ) {
            return false;
        }
        $family = strtolower($family);
        return $family === 'sakai' || strpos($family, 'sakai') === 0;
    }

    /**
     * @param object|null $platform
     * @return string
     */
    private static function platformFamily($platform) {
        if ( ! is_object($platform) ) {
            return '';
        }
        $key = 'https://purl.imsglobal.org/spec/lti-platform-configuration';
        if ( ! isset($platform->{$key}) ) {
            return '';
        }
        $block = $platform->{$key};
        $family = '';
        if ( is_object($block) && isset($block->product_family_code) && is_string($block->product_family_code) ) {
            $family = $block->product_family_code;
        } elseif ( is_array($block) && isset($block['product_family_code']) && is_string($block['product_family_code']) ) {
            $family = $block['product_family_code'];
        }
        return trim($family);
    }

    /**
     * One launch, then privacy. Plus is the resource link. Otherwise the
     * launch is the store deep link. Privacy stays second so Sakai keeps
     * the launch URL.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, array<string, mixed>>
     */
    private static function sakaiMessages(array $messages) {
        $launch = null;
        foreach ( $messages as $message ) {
            if ( ! is_array($message) || ($message['type'] ?? '') !== 'LtiResourceLinkRequest' ) {
                continue;
            }
            $target = $message['target_link_uri'] ?? '';
            if ( ! is_string($target) || $target === '' ) {
                continue;
            }
            $launch = self::sakaiLaunch($message);
            break;
        }
        if ( $launch === null ) {
            foreach ( $messages as $message ) {
                if ( ! is_array($message) || ($message['type'] ?? '') !== 'LtiDeepLinkingRequest' ) {
                    continue;
                }
                $target = $message['target_link_uri'] ?? '';
                if ( ! is_string($target) || ! self::isStoreTarget($target) ) {
                    continue;
                }
                $launch = self::sakaiLaunch($message);
                break;
            }
        }
        $out = array();
        if ( $launch !== null ) {
            $out[] = $launch;
        }
        foreach ( $messages as $message ) {
            if ( ! is_array($message) || ($message['type'] ?? '') !== 'LtiDataPrivacyLaunchRequest' ) {
                continue;
            }
            $target = $message['target_link_uri'] ?? '';
            if ( ! is_string($target) || $target === '' ) {
                continue;
            }
            $out[] = self::sakaiLaunch($message);
            break;
        }
        return $out;
    }

    /**
     * The store picker, not a placement-specific query on that URL.
     *
     * @param string $target
     * @return bool
     */
    private static function isStoreTarget($target) {
        $parts = parse_url($target);
        if ( ! is_array($parts) || (isset($parts['query']) && $parts['query'] !== '') ) {
            return false;
        }
        $path = isset($parts['path']) && is_string($parts['path']) ? rtrim($parts['path'], '/') : '';
        return $path === '/lti/store' || substr($path, -6) === '/store';
    }

    /**
     * One launch. Placements and Canvas extensions stay off this message.
     *
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    private static function sakaiLaunch(array $message) {
        $out = array(
            'type' => $message['type'],
            'target_link_uri' => $message['target_link_uri'],
        );
        if ( isset($message['label']) ) {
            $out['label'] = $message['label'];
        }
        if ( isset($message['custom_parameters']) && is_array($message['custom_parameters']) ) {
            $out['custom_parameters'] = $message['custom_parameters'];
        }
        return $out;
    }

    /**
     * Keep message types and placements the platform advertised.
     * A platform that does not publish messages_supported is left unchanged.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param object|null $platform OpenID configuration
     * @return array<int, array<string, mixed>>
     */
    public static function messagesForPlatform(array $messages, $platform) {
        if ( self::isSakai($platform) ) {
            return self::sakaiMessages($messages);
        }
        $rules = self::messagesSupported($platform);
        if ( $rules === null ) {
            return $messages;
        }
        $out = array();
        foreach ( $messages as $message ) {
            if ( ! is_array($message) ) {
                continue;
            }
            $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';
            if ( ! array_key_exists($type, $rules) ) {
                continue;
            }
            $advertised = $rules[$type];
            if ( is_array($advertised) && isset($message['placements']) && is_array($message['placements']) ) {
                $kept = array();
                foreach ( $message['placements'] as $placement ) {
                    if ( ! is_string($placement) || $placement === '' ) {
                        continue;
                    }
                    if ( self::placementAdvertised($placement, $advertised) ) {
                        $kept[] = $placement;
                    }
                }
                // An empty placements array is a link_selection launch on Canvas,
                // which is a deep-link request. Drop a deep link that lost
                // every placement. A resource link such as Tsugi Plus stays,
                // without placements, so an LMS that does not advertise
                // course_navigation can still place it.
                if ( count($kept) === 0 ) {
                    if ( $type === 'LtiDeepLinkingRequest' ) {
                        continue;
                    }
                    unset($message['placements']);
                } else {
                    $message['placements'] = array_values($kept);
                }
            }
            $out[] = $message;
        }
        return self::omitRedundantMessages($out, $rules);
    }

    /**
     * Drop an exact copy, and a placement-less message whose target is already
     * offered by a placed message of the same type. A placement-less message
     * with its own target, such as Tsugi Plus, stays.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, array<string, true>|null> $rules
     * @return array<int, array<string, mixed>>
     */
    private static function omitRedundantMessages(array $messages, array $rules) {
        $placed_targets = array();
        foreach ( $messages as $message ) {
            if ( ! isset($message['placements']) || ! is_array($message['placements']) || count($message['placements']) === 0 ) {
                continue;
            }
            $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';
            $target = isset($message['target_link_uri']) && is_string($message['target_link_uri']) ? $message['target_link_uri'] : '';
            if ( $type === '' || $target === '' || ! is_array($rules[$type] ?? null) ) {
                continue;
            }
            $placed_targets[$type."\n".$target] = true;
        }
        $out = array();
        $seen = array();
        foreach ( $messages as $message ) {
            $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';
            $target = isset($message['target_link_uri']) && is_string($message['target_link_uri']) ? $message['target_link_uri'] : '';
            $placements = (isset($message['placements']) && is_array($message['placements'])) ? array_values($message['placements']) : null;
            $identity = $type."\n".$target."\n".json_encode($placements);
            if ( isset($seen[$identity]) ) {
                continue;
            }
            $seen[$identity] = true;
            $unplaced = $placements === null || count($placements) === 0;
            if ( $unplaced && $target !== '' && isset($placed_targets[$type."\n".$target]) ) {
                continue;
            }
            $out[] = $message;
        }
        return $out;
    }

    /**
     * A short name matches an advertised URL whose path ends in that name.
     * The name we send is left as it is.
     *
     * @param string $placement
     * @param array<string, true> $advertised
     * @return bool
     */
    private static function placementAdvertised($placement, array $advertised) {
        if ( isset($advertised[$placement]) ) {
            return true;
        }
        $leaf = self::placementLeaf($placement);
        if ( $leaf === '' ) {
            return false;
        }
        foreach ( array_keys($advertised) as $name ) {
            if ( ! is_string($name) ) {
                continue;
            }
            if ( self::placementLeaf($name) === $leaf ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return string
     */
    private static function placementLeaf($name) {
        if ( strpos($name, '://') === false ) {
            return $name;
        }
        $path = parse_url($name, PHP_URL_PATH);
        if ( ! is_string($path) || $path === '' || $path === '/' ) {
            return '';
        }
        $parts = explode('/', trim($path, '/'));
        $last = end($parts);
        return is_string($last) ? $last : '';
    }

    /**
     * Null when the platform did not publish messages_supported.
     *
     * @param object|null $platform
     * @return array<string, array<string, true>|null>|null
     */
    private static function messagesSupported($platform) {
        if ( ! is_object($platform) ) {
            return null;
        }
        $key = 'https://purl.imsglobal.org/spec/lti-platform-configuration';
        if ( ! isset($platform->{$key}) ) {
            return null;
        }
        $block = $platform->{$key};
        $rows = null;
        if ( is_object($block) && isset($block->messages_supported) && is_array($block->messages_supported) ) {
            $rows = $block->messages_supported;
        } elseif ( is_array($block) && isset($block['messages_supported']) && is_array($block['messages_supported']) ) {
            $rows = $block['messages_supported'];
        }
        if ( $rows === null ) {
            return null;
        }
        $listed = array();
        foreach ( $rows as $row ) {
            $type = '';
            $placements = null;
            if ( is_object($row) ) {
                $type = isset($row->type) && is_string($row->type) ? $row->type : '';
                if ( isset($row->placements) && is_array($row->placements) ) {
                    $placements = $row->placements;
                }
            } elseif ( is_array($row) ) {
                $type = isset($row['type']) && is_string($row['type']) ? $row['type'] : '';
                if ( isset($row['placements']) && is_array($row['placements']) ) {
                    $placements = $row['placements'];
                }
            }
            if ( $type === '' ) {
                continue;
            }
            if ( $placements === null ) {
                $listed[$type] = null;
                continue;
            }
            $set = array();
            foreach ( $placements as $name ) {
                if ( is_string($name) && $name !== '' ) {
                    $set[$name] = true;
                }
            }
            $listed[$type] = $set;
        }
        return $listed;
    }

    /**
     * Indent JSON for display. Other text is left as it was retrieved.
     *
     * @param mixed $raw
     * @return mixed
     */
    private static function prettyJson($raw) {
        if ( ! is_string($raw) ) {
            return $raw;
        }
        $decoded = json_decode($raw);
        if ( json_last_error() !== JSON_ERROR_NONE || $decoded === null ) {
            return $raw;
        }
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($pretty) ? $pretty : $raw;
    }
}
