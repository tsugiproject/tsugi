<?php

namespace Tsugi\Services\Outbound;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tsugi\Core\Keyset;
use Tsugi\Core\LTIX;
use Tsugi\Util\LTI13;

/**
 * A signed LTI 1.3 resource-link test for a tool this course can already see.
 *
 * The test page starts the tool's login. The tool sends the browser back to
 * the platform authorization URL, and that response is the launch.
 * A privacy launch uses the same login and a smaller token. A deep link opens
 * in a panel and its return is not stored. Course scoped stays in the list.
 */
class Lti13TestLaunch {

    const HINT_SECONDS = 600;

    /**
     * @return array<string, array{label:string, ready:bool}>
     */
    public static function launches() {
        return array(
            'LtiResourceLinkRequest' => array(
                'label' => 'Resource link',
                'ready' => true,
            ),
            'LtiDeepLinkingRequest' => array(
                'label' => 'Deep link',
                'ready' => true,
            ),
            'LtiDataPrivacyLaunchRequest' => array(
                'label' => 'Privacy launch',
                'ready' => true,
            ),
            'LtiContextLaunchRequest' => array(
                'label' => 'Course scoped launch',
                'ready' => false,
            ),
        );
    }

    /**
     * Launches this tool registered, in test-page order.
     * Resource link is listed even when the registration did not declare one.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array<int, array{type:string, label:string, ready:bool}>
     */
    public static function choices($contextId, $registrationId) {
        $tool = ToolRegistrationService::visibleLti13((int) $contextId, (int) $registrationId);
        $have = array('LtiResourceLinkRequest' => true);
        foreach ( $tool['messages'] as $message ) {
            $have[$message['message_type']] = true;
        }
        $choices = array();
        foreach ( self::launches() as $type => $spec ) {
            if ( empty($have[$type]) ) {
                continue;
            }
            $ready = $spec['ready']
                && $tool['oidc_login_url'] !== ''
                && self::messageTarget($tool, $type) !== '';
            $choices[] = array(
                'type' => $type,
                'label' => $spec['label'],
                'ready' => $ready,
            );
        }
        return $choices;
    }

    /**
     * Start one test launch, or report that this message type is not sent yet.
     *
     * Ready launches post the login request to the tool. The signed token is
     * produced when the tool calls the authorization URL.
     *
     * @param int $contextId
     * @param int $registrationId
     * @param int $userId
     * @param string $messageType
     * @param string $returnUrl
     * @param string $role Instructor or Learner
     * @return array{title:string, endpoint:string, form_endpoint:string, message_type:string, label:string, role:string, ready:bool, new_window:bool, missing_resource_link:bool, content_item_url:bool, parameters:array<string, string>, jwt_json:string, jwt_signed:string}
     */
    public static function launch($contextId, $registrationId, $userId, $messageType, $returnUrl, $role = 'Instructor') {
        $tool = ToolRegistrationService::visibleLti13((int) $contextId, (int) $registrationId);
        $spec = self::launches()[$messageType] ?? null;
        $have = false;
        foreach ( self::choices((int) $contextId, (int) $registrationId) as $choice ) {
            if ( $choice['type'] === $messageType ) {
                $have = true;
                break;
            }
        }
        if ( $spec === null || ! $have ) {
            throw new \InvalidArgumentException('This tool does not have that launch.');
        }
        $role = Lti11TestLaunch::role($role);
        $target = self::messageTarget($tool, $messageType);
        $missingResourceLink = $messageType === 'LtiResourceLinkRequest' && $target === '';
        $result = array(
            'title' => $tool['title'],
            'endpoint' => $missingResourceLink ? trim((string) $tool['launch_url']) : $target,
            'form_endpoint' => $tool['oidc_login_url'],
            'message_type' => $messageType,
            'label' => $spec['label'],
            'role' => $role,
            'ready' => $spec['ready'] && $tool['oidc_login_url'] !== '' && $target !== '',
            'new_window' => $messageType !== 'LtiDeepLinkingRequest',
            'modal' => $messageType === 'LtiDeepLinkingRequest',
            'missing_resource_link' => $missingResourceLink,
            'content_item_url' => $missingResourceLink && self::launchUrlIsContentItem($tool),
            'parameters' => array(),
            'jwt_json' => '',
            'jwt_signed' => '',
        );
        if ( ! $result['ready'] ) {
            return $result;
        }
        $userId = (int) $userId;
        if ( $userId < 1 ) {
            throw new \InvalidArgumentException('A user is required to test a launch.');
        }
        $deployment = self::deployment($tool);
        $loginHint = bin2hex(random_bytes(16));
        $hint = self::encodeHint(array(
            'registration_id' => (int) $tool['registration_id'],
            'context_id' => (int) $contextId,
            'user_id' => $userId,
            'role' => $role,
            'message_type' => $messageType,
            'target_link_uri' => $target,
            'login_hint' => $loginHint,
            'deployment_id' => $deployment['deployment_id'],
            'return_url' => self::httpUrl($returnUrl),
            'iat' => time(),
            'exp' => time() + self::HINT_SECONDS,
        ));
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $result['parameters'] = array(
            'iss' => $issuer,
            'login_hint' => $loginHint,
            'target_link_uri' => $target,
            'client_id' => $tool['client_id'],
            'lti_deployment_id' => $deployment['deployment_id'],
            'lti_message_hint' => $hint,
            'lti_message_type' => $messageType,
            'ext_submit' => 'Send '.$spec['label'],
        );
        $previewHint = array(
            'registration_id' => (int) $tool['registration_id'],
            'context_id' => (int) $contextId,
            'user_id' => $userId,
            'role' => $role,
            'target_link_uri' => $target,
            'deployment_id' => $deployment['deployment_id'],
            'return_url' => self::httpUrl($returnUrl),
        );
        if ( $messageType === 'LtiDataPrivacyLaunchRequest' ) {
            $claims = self::privacyClaims($tool, $deployment, $previewHint, 'preview');
        } else if ( $messageType === 'LtiDeepLinkingRequest' ) {
            $claims = self::deepLinkClaims($tool, $deployment, $previewHint, 'preview');
        } else {
            $claims = self::resourceLinkClaims($tool, $deployment, $previewHint, 'preview');
        }
        $preview = self::previewToken($claims);
        $result['jwt_json'] = $preview['json'];
        $result['jwt_signed'] = $preview['signed'];
        return $result;
    }

    /**
     * A resource link aimed at a URL a deep link return just verified.
     * Nothing about that return is written down.
     *
     * @param int $contextId
     * @param int $registrationId
     * @param int $userId
     * @param string $target
     * @param string $returnUrl
     * @param string $role
     * @param string $title
     * @return array{title:string, endpoint:string, form_endpoint:string, message_type:string, label:string, role:string, ready:bool, new_window:bool, modal:bool, missing_resource_link:bool, content_item_url:bool, parameters:array<string, string>, jwt_json:string, jwt_signed:string}
     */
    public static function launchReturned($contextId, $registrationId, $userId, $target, $returnUrl, $role = 'Instructor', $title = '') {
        $tool = ToolRegistrationService::visibleLti13((int) $contextId, (int) $registrationId);
        $target = self::httpUrl($target);
        if ( $target === '' || $tool['oidc_login_url'] === '' ) {
            throw new \InvalidArgumentException('The deep link return has no target link.');
        }
        $role = Lti11TestLaunch::role($role);
        $userId = (int) $userId;
        if ( $userId < 1 ) {
            throw new \InvalidArgumentException('A user is required to test a launch.');
        }
        $title = trim((string) $title);
        if ( strlen($title) > 255 ) {
            $title = substr($title, 0, 255);
        }
        $deployment = self::deployment($tool);
        $loginHint = bin2hex(random_bytes(16));
        $hint = self::encodeHint(array(
            'registration_id' => (int) $tool['registration_id'],
            'context_id' => (int) $contextId,
            'user_id' => $userId,
            'role' => $role,
            'message_type' => 'LtiResourceLinkRequest',
            'target_link_uri' => $target,
            'login_hint' => $loginHint,
            'deployment_id' => $deployment['deployment_id'],
            'return_url' => self::httpUrl($returnUrl),
            'returned' => true,
            'title' => $title,
            'iat' => time(),
            'exp' => time() + self::HINT_SECONDS,
        ));
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $previewHint = array(
            'context_id' => (int) $contextId,
            'user_id' => $userId,
            'role' => $role,
            'target_link_uri' => $target,
            'return_url' => self::httpUrl($returnUrl),
            'returned' => true,
            'title' => $title,
        );
        $preview = self::previewToken(self::resourceLinkClaims($tool, $deployment, $previewHint, 'preview'));
        return array(
            'title' => $tool['title'],
            'endpoint' => $target,
            'form_endpoint' => $tool['oidc_login_url'],
            'message_type' => 'LtiResourceLinkRequest',
            'label' => 'Resource link',
            'role' => $role,
            'ready' => true,
            'new_window' => true,
            'modal' => false,
            'missing_resource_link' => false,
            'content_item_url' => false,
            'parameters' => array(
                'iss' => $issuer,
                'login_hint' => $loginHint,
                'target_link_uri' => $target,
                'client_id' => $tool['client_id'],
                'lti_deployment_id' => $deployment['deployment_id'],
                'lti_message_hint' => $hint,
                'lti_message_type' => 'LtiResourceLinkRequest',
                'ext_submit' => 'Send Resource link',
            ),
            'jwt_json' => $preview['json'],
            'jwt_signed' => $preview['signed'],
        );
    }

    /**
     * The tool's authentication request. Returns the form fields for the launch.
     *
     * @param array<string, mixed> $request
     * @return array{redirect_uri:string, id_token:string, state:string}
     */
    public static function complete(array $request) {
        $hint = self::decodeHint(self::requestString($request, 'lti_message_hint', 4000));
        $loginHint = self::requestString($request, 'login_hint', 255);
        if ( (string) $hint['login_hint'] !== $loginHint ) {
            throw new \InvalidArgumentException('That launch link is not valid.');
        }
        $clientId = self::requestString($request, 'client_id', 255);
        $redirectUri = self::requestString($request, 'redirect_uri', 2000);
        $nonce = self::requestString($request, 'nonce', 512);
        $state = self::requestString($request, 'state', 8000);
        $responseType = self::requestString($request, 'response_type', 64);
        if ( $responseType !== 'id_token' ) {
            throw new \InvalidArgumentException('The launch response type is not valid.');
        }
        $scope = self::requestString($request, 'scope', 255);
        if ( ! in_array('openid', preg_split('/\s+/', $scope), true) ) {
            throw new \InvalidArgumentException('The launch scope is not valid.');
        }
        $postedDeployment = '';
        if ( isset($request['lti_deployment_id']) && is_string($request['lti_deployment_id']) && $request['lti_deployment_id'] !== '' ) {
            $postedDeployment = self::requestString($request, 'lti_deployment_id', 255);
            if ( (string) $hint['deployment_id'] !== $postedDeployment ) {
                throw new \InvalidArgumentException('That deployment is not part of this launch.');
            }
        }

        $tool = ToolRegistrationService::visibleLti13((int) $hint['context_id'], (int) $hint['registration_id']);
        if ( $tool['client_id'] === '' || $tool['client_id'] !== $clientId ) {
            throw new \InvalidArgumentException('That client is not registered.');
        }
        if ( ! in_array($redirectUri, $tool['redirect_uris'], true) ) {
            throw new \InvalidArgumentException('That redirect URL is not registered.');
        }
        $deployment = self::deployment($tool);
        if ( $deployment['deployment_id'] !== (string) $hint['deployment_id'] ) {
            throw new \InvalidArgumentException('That deployment is not part of this launch.');
        }
        $messageType = (string) $hint['message_type'];
        $returned = ! empty($hint['returned']);
        if ( $messageType === 'LtiDeepLinkingRequest' ) {
            if ( self::messageTarget($tool, $messageType) !== (string) $hint['target_link_uri'] ) {
                throw new \InvalidArgumentException('This registration has no deep link.');
            }
            $claims = self::deepLinkClaims($tool, $deployment, $hint, $nonce);
        } else if ( $messageType === 'LtiResourceLinkRequest' && $returned ) {
            $claims = self::resourceLinkClaims($tool, $deployment, $hint, $nonce);
        } else if ( $messageType === 'LtiResourceLinkRequest' || $messageType === 'LtiDataPrivacyLaunchRequest' ) {
            if ( self::messageTarget($tool, $messageType) !== (string) $hint['target_link_uri'] ) {
                throw new \InvalidArgumentException(
                    $messageType === 'LtiDataPrivacyLaunchRequest'
                        ? 'This registration has no privacy launch.'
                        : 'This registration has no resource link message.'
                );
            }
            $claims = $messageType === 'LtiDataPrivacyLaunchRequest'
                ? self::privacyClaims($tool, $deployment, $hint, $nonce)
                : self::resourceLinkClaims($tool, $deployment, $hint, $nonce);
        } else {
            throw new \InvalidArgumentException('This tool does not have that launch.');
        }
        return array(
            'redirect_uri' => $redirectUri,
            'id_token' => self::sign($claims),
            'state' => $state,
        );
    }

    /**
     * @param array<string, mixed> $tool
     * @param array{tool_deployment_id:int, deployment_id:string, allowed_claims:array<int, string>, allowed_scopes:array<int, string>} $deployment
     * @param array<string, mixed> $hint
     * @param string $nonce
     * @return array<string, mixed>
     */
    private static function resourceLinkClaims(array $tool, array $deployment, array $hint, $nonce) {
        $contextId = (int) $hint['context_id'];
        $userId = (int) $hint['user_id'];
        $context = self::contextRow($contextId);
        $key = self::keyRow($context['key_id']);
        $user = self::userRow($userId, $context['key_id']);
        $now = time();
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $claims = array(
            'iss' => $issuer,
            'aud' => $tool['client_id'],
            'sub' => (string) $userId,
            'iat' => $now,
            'exp' => $now + 3600,
            'nonce' => $nonce,
            LTI13::MESSAGE_TYPE_CLAIM => 'LtiResourceLinkRequest',
            LTI13::VERSION_CLAIM => '1.3.0',
            LTI13::DEPLOYMENT_ID_CLAIM => $deployment['deployment_id'],
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => (string) $hint['target_link_uri'],
            LTI13::RESOURCE_LINK_CLAIM => array(
                'id' => ! empty($hint['returned']) ? 'deep-'.$tool['registration_id'] : 'test-'.$tool['registration_id'],
                'title' => (isset($hint['title']) && (string) $hint['title'] !== '') ? (string) $hint['title'] : $tool['title'],
                'description' => 'Test launch',
            ),
            LTI13::ROLES_CLAIM => array(self::roleUri((string) $hint['role'])),
            LTI13::CONTEXT_ID_CLAIM => array(
                'id' => $context['context_id'],
                'label' => $context['label'],
                'title' => $context['title'],
                'type' => array('http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering'),
            ),
            LTI13::TOOL_PLATFORM_CLAIM => array(
                'guid' => $key['guid'],
                'product_family_code' => 'tsugi.org',
            ),
            LTI13::PRESENTATION_CLAIM => array(
                'document_target' => 'window',
                'locale' => 'en',
            ),
        );
        if ( $key['name'] !== '' ) {
            $claims[LTI13::TOOL_PLATFORM_CLAIM]['name'] = $key['name'];
        }
        $returnUrl = isset($hint['return_url']) ? (string) $hint['return_url'] : '';
        if ( $returnUrl !== '' ) {
            $claims[LTI13::PRESENTATION_CLAIM]['return_url'] = $returnUrl;
        }
        $allowed = $deployment['allowed_claims'];
        if ( in_array('name', $allowed, true) && $user['full'] !== '' ) {
            $claims['name'] = $user['full'];
        }
        if ( in_array('given_name', $allowed, true) && $user['given'] !== '' ) {
            $claims['given_name'] = $user['given'];
        }
        if ( in_array('family_name', $allowed, true) && $user['family'] !== '' ) {
            $claims['family_name'] = $user['family'];
        }
        if ( in_array('email', $allowed, true) && $user['email'] !== '' ) {
            $claims['email'] = $user['email'];
        }
        $scopes = $deployment['allowed_scopes'];
        $grade = array();
        foreach ( array(ToolRegistrationDocument::SCOPE_LINEITEM, ToolRegistrationDocument::SCOPE_RESULT, ToolRegistrationDocument::SCOPE_SCORE) as $scope ) {
            if ( in_array($scope, $scopes, true) ) {
                $grade[] = $scope;
            }
        }
        if ( count($grade) > 0 ) {
            $root = $issuer;
            $claims[LTI13::ENDPOINT_CLAIM] = array(
                'scope' => $grade,
                'lineitems' => $root.'/lti/ags/context/'.$contextId.'/lineitems',
            );
        }
        if ( in_array(ToolRegistrationDocument::SCOPE_ROSTER, $scopes, true) ) {
            $claims[LTI13::NAMESANDROLES_CLAIM] = array(
                'context_memberships_url' => $issuer.'/lti/nrps/context/'.$contextId.'/memberships',
                'service_versions' => array('2.0'),
            );
        }
        return $claims;
    }

    /**
     * @param array<string, mixed> $tool
     * @param array{tool_deployment_id:int, deployment_id:string, allowed_claims:array<int, string>, allowed_scopes:array<int, string>} $deployment
     * @param array<string, mixed> $hint
     * @param string $nonce
     * @return array<string, mixed>
     */
    private static function deepLinkClaims(array $tool, array $deployment, array $hint, $nonce) {
        $contextId = (int) $hint['context_id'];
        $userId = (int) $hint['user_id'];
        $context = self::contextRow($contextId);
        $key = self::keyRow($context['key_id']);
        $user = self::userRow($userId, $context['key_id']);
        $now = time();
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $claims = array(
            'iss' => $issuer,
            'aud' => $tool['client_id'],
            'sub' => (string) $userId,
            'iat' => $now,
            'exp' => $now + 3600,
            'nonce' => $nonce,
            LTI13::MESSAGE_TYPE_CLAIM => 'LtiDeepLinkingRequest',
            LTI13::VERSION_CLAIM => '1.3.0',
            LTI13::DEPLOYMENT_ID_CLAIM => $deployment['deployment_id'],
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => (string) $hint['target_link_uri'],
            LTI13::ROLES_CLAIM => array(self::roleUri((string) $hint['role'])),
            LTI13::CONTEXT_ID_CLAIM => array(
                'id' => $context['context_id'],
                'label' => $context['label'],
                'title' => $context['title'],
                'type' => array('http://purl.imsglobal.org/vocab/lis/v2/course#CourseOffering'),
            ),
            LTI13::DEEPLINK_CLAIM => array(
                'deep_link_return_url' => self::returnUrl(),
                'accept_types' => array('ltiResourceLink'),
                'accept_presentation_document_targets' => array('iframe', 'window'),
                'accept_multiple' => true,
                'auto_create' => false,
                'data' => self::deepLinkData($hint),
            ),
            LTI13::TOOL_PLATFORM_CLAIM => array(
                'guid' => $key['guid'],
                'product_family_code' => 'tsugi.org',
            ),
            LTI13::PRESENTATION_CLAIM => array(
                'document_target' => 'iframe',
                'locale' => 'en',
            ),
        );
        if ( $key['name'] !== '' ) {
            $claims[LTI13::TOOL_PLATFORM_CLAIM]['name'] = $key['name'];
        }
        $returnUrl = isset($hint['return_url']) ? (string) $hint['return_url'] : '';
        if ( $returnUrl !== '' ) {
            $claims[LTI13::PRESENTATION_CLAIM]['return_url'] = $returnUrl;
        }
        $allowed = $deployment['allowed_claims'];
        if ( in_array('name', $allowed, true) && $user['full'] !== '' ) {
            $claims['name'] = $user['full'];
        }
        if ( in_array('given_name', $allowed, true) && $user['given'] !== '' ) {
            $claims['given_name'] = $user['given'];
        }
        if ( in_array('family_name', $allowed, true) && $user['family'] !== '' ) {
            $claims['family_name'] = $user['family'];
        }
        if ( in_array('email', $allowed, true) && $user['email'] !== '' ) {
            $claims['email'] = $user['email'];
        }
        return $claims;
    }

    /**
     * Check a deep link return and pull out its items. The return is not stored.
     *
     * @param string $jwt
     * @param string|null $publicKey Tool public key. When omitted, the registration key set is read.
     * @return array{registration_id:int, context_id:int, user_id:int, role:string, deployment_id:string, items:array<int, array{type:string, title:string, text:string, url:string, json:string}>, msg:string, log:string, errormsg:string, errorlog:string}
     */
    public static function acceptReturn($jwt, $publicKey = null) {
        $jwt = trim((string) $jwt);
        if ( $jwt === '' || strlen($jwt) > 100000 ) {
            throw new \InvalidArgumentException('The deep link return is not valid.');
        }
        $parsed = LTI13::parse_jwt($jwt, false);
        if ( ! is_object($parsed) || ! isset($parsed->body) || ! is_object($parsed->body) ) {
            throw new \InvalidArgumentException('The deep link return is not valid.');
        }
        $body = $parsed->body;
        $dataClaim = 'https://purl.imsglobal.org/spec/lti-dl/claim/data';
        $itemsClaim = 'https://purl.imsglobal.org/spec/lti-dl/claim/content_items';
        $data = isset($body->{$dataClaim}) ? (string) $body->{$dataClaim} : '';
        $hint = self::decodeHint($data);
        if ( $hint['message_type'] !== 'LtiDeepLinkingRequest' ) {
            throw new \InvalidArgumentException('The deep link return is not valid.');
        }
        $tool = ToolRegistrationService::visibleLti13((int) $hint['context_id'], (int) $hint['registration_id']);
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        if ( ! isset($body->iss) || (string) $body->iss !== $tool['client_id'] ) {
            throw new \InvalidArgumentException('That client is not registered.');
        }
        $aud = isset($body->aud) ? $body->aud : null;
        $audOk = (is_string($aud) && $aud === $issuer) || (is_array($aud) && in_array($issuer, $aud, true));
        if ( ! $audOk ) {
            throw new \InvalidArgumentException('The deep link return is not for this platform.');
        }
        if ( ! isset($body->{LTI13::MESSAGE_TYPE_CLAIM}) || (string) $body->{LTI13::MESSAGE_TYPE_CLAIM} !== 'LtiDeepLinkingResponse' ) {
            throw new \InvalidArgumentException('The deep link return is not valid.');
        }
        $version = isset($body->{LTI13::VERSION_CLAIM}) ? (string) $body->{LTI13::VERSION_CLAIM} : '';
        if ( strpos($version, '1.3') !== 0 ) {
            throw new \InvalidArgumentException('The deep link return is not valid.');
        }
        if ( ! isset($body->{LTI13::DEPLOYMENT_ID_CLAIM}) || (string) $body->{LTI13::DEPLOYMENT_ID_CLAIM} !== (string) $hint['deployment_id'] ) {
            throw new \InvalidArgumentException('That deployment is not part of this launch.');
        }
        if ( is_string($publicKey) && $publicKey !== '' ) {
            $verified = LTI13::verifyPublicKey($jwt, $publicKey);
            if ( $verified !== true ) {
                throw new \InvalidArgumentException('The deep link return is not signed by the tool.');
            }
        } else {
            self::verifyToolKeyset($jwt, $tool['jwks_url']);
        }

        $items = array();
        $rawItems = isset($body->{$itemsClaim}) ? $body->{$itemsClaim} : array();
        if ( is_array($rawItems) ) {
            foreach ( $rawItems as $item ) {
                $items[] = self::contentItem($item);
            }
        }
        return array(
            'registration_id' => (int) $hint['registration_id'],
            'context_id' => (int) $hint['context_id'],
            'user_id' => (int) $hint['user_id'],
            'role' => (string) $hint['role'],
            'deployment_id' => (string) $hint['deployment_id'],
            'items' => $items,
            'msg' => isset($body->{'https://purl.imsglobal.org/spec/lti-dl/claim/msg'}) ? (string) $body->{'https://purl.imsglobal.org/spec/lti-dl/claim/msg'} : '',
            'log' => isset($body->{'https://purl.imsglobal.org/spec/lti-dl/claim/log'}) ? (string) $body->{'https://purl.imsglobal.org/spec/lti-dl/claim/log'} : '',
            'errormsg' => isset($body->{'https://purl.imsglobal.org/spec/lti-dl/claim/errormsg'}) ? (string) $body->{'https://purl.imsglobal.org/spec/lti-dl/claim/errormsg'} : '',
            'errorlog' => isset($body->{'https://purl.imsglobal.org/spec/lti-dl/claim/errorlog'}) ? (string) $body->{'https://purl.imsglobal.org/spec/lti-dl/claim/errorlog'} : '',
        );
    }

    /**
     * @return string
     */
    public static function returnUrl() {
        global $CFG;
        $root = (isset($CFG) && is_object($CFG) && isset($CFG->wwwroot)) ? rtrim((string) $CFG->wwwroot, '/') : '';
        return $root.'/lti/deep_link_return.php';
    }

    /**
     * The value echoed in the deep link data claim. It outlives the login hint
     * so a person can spend time choosing.
     *
     * @param array<string, mixed> $hint
     * @return string
     */
    private static function deepLinkData(array $hint) {
        return self::encodeHint(array(
            'registration_id' => (int) $hint['registration_id'],
            'context_id' => (int) $hint['context_id'],
            'user_id' => (int) $hint['user_id'],
            'role' => isset($hint['role']) ? (string) $hint['role'] : 'Instructor',
            'message_type' => 'LtiDeepLinkingRequest',
            'target_link_uri' => (string) $hint['target_link_uri'],
            'login_hint' => 'deep-link',
            'deployment_id' => (string) $hint['deployment_id'],
            'return_url' => isset($hint['return_url']) ? (string) $hint['return_url'] : '',
            'iat' => time(),
            'exp' => time() + 3600,
        ));
    }

    /**
     * @param mixed $item
     * @return array{type:string, title:string, text:string, url:string, json:string}
     */
    private static function contentItem($item) {
        $row = json_decode(json_encode($item), true);
        if ( ! is_array($row) ) {
            $row = array();
        }
        $encoded = json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return array(
            'type' => isset($row['type']) ? (string) $row['type'] : '',
            'title' => isset($row['title']) ? (string) $row['title'] : '',
            'text' => isset($row['text']) ? (string) $row['text'] : '',
            'url' => isset($row['url']) && is_string($row['url']) ? $row['url'] : '',
            'json' => is_string($encoded) ? $encoded : '',
        );
    }

    /**
     * @param string $jwt
     * @param string $jwksUrl
     * @return void
     */
    private static function verifyToolKeyset($jwt, $jwksUrl) {
        $jwksUrl = trim((string) $jwksUrl);
        if ( ! preg_match('#^https://#i', $jwksUrl) ) {
            throw new \InvalidArgumentException('This tool has no key set.');
        }
        $body = \Tsugi\Util\Net::doGet($jwksUrl);
        $json = is_string($body) ? json_decode($body, true) : null;
        if ( ! is_array($json) || ! isset($json['keys']) || ! is_array($json['keys']) ) {
            throw new \InvalidArgumentException('Could not read the tool key set.');
        }
        try {
            JWT::decode($jwt, \Firebase\JWT\JWK::parseKeySet($json));
        } catch ( \Throwable $ex ) {
            throw new \InvalidArgumentException('The deep link return is not signed by the tool.');
        }
    }

    /**
     * @param array<string, mixed> $claims
     * @return array{json:string, signed:string}
     */
    private static function previewToken(array $claims) {
        $signed = self::sign($claims);
        $parsed = LTI13::parse_jwt($signed, false);
        $json = '';
        if ( is_object($parsed) ) {
            $encoded = json_encode(
                array('header' => $parsed->header, 'payload' => $parsed->body),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            $json = is_string($encoded) ? $encoded : '';
        }
        return array(
            'json' => $json,
            'signed' => $signed,
        );
    }

    /**
     * A privacy launch is the resource-link token without the resource, the
     * course, and the service claims. for_user names the person whose data
     * the tool is asked to act on. This test acts on the person who launched.
     *
     * @param array<string, mixed> $tool
     * @param array{tool_deployment_id:int, deployment_id:string, allowed_claims:array<int, string>, allowed_scopes:array<int, string>} $deployment
     * @param array<string, mixed> $hint
     * @param string $nonce
     * @return array<string, mixed>
     */
    private static function privacyClaims(array $tool, array $deployment, array $hint, $nonce) {
        $userId = (int) $hint['user_id'];
        $context = self::contextRow((int) $hint['context_id']);
        $key = self::keyRow($context['key_id']);
        $user = self::userRow($userId, $context['key_id']);
        $now = time();
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $forUser = array(
            'user_id' => (string) $userId,
            'roles' => array('http://purl.imsglobal.org/vocab/lis/v2/system/person#User'),
        );
        $allowed = $deployment['allowed_claims'];
        $claims = array(
            'iss' => $issuer,
            'aud' => $tool['client_id'],
            'sub' => (string) $userId,
            'iat' => $now,
            'exp' => $now + 3600,
            'nonce' => $nonce,
            LTI13::MESSAGE_TYPE_CLAIM => 'LtiDataPrivacyLaunchRequest',
            LTI13::VERSION_CLAIM => '1.3.0',
            LTI13::DEPLOYMENT_ID_CLAIM => $deployment['deployment_id'],
            'https://purl.imsglobal.org/spec/lti/claim/target_link_uri' => (string) $hint['target_link_uri'],
            LTI13::ROLES_CLAIM => array(self::privacyRoleUri((string) $hint['role'])),
            LTI13::FOR_USER_CLAIM => $forUser,
            LTI13::TOOL_PLATFORM_CLAIM => array(
                'guid' => $key['guid'],
                'product_family_code' => 'tsugi.org',
            ),
            LTI13::PRESENTATION_CLAIM => array(
                'document_target' => 'window',
                'locale' => 'en',
            ),
        );
        if ( $key['name'] !== '' ) {
            $claims[LTI13::TOOL_PLATFORM_CLAIM]['name'] = $key['name'];
        }
        $returnUrl = isset($hint['return_url']) ? (string) $hint['return_url'] : '';
        if ( $returnUrl !== '' ) {
            $claims[LTI13::PRESENTATION_CLAIM]['return_url'] = $returnUrl;
        }
        if ( in_array('name', $allowed, true) && $user['full'] !== '' ) {
            $claims['name'] = $user['full'];
            $forUser['name'] = $user['full'];
        }
        if ( in_array('given_name', $allowed, true) && $user['given'] !== '' ) {
            $claims['given_name'] = $user['given'];
            $forUser['given_name'] = $user['given'];
        }
        if ( in_array('family_name', $allowed, true) && $user['family'] !== '' ) {
            $claims['family_name'] = $user['family'];
            $forUser['family_name'] = $user['family'];
        }
        if ( in_array('email', $allowed, true) && $user['email'] !== '' ) {
            $claims['email'] = $user['email'];
            $forUser['email'] = $user['email'];
        }
        $claims[LTI13::FOR_USER_CLAIM] = $forUser;
        return $claims;
    }

    /**
     * @param array<string, mixed> $claims
     * @return string
     */
    private static function sign(array $claims) {
        $privkey = null;
        $kid = null;
        $ok = Keyset::getSigning($privkey, $kid);
        if ( $ok !== true || ! is_string($privkey) || $privkey === '' || ! is_string($kid) || $kid === '' ) {
            throw new \RuntimeException('Could not sign the launch.');
        }
        return LTI13::encode_jwt($claims, $privkey, $kid);
    }

    /**
     * @param string $role
     * @return string
     */
    private static function roleUri($role) {
        if ( $role === 'Instructor' ) {
            return 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
        }
        return 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
    }

    /**
     * Privacy launches carry a system role. Context membership roles do not apply.
     *
     * @param string $role
     * @return string
     */
    private static function privacyRoleUri($role) {
        if ( $role === 'Instructor' ) {
            return 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator';
        }
        return 'http://purl.imsglobal.org/vocab/lis/v2/system/person#User';
    }

    /**
     * Target of the first message of this type. A registration launch URL is not a message target.
     *
     * @param array<string, mixed> $tool
     * @param string $messageType
     * @return string
     */
    private static function messageTarget(array $tool, $messageType) {
        if ( $messageType !== 'LtiResourceLinkRequest' && $messageType !== 'LtiDataPrivacyLaunchRequest' && $messageType !== 'LtiDeepLinkingRequest' ) {
            return '';
        }
        foreach ( $tool['messages'] as $message ) {
            if ( $message['message_type'] !== $messageType ) {
                continue;
            }
            $target = trim((string) $message['target_link_uri']);
            if ( $target !== '' ) {
                return $target;
            }
        }
        return '';
    }

    /**
     * The registration launch URL is also a content-item message target.
     *
     * @param array<string, mixed> $tool
     * @return bool
     */
    private static function launchUrlIsContentItem(array $tool) {
        $url = trim((string) $tool['launch_url']);
        if ( $url === '' ) {
            return false;
        }
        foreach ( $tool['messages'] as $message ) {
            if ( $message['message_type'] === 'LtiDeepLinkingRequest' && trim((string) $message['target_link_uri']) === $url ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $tool
     * @return array{tool_deployment_id:int, deployment_id:string, allowed_claims:array<int, string>, allowed_scopes:array<int, string>}
     */
    private static function deployment(array $tool) {
        foreach ( $tool['deployments'] as $deployment ) {
            if ( isset($deployment['deployment_id']) && (string) $deployment['deployment_id'] !== '' ) {
                return $deployment;
            }
        }
        throw new \InvalidArgumentException('This course has no deployment for that tool.');
    }

    /**
     * @param array<string, mixed> $payload
     * @return string
     */
    private static function encodeHint(array $payload) {
        return JWT::encode($payload, self::hintSecret(), 'HS256');
    }

    /**
     * @param string $hint
     * @return array<string, mixed>
     */
    private static function decodeHint($hint) {
        try {
            $decoded = JWT::decode($hint, new Key(self::hintSecret(), 'HS256'));
        } catch ( \Throwable $ex ) {
            throw new \InvalidArgumentException('That launch link is not valid.');
        }
        if ( ! is_object($decoded) ) {
            throw new \InvalidArgumentException('That launch link is not valid.');
        }
        $registrationId = isset($decoded->registration_id) ? (int) $decoded->registration_id : 0;
        $contextId = isset($decoded->context_id) ? (int) $decoded->context_id : 0;
        $userId = isset($decoded->user_id) ? (int) $decoded->user_id : 0;
        $loginHint = isset($decoded->login_hint) ? (string) $decoded->login_hint : '';
        $deploymentId = isset($decoded->deployment_id) ? (string) $decoded->deployment_id : '';
        $target = isset($decoded->target_link_uri) ? (string) $decoded->target_link_uri : '';
        $messageType = isset($decoded->message_type) ? (string) $decoded->message_type : '';
        $role = isset($decoded->role) ? (string) $decoded->role : '';
        if ( $registrationId < 1 || $contextId < 1 || $userId < 1 || $loginHint === '' || $deploymentId === '' || $target === '' ) {
            throw new \InvalidArgumentException('That launch link is not valid.');
        }
        return array(
            'registration_id' => $registrationId,
            'context_id' => $contextId,
            'user_id' => $userId,
            'role' => $role === 'Instructor' ? 'Instructor' : 'Learner',
            'message_type' => $messageType,
            'target_link_uri' => $target,
            'login_hint' => $loginHint,
            'deployment_id' => $deploymentId,
            'return_url' => isset($decoded->return_url) ? (string) $decoded->return_url : '',
            'returned' => isset($decoded->returned) && $decoded->returned === true,
            'title' => isset($decoded->title) ? substr((string) $decoded->title, 0, 255) : '',
        );
    }

    /**
     * @return string
     */
    private static function hintSecret() {
        global $CFG;
        $secret = (isset($CFG) && is_object($CFG) && isset($CFG->cookiesecret)) ? (string) $CFG->cookiesecret : '';
        if ( $secret === '' ) {
            throw new \RuntimeException('Could not sign the launch.');
        }
        return $secret;
    }

    /**
     * @param array<string, mixed> $request
     * @param string $name
     * @param int $limit
     * @return string
     */
    private static function requestString(array $request, $name, $limit) {
        $value = isset($request[$name]) && is_string($request[$name]) ? trim($request[$name]) : '';
        if ( $value === '' || strlen($value) > $limit ) {
            throw new \InvalidArgumentException('The launch request is missing '.$name.'.');
        }
        return $value;
    }

    /**
     * @param string $url
     * @return string
     */
    private static function httpUrl($url) {
        $url = trim((string) $url);
        if ( preg_match('#^https?://#i', $url) ) {
            return $url;
        }
        return '';
    }

    /**
     * @param int $contextId
     * @return array{context_id:string, title:string, label:string, key_id:int}
     */
    private static function contextRow($contextId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id, title, short_title
             FROM {$p}lti_context
             WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        $title = trim((string) $row['title']);
        if ( $title === '' ) {
            $title = 'Course';
        }
        $label = trim((string) $row['short_title']);
        if ( $label === '' ) {
            $label = $title;
        }
        return array(
            'context_id' => (string) $row['context_id'],
            'key_id' => (int) $row['key_id'],
            'title' => $title,
            'label' => $label,
        );
    }

    /**
     * @param int $keyId
     * @return array{guid:string, name:string}
     */
    private static function keyRow($keyId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT key_key, key_title FROM {$p}lti_key WHERE key_id = :key_id",
            array(':key_id' => (int) $keyId)
        );
        $guid = is_array($row) ? trim((string) $row['key_key']) : '';
        $name = is_array($row) ? trim((string) $row['key_title']) : '';
        if ( $guid === '' ) {
            $guid = 'key-'.(int) $keyId;
        }
        return array('guid' => $guid, 'name' => $name);
    }

    /**
     * @param int $userId
     * @param int $keyId
     * @return array{full:string, given:string, family:string, email:string}
     */
    private static function userRow($userId, $keyId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT displayname, email FROM {$p}lti_user
             WHERE user_id = :user_id AND key_id = :key_id",
            array(
                ':user_id' => (int) $userId,
                ':key_id' => (int) $keyId,
            )
        );
        $full = is_array($row) ? trim((string) $row['displayname']) : '';
        $email = is_array($row) ? trim((string) $row['email']) : '';
        $given = $full;
        $family = '';
        $space = strpos($full, ' ');
        if ( $space !== false ) {
            $given = trim(substr($full, 0, $space));
            $family = trim(substr($full, $space + 1));
        }
        return array(
            'full' => $full,
            'given' => $given,
            'family' => $family,
            'email' => $email,
        );
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        $PDOX = LTIX::getConnection();
        if ( ! $PDOX ) {
            throw new \RuntimeException('Database connection is not available.');
        }
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
    }
}
