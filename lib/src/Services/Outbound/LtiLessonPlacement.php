<?php

namespace Tsugi\Services\Outbound;

/**
 * Lesson placements for one LTI 1.3 deployment.
 *
 * A message with no placement can be placed anywhere. A message placed in
 * lessons, or in link selection, can be placed in a lesson. Assignment and
 * rich-text placements are left out. Only the first deep link that fits is
 * offered. A resource link or a privacy launch stores its target link URI.
 * A deep link is launched, and the returned item's URL is stored instead.
 */
class LtiLessonPlacement {

    /**
     * @param array<int, string> $placements
     * @return bool
     */
    public static function fitsLesson(array $placements) {
        if ( count($placements) === 0 ) {
            return true;
        }
        foreach ( $placements as $placement ) {
            if ( $placement === 'lessons' || $placement === 'link_selection' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Choices the author sees after picking this deployment.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return array<int, array{message_id:int, action:string, label:string, detail:string, launch:string}>
     */
    public static function choices($contextId, $toolDeploymentId) {
        $deployment = self::requireDeployment((int) $contextId, (int) $toolDeploymentId);
        if ( $deployment['lti_version'] !== '1.3' ) {
            return array();
        }
        $deepLink = false;
        $out = array();
        foreach ( self::messages($deployment['registration_id']) as $message ) {
            if ( ! self::fitsLesson($message['placements']) ) {
                continue;
            }
            $target = trim((string) $message['target_link_uri']);
            if ( $target === '' ) {
                continue;
            }
            $type = $message['message_type'];
            if ( $type === 'LtiDeepLinkingRequest' ) {
                if ( $deepLink ) {
                    continue;
                }
                $deepLink = true;
                $out[] = self::choice($message, 'select', $deployment['title'].' select item', $deployment['title'], $target);
                continue;
            }
            if ( $type === 'LtiResourceLinkRequest' ) {
                $out[] = self::choice($message, 'install', 'Install this', $deployment['title'], $target);
                continue;
            }
            if ( $type === 'LtiDataPrivacyLaunchRequest' ) {
                $out[] = self::choice($message, 'install', 'Give me the privacy link', $deployment['title'], $target);
            }
        }
        return $out;
    }

    /**
     * Target link URI for a resource link or privacy message in the lesson list.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @param int $messageId
     * @return string
     */
    public static function installTarget($contextId, $toolDeploymentId, $messageId) {
        $messageId = (int) $messageId;
        foreach ( self::choices((int) $contextId, (int) $toolDeploymentId) as $choice ) {
            if ( $choice['message_id'] === $messageId && $choice['action'] === 'install' ) {
                return $choice['launch'];
            }
        }
        throw new \InvalidArgumentException('That launch is not a lesson placement.');
    }

    /**
     * Start the first lesson deep link for this deployment.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @param int $messageId
     * @param int $userId
     * @param string $returnUrl
     * @return array<string, mixed>
     */
    public static function selectLaunch($contextId, $toolDeploymentId, $messageId, $userId, $returnUrl) {
        $messageId = (int) $messageId;
        $choice = null;
        foreach ( self::choices((int) $contextId, (int) $toolDeploymentId) as $row ) {
            if ( $row['message_id'] === $messageId && $row['action'] === 'select' ) {
                $choice = $row;
                break;
            }
        }
        if ( $choice === null ) {
            throw new \InvalidArgumentException('That launch is not a lesson placement.');
        }
        $deployment = self::requireDeployment((int) $contextId, (int) $toolDeploymentId);
        $launch = Lti13TestLaunch::launch(
            (int) $contextId,
            $deployment['registration_id'],
            (int) $userId,
            'LtiDeepLinkingRequest',
            (string) $returnUrl,
            'Instructor',
            true,
            $choice['launch'],
            (int) $toolDeploymentId
        );
        if ( empty($launch['ready']) ) {
            throw new \InvalidArgumentException('This tool has no deep link.');
        }
        return $launch;
    }

    /**
     * The target link URI from a deep link return, or the items to choose from.
     *
     * One item is the placement. Several items come back so the author can pick.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @param string $jwt
     * @param int|null $index
     * @param string|null $publicKey
     * @return array{url:string, title:string, items:?array<int, array{title:string, url:string}>}
     */
    public static function returnedTarget($contextId, $toolDeploymentId, $jwt, $index = null, $publicKey = null) {
        $accepted = Lti13TestLaunch::acceptReturn($jwt, $publicKey);
        if ( (int) $accepted['context_id'] !== (int) $contextId ) {
            throw new \InvalidArgumentException('The deep link return is not for this course.');
        }
        $deployment = self::requireDeployment((int) $contextId, (int) $toolDeploymentId);
        if ( (string) $accepted['deployment_id'] !== $deployment['deployment_id'] ) {
            throw new \InvalidArgumentException('That deployment is not part of this launch.');
        }
        $items = array();
        foreach ( $accepted['items'] as $item ) {
            $url = trim((string) $item['url']);
            if ( ! preg_match('#^https?://#i', $url) ) {
                continue;
            }
            $items[] = array(
                'title' => trim((string) $item['title']),
                'url' => $url,
            );
        }
        if ( count($items) === 0 ) {
            throw new \InvalidArgumentException('The deep link return had no target link.');
        }
        if ( $index === null ) {
            if ( count($items) === 1 ) {
                return array(
                    'url' => $items[0]['url'],
                    'title' => $items[0]['title'],
                    'items' => null,
                );
            }
            return array(
                'url' => '',
                'title' => '',
                'items' => $items,
            );
        }
        $index = (int) $index;
        if ( ! isset($items[$index]) ) {
            throw new \InvalidArgumentException('That item is not in the deep link return.');
        }
        return array(
            'url' => $items[$index]['url'],
            'title' => $items[$index]['title'],
            'items' => null,
        );
    }

    /**
     * @param array<string, mixed> $message
     * @param string $action
     * @param string $label
     * @param string $toolTitle
     * @param string $target
     * @return array{message_id:int, action:string, label:string, detail:string, launch:string}
     */
    private static function choice(array $message, $action, $label, $toolTitle, $target) {
        $detail = $message['label'] === null ? '' : trim((string) $message['label']);
        if ( $detail !== '' && strcasecmp($detail, trim((string) $toolTitle)) === 0 ) {
            $detail = '';
        }
        return array(
            'message_id' => (int) $message['message_id'],
            'action' => $action,
            'label' => $label,
            'detail' => $detail,
            'launch' => $target,
        );
    }

    /**
     * @param int $registrationId
     * @return array<int, array{message_id:int, message_type:string, target_link_uri:?string, label:?string, placements:array<int, string>}>
     */
    private static function messages($registrationId) {
        $out = array();
        foreach ( ToolRegistrationDocument::messagesForRegistration((int) $registrationId) as $message ) {
            $placements = array();
            foreach ( ToolPlacementService::getPlacementsForMessage((int) $message['message_id']) as $placement ) {
                $placements[] = (string) $placement['placement'];
            }
            $out[] = array(
                'message_id' => (int) $message['message_id'],
                'message_type' => (string) $message['message_type'],
                'target_link_uri' => $message['target_link_uri'],
                'label' => $message['label'],
                'placements' => $placements,
            );
        }
        return $out;
    }

    /**
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return array{tool_deployment_id:int, registration_id:int, key_id:int, deployment_id:string, title:string, lti_version:string}
     */
    private static function requireDeployment($contextId, $toolDeploymentId) {
        $toolDeploymentId = (int) $toolDeploymentId;
        $found = null;
        foreach ( ToolDeploymentService::getDeploymentsForContext((int) $contextId) as $row ) {
            if ( (int) $row['tool_deployment_id'] === $toolDeploymentId ) {
                $found = $row;
                break;
            }
        }
        if ( $found === null ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        $p = self::prefix();
        $reg = self::db()->rowDie(
            "SELECT registration_id, key_id, title, lti_version
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $found['registration_id'])
        );
        if ( ! is_array($reg) || (int) $reg['key_id'] !== (int) $found['key_id'] ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        return array(
            'tool_deployment_id' => $toolDeploymentId,
            'registration_id' => (int) $reg['registration_id'],
            'key_id' => (int) $reg['key_id'],
            'deployment_id' => $found['deployment_id'] === null ? '' : (string) $found['deployment_id'],
            'title' => (string) $reg['title'],
            'lti_version' => (string) $reg['lti_version'],
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
