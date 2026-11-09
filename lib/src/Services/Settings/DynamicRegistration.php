<?php

namespace Tsugi\Services\Settings;

use Tsugi\Services\Outbound\ToolRegistrationDocument;
use Tsugi\Util\SakaiCustom;
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

        try {
            $issuer = $platform_configuration->issuer;
            $authorization_endpoint = $platform_configuration->authorization_endpoint;
            $token_endpoint = $platform_configuration->token_endpoint;
            $jwks_uri = $platform_configuration->jwks_uri;
            $registration_endpoint = $platform_configuration->registration_endpoint;
        } catch (\Exception $e) {
            echo("<pre>\n");
            echo 'Missing required value: ',  htmlentities($e->getMessage()), "\n";
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

        $pieces = parse_url(is_string($CFG->apphome) ? $CFG->apphome : $CFG->apphome);
        if ( U::get($pieces, 'host') ) $tool->domain = U::get($pieces, 'host');

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
        $tool->messages = self::messagesForPlatform($tool->messages, $platform_configuration);

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

        $response = Net::bodyCurl($url, $method, $body, $header);

        $response_code = Net::getLastHttpResponse();

        if ( $response_code != 200 && $response_code != 201 ) {
            echo("<pre>\n");
            echo(htmlentities(self::registrationFailureReport(
                $response_code,
                is_string($response) ? $response : '',
                Net::getLastHeadersReceived()
            )));
            echo("</pre>\n");
            echo("<p>The one-time unlock code is still set, so you can try this registration again.</p>\n");
            return '';
        }

        $resp = is_string($response) ? json_decode($response) : null;
        $OUTPUT->togglePre("Registration response from LMS", self::prettyJson($response));
        if ( ! $resp || ! is_object($resp) ) {
            echo("Unable to parse JSON retrieved from:\n".htmlentities($registration_endpoint)."\n\n");
            echo(htmlentities($response));
            echo("</pre>\n");
            return '';
        }

        if ( !isset($resp->client_id) ) {
            echo("Did not find client_id in response\n");
            echo htmlentities(print_r($resp, true), ENT_QUOTES, 'UTF-8');
            echo("</pre>\n");
            return '';
        }

        $client_id = $resp->client_id;

        // The LMS has created the client. A second POST with the same unlock
        // would register the tool again.
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_key SET unlock_code = NULL WHERE key_id = :KID",
            array(":KID" => $tsugi_key)
        );

        // Sakai puts deployment_id inside the tool configuration. Canvas puts it
        // on the registration response itself.
        $deployment_id = self::deploymentIdFromResponse($resp);

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
     * @param mixed $status
     * @param mixed $body
     * @param mixed $headers
     * @return string
     */
    public static function registrationFailureReport($status, $body, $headers) {
        $report = "Http response code: ".(string) $status."\n\n";
        $report .= "Response headers:\n";
        $header_text = is_string($headers) ? trim(self::redactSecrets($headers)) : '';
        $report .= ($header_text === '' ? "(none)" : $header_text)."\n\n";
        $report .= "Response body:\n";
        $shown = is_string($body) ? self::prettyJson(self::redactSecrets($body)) : '';
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
     * Hide a registration token or bearer token in text shown to the browser.
     *
     * @param mixed $text
     * @return string
     */
    public static function redactSecrets($text) {
        if ( ! is_string($text) || $text === '' ) {
            return '';
        }
        $text = preg_replace('/(registration_token=)[^&\s]*/i', '$1[redacted]', $text);
        $text = preg_replace('/(Authorization:\s*Bearer\s+)\S+/i', '$1[redacted]', $text);
        return is_string($text) ? $text : '';
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
