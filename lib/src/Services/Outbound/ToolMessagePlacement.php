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
     * IMS Dynamic Registration names ContentArea and RichTextEditor.
     * Canvas uses its own strings, such as course_navigation and link_selection.
     * lessons and content_editor are Tsugi/Sakai placements. common_cartridge is too,
     * and a received registration stores it as Canvas migration_selection.
     * A name that is not listed here is still stored. It is not rejected.
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
            // Lessons, the editor, and assignments also work on a resource link.
            // A cartridge response is content-item only, like Sakai's import item.
            'lessons' => true,
            'common_cartridge' => true,
        ),
        'LtiResourceLinkRequest' => array(
            'ContentArea' => true,
            'RichTextEditor' => true,
            'course_navigation' => true,
            'account_navigation' => true,
            'user_navigation' => true,
            'global_navigation' => true,
            'lessons' => true,
            'content_editor' => true,
            'assignment_selection' => true,
        ),
    );

    /**
     * Extensions a received registration stores under another name.
     *
     * editor_button is Canvas's rich text editor placement.
     * content_editor is the Tsugi/Sakai rich text editor placement.
     * lessons is the Tsugi/Sakai content area placement.
     * common_cartridge is the Tsugi/Sakai cartridge placement. Import stores
     * it as Canvas migration_selection.
     * The registration Tsugi sends still includes the extension strings.
     * Canvas and Sakai select the tool by the extension, and the other name
     * is added beside it. Import keeps each stored name once.
     *
     * @var array<string, string>
     */
    private const RECEIVED = array(
        'editor_button' => 'RichTextEditor',
        'content_editor' => 'RichTextEditor',
        'lessons' => 'ContentArea',
        'common_cartridge' => 'migration_selection',
    );

    /**
     * Who uses this placement string. standard is ContentArea and RichTextEditor.
     * canvas and tsugi are extensions. A name that is absent here is unclassified.
     *
     * @var array<string, string>
     */
    private const FAMILY = array(
        'ContentArea' => 'standard',
        'RichTextEditor' => 'standard',
        'account_navigation' => 'canvas',
        'assignment_selection' => 'canvas',
        'course_navigation' => 'canvas',
        'editor_button' => 'canvas',
        'global_navigation' => 'canvas',
        'homework_submission' => 'canvas',
        'link_selection' => 'canvas',
        'migration_selection' => 'canvas',
        'user_navigation' => 'canvas',
        'common_cartridge' => 'tsugi',
        'content_editor' => 'tsugi',
        'lessons' => 'tsugi',
    );

    /**
     * Labels for a rolled-up placement list. Standard names come first.
     * Canvas and Tsugi/Sakai names keep their identifier and gain a note.
     *
     * @param array<int, mixed> $names
     * @return array<int, string>
     */
    public static function displayPlacements(array $names) {
        $rows = array();
        foreach ( $names as $index => $name ) {
            if ( ! is_string($name) || $name === '' ) {
                continue;
            }
            $rows[] = array(
                'name' => $name,
                'index' => (int) $index,
                'standard' => isset(self::FAMILY[$name]) && self::FAMILY[$name] === 'standard',
            );
        }
        usort($rows, function ($left, $right) {
            if ( $left['standard'] !== $right['standard'] ) {
                return $left['standard'] ? -1 : 1;
            }
            return $left['index'] <=> $right['index'];
        });
        $out = array();
        foreach ( $rows as $row ) {
            $note = '';
            if ( isset(self::FAMILY[$row['name']]) && self::FAMILY[$row['name']] === 'canvas' ) {
                $note = __('Canvas extension');
            } elseif ( isset(self::FAMILY[$row['name']]) && self::FAMILY[$row['name']] === 'tsugi' ) {
                $note = __('Tsugi/Sakai extension');
            }
            $out[] = $note === '' ? $row['name'] : $row['name'].' ('.$note.')';
        }
        return $out;
    }

    /**
     * Message types the course editor can add.
     *
     * @return array<int, string>
     */
    public static function knownMessageTypes() {
        return array(
            'LtiResourceLinkRequest',
            'LtiDeepLinkingRequest',
            'LtiDataPrivacyLaunchRequest',
            'LtiContextLaunchRequest',
        );
    }

    /**
     * Rewrite a received extension to the placement this message stores.
     *
     * A target this message type cannot carry stays as the extension.
     * Other placement strings are unchanged. Repeated names collapse to one.
     *
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public static function foldReceivedPlacements(array $document) {
        $messages = ToolRegistrationDocument::messagesInDocument($document);
        if ( count($messages) === 0 ) {
            return $document;
        }
        $changed = false;
        foreach ( $messages as $index => $message ) {
            if ( ! is_array($message) || ! isset($message['placements']) || ! is_array($message['placements']) ) {
                continue;
            }
            $type = isset($message['type']) && is_string($message['type']) ? $message['type'] : '';
            $folded = array();
            $seen = array();
            foreach ( $message['placements'] as $placement ) {
                if ( ! is_string($placement) ) {
                    $folded[] = $placement;
                    continue;
                }
                $standard = self::receivedPlacement($type, $placement);
                if ( $standard !== $placement || isset($seen[$standard]) ) {
                    $changed = true;
                }
                if ( isset($seen[$standard]) ) {
                    continue;
                }
                $seen[$standard] = true;
                $folded[] = $standard;
            }
            if ( $folded !== $message['placements'] ) {
                $messages[$index]['placements'] = array_values($folded);
                $changed = true;
            }
        }
        if ( ! $changed ) {
            return $document;
        }
        return ToolRegistrationDocument::withMessages($document, $messages);
    }

    /**
     * @param string $messageType
     * @param string $placement
     * @return string
     */
    private static function receivedPlacement($messageType, $placement) {
        if ( ! isset(self::RECEIVED[$placement]) ) {
            return $placement;
        }
        $standard = self::RECEIVED[$placement];
        try {
            self::assertCompatible($messageType, $standard);
        } catch ( \InvalidArgumentException $ex ) {
            return $placement;
        }
        return $standard;
    }

    /**
     * Placements this message type may carry. An unknown type has none.
     *
     * @param string $messageType
     * @return array<int, string>
     */
    public static function knownPlacements($messageType) {
        $messageType = (string) $messageType;
        if ( ! isset(self::ALLOWED[$messageType]) ) {
            return array();
        }
        return array_keys(self::ALLOWED[$messageType]);
    }

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
     * Resource link and content item are independent. At least one is required.
     * A privacy launch is a third message and does not satisfy that rule.
     * A placement is stored on every selected launch that can carry it.
     * Lessons, the editor, and assignments work on a resource link or a content
     * item. Course navigation requires a resource link. A cartridge response
     * requires a content item. LTI 1.1 calls this launch a content item. The
     * stored message type is still LtiDeepLinkingRequest.
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
            $types = self::launchesForCheckbox($placement);
            if ( isset($seen[$placement]) ) {
                throw new \InvalidArgumentException('That placement checkbox is already selected.');
            }
            $matched = array();
            foreach ( $types as $type ) {
                if ( isset($selected[$type]) && isset($byType[$type]) ) {
                    $matched[] = $type;
                }
            }
            if ( count($matched) === 0 ) {
                $launch = $types === array('LtiResourceLinkRequest') ? 'resource link launch' : 'content item launch';
                throw new \InvalidArgumentException('That placement requires a '.$launch.'.');
            }
            $seen[$placement] = true;
            foreach ( $matched as $type ) {
                $byType[$type][] = $placement;
            }
        }
        $out = array();
        foreach ( array('LtiResourceLinkRequest', 'LtiDeepLinkingRequest', 'LtiDataPrivacyLaunchRequest', 'LtiContextLaunchRequest') as $type ) {
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
            'LtiContextLaunchRequest' => true,
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
            throw new \InvalidArgumentException('An LTI 1.1 registration needs a resource link launch or a content item launch.');
        }
        return $selected;
    }

    /**
     * Launch checkboxes that can carry this placement. One placement may fit both.
     *
     * @param string $placement
     * @return array<int, string>
     */
    public static function launchesForCheckbox($placement) {
        $placement = trim((string) $placement);
        $types = array();
        foreach ( self::ALLOWED as $messageType => $names ) {
            if ( isset($names[$placement]) ) {
                $types[] = $messageType;
            }
        }
        if ( count($types) === 0 ) {
            throw new \InvalidArgumentException('That placement is not an LTI 1.1 registration checkbox.');
        }
        return $types;
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
