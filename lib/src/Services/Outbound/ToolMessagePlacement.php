<?php

namespace Tsugi\Services\Outbound;

/**
 * Which placements a known LTI message type may carry.
 *
 * A placement named here is legal only for the message types that list it.
 * A placement that is not named here is a vendor extension and is stored.
 * A message type that is not named here has no rule.
 *
 * Callers go through assertCompatible(). Do not compare these strings elsewhere.
 */
class ToolMessagePlacement {

    /**
     * IMS Dynamic Registration examples use ContentArea and RichTextEditor.
     * The snake_case names are the placement strings Tsugi already sends.
     *
     * @var array<string, array<string, true>>
     */
    private const ALLOWED = array(
        'LtiDeepLinkingRequest' => array(
            'ContentArea' => true,
            'RichTextEditor' => true,
            'assignment_selection' => true,
            'link_selection' => true,
            'content_editor' => true,
            'editor_button' => true,
            'migration_selection' => true,
            'homework_submission' => true,
            // Lessons picker, and a deep-link tool that returns a cartridge.
            'lessons' => true,
            'common_cartridge' => true,
        ),
        'LtiResourceLinkRequest' => array(
            'course_navigation' => true,
            'account_navigation' => true,
            'user_navigation' => true,
            'global_navigation' => true,
        ),
    );

    /**
     * @param string $messageType
     * @param mixed $placement
     * @return string
     */
    public static function assertCompatible($messageType, $placement) {
        $placement = trim((string) $placement);
        if ( $placement === '' ) {
            throw new \InvalidArgumentException('Placement is required.');
        }
        if ( strlen($placement) > 255 ) {
            throw new \InvalidArgumentException('Placement is too long.');
        }
        $messageType = (string) $messageType;
        if ( ! isset(self::ALLOWED[$messageType]) ) {
            return $placement;
        }
        if ( isset(self::ALLOWED[$messageType][$placement]) ) {
            return $placement;
        }
        if ( self::isKnownPlacement($placement) ) {
            throw new \InvalidArgumentException('That placement is not valid for this message type.');
        }
        return $placement;
    }

    /**
     * Turn LTI 1.1 launch and placement checkboxes into message rows.
     *
     * Resource link and deep link are independent. At least one is required.
     * A privacy launch is a third message and does not satisfy that rule.
     * A placement checkbox is rejected when its launch checkbox is off.
     * Site navigation requires a resource link. Lessons, the editor, assignment
     * selection, and a cartridge response require a deep link.
     *
     * @param mixed $messageTypes
     * @param mixed $placements
     * @return array<int, array{type: string, placements: array<int, string>}>
     */
    public static function arrange($messageTypes, $placements) {
        $selected = self::messageSelection($messageTypes);
        $byType = array(
            'LtiResourceLinkRequest' => array(),
            'LtiDeepLinkingRequest' => array(),
        );
        if ( $placements === null ) {
            $placements = array();
        }
        if ( ! is_array($placements) || ! array_is_list($placements) ) {
            throw new \InvalidArgumentException('LTI 1.1 placements must be a list of checkbox names.');
        }
        $seen = array();
        foreach ( $placements as $placement ) {
            if ( ! is_string($placement) ) {
                throw new \InvalidArgumentException('Each LTI 1.1 placement checkbox must be a string.');
            }
            $placement = trim($placement);
            $type = self::typeForCheckbox($placement);
            if ( isset($seen[$placement]) ) {
                throw new \InvalidArgumentException('That placement checkbox is already selected.');
            }
            if ( ! isset($selected[$type]) ) {
                $launch = $type === 'LtiResourceLinkRequest' ? 'resource link launch' : 'deep link launch';
                throw new \InvalidArgumentException('That placement requires a '.$launch.'.');
            }
            $seen[$placement] = true;
            $byType[$type][] = $placement;
        }
        $out = array();
        foreach ( array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest', 'LtiDataPrivacyLaunchRequest') as $type ) {
            if ( ! isset($selected[$type]) ) {
                continue;
            }
            $out[] = array(
                'type' => $type,
                'placements' => isset($byType[$type]) ? $byType[$type] : array(),
            );
        }
        return $out;
    }

    /**
     * @param mixed $messageTypes
     * @return array<string, true>
     */
    private static function messageSelection($messageTypes) {
        if ( ! is_array($messageTypes) || ! array_is_list($messageTypes) ) {
            throw new \InvalidArgumentException('LTI 1.1 launches must be a list of message types.');
        }
        $allowed = array(
            'LtiResourceLinkRequest' => true,
            'LtiDeepLinkingRequest' => true,
            'LtiDataPrivacyLaunchRequest' => true,
        );
        $selected = array();
        foreach ( $messageTypes as $type ) {
            if ( ! is_string($type) || ! isset($allowed[$type]) ) {
                throw new \InvalidArgumentException('That launch checkbox is not a supported message type.');
            }
            if ( isset($selected[$type]) ) {
                throw new \InvalidArgumentException('That launch checkbox is already selected.');
            }
            $selected[$type] = true;
        }
        if ( ! isset($selected['LtiResourceLinkRequest']) && ! isset($selected['LtiDeepLinkingRequest']) ) {
            throw new \InvalidArgumentException('An LTI 1.1 registration needs a resource link launch or a deep link launch.');
        }
        return $selected;
    }

    /**
     * @param string $placement
     * @return string
     */
    private static function typeForCheckbox($placement) {
        foreach ( self::ALLOWED as $messageType => $names ) {
            if ( isset($names[$placement]) ) {
                return $messageType;
            }
        }
        throw new \InvalidArgumentException('That placement is not an LTI 1.1 registration checkbox.');
    }

    /**
     * @param string $placement
     * @return bool
     */
    private static function isKnownPlacement($placement) {
        foreach ( self::ALLOWED as $names ) {
            if ( isset($names[$placement]) ) {
                return true;
            }
        }
        return false;
    }
}
