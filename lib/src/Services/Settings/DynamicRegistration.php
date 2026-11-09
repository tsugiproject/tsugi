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
        echo(htmlentities($openid_configuration ?? '')."\n</p>\n");

        $response = Net::doGet($openid_configuration );
        $code = Net::getLastHttpResponse();
        if ( ! $response || U::strlen($response) < 1 ) {
            echo("<pre>\n");
            echo("Unable to retrieve:\n".htmlentities($openid_configuration??'')."\n");
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
                // lessons is the Tsugi/Sakai content area. ContentArea is the
                // standard name. Both go out so Sakai still sees lessons.
                // A registration imported into Tsugi keeps ContentArea only.
                "placements" => array("ContentArea", "lessons"),
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
            $tool->messages[] = array(
                "type" => "LtiResourceLinkRequest",
                "label" => __('Tsugi Plus'),
                "target_link_uri" => $CFG->wwwroot . '/plus',
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
                "label" => $json->client_name . ' (' . __('Link Selection') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=link_selection',
                "placements" => array( "link_selection"),
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
                "label" => $json->client_name . ' (' . __('Assignment Selection') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=assignment_selection',
                "placements" => array( "assignment_selection"),
                "custom_parameters" => $custom_parameters,
            ),
            array(
                "type" => "LtiDeepLinkingRequest",
                "label" => $json->client_name . ' (' . __('Deep Link') . ')',
                "target_link_uri" => $CFG->wwwroot . '/lti/store/',
                "custom_parameters" => $custom_parameters,
            ),
        ));
        // Canvas rejects lessons, content_editor, and the privacy launch
        // with 422. Other platforms keep those names.
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
            echo("Http response code: ".(string) $response_code."\n\n");
            echo("The LMS rejected the registration. Response from the LMS:\n");
            $shown = is_string($response) ? self::prettyJson($response) : '';
            if ( ! is_string($shown) || $shown === '' ) {
                echo("(empty)\n");
            } else {
                $limit = 20000;
                if ( strlen($shown) > $limit ) {
                    echo(htmlentities(substr($shown, 0, $limit)));
                    echo("\n\n(truncated)\n");
                } else {
                    echo(htmlentities($shown)."\n");
                }
            }
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

        $tc_key = "https://purl.imsglobal.org/spec/lti-tool-configuration";
        $tool_configuration = isset($resp->{$tc_key}) ? $resp->{$tc_key} : null;
        $deployment_id = null;
        if ( $tool_configuration ) {
            $deployment_id = isset($tool_configuration->deployment_id) ? $tool_configuration->deployment_id : null;
        }

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
     * Placements Canvas accepts when its OpenID configuration does not list any.
     * lessons and content_editor are Tsugi/Sakai names. Canvas's schema rejects them.
     *
     * @var array<string, true>
     */
    private const CANVAS_PLACEMENTS = array(
        'ContentArea' => true,
        'RichTextEditor' => true,
        'account_navigation' => true,
        'assignment_selection' => true,
        'course_navigation' => true,
        'editor_button' => true,
        'global_navigation' => true,
        'homework_submission' => true,
        'link_selection' => true,
        'migration_selection' => true,
        'user_navigation' => true,
    );

    /**
     * Message types Canvas accepts when its OpenID configuration does not list any.
     * LtiDataPrivacyLaunchRequest is not one of them.
     *
     * @var array<string, true>
     */
    private const CANVAS_MESSAGE_TYPES = array(
        'LtiResourceLinkRequest' => true,
        'LtiDeepLinkingRequest' => true,
        'LtiEulaRequest' => true,
    );

    /**
     * Drop message types and placements Canvas will reject.
     * Other platforms receive the registration unchanged.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param object|null $platform OpenID configuration
     * @return array<int, array<string, mixed>>
     */
    public static function messagesForPlatform(array $messages, $platform) {
        if ( ! self::isCanvas($platform) ) {
            return $messages;
        }
        $rules = self::canvasMessageRules($platform);
        $out = array();
        foreach ( $messages as $message ) {
            if ( ! is_array($message) ) {
                continue;
            }
            $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';
            if ( ! array_key_exists($type, $rules) ) {
                continue;
            }
            if ( isset($message['placements']) && is_array($message['placements']) ) {
                $kept = array();
                foreach ( $message['placements'] as $placement ) {
                    if ( ! is_string($placement) || $placement === '' ) {
                        continue;
                    }
                    if ( self::canvasPlacementAllowed($placement, $rules[$type]) ) {
                        $kept[] = $placement;
                    }
                }
                // An empty placements array is a link_selection launch on Canvas.
                if ( count($kept) === 0 ) {
                    continue;
                }
                $message['placements'] = array_values($kept);
            }
            $out[] = $message;
        }
        return $out;
    }

    /**
     * @param object|null $platform
     * @return bool
     */
    private static function isCanvas($platform) {
        if ( ! is_object($platform) ) {
            return false;
        }
        $family = self::productFamily($platform);
        if ( $family !== '' ) {
            return strcasecmp($family, 'canvas') === 0;
        }
        foreach ( array('issuer', 'registration_endpoint', 'authorization_endpoint', 'token_endpoint') as $field ) {
            if ( isset($platform->{$field}) && self::hostIsCanvas($platform->{$field}) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param object $platform
     * @return string
     */
    private static function productFamily($platform) {
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
     * @param mixed $url
     * @return bool
     */
    private static function hostIsCanvas($url) {
        if ( ! is_string($url) || $url === '' ) {
            return false;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if ( ! is_string($host) || $host === '' ) {
            return false;
        }
        $host = strtolower($host);
        return $host === 'canvas.instructure.com'
            || $host === 'sso.canvaslms.com'
            || str_ends_with($host, '.canvaslms.com')
            || str_ends_with($host, '.instructure.com');
    }

    /**
     * Type to advertised placements. Null placements means use CANVAS_PLACEMENTS.
     *
     * @param object $platform
     * @return array<string, array<string, true>|null>
     */
    private static function canvasMessageRules($platform) {
        $listed = self::messagesSupported($platform);
        if ( $listed === null ) {
            $rules = array();
            foreach ( array_keys(self::CANVAS_MESSAGE_TYPES) as $type ) {
                $rules[$type] = null;
            }
            return $rules;
        }
        return $listed;
    }

    /**
     * Null when the platform did not publish messages_supported.
     *
     * @param object $platform
     * @return array<string, array<string, true>|null>|null
     */
    private static function messagesSupported($platform) {
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
     * Canvas advertises many placements as https://canvas.instructure.com/lti/{name}
     * and also accepts the short name in a registration.
     *
     * @param string $placement
     * @param array<string, true>|null $advertised
     * @return bool
     */
    private static function canvasPlacementAllowed($placement, $advertised) {
        if ( $advertised === null ) {
            return isset(self::CANVAS_PLACEMENTS[$placement]);
        }
        if ( isset($advertised[$placement]) ) {
            return true;
        }
        $suffix = '/lti/'.$placement;
        foreach ( array_keys($advertised) as $name ) {
            if ( ! is_string($name) ) {
                continue;
            }
            if ( strlen($name) > strlen($suffix) && substr($name, -strlen($suffix)) === $suffix ) {
                return true;
            }
        }
        return false;
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
