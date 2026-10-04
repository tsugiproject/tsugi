<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Util\LTI;

/**
 * A signed LTI 1.1 test launch for a tool this course can already see.
 *
 * Resource link is sent now. Content item, privacy, and course scoped stay
 * in the same list so a later test can sign those message types too.
 */
class Lti11TestLaunch {

    /**
     * Internal message type, the words on the test page, the LTI 1.1 wire
     * value, and whether this test can sign it.
     *
     * @return array<string, array{label:string, wire:?string, ready:bool}>
     */
    public static function launches() {
        return array(
            'LtiResourceLinkRequest' => array(
                'label' => 'Resource link',
                'wire' => 'basic-lti-launch-request',
                'ready' => true,
            ),
            'LtiDeepLinkingRequest' => array(
                'label' => 'Content item',
                'wire' => 'ContentItemSelectionRequest',
                'ready' => false,
            ),
            'LtiDataPrivacyLaunchRequest' => array(
                'label' => 'Privacy launch',
                'wire' => null,
                'ready' => false,
            ),
            'LtiContextLaunchRequest' => array(
                'label' => 'Course scoped launch',
                'wire' => null,
                'ready' => false,
            ),
        );
    }

    /**
     * Launches this tool registered, in test-page order.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array<int, array{type:string, label:string, ready:bool}>
     */
    public static function choices($contextId, $registrationId) {
        $tool = ToolRegistrationService::visibleLti11((int) $contextId, (int) $registrationId);
        $have = array();
        foreach ( ToolRegistrationDocument::messagesForRegistration($tool['registration_id']) as $message ) {
            $have[$message['message_type']] = true;
        }
        $choices = array();
        foreach ( self::launches() as $type => $spec ) {
            if ( ! isset($have[$type]) ) {
                continue;
            }
            $choices[] = array(
                'type' => $type,
                'label' => $spec['label'],
                'ready' => $spec['ready'],
            );
        }
        return $choices;
    }

    /**
     * Instructor or Learner. An empty value is Instructor.
     *
     * @param string $role
     * @return string
     */
    public static function role($role) {
        $role = trim((string) $role);
        if ( $role === '' ) {
            return 'Instructor';
        }
        if ( $role !== 'Instructor' && $role !== 'Learner' ) {
            throw new \InvalidArgumentException('That role is not part of this test.');
        }
        return $role;
    }

    /**
     * The launch to open when the test page does not name one.
     *
     * @param array<int, array{type:string, label:string, ready:bool}> $choices
     * @return string|null
     */
    public static function defaultType(array $choices) {
        foreach ( $choices as $choice ) {
            if ( $choice['ready'] ) {
                return $choice['type'];
            }
        }
        return isset($choices[0]['type']) ? $choices[0]['type'] : null;
    }

    /**
     * This course can launch the tool as a resource link.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return bool
     */
    public static function hasResourceLink($contextId, $registrationId) {
        try {
            $tool = ToolRegistrationService::visibleLti11((int) $contextId, (int) $registrationId);
        } catch ( \InvalidArgumentException $ex ) {
            return false;
        }
        return self::hasMessage($tool['registration_id'], 'LtiResourceLinkRequest');
    }

    /**
     * Whether this course tool is allowed to receive names and email.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array{send_name:bool, send_email:bool}
     */
    public static function privacy($contextId, $registrationId) {
        $tool = ToolRegistrationService::visibleLti11((int) $contextId, (int) $registrationId);
        $claims = ToolDeploymentGrant::allowedClaims(ToolDeploymentService::onlyDeploymentId($tool['registration_id']));
        return array(
            'send_name' => in_array('name', $claims, true)
                || in_array('given_name', $claims, true)
                || in_array('family_name', $claims, true),
            'send_email' => in_array('email', $claims, true),
        );
    }

    /**
     * Resource link for a lesson item. The item is the link. The tool is the launch.
     *
     * @param int $contextId
     * @param int $registrationId
     * @param int $userId
     * @param string $resourceLinkId
     * @param string $resourceLinkTitle
     * @param string $returnUrl
     * @param string $role Instructor or Learner
     * @param string $userKey Opaque user id sent to the tool. Empty uses the numeric user id.
     * @param bool|null $sendName Null follows the tool privacy grant. False omits the name.
     * @param bool|null $sendEmail Null follows the tool privacy grant. False omits the email.
     * @param string $launchUrl Lesson item launch URL. Empty uses the registration URL.
     * @param string $documentTarget window, iframe, or frame. Empty omits the parameter.
     * @return array{endpoint:string, parameters:array<string, string>}
     */
    public static function courseResourceLink($contextId, $registrationId, $userId, $resourceLinkId, $resourceLinkTitle, $returnUrl, $role = 'Learner', $userKey = '', $sendName = null, $sendEmail = null, $launchUrl = '', $documentTarget = '') {
        $tool = ToolRegistrationService::visibleLti11((int) $contextId, (int) $registrationId);
        if ( ! self::hasMessage($tool['registration_id'], 'LtiResourceLinkRequest') ) {
            throw new \InvalidArgumentException('This tool does not have a resource link launch.');
        }
        $resourceLinkId = trim((string) $resourceLinkId);
        if ( $resourceLinkId === '' ) {
            throw new \InvalidArgumentException('This lesson link has no resource link.');
        }
        $title = trim((string) $resourceLinkTitle);
        if ( $title === '' ) {
            $title = $tool['title'];
        }
        $userKey = trim((string) $userKey);
        $userId = (int) $userId;
        if ( $userId < 1 && $userKey === '' ) {
            throw new \InvalidArgumentException('A user is required to launch.');
        }
        $endpoint = trim((string) $launchUrl);
        if ( $endpoint === '' ) {
            $endpoint = $tool['lti11_url'];
        }
        $parms = self::resourceLinkParameters(
            $tool,
            (int) $contextId,
            $userId,
            $returnUrl,
            'basic-lti-launch-request',
            self::role($role),
            $resourceLinkId,
            $title,
            '',
            $userKey,
            $sendName,
            $sendEmail,
            $documentTarget
        );
        return array(
            'endpoint' => $endpoint,
            'parameters' => LTI::signParameters(
                $parms,
                $endpoint,
                'POST',
                $tool['lti11_key'],
                $tool['lti11_secret'],
                'Finish Launch'
            ),
        );
    }

    /**
     * @param int $registrationId
     * @param string $messageType
     * @return bool
     */
    private static function hasMessage($registrationId, $messageType) {
        foreach ( ToolRegistrationDocument::messagesForRegistration((int) $registrationId) as $message ) {
            if ( $message['message_type'] === $messageType ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sign one test launch, or report that this message type is not sent yet.
     *
     * @param int $contextId
     * @param int $registrationId
     * @param int $userId
     * @param string $messageType
     * @param string $returnUrl
     * @param string $role Instructor or Learner
     * @return array{title:string, endpoint:string, message_type:string, label:string, role:string, ready:bool, parameters:array<string, string>}
     */
    public static function launch($contextId, $registrationId, $userId, $messageType, $returnUrl, $role = 'Instructor') {
        $tool = ToolRegistrationService::visibleLti11((int) $contextId, (int) $registrationId);
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
        $role = self::role($role);
        $result = array(
            'title' => $tool['title'],
            'endpoint' => $tool['lti11_url'],
            'message_type' => $messageType,
            'label' => $spec['label'],
            'role' => $role,
            'ready' => $spec['ready'],
            'parameters' => array(),
        );
        if ( ! $spec['ready'] || $spec['wire'] === null ) {
            return $result;
        }
        $userId = (int) $userId;
        if ( $userId < 1 ) {
            throw new \InvalidArgumentException('A user is required to test a launch.');
        }
        $parms = self::resourceLinkParameters($tool, (int) $contextId, $userId, $returnUrl, $spec['wire'], $role);
        $result['parameters'] = LTI::signParameters(
            $parms,
            $tool['lti11_url'],
            'POST',
            $tool['lti11_key'],
            $tool['lti11_secret'],
            'Send '.$spec['label']
        );
        return $result;
    }

    /**
     * @param array{registration_id:int, key_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string} $tool
     * @param int $contextId
     * @param int $userId
     * @param string $returnUrl
     * @param string $wireType
     * @param string $role
     * @param string|null $resourceLinkId
     * @param string|null $resourceLinkTitle
     * @param string $resourceLinkDescription
     * @param string $userKey
     * @param bool|null $sendName
     * @param bool|null $sendEmail
     * @return array<string, string>
     */
    private static function resourceLinkParameters(array $tool, $contextId, $userId, $returnUrl, $wireType, $role, $resourceLinkId = null, $resourceLinkTitle = null, $resourceLinkDescription = 'Test launch', $userKey = '', $sendName = null, $sendEmail = null, $documentTarget = '') {
        $context = self::contextRow($contextId, (int) $tool['key_id']);
        $key = self::keyRow((int) $tool['key_id']);
        $user = self::userRow($userId, (int) $tool['key_id']);
        $claims = ToolDeploymentGrant::allowedClaims(ToolDeploymentService::onlyDeploymentId($tool['registration_id']));
        $linkId = ($resourceLinkId !== null && $resourceLinkId !== '')
            ? $resourceLinkId
            : 'test-'.$tool['registration_id'];
        $linkTitle = ($resourceLinkTitle !== null && $resourceLinkTitle !== '')
            ? $resourceLinkTitle
            : $tool['title'];
        $parms = array(
            'lti_message_type' => $wireType,
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => $linkId,
            'resource_link_title' => $linkTitle,
            'user_id' => (string) $userId,
            'roles' => $role,
            'context_id' => $context['context_id'],
            'context_title' => $context['title'],
            'context_label' => $context['label'],
            'context_type' => 'Course',
            'tool_consumer_instance_guid' => $key['guid'],
        );
        if ( $resourceLinkDescription !== '' ) {
            $parms['resource_link_description'] = $resourceLinkDescription;
        }
        if ( $key['name'] !== '' ) {
            $parms['tool_consumer_instance_name'] = $key['name'];
        }
        if ( $sendName !== false && in_array('name', $claims, true) && $user['full'] !== '' ) {
            $parms['lis_person_name_full'] = $user['full'];
        }
        if ( $sendName !== false && in_array('given_name', $claims, true) && $user['given'] !== '' ) {
            $parms['lis_person_name_given'] = $user['given'];
        }
        if ( $sendName !== false && in_array('family_name', $claims, true) && $user['family'] !== '' ) {
            $parms['lis_person_name_family'] = $user['family'];
        }
        if ( $sendEmail !== false && in_array('email', $claims, true) && $user['email'] !== '' ) {
            $parms['lis_person_contact_email_primary'] = $user['email'];
        }
        $returnUrl = trim((string) $returnUrl);
        if ( preg_match('#^https?://#i', $returnUrl) ) {
            $parms['launch_presentation_return_url'] = $returnUrl;
        }
        $documentTarget = self::documentTargetForLesson($documentTarget);
        if ( $documentTarget !== '' ) {
            $parms['launch_presentation_document_target'] = $documentTarget;
        }
        $parms['launch_presentation_locale'] = 'en';
        return $parms;
    }

    /**
     * LTI launch_presentation_document_target for a lesson open choice.
     *
     * Same page and new page own the browser window. Modal and in-page use an iframe.
     * An empty value omits the parameter.
     *
     * @param string $lessonTarget
     * @return string
     */
    public static function documentTargetForLesson($lessonTarget) {
        $target = strtolower(trim((string) $lessonTarget));
        if ( $target === '' ) {
            return '';
        }
        if ( $target === '_blank' || $target === 'window' || $target === 'blank' || $target === '_self' || $target === 'self' ) {
            return 'window';
        }
        if ( $target === 'frame' ) {
            return 'frame';
        }
        return 'iframe';
    }

    /**
     * @param int $contextId
     * @param int $keyId
     * @return array{context_id:string, title:string, label:string}
     */
    private static function contextRow($contextId, $keyId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, title, short_title
             FROM {$p}lti_context
             WHERE context_id = :context_id AND key_id = :key_id",
            array(
                ':context_id' => (int) $contextId,
                ':key_id' => (int) $keyId,
            )
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
        $PDOX = \Tsugi\Core\LTIX::getConnection();
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
