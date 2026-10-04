<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * Placements a registered message can use, and which of those a deployment enables.
 *
 * A message placement says where that message is capable of appearing.
 * A deployment placement says the deployment has enabled that exact row.
 * Org and course scope are a different question and stay on the scope tables.
 *
 * An LTI 1.1 registration uses the same message rows. They are not parsed
 * from a Dynamic Registration document. synthesizeLti11Messages() builds
 * them from the launch checkboxes and the placement checkboxes. A resource
 * link and a deep link are separate messages. A privacy launch is a third
 * message. Name, email, grade, and roster checkboxes are written as the
 * same claims and scope a Dynamic Registration document carries.
 * Replacing a registration document, or synthesizing again, deletes the
 * message rows, and that deletes their placements and any deployment
 * enablement of those placements.
 */
class ToolPlacementService {

    /**
     * @param int $messageId
     * @param string $placement
     * @return int message_placement_id
     */
    public static function addPlacementToMessage($messageId, $placement) {
        $message = self::requireMessage($messageId);
        $placement = ToolMessagePlacement::assertCompatible($message['message_type'], $placement);
        if ( self::findPlacementOnMessage((int) $message['message_id'], $placement) !== null ) {
            throw new \InvalidArgumentException('That placement is already on this message.');
        }
        return self::insertPlacement($message, $placement);
    }

    /**
     * Removing the placement also removes deployment enablement of that row.
     *
     * @param int $messageId
     * @param string $placement
     * @return void
     */
    public static function removePlacementFromMessage($messageId, $placement) {
        $message = self::requireMessage($messageId);
        $placement = trim((string) $placement);
        $row = self::findPlacementOnMessage((int) $message['message_id'], $placement);
        if ( $row === null ) {
            throw new \InvalidArgumentException('That placement is not on this message.');
        }
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "DELETE FROM {$p}lti_tool_message_placement
             WHERE message_placement_id = :message_placement_id",
            array(':message_placement_id' => (int) $row['message_placement_id'])
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not remove placement. '.$detail);
        }
    }

    /**
     * @param int $messageId
     * @return array<int, array<string, mixed>>
     */
    public static function getPlacementsForMessage($messageId) {
        $message = self::requireMessage($messageId);
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT message_placement_id, message_id, registration_id, key_id, placement
             FROM {$p}lti_tool_message_placement
             WHERE message_id = :message_id
             ORDER BY placement ASC, message_placement_id ASC",
            array(':message_id' => (int) $message['message_id'])
        );
        return self::placementRows($rows, $message);
    }

    /**
     * @param int $toolDeploymentId
     * @param int $messagePlacementId
     * @return void
     */
    public static function enablePlacementForDeployment($toolDeploymentId, $messagePlacementId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $placement = self::requirePlacement($messagePlacementId);
        self::requireSameRegistration($deployment, $placement);
        $existing = self::db()->rowDie(
            "SELECT tool_deployment_id FROM ".self::prefix()."lti_tool_deployment_placement
             WHERE tool_deployment_id = :tool_deployment_id
               AND message_placement_id = :message_placement_id",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':message_placement_id' => (int) $placement['message_placement_id'],
            )
        );
        if ( is_array($existing) ) {
            throw new \InvalidArgumentException('That placement is already enabled for this deployment.');
        }
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment_placement
                (tool_deployment_id, message_placement_id, registration_id, key_id, created_at)
             VALUES
                (:tool_deployment_id, :message_placement_id, :registration_id, :key_id, NOW())",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':message_placement_id' => (int) $placement['message_placement_id'],
                ':registration_id' => (int) $deployment['registration_id'],
                ':key_id' => (int) $deployment['key_id'],
            )
        );
        if ( $stmt->success ) {
            return;
        }
        $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
        if ( stripos($detail, 'Duplicate') !== false ) {
            throw new \InvalidArgumentException('That placement is already enabled for this deployment.');
        }
        throw new \RuntimeException('Could not enable placement. '.$detail);
    }

    /**
     * @param int $toolDeploymentId
     * @param int $messagePlacementId
     * @return void
     */
    public static function disablePlacementForDeployment($toolDeploymentId, $messagePlacementId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $placement = self::requirePlacement($messagePlacementId);
        self::requireSameRegistration($deployment, $placement);
        $p = self::prefix();
        $existing = self::db()->rowDie(
            "SELECT tool_deployment_id FROM {$p}lti_tool_deployment_placement
             WHERE tool_deployment_id = :tool_deployment_id
               AND message_placement_id = :message_placement_id",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':message_placement_id' => (int) $placement['message_placement_id'],
            )
        );
        if ( ! is_array($existing) ) {
            throw new \InvalidArgumentException('That placement is not enabled for this deployment.');
        }
        $stmt = self::db()->queryReturnError(
            "DELETE FROM {$p}lti_tool_deployment_placement
             WHERE tool_deployment_id = :tool_deployment_id
               AND message_placement_id = :message_placement_id",
            array(
                ':tool_deployment_id' => (int) $deployment['tool_deployment_id'],
                ':message_placement_id' => (int) $placement['message_placement_id'],
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not disable placement. '.$detail);
        }
    }

    /**
     * Every message placement declared by this deployment's registration.
     * Scope does not filter this list. Enabled and not-yet-enabled rows are both included.
     *
     * @param int $toolDeploymentId
     * @return array<int, array<string, mixed>>
     */
    public static function getAvailablePlacementsForDeployment($toolDeploymentId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT p.message_placement_id, p.message_id, p.registration_id, p.key_id, p.placement,
                    m.message_type, m.target_link_uri, m.sequence
             FROM {$p}lti_tool_message m
             INNER JOIN {$p}lti_tool_message_placement p
                ON p.message_id = m.message_id
               AND p.registration_id = m.registration_id
               AND p.key_id = m.key_id
             WHERE m.registration_id = :registration_id
               AND m.key_id = :key_id
             ORDER BY m.sequence ASC, p.placement ASC, p.message_placement_id ASC",
            array(
                ':registration_id' => (int) $deployment['registration_id'],
                ':key_id' => (int) $deployment['key_id'],
            )
        );
        return self::capabilityRows($rows);
    }

    /**
     * Message placements this deployment has enabled.
     *
     * @param int $toolDeploymentId
     * @return array<int, array<string, mixed>>
     */
    public static function getEnabledPlacementsForDeployment($toolDeploymentId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT p.message_placement_id, p.message_id, p.registration_id, p.key_id, p.placement,
                    m.message_type, m.target_link_uri, m.sequence
             FROM {$p}lti_tool_deployment_placement e
             INNER JOIN {$p}lti_tool_message_placement p
                ON p.message_placement_id = e.message_placement_id
               AND p.registration_id = e.registration_id
               AND p.key_id = e.key_id
             INNER JOIN {$p}lti_tool_message m
                ON m.message_id = p.message_id
               AND m.registration_id = p.registration_id
               AND m.key_id = p.key_id
             WHERE e.tool_deployment_id = :tool_deployment_id
             ORDER BY m.sequence ASC, p.placement ASC, p.message_placement_id ASC",
            array(':tool_deployment_id' => (int) $deployment['tool_deployment_id'])
        );
        return self::capabilityRows($rows);
    }

    /**
     * Build the LTI 1.1 message rows for one registration.
     *
     * Each selected launch is one message whose target is the launch URL.
     * Placement checkboxes hang off the launch they require. Claims and
     * scopes are stored in registration_json in the Dynamic Registration
     * shape. Nothing here enables placements on the deployment.
     *
     * @param int $registrationId
     * @param array<int, string> $messageTypes
     * @param array<int, string> $checkedPlacements
     * @param array<int, string> $claims
     * @param array<int, string> $scopes
     * @return void
     */
    public static function synthesizeLti11Messages($registrationId, array $messageTypes, array $checkedPlacements = array(), array $claims = array(), array $scopes = array()) {
        $registration = self::requireLti11Registration($registrationId);
        $messages = ToolMessagePlacement::arrange($messageTypes, $checkedPlacements);
        $document = ToolRegistrationDocument::lti11Document(
            $registration['lti11_url'],
            $messages,
            $claims,
            $scopes
        );
        ToolRegistrationDocument::storeDocument((int) $registration['registration_id'], $document);
    }

    /**
     * @param int $registrationId
     * @return array<string, mixed>
     */
    private static function requireLti11Registration($registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_id, key_id, lti_version, lti11_url
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        if ( (string) $row['lti_version'] !== '1.1' ) {
            throw new \InvalidArgumentException('Only an LTI 1.1 registration synthesizes messages from checkboxes.');
        }
        if ( $row['lti11_url'] === null || (string) $row['lti11_url'] === '' ) {
            throw new \InvalidArgumentException('An LTI 1.1 registration requires a launch URL.');
        }
        return array(
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'lti11_url' => (string) $row['lti11_url'],
        );
    }

    /**
     * @param array<string, mixed> $message
     * @param string $placement already validated
     * @return int
     */
    private static function insertPlacement(array $message, $placement) {
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "INSERT INTO {$p}lti_tool_message_placement
                (message_id, registration_id, key_id, placement, created_at)
             VALUES
                (:message_id, :registration_id, :key_id, :placement, NOW())",
            array(
                ':message_id' => (int) $message['message_id'],
                ':registration_id' => (int) $message['registration_id'],
                ':key_id' => (int) $message['key_id'],
                ':placement' => $placement,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            if ( stripos($detail, 'Duplicate') !== false ) {
                throw new \InvalidArgumentException('That placement is already on this message.');
            }
            throw new \RuntimeException('Could not add placement. '.$detail);
        }
        $id = (int) self::db()->lastInsertId();
        if ( $id < 1 ) {
            throw new \RuntimeException('Could not add placement.');
        }
        return $id;
    }

    /**
     * @param array<string, mixed> $deployment
     * @param array<string, mixed> $placement
     * @return void
     */
    private static function requireSameRegistration(array $deployment, array $placement) {
        if ( (int) $placement['key_id'] !== (int) $deployment['key_id'] ) {
            throw new \InvalidArgumentException('That placement is in a different tenant.');
        }
        if ( (int) $placement['registration_id'] !== (int) $deployment['registration_id'] ) {
            throw new \InvalidArgumentException('That placement belongs to a different registration.');
        }
    }

    /**
     * @param int $messageId
     * @param string $placement
     * @return array<string, mixed>|null
     */
    private static function findPlacementOnMessage($messageId, $placement) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT message_placement_id, message_id, registration_id, key_id, placement
             FROM {$p}lti_tool_message_placement
             WHERE message_id = :message_id AND placement = :placement",
            array(
                ':message_id' => (int) $messageId,
                ':placement' => $placement,
            )
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $messageId
     * @return array<string, mixed>
     */
    private static function requireMessage($messageId) {
        $messageId = (int) $messageId;
        if ( $messageId < 1 ) {
            throw new \InvalidArgumentException('Message is required.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT message_id, registration_id, key_id, message_type, target_link_uri, sequence
             FROM {$p}lti_tool_message
             WHERE message_id = :message_id",
            array(':message_id' => $messageId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Message was not found.');
        }
        return array(
            'message_id' => (int) $row['message_id'],
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'message_type' => (string) $row['message_type'],
            'target_link_uri' => $row['target_link_uri'] === null ? null : (string) $row['target_link_uri'],
            'sequence' => (int) $row['sequence'],
        );
    }

    /**
     * @param int $messagePlacementId
     * @return array<string, mixed>
     */
    private static function requirePlacement($messagePlacementId) {
        $messagePlacementId = (int) $messagePlacementId;
        if ( $messagePlacementId < 1 ) {
            throw new \InvalidArgumentException('Placement is required.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT message_placement_id, message_id, registration_id, key_id, placement
             FROM {$p}lti_tool_message_placement
             WHERE message_placement_id = :message_placement_id",
            array(':message_placement_id' => $messagePlacementId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Placement was not found.');
        }
        return array(
            'message_placement_id' => (int) $row['message_placement_id'],
            'message_id' => (int) $row['message_id'],
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'placement' => (string) $row['placement'],
        );
    }

    /**
     * @param int $toolDeploymentId
     * @return array<string, mixed>
     */
    private static function requireDeployment($toolDeploymentId) {
        $toolDeploymentId = (int) $toolDeploymentId;
        if ( $toolDeploymentId < 1 ) {
            throw new \InvalidArgumentException('Deployment is required.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT tool_deployment_id, registration_id, key_id
             FROM {$p}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Deployment was not found.');
        }
        return array(
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $message
     * @return array<int, array<string, mixed>>
     */
    private static function placementRows($rows, array $message) {
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'message_placement_id' => (int) $row['message_placement_id'],
                'message_id' => (int) $row['message_id'],
                'registration_id' => (int) $row['registration_id'],
                'key_id' => (int) $row['key_id'],
                'placement' => (string) $row['placement'],
                'message_type' => (string) $message['message_type'],
                'target_link_uri' => $message['target_link_uri'],
                'sequence' => (int) $message['sequence'],
            );
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function capabilityRows($rows) {
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'message_placement_id' => (int) $row['message_placement_id'],
                'message_id' => (int) $row['message_id'],
                'registration_id' => (int) $row['registration_id'],
                'key_id' => (int) $row['key_id'],
                'placement' => (string) $row['placement'],
                'message_type' => (string) $row['message_type'],
                'target_link_uri' => $row['target_link_uri'] === null ? null : (string) $row['target_link_uri'],
                'sequence' => (int) $row['sequence'],
            );
        }
        return $out;
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
}
