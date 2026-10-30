<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * Course Settings editor for one LTI 1.3 registration this course owns.
 *
 * Scopes, claims, and placements stay on the tool when they are turned off.
 * A grant or an enabled placement is the on switch. Delete removes the item
 * from the registration. Add puts a known vocabulary value on the tool and
 * turns it on. A placement string that is not in the menu is stored as typed.
 * Messages, redirect URLs, and custom parameters are added, changed, and removed.
 */
class Lti13CourseTool {

    /**
     * @param int $contextId
     * @param int $registrationId
     * @return array<string, mixed>
     */
    public static function editState($contextId, $registrationId) {
        $owned = self::requireOwned($contextId, $registrationId);
        $document = self::document($registrationId);
        $deployments = self::courseDeployments($contextId, $registrationId);
        $requestedScopes = ToolRegistrationDocument::requestedScopes($document);
        $requestedClaims = ToolRegistrationDocument::requestedClaims($document);
        $grantRows = array();
        foreach ( $deployments as $deployment ) {
            $toolDeploymentId = (int) $deployment['tool_deployment_id'];
            $grantRows[] = array(
                'tool_deployment_id' => $toolDeploymentId,
                'deployment_id' => $deployment['deployment_id'] === null ? '' : (string) $deployment['deployment_id'],
                'claims' => ToolDeploymentGrant::allowedClaims($toolDeploymentId),
                'scopes' => ToolDeploymentGrant::allowedScopes($toolDeploymentId),
                'placements' => self::enabledPlacementIds($toolDeploymentId),
            );
        }

        $redirects = array();
        if ( isset($document['redirect_uris']) && is_array($document['redirect_uris']) ) {
            foreach ( $document['redirect_uris'] as $uri ) {
                if ( is_string($uri) && $uri !== '' ) {
                    $redirects[] = $uri;
                }
            }
        }

        $messages = array();
        foreach ( ToolRegistrationDocument::messagesForRegistration($registrationId) as $message ) {
            $placements = array();
            $have = array();
            foreach ( ToolPlacementService::getPlacementsForMessage((int) $message['message_id']) as $placement ) {
                $name = (string) $placement['placement'];
                $have[$name] = true;
                $described = self::placementDescribe($name);
                $placements[] = array(
                    'message_placement_id' => (int) $placement['message_placement_id'],
                    'value' => $name,
                    'label' => $described['label'],
                    'group' => $described['group'],
                    'menu' => $described['menu'],
                    'deployments' => self::grantFlags($grantRows, 'placements', (int) $placement['message_placement_id']),
                );
            }
            $messages[] = array(
                'message_id' => (int) $message['message_id'],
                'message_type' => (string) $message['message_type'],
                'type_label' => self::messageLabel($message['message_type']),
                'label' => $message['label'] === null ? '' : (string) $message['label'],
                'target_link_uri' => $message['target_link_uri'] === null ? '' : (string) $message['target_link_uri'],
                'custom_parameters' => ToolRegistrationDocument::customParameterMap(
                    isset($message['message_json']['custom_parameters']) ? $message['message_json']['custom_parameters'] : null
                ),
                'placements' => $placements,
                'placement_groups' => self::placementChoices($message['message_type'], $have),
            );
        }

        $messageTypes = array();
        foreach ( ToolMessagePlacement::knownMessageTypes() as $type ) {
            $messageTypes[] = array(
                'value' => $type,
                'label' => self::messageLabel($type),
            );
        }

        return array(
            'registration_id' => (int) $owned['registration_id'],
            'title' => (string) $owned['title'],
            'client_id' => self::column($registrationId, 'lti13_client_id'),
            'oidc_login_url' => self::column($registrationId, 'lti13_oidc_login_url'),
            'jwks_url' => self::column($registrationId, 'lti13_jwks_url'),
            'launch_url' => self::column($registrationId, 'lti13_launch_url'),
            'redirect_uris' => $redirects,
            'custom_parameters' => self::documentCustom($document),
            'registration_json' => self::prettyDocument($document),
            'registration_logs' => ToolRegistrationDocument::logsForRegistration($registrationId),
            'deployments' => $grantRows,
            'scopes' => self::presentRows(self::scopeCatalog(), $requestedScopes, $grantRows, 'scopes'),
            'scope_available' => self::missingRows(self::scopeCatalog(), $requestedScopes),
            'claims' => self::claimRows($requestedClaims, $grantRows),
            'claim_available' => self::missingRows(self::claimCatalog(), $requestedClaims),
            'messages' => $messages,
            'message_types' => $messageTypes,
            'missing' => self::missingRequired(
                self::column($registrationId, 'lti13_oidc_login_url'),
                self::column($registrationId, 'lti13_jwks_url'),
                self::column($registrationId, 'lti13_launch_url'),
                $redirects
            ),
        );
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return string flash message
     */
    public static function apply($contextId, $registrationId, array $post) {
        self::requireOwned($contextId, $registrationId);
        $action = self::posted($post, 'edit_action');
        $PDOX = self::db();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            $message = self::dispatch($contextId, $registrationId, $action, $post);
            if ( $owns ) {
                $PDOX->commit();
            }
            return $message;
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Create an empty LTI 1.3 registration this course owns.
     *
     * Client id and deployment id are assigned here. The login URL, keyset URL,
     * launch URL, and a redirect URL can wait. Until those are set, the tool
     * is a draft. Messages, scopes, and placements are added on the editor.
     *
     * @param int $contextId
     * @param int|null $userId
     * @param array<string, mixed> $post
     * @return int registration_id
     */
    public static function createManual($contextId, $userId, array $post) {
        $title = self::posted($post, 'title');
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Registration title is required.');
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('Registration title is too long.');
        }
        $login = self::optionalUrl(self::posted($post, 'oidc_login_url'), 'Login URL');
        $jwks = self::optionalUrl(self::posted($post, 'jwks_url'), 'Keyset URL');
        $launch = self::optionalUrl(self::posted($post, 'launch_url'), 'Launch URL');
        $redirect = self::optionalUrl(self::posted($post, 'redirect_uri'), 'Redirect URL');
        $clientId = self::uuid();
        $deploymentId = self::uuid();
        $userId = (int) $userId;
        $document = array(
            'client_name' => $title,
            'client_id' => $clientId,
            ToolRegistrationDocument::TOOL_CONFIGURATION => array(
                'deployment_id' => $deploymentId,
                'messages' => array(),
            ),
        );
        $document = self::putOptional($document, 'initiate_login_uri', $login);
        $document = self::putOptional($document, 'jwks_uri', $jwks);
        if ( $redirect !== null ) {
            $document['redirect_uris'] = array($redirect);
        }
        $document = self::putConfigOptional($document, 'target_link_uri', $launch);
        $made = ToolRegistrationService::createLti13ForCourse(
            (int) $contextId,
            $userId > 0 ? $userId : null,
            $title,
            array(
                'lti13_client_id' => $clientId,
                'lti13_oidc_login_url' => $login,
                'lti13_jwks_url' => $jwks,
                'lti13_launch_url' => $launch,
                'lti13_redirect_uri' => $redirect,
            ),
            $document,
            $deploymentId
        );
        return (int) $made['registration_id'];
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param string $action
     * @param array<string, mixed> $post
     * @return string
     */
    private static function dispatch($contextId, $registrationId, $action, array $post) {
        if ( $action === 'save_profile' ) {
            self::saveProfile($contextId, $registrationId, $post);
            return __('Saved.');
        }
        if ( $action === 'add_redirect' ) {
            self::addRedirect($registrationId, self::posted($post, 'redirect_uri'));
            return __('Added.');
        }
        if ( $action === 'delete_redirect' ) {
            self::deleteRedirect($registrationId, self::posted($post, 'redirect_uri'));
            return __('Removed.');
        }
        if ( $action === 'add_scope' ) {
            self::addScope($contextId, $registrationId, self::posted($post, 'scope'));
            return __('Added.');
        }
        if ( $action === 'delete_scope' ) {
            self::deleteScope($registrationId, self::posted($post, 'scope'));
            return __('Removed.');
        }
        if ( $action === 'scope_on' || $action === 'scope_off' ) {
            self::setScope($contextId, $registrationId, self::posted($post, 'scope'), self::postedInt($post, 'tool_deployment_id'), $action === 'scope_on');
            return $action === 'scope_on' ? __('Turned on.') : __('Turned off.');
        }
        if ( $action === 'add_claim' ) {
            self::addClaim($contextId, $registrationId, self::posted($post, 'claim'));
            return __('Added.');
        }
        if ( $action === 'delete_claim' ) {
            self::deleteClaim($registrationId, self::posted($post, 'claim'));
            return __('Removed.');
        }
        if ( $action === 'claim_on' || $action === 'claim_off' ) {
            self::setClaim($contextId, $registrationId, self::posted($post, 'claim'), self::postedInt($post, 'tool_deployment_id'), $action === 'claim_on');
            return $action === 'claim_on' ? __('Turned on.') : __('Turned off.');
        }
        if ( $action === 'add_message' ) {
            self::addMessage($registrationId, $post);
            return __('Added.');
        }
        if ( $action === 'save_message' ) {
            self::saveMessage($registrationId, $post);
            return __('Saved.');
        }
        if ( $action === 'delete_message' ) {
            self::deleteMessage($registrationId, self::postedInt($post, 'message_id'));
            return __('Removed.');
        }
        if ( $action === 'add_placement' ) {
            self::addPlacement($contextId, $registrationId, self::postedInt($post, 'message_id'), self::posted($post, 'placement'));
            return __('Added.');
        }
        if ( $action === 'delete_placement' ) {
            self::deletePlacement($registrationId, self::postedInt($post, 'message_id'), self::postedInt($post, 'message_placement_id'));
            return __('Removed.');
        }
        if ( $action === 'placement_on' || $action === 'placement_off' ) {
            self::setPlacement(
                $contextId,
                $registrationId,
                self::postedInt($post, 'message_id'),
                self::postedInt($post, 'message_placement_id'),
                self::postedInt($post, 'tool_deployment_id'),
                $action === 'placement_on'
            );
            return $action === 'placement_on' ? __('Turned on.') : __('Turned off.');
        }
        if ( $action === 'add_custom' ) {
            self::addCustom($registrationId, $post);
            return __('Added.');
        }
        if ( $action === 'delete_custom' ) {
            self::deleteCustom($registrationId, $post);
            return __('Removed.');
        }
        throw new \InvalidArgumentException('That edit is not available.');
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    private static function saveProfile($contextId, $registrationId, array $post) {
        $title = self::posted($post, 'title');
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Registration title is required.');
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('Registration title is too long.');
        }
        $login = self::optionalUrl(self::posted($post, 'oidc_login_url'), 'Login URL');
        $jwks = self::optionalUrl(self::posted($post, 'jwks_url'), 'Keyset URL');
        $launch = self::optionalUrl(self::posted($post, 'launch_url'), 'Launch URL');
        self::updateColumns($contextId, $registrationId, array(
            'title' => $title,
            'lti13_oidc_login_url' => $login,
            'lti13_jwks_url' => $jwks,
            'lti13_launch_url' => $launch,
        ));
        $document = self::document($registrationId);
        $document['client_name'] = $title;
        $document = self::putOptional($document, 'initiate_login_uri', $login);
        $document = self::putOptional($document, 'jwks_uri', $jwks);
        $document = self::putConfigOptional($document, 'target_link_uri', $launch);
        ToolRegistrationDocument::persistDocument($registrationId, $document);
    }

    /**
     * @param int $registrationId
     * @param string $uri
     * @return void
     */
    private static function addRedirect($registrationId, $uri) {
        $uri = self::httpUrl($uri, 'Redirect URL');
        $document = self::document($registrationId);
        $uris = self::redirectList($document);
        if ( in_array($uri, $uris, true) ) {
            throw new \InvalidArgumentException('That redirect URL is already on this tool.');
        }
        $uris[] = $uri;
        self::writeRedirects($registrationId, $document, $uris);
    }

    /**
     * @param int $registrationId
     * @param string $uri
     * @return void
     */
    private static function deleteRedirect($registrationId, $uri) {
        $uri = trim($uri);
        $document = self::document($registrationId);
        $uris = self::redirectList($document);
        $keep = array();
        $found = false;
        foreach ( $uris as $have ) {
            if ( $have === $uri ) {
                $found = true;
                continue;
            }
            $keep[] = $have;
        }
        if ( ! $found ) {
            throw new \InvalidArgumentException('That redirect URL is not on this tool.');
        }
        self::writeRedirects($registrationId, $document, $keep);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param string $scope
     * @return void
     */
    private static function addScope($contextId, $registrationId, $scope) {
        $scope = self::knownScope($scope);
        $document = self::document($registrationId);
        $scopes = ToolRegistrationDocument::requestedScopes($document);
        if ( in_array($scope, $scopes, true) ) {
            throw new \InvalidArgumentException('That scope is already on this tool.');
        }
        $scopes[] = $scope;
        self::writeScopes($registrationId, $document, $scopes);
        foreach ( self::courseDeployments($contextId, $registrationId) as $deployment ) {
            ToolDeploymentGrant::allowScope((int) $deployment['tool_deployment_id'], $scope);
        }
    }

    /**
     * @param int $registrationId
     * @param string $scope
     * @return void
     */
    private static function deleteScope($registrationId, $scope) {
        $scope = trim($scope);
        $document = self::document($registrationId);
        $scopes = ToolRegistrationDocument::requestedScopes($document);
        if ( ! in_array($scope, $scopes, true) ) {
            throw new \InvalidArgumentException('That scope is not on this tool.');
        }
        $keep = array();
        foreach ( $scopes as $have ) {
            if ( $have !== $scope ) {
                $keep[] = $have;
            }
        }
        self::writeScopes($registrationId, $document, $keep);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param string $scope
     * @param int $toolDeploymentId
     * @param bool $on
     * @return void
     */
    private static function setScope($contextId, $registrationId, $scope, $toolDeploymentId, $on) {
        $deployment = self::requireDeployment($contextId, $registrationId, $toolDeploymentId);
        $scope = trim($scope);
        if ( $on ) {
            ToolDeploymentGrant::allowScope((int) $deployment['tool_deployment_id'], $scope);
            return;
        }
        ToolDeploymentGrant::blockScope((int) $deployment['tool_deployment_id'], $scope);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param string $claim
     * @return void
     */
    private static function addClaim($contextId, $registrationId, $claim) {
        $claim = self::knownClaim($claim);
        $document = self::document($registrationId);
        $claims = ToolRegistrationDocument::requestedClaims($document);
        if ( in_array($claim, $claims, true) ) {
            throw new \InvalidArgumentException('That claim is already on this tool.');
        }
        $claims[] = $claim;
        self::writeClaims($registrationId, $document, $claims);
        foreach ( self::courseDeployments($contextId, $registrationId) as $deployment ) {
            ToolDeploymentGrant::allowClaim((int) $deployment['tool_deployment_id'], $claim);
        }
    }

    /**
     * @param int $registrationId
     * @param string $claim
     * @return void
     */
    private static function deleteClaim($registrationId, $claim) {
        self::rejectLockedClaim($claim);
        $claim = trim($claim);
        $document = self::document($registrationId);
        $claims = ToolRegistrationDocument::requestedClaims($document);
        if ( ! in_array($claim, $claims, true) ) {
            throw new \InvalidArgumentException('That claim is not on this tool.');
        }
        $keep = array();
        foreach ( $claims as $have ) {
            if ( $have !== $claim ) {
                $keep[] = $have;
            }
        }
        self::writeClaims($registrationId, $document, $keep);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param string $claim
     * @param int $toolDeploymentId
     * @param bool $on
     * @return void
     */
    private static function setClaim($contextId, $registrationId, $claim, $toolDeploymentId, $on) {
        self::rejectLockedClaim($claim);
        $deployment = self::requireDeployment($contextId, $registrationId, $toolDeploymentId);
        $claim = trim($claim);
        if ( $on ) {
            ToolDeploymentGrant::allowClaim((int) $deployment['tool_deployment_id'], $claim);
            return;
        }
        ToolDeploymentGrant::blockClaim((int) $deployment['tool_deployment_id'], $claim);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    private static function addMessage($registrationId, array $post) {
        $type = self::posted($post, 'message_type');
        if ( ! in_array($type, ToolMessagePlacement::knownMessageTypes(), true) ) {
            throw new \InvalidArgumentException('That launch is not a registration message.');
        }
        $label = self::optionalLabel(self::posted($post, 'label'));
        $target = self::optionalUrl(self::posted($post, 'target_link_uri'), 'Target link');
        $object = array('type' => $type);
        if ( $label !== null ) {
            $object['label'] = $label;
        }
        if ( $target !== null ) {
            $object['target_link_uri'] = $target;
        }
        $document = self::document($registrationId);
        $messages = ToolRegistrationDocument::messagesInDocument($document);
        $messages[] = $object;
        ToolRegistrationDocument::persistDocument($registrationId, ToolRegistrationDocument::withMessages($document, $messages));
        ToolRegistrationDocument::appendMessageRow($registrationId, $object);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    private static function saveMessage($registrationId, array $post) {
        $message = self::requireMessage($registrationId, self::postedInt($post, 'message_id'));
        $label = self::optionalLabel(self::posted($post, 'label'));
        $target = self::optionalUrl(self::posted($post, 'target_link_uri'), 'Target link');
        $object = self::messageObject($message);
        if ( $label === null ) {
            unset($object['label']);
        } else {
            $object['label'] = $label;
        }
        if ( $target === null ) {
            unset($object['target_link_uri']);
        } else {
            $object['target_link_uri'] = $target;
        }
        self::writeMessageObject($registrationId, $message, $object);
    }

    /**
     * @param int $registrationId
     * @param int $messageId
     * @return void
     */
    private static function deleteMessage($registrationId, $messageId) {
        $message = self::requireMessage($registrationId, $messageId);
        $document = self::document($registrationId);
        $messages = ToolRegistrationDocument::messagesInDocument($document);
        $sequence = (int) $message['sequence'];
        if ( ! self::documentMessageMatches($messages, $sequence, $message['message_type']) ) {
            throw new \InvalidArgumentException('That message is not in the registration document.');
        }
        array_splice($messages, $sequence, 1);
        ToolRegistrationDocument::persistDocument($registrationId, ToolRegistrationDocument::withMessages($document, $messages));
        ToolRegistrationDocument::deleteMessageRow($registrationId, (int) $message['message_id']);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param int $messageId
     * @param string $placement
     * @return void
     */
    private static function addPlacement($contextId, $registrationId, $messageId, $placement) {
        $message = self::requireMessage($registrationId, $messageId);
        $placement = ToolMessagePlacement::assertCompatible($message['message_type'], $placement);
        $messagePlacementId = ToolPlacementService::addPlacementToMessage((int) $message['message_id'], $placement);
        foreach ( self::courseDeployments($contextId, $registrationId) as $deployment ) {
            ToolPlacementService::enablePlacementForDeployment((int) $deployment['tool_deployment_id'], $messagePlacementId);
        }
        $object = self::messageObject($message);
        $names = array();
        foreach ( ToolPlacementService::getPlacementsForMessage((int) $message['message_id']) as $row ) {
            $names[] = (string) $row['placement'];
        }
        $object['placements'] = $names;
        self::writeMessageObject($registrationId, $message, $object);
    }

    /**
     * @param int $registrationId
     * @param int $messageId
     * @param int $messagePlacementId
     * @return void
     */
    private static function deletePlacement($registrationId, $messageId, $messagePlacementId) {
        $message = self::requireMessage($registrationId, $messageId);
        $placement = self::requirePlacement($message, $messagePlacementId);
        ToolPlacementService::removePlacementFromMessage((int) $message['message_id'], $placement['placement']);
        $object = self::messageObject($message);
        $names = array();
        if ( isset($object['placements']) && is_array($object['placements']) ) {
            foreach ( $object['placements'] as $name ) {
                if ( is_string($name) && $name !== $placement['placement'] ) {
                    $names[] = $name;
                }
            }
        }
        if ( count($names) === 0 ) {
            unset($object['placements']);
        } else {
            $object['placements'] = $names;
        }
        self::writeMessageObject($registrationId, $message, $object);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param int $messageId
     * @param int $messagePlacementId
     * @param int $toolDeploymentId
     * @param bool $on
     * @return void
     */
    private static function setPlacement($contextId, $registrationId, $messageId, $messagePlacementId, $toolDeploymentId, $on) {
        $message = self::requireMessage($registrationId, $messageId);
        $placement = self::requirePlacement($message, $messagePlacementId);
        $deployment = self::requireDeployment($contextId, $registrationId, $toolDeploymentId);
        if ( $on ) {
            ToolPlacementService::enablePlacementForDeployment((int) $deployment['tool_deployment_id'], (int) $placement['message_placement_id']);
            return;
        }
        ToolPlacementService::disablePlacementForDeployment((int) $deployment['tool_deployment_id'], (int) $placement['message_placement_id']);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    private static function addCustom($registrationId, array $post) {
        $name = self::customName(self::posted($post, 'custom_name'));
        $value = self::customValue(self::posted($post, 'custom_value'));
        $messageId = self::postedMessageId($post);
        if ( $messageId === null ) {
            $document = self::document($registrationId);
            $custom = self::rawCustom(self::configCustom($document));
            $custom[$name] = $value;
            self::writeConfigCustom($registrationId, $document, $custom);
            return;
        }
        $message = self::requireMessage($registrationId, $messageId);
        $object = self::messageObject($message);
        $custom = self::rawCustom(isset($object['custom_parameters']) ? $object['custom_parameters'] : null);
        $custom[$name] = $value;
        self::writeMessageCustom($registrationId, $message, $custom);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    private static function deleteCustom($registrationId, array $post) {
        $name = trim(self::posted($post, 'custom_name'));
        if ( $name === '' || strlen($name) > 255 ) {
            throw new \InvalidArgumentException('That custom parameter is not on this tool.');
        }
        $messageId = self::postedMessageId($post);
        if ( $messageId === null ) {
            $document = self::document($registrationId);
            $custom = self::rawCustom(self::configCustom($document));
            if ( ! array_key_exists($name, $custom) ) {
                throw new \InvalidArgumentException('That custom parameter is not on this tool.');
            }
            unset($custom[$name]);
            self::writeConfigCustom($registrationId, $document, $custom);
            return;
        }
        $message = self::requireMessage($registrationId, $messageId);
        $object = self::messageObject($message);
        $custom = self::rawCustom(isset($object['custom_parameters']) ? $object['custom_parameters'] : null);
        if ( ! array_key_exists($name, $custom) ) {
            throw new \InvalidArgumentException('That custom parameter is not on this message.');
        }
        unset($custom[$name]);
        self::writeMessageCustom($registrationId, $message, $custom);
    }

    /**
     * @param array<string, mixed> $post
     * @return int|null
     */
    private static function postedMessageId(array $post) {
        if ( ! array_key_exists('message_id', $post) ) {
            return null;
        }
        $text = trim((string) $post['message_id']);
        if ( $text === '' ) {
            return null;
        }
        $messageId = self::postedInt($post, 'message_id');
        if ( $messageId < 1 ) {
            throw new \InvalidArgumentException('That message is not on this tool.');
        }
        return $messageId;
    }

    /**
     * @param string $name
     * @return string
     */
    private static function customName($name) {
        $name = trim($name);
        if ( $name === '' || strlen($name) > 255 || ! preg_match('/^[A-Za-z][A-Za-z0-9_.:-]*$/', $name) ) {
            throw new \InvalidArgumentException('Custom parameter name is not valid.');
        }
        return $name;
    }

    /**
     * @param string $value
     * @return string
     */
    private static function customValue($value) {
        if ( strlen($value) > 2048 || strpos($value, "\0") !== false ) {
            throw new \InvalidArgumentException('Custom parameter value is too long.');
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function rawCustom($value) {
        if ( ! is_array($value) || array_is_list($value) ) {
            return array();
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $document
     * @return mixed
     */
    private static function configCustom(array $document) {
        $key = ToolRegistrationDocument::TOOL_CONFIGURATION;
        if ( ! isset($document[$key]) || ! is_array($document[$key]) || array_is_list($document[$key]) ) {
            return null;
        }
        return isset($document[$key]['custom_parameters']) ? $document[$key]['custom_parameters'] : null;
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, string>
     */
    private static function documentCustom(array $document) {
        return ToolRegistrationDocument::customParameterMap(self::configCustom($document));
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $document
     * @param array<string, mixed> $custom
     * @return void
     */
    private static function writeConfigCustom($registrationId, array $document, array $custom) {
        $key = ToolRegistrationDocument::TOOL_CONFIGURATION;
        if ( count($custom) === 0 ) {
            if ( isset($document[$key]) && is_array($document[$key]) && ! array_is_list($document[$key]) ) {
                unset($document[$key]['custom_parameters']);
            }
        } else {
            $document = self::setConfigValue($document, 'custom_parameters', $custom);
        }
        ToolRegistrationDocument::persistDocument($registrationId, $document);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $message
     * @param array<string, mixed> $custom
     * @return void
     */
    private static function writeMessageCustom($registrationId, array $message, array $custom) {
        $object = self::messageObject($message);
        if ( count($custom) === 0 ) {
            unset($object['custom_parameters']);
        } else {
            $object['custom_parameters'] = $custom;
        }
        self::writeMessageObject($registrationId, $message, $object);
    }

    /**
     * @param array<string, mixed> $document
     * @return string
     */
    private static function prettyDocument(array $document) {
        $encoded = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return is_string($encoded) ? $encoded : '';
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $document
     * @param array<int, string> $uris
     * @return void
     */
    private static function writeRedirects($registrationId, array $document, array $uris) {
        $document['redirect_uris'] = array_values($uris);
        ToolRegistrationDocument::persistDocument($registrationId, $document);
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "UPDATE {$p}lti_tool_registration
             SET lti13_redirect_uri = :lti13_redirect_uri, updated_at = NOW()
             WHERE registration_id = :registration_id AND lti_version = '1.3'",
            array(
                ':lti13_redirect_uri' => count($uris) > 0 ? $uris[0] : null,
                ':registration_id' => (int) $registrationId,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not update tool registration. '.$detail);
        }
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $document
     * @param array<int, string> $scopes
     * @return void
     */
    private static function writeScopes($registrationId, array $document, array $scopes) {
        $text = self::orderedScope($scopes);
        if ( $text === '' ) {
            unset($document['scope']);
        } else {
            $document['scope'] = $text;
        }
        ToolRegistrationDocument::persistDocument($registrationId, $document);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $document
     * @param array<int, string> $claims
     * @return void
     */
    private static function writeClaims($registrationId, array $document, array $claims) {
        $document = self::setConfigValue($document, 'claims', self::orderedClaims($claims));
        ToolRegistrationDocument::persistDocument($registrationId, $document);
    }

    /**
     * @param int $registrationId
     * @param array<string, mixed> $message
     * @param array<string, mixed> $object
     * @return void
     */
    private static function writeMessageObject($registrationId, array $message, array $object) {
        $document = self::document($registrationId);
        $messages = ToolRegistrationDocument::messagesInDocument($document);
        $sequence = (int) $message['sequence'];
        if ( ! self::documentMessageMatches($messages, $sequence, $message['message_type']) ) {
            throw new \InvalidArgumentException('That message is not in the registration document.');
        }
        $object['type'] = (string) $messages[$sequence]['type'];
        $messages[$sequence] = $object;
        ToolRegistrationDocument::saveMessageRow($registrationId, (int) $message['message_id'], $object);
        ToolRegistrationDocument::persistDocument($registrationId, ToolRegistrationDocument::withMessages($document, $messages));
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param array<string, string|null> $fields
     * @return void
     */
    private static function updateColumns($contextId, $registrationId, array $fields) {
        $contextId = (int) $contextId;
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "UPDATE {$p}lti_tool_registration
             SET title = :title, lti13_oidc_login_url = :lti13_oidc_login_url,
                 lti13_jwks_url = :lti13_jwks_url, lti13_launch_url = :lti13_launch_url, updated_at = NOW()
             WHERE registration_id = :registration_id
               AND owner_context_id = :owner_context_id
               AND lti_version = '1.3'",
            array(
                ':title' => $fields['title'],
                ':lti13_oidc_login_url' => $fields['lti13_oidc_login_url'],
                ':lti13_jwks_url' => $fields['lti13_jwks_url'],
                ':lti13_launch_url' => $fields['lti13_launch_url'],
                ':registration_id' => (int) $registrationId,
                ':owner_context_id' => $contextId,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not update tool registration. '.$detail);
        }
    }

    /**
     * @param array<string, mixed> $document
     * @param string $key
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function setConfigValue(array $document, $key, $value) {
        $configKey = ToolRegistrationDocument::TOOL_CONFIGURATION;
        if ( ! isset($document[$configKey]) || ! is_array($document[$configKey])
            || (array_is_list($document[$configKey]) && $document[$configKey] !== array()) ) {
            $document[$configKey] = array();
        }
        $document[$configKey][$key] = $value;
        return $document;
    }

    /**
     * @param array<int, string> $scopes
     * @return string
     */
    private static function orderedScope(array $scopes) {
        $known = array_fill_keys(ToolRegistrationDocument::scopeVocabulary(), true);
        $wanted = array_fill_keys($scopes, true);
        $out = array();
        foreach ( ToolRegistrationDocument::scopeVocabulary() as $scope ) {
            if ( isset($wanted[$scope]) ) {
                $out[] = $scope;
            }
        }
        foreach ( $scopes as $scope ) {
            if ( ! isset($known[$scope]) ) {
                $out[] = $scope;
            }
        }
        return implode(' ', $out);
    }

    /**
     * iss and sub stay first. Unknown claims already on the tool stay after the known ones.
     *
     * @param array<int, string> $claims
     * @return array<int, string>
     */
    private static function orderedClaims(array $claims) {
        $toggleable = array();
        foreach ( self::claimCatalog() as $row ) {
            $toggleable[$row['value']] = true;
        }
        $wanted = array_fill_keys($claims, true);
        $out = array('iss', 'sub');
        foreach ( self::claimCatalog() as $row ) {
            if ( isset($wanted[$row['value']]) ) {
                $out[] = $row['value'];
            }
        }
        foreach ( $claims as $claim ) {
            if ( $claim !== 'iss' && $claim !== 'sub' && ! isset($toggleable[$claim]) ) {
                $out[] = $claim;
            }
        }
        return $out;
    }

    /**
     * @param string $scope
     * @return string
     */
    private static function knownScope($scope) {
        $scope = trim($scope);
        if ( ! in_array($scope, ToolRegistrationDocument::scopeVocabulary(), true) ) {
            throw new \InvalidArgumentException('That scope is not a registration scope.');
        }
        return $scope;
    }

    /**
     * @param string $claim
     * @return string
     */
    private static function knownClaim($claim) {
        self::rejectLockedClaim($claim);
        $claim = trim($claim);
        $allowed = array();
        foreach ( self::claimCatalog() as $row ) {
            $allowed[$row['value']] = true;
        }
        if ( ! isset($allowed[$claim]) ) {
            throw new \InvalidArgumentException('That claim is not a registration claim.');
        }
        return $claim;
    }

    /**
     * @param string $claim
     * @return void
     */
    private static function rejectLockedClaim($claim) {
        $claim = trim($claim);
        if ( $claim === 'iss' || $claim === 'sub' ) {
            throw new \InvalidArgumentException('iss and sub are always sent.');
        }
    }

    /**
     * @param array<string, mixed> $document
     * @return array<int, string>
     */
    private static function redirectList(array $document) {
        $uris = array();
        if ( ! isset($document['redirect_uris']) || ! is_array($document['redirect_uris']) ) {
            return $uris;
        }
        foreach ( $document['redirect_uris'] as $uri ) {
            if ( is_string($uri) && $uri !== '' ) {
                $uris[] = $uri;
            }
        }
        return $uris;
    }

    /**
     * The document message at this sequence is the same launch as the row.
     *
     * The document may still say LtiResourceLink. The row says LtiResourceLinkRequest.
     *
     * @param array<int, mixed> $messages
     * @param int $sequence
     * @param string $messageType
     * @return bool
     */
    private static function documentMessageMatches(array $messages, $sequence, $messageType) {
        if ( ! isset($messages[$sequence]) || ! is_array($messages[$sequence]) || ! isset($messages[$sequence]['type']) ) {
            return false;
        }
        return ToolRegistrationDocument::canonicalMessageType($messages[$sequence]['type'])
            === ToolRegistrationDocument::canonicalMessageType($messageType);
    }

    /**
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    private static function messageObject(array $message) {
        $object = isset($message['message_json']) && is_array($message['message_json']) ? $message['message_json'] : array();
        if ( array_is_list($object) ) {
            $object = array();
        }
        $object['type'] = $message['message_type'];
        return $object;
    }

    /**
     * @param int $registrationId
     * @param int $messageId
     * @return array<string, mixed>
     */
    private static function requireMessage($registrationId, $messageId) {
        foreach ( ToolRegistrationDocument::messagesForRegistration($registrationId) as $message ) {
            if ( (int) $message['message_id'] === (int) $messageId ) {
                return $message;
            }
        }
        throw new \InvalidArgumentException('That message is not on this tool.');
    }

    /**
     * @param array<string, mixed> $message
     * @param int $messagePlacementId
     * @return array<string, mixed>
     */
    private static function requirePlacement(array $message, $messagePlacementId) {
        foreach ( ToolPlacementService::getPlacementsForMessage((int) $message['message_id']) as $placement ) {
            if ( (int) $placement['message_placement_id'] === (int) $messagePlacementId ) {
                return $placement;
            }
        }
        throw new \InvalidArgumentException('That placement is not on this message.');
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param int $toolDeploymentId
     * @return array<string, mixed>
     */
    private static function requireDeployment($contextId, $registrationId, $toolDeploymentId) {
        foreach ( self::courseDeployments($contextId, $registrationId) as $deployment ) {
            if ( (int) $deployment['tool_deployment_id'] === (int) $toolDeploymentId ) {
                return $deployment;
            }
        }
        throw new \InvalidArgumentException('This course does not have that deployment.');
    }

    /**
     * @param int $toolDeploymentId
     * @return array<int, int>
     */
    private static function enabledPlacementIds($toolDeploymentId) {
        $ids = array();
        foreach ( ToolPlacementService::getEnabledPlacementsForDeployment($toolDeploymentId) as $placement ) {
            $ids[] = (int) $placement['message_placement_id'];
        }
        return $ids;
    }

    /**
     * @param array<int, array<string, mixed>> $grantRows
     * @param string $key
     * @param int|string $value
     * @return array<int, array{tool_deployment_id:int, deployment_id:string, on:bool}>
     */
    private static function grantFlags(array $grantRows, $key, $value) {
        $out = array();
        foreach ( $grantRows as $row ) {
            $out[] = array(
                'tool_deployment_id' => (int) $row['tool_deployment_id'],
                'deployment_id' => (string) $row['deployment_id'],
                'on' => in_array($value, $row[$key], true),
            );
        }
        return $out;
    }

    /**
     * @param array<int, array{value:string, label:string}> $catalog
     * @param array<int, string> $requested
     * @param array<int, array<string, mixed>> $grantRows
     * @param string $key
     * @return array<int, array<string, mixed>>
     */
    private static function presentRows(array $catalog, array $requested, array $grantRows, $key) {
        $labels = array();
        foreach ( $catalog as $row ) {
            $labels[$row['value']] = $row['label'];
        }
        $ordered = array();
        foreach ( $catalog as $row ) {
            if ( in_array($row['value'], $requested, true) ) {
                $ordered[] = $row['value'];
            }
        }
        foreach ( $requested as $value ) {
            if ( ! isset($labels[$value]) ) {
                $ordered[] = $value;
            }
        }
        $out = array();
        foreach ( $ordered as $value ) {
            $out[] = array(
                'value' => $value,
                'label' => isset($labels[$value]) ? $labels[$value] : $value,
                'deployments' => self::grantFlags($grantRows, $key, $value),
            );
        }
        return $out;
    }

    /**
     * @param array<int, string> $requested
     * @param array<int, array<string, mixed>> $grantRows
     * @return array<int, array<string, mixed>>
     */
    private static function claimRows(array $requested, array $grantRows) {
        $rows = array(
            array(
                'value' => 'iss',
                'label' => __('Issuer'),
                'locked' => true,
                'deployments' => array(),
            ),
            array(
                'value' => 'sub',
                'label' => __('Subject'),
                'locked' => true,
                'deployments' => array(),
            ),
        );
        foreach ( self::presentRows(self::claimCatalog(), $requested, $grantRows, 'claims') as $row ) {
            $row['locked'] = false;
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @param array<int, array{value:string, label:string}> $catalog
     * @param array<int, string> $requested
     * @return array<int, array{value:string, label:string}>
     */
    private static function missingRows(array $catalog, array $requested) {
        $out = array();
        foreach ( $catalog as $row ) {
            if ( ! in_array($row['value'], $requested, true) ) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private static function scopeCatalog() {
        return array(
            array('value' => ToolRegistrationDocument::SCOPE_SCORE, 'label' => __('Return a score')),
            array('value' => ToolRegistrationDocument::SCOPE_LINEITEM, 'label' => __('Create and edit line items')),
            array('value' => ToolRegistrationDocument::SCOPE_LINEITEM_READONLY, 'label' => __('Read line items')),
            array('value' => ToolRegistrationDocument::SCOPE_RESULT, 'label' => __('Read results')),
            array('value' => ToolRegistrationDocument::SCOPE_ROSTER, 'label' => __('Read the course roster')),
        );
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private static function claimCatalog() {
        return array(
            array('value' => 'name', 'label' => __('Name')),
            array('value' => 'given_name', 'label' => __('Given name')),
            array('value' => 'family_name', 'label' => __('Family name')),
            array('value' => 'email', 'label' => __('Email')),
        );
    }

    /**
     * Suggested placements for the editor. The stored value is the identifier.
     *
     * Dynamic Registration names ContentArea and RichTextEditor. Everything
     * else is a platform extension. A string that is not listed here can
     * still be stored.
     *
     * @return array<int, array{label: string, placements: array<int, array{value: string, label: string}>}>
     */
    private static function placementCatalog() {
        return array(
            array(
                'label' => __('Standard LTI'),
                'placements' => array(
                    array('value' => 'ContentArea', 'label' => __('Content area')),
                    array('value' => 'RichTextEditor', 'label' => __('Rich text editor')),
                ),
            ),
            array(
                'label' => __('Canvas'),
                'placements' => array(
                    array('value' => 'account_navigation', 'label' => __('Account navigation')),
                    array('value' => 'assignment_selection', 'label' => __('Assignment selection')),
                    array('value' => 'course_navigation', 'label' => __('Course navigation')),
                    array('value' => 'editor_button', 'label' => __('Editor button')),
                    array('value' => 'global_navigation', 'label' => __('Global navigation')),
                    array('value' => 'homework_submission', 'label' => __('Homework submission')),
                    array('value' => 'link_selection', 'label' => __('Link selection')),
                    array('value' => 'migration_selection', 'label' => __('Migration selection')),
                    array('value' => 'user_navigation', 'label' => __('User navigation')),
                ),
            ),
            array(
                'label' => __('Tsugi'),
                'placements' => array(
                    // These stay here so a stored row still has a label.
                    // The add menu does not offer them. migration_selection,
                    // RichTextEditor, and ContentArea are those placements.
                    array('value' => 'common_cartridge', 'label' => __('Common cartridge'), 'menu' => false),
                    array('value' => 'content_editor', 'label' => __('Content editor'), 'menu' => false),
                    array('value' => 'lessons', 'label' => __('Lessons'), 'menu' => false),
                ),
            ),
        );
    }

    /**
     * @param string $placement
     * @return array{label: string, group: string, menu: bool}
     */
    private static function placementDescribe($placement) {
        foreach ( self::placementCatalog() as $group ) {
            foreach ( $group['placements'] as $row ) {
                if ( $row['value'] === $placement ) {
                    return array(
                        'label' => $row['label'],
                        'group' => $group['label'],
                        'menu' => ! (isset($row['menu']) && $row['menu'] === false),
                    );
                }
            }
        }
        return array(
            'label' => $placement,
            'group' => '',
            'menu' => false,
        );
    }

    /**
     * Menu of placements this message type can still add.
     *
     * A known placement is offered only when this message type can carry it.
     * A placement the catalog does not name stays available under Other.
     *
     * @param string $messageType
     * @param array<string, true> $have
     * @return array<int, array{label: string, placements: array<int, array{value: string, label: string}>}>
     */
    private static function placementChoices($messageType, array $have) {
        $known = array();
        foreach ( ToolMessagePlacement::knownPlacements($messageType) as $name ) {
            $known[$name] = true;
        }
        $groups = array();
        $seen = array();
        foreach ( self::placementCatalog() as $group ) {
            $options = array();
            foreach ( $group['placements'] as $row ) {
                $seen[$row['value']] = true;
                if ( isset($row['menu']) && $row['menu'] === false ) {
                    continue;
                }
                if ( isset($have[$row['value']]) || ! isset($known[$row['value']]) ) {
                    continue;
                }
                $options[] = $row;
            }
            if ( count($options) > 0 ) {
                $groups[] = array(
                    'label' => $group['label'],
                    'placements' => $options,
                );
            }
        }
        $other = array();
        foreach ( array_keys($known) as $name ) {
            if ( isset($have[$name]) || isset($seen[$name]) ) {
                continue;
            }
            $other[] = array(
                'value' => $name,
                'label' => $name,
            );
        }
        if ( count($other) > 0 ) {
            $groups[] = array(
                'label' => __('Other'),
                'placements' => $other,
            );
        }
        return $groups;
    }

    /**
     * @param string $type
     * @return string
     */
    private static function messageLabel($type) {
        $labels = array(
            'LtiResourceLinkRequest' => __('Resource link'),
            'LtiDeepLinkingRequest' => __('Deep link'),
            'LtiDataPrivacyLaunchRequest' => __('Privacy'),
            'LtiContextLaunchRequest' => __('Context'),
        );
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    /**
     * @param int $registrationId
     * @return array<string, mixed>
     */
    private static function document($registrationId) {
        $document = ToolRegistrationDocument::registrationDocument($registrationId);
        if ( ! is_array($document) || array_is_list($document) ) {
            throw new \InvalidArgumentException('This tool has no registration document.');
        }
        return $document;
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @return array<string, mixed>
     */
    private static function requireOwned($contextId, $registrationId) {
        $registrationId = (int) $registrationId;
        $contextId = (int) $contextId;
        if ( ! ToolRegistrationService::mayAdminister($registrationId, array('context_id' => $contextId)) ) {
            throw new \InvalidArgumentException('This course does not own that tool.');
        }
        $found = ToolRegistrationService::findRegistration($registrationId);
        if ( $found === null || $found['lti_version'] !== '1.3' ) {
            throw new \InvalidArgumentException('This page edits an LTI 1.3 tool.');
        }
        return $found;
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @return array<int, array<string, mixed>>
     */
    private static function courseDeployments($contextId, $registrationId) {
        $out = array();
        foreach ( ToolDeploymentService::getDeploymentsForContext($contextId) as $deployment ) {
            if ( (int) $deployment['registration_id'] === (int) $registrationId ) {
                $out[] = $deployment;
            }
        }
        return $out;
    }

    /**
     * @param int $registrationId
     * @param string $column
     * @return string
     */
    private static function column($registrationId, $column) {
        $allowed = array(
            'lti13_client_id' => true,
            'lti13_oidc_login_url' => true,
            'lti13_jwks_url' => true,
            'lti13_launch_url' => true,
        );
        if ( ! isset($allowed[$column]) ) {
            throw new \InvalidArgumentException('Unknown registration field.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT {$column} AS value FROM {$p}lti_tool_registration WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $registrationId)
        );
        if ( ! is_array($row) || $row['value'] === null ) {
            return '';
        }
        return (string) $row['value'];
    }

    /**
     * @param string $value
     * @param string $label
     * @return string
     */
    private static function httpUrl($value, $label) {
        $value = trim($value);
        if ( $value === '' || ! preg_match('#^https?://#i', $value) || preg_match('/\s/', $value) ) {
            throw new \InvalidArgumentException($label.' must be an http URL.');
        }
        if ( strlen($value) > 2048 ) {
            throw new \InvalidArgumentException($label.' is too long.');
        }
        return $value;
    }

    /**
     * Login URL, keyset URL, launch URL, and one redirect URL.
     * An empty list means the registration is still a draft.
     *
     * @param string|null $login
     * @param string|null $jwks
     * @param string|null $launch
     * @param array<int, mixed> $redirects
     * @return array<int, string>
     */
    public static function missingRequired($login, $jwks, $launch, array $redirects) {
        $missing = array();
        if ( trim((string) $login) === '' ) {
            $missing[] = __('Login URL');
        }
        if ( trim((string) $jwks) === '' ) {
            $missing[] = __('Keyset URL');
        }
        if ( trim((string) $launch) === '' ) {
            $missing[] = __('Launch URL');
        }
        $redirect = false;
        foreach ( $redirects as $uri ) {
            if ( is_string($uri) && trim($uri) !== '' ) {
                $redirect = true;
                break;
            }
        }
        if ( ! $redirect ) {
            $missing[] = __('Redirect URL');
        }
        return $missing;
    }

    /**
     * LTI 1.3 registrations in this set that are still missing a required URL.
     *
     * @param array<int, mixed> $registrationIds
     * @return array<int, true>
     */
    public static function draftIds(array $registrationIds) {
        $ids = array();
        foreach ( $registrationIds as $registrationId ) {
            $registrationId = (int) $registrationId;
            if ( $registrationId > 0 ) {
                $ids[$registrationId] = $registrationId;
            }
        }
        if ( count($ids) === 0 ) {
            return array();
        }
        $params = array();
        $holders = array();
        $i = 0;
        foreach ( $ids as $registrationId ) {
            $key = ':id'.$i;
            $holders[] = $key;
            $params[$key] = $registrationId;
            $i++;
        }
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT registration_id
             FROM {$p}lti_tool_registration
             WHERE registration_id IN (".implode(', ', $holders).")
               AND lti_version = '1.3'
               AND (
                    lti13_oidc_login_url IS NULL OR lti13_oidc_login_url = ''
                    OR lti13_jwks_url IS NULL OR lti13_jwks_url = ''
                    OR lti13_launch_url IS NULL OR lti13_launch_url = ''
                    OR lti13_redirect_uri IS NULL OR lti13_redirect_uri = ''
               )",
            $params
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[(int) $row['registration_id']] = true;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $document
     * @param string $key
     * @param string|null $value
     * @return array<string, mixed>
     */
    private static function putOptional(array $document, $key, $value) {
        if ( $value === null || $value === '' ) {
            unset($document[$key]);
            return $document;
        }
        $document[$key] = $value;
        return $document;
    }

    /**
     * @param array<string, mixed> $document
     * @param string $key
     * @param string|null $value
     * @return array<string, mixed>
     */
    private static function putConfigOptional(array $document, $key, $value) {
        if ( $value === null || $value === '' ) {
            $configKey = ToolRegistrationDocument::TOOL_CONFIGURATION;
            if ( isset($document[$configKey]) && is_array($document[$configKey]) ) {
                unset($document[$configKey][$key]);
            }
            return $document;
        }
        return self::setConfigValue($document, $key, $value);
    }

    /**
     * @param string $value
     * @param string $label
     * @return string|null
     */
    private static function optionalUrl($value, $label) {
        $value = trim($value);
        if ( $value === '' ) {
            return null;
        }
        return self::httpUrl($value, $label);
    }

    /**
     * @param string $value
     * @return string|null
     */
    private static function optionalLabel($value) {
        $value = trim($value);
        if ( $value === '' ) {
            return null;
        }
        if ( mb_strlen($value) > 512 ) {
            throw new \InvalidArgumentException('Label is too long.');
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $post
     * @param string $key
     * @return string
     */
    private static function posted(array $post, $key) {
        if ( ! isset($post[$key]) || is_array($post[$key]) ) {
            return '';
        }
        return trim((string) $post[$key]);
    }

    /**
     * @param array<string, mixed> $post
     * @param string $key
     * @return int
     */
    private static function postedInt(array $post, $key) {
        $value = self::posted($post, $key);
        if ( $value === '' || ! ctype_digit($value) ) {
            return 0;
        }
        return (int) $value;
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        global $CFG, $PDOX;
        LTIX::getConnection();
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return (string) $CFG->dbprefix;
    }

    /**
     * @return string
     */
    private static function uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $hex = bin2hex($data);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }
}
