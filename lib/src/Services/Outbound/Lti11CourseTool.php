<?php

namespace Tsugi\Services\Outbound;

/**
 * The course Settings form for an LTI 1.1 tool on that course.
 *
 * The checkboxes are the LTI 1.1 choices: a resource link, a content item,
 * where the tool appears, names, email, and returning a grade. They become
 * message, placement, claim, and scope rows. This course owns the registration
 * and is the only course the deployment is assigned to. Edit and delete
 * apply only to a tool this course owns.
 */
class Lti11CourseTool {

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function formSections() {
        $sections = array(
            array(
                'kind' => 'fields',
                'fields' => array(
                    array('name' => 'title', 'label' => 'Tool title', 'type' => 'text', 'maxlength' => 512),
                    array('name' => 'lti11_url', 'label' => 'Launch URL', 'type' => 'url'),
                    array('name' => 'lti11_key', 'label' => 'Key', 'type' => 'text', 'maxlength' => 255),
                    array('name' => 'lti11_secret', 'label' => 'Secret', 'type' => 'password', 'maxlength' => 512),
                ),
            ),
            array(
                'kind' => 'checks',
                'legend' => 'Launch',
                'note' => 'The launch URL must support a resource link or a content item. A tool can support both.',
                'name' => 'messages',
                'boxes' => array(
                    array(
                        'value' => 'LtiResourceLinkRequest',
                        'label' => 'The tool URL supports a single LTI tool (Resource Link launch)',
                    ),
                    array(
                        'value' => 'LtiDeepLinkingRequest',
                        'label' => 'The tool URL supports a resource picker (Content Item launch)',
                    ),
                ),
            ),
            array(
                'kind' => 'checks',
                'legend' => 'Where it appears',
                'note' => 'Lessons, the editor, and assignments work with either launch. Course navigation needs a resource link launch. A cartridge response needs a content item launch.',
                'name' => 'placements',
                'boxes' => array(
                    array('value' => 'lessons', 'label' => 'Allow the tool to be selected from Lessons'),
                    array('value' => 'content_editor', 'label' => 'Allow the tool to be used from the rich text editor'),
                    array('value' => 'assignment_selection', 'label' => 'Allow the tool to be one of the assignment types'),
                    array('value' => 'course_navigation', 'label' => 'Allow the tool to be placed in course navigation (requires a resource link launch)'),
                    array('value' => 'common_cartridge', 'label' => 'Allow the tool to provide a common cartridge response (requires a content item launch)'),
                ),
            ),
            array(
                'kind' => 'checks',
                'legend' => 'Privacy',
                'note' => 'Names and email are sent on the launch.',
                'name' => 'privacy',
                'boxes' => array(
                    array('value' => 'names', 'label' => 'Send user names to the external tool'),
                    array('value' => 'email', 'label' => 'Send email addresses to the external tool'),
                ),
            ),
            array(
                'kind' => 'checks',
                'legend' => 'Services',
                'note' => 'The tool can return a grade to this course.',
                'name' => 'services',
                'boxes' => array(
                    array('value' => 'score', 'label' => 'Allow the external tool to return grades'),
                ),
            ),
        );
        foreach ( $sections as $i => $section ) {
            if ( ($section['name'] ?? '') !== 'placements' ) {
                continue;
            }
            foreach ( $section['boxes'] as $j => $box ) {
                $sections[$i]['boxes'][$j]['requires'] = ToolMessagePlacement::launchesForCheckbox($box['value']);
            }
        }
        return $sections;
    }

    /**
     * @param int $contextId
     * @param int $userId
     * @param array<string, mixed> $post
     * @return int registration_id
     */
    public static function addToCourse($contextId, $userId, array $post) {
        $meta = self::metaFromPost($post);
        $title = $meta['title'];
        unset($meta['title']);
        $userId = (int) $userId;
        return ToolRegistrationService::createLti11ForCourse(
            (int) $contextId,
            $title,
            $userId > 0 ? $userId : null,
            $meta
        );
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @param array<string, mixed> $post
     * @return void
     */
    public static function updateOnCourse($contextId, $registrationId, array $post) {
        $meta = self::metaFromPost($post);
        $title = $meta['title'];
        unset($meta['title']);
        ToolRegistrationService::updateLti11ForCourse((int) $contextId, (int) $registrationId, $title, $meta);
    }

    /**
     * @param int $contextId
     * @param int $registrationId
     * @return void
     */
    public static function deleteFromCourse($contextId, $registrationId) {
        ToolRegistrationService::deleteLti11ForCourse((int) $contextId, (int) $registrationId);
    }

    /**
     * Saved form values for a tool this course owns.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array<string, mixed>
     */
    public static function formState($contextId, $registrationId) {
        $row = ToolRegistrationService::courseLti11((int) $contextId, (int) $registrationId);
        $messages = array();
        $placements = array();
        foreach ( ToolRegistrationDocument::messagesForRegistration($row['registration_id']) as $message ) {
            $messages[] = $message['message_type'];
            foreach ( ToolPlacementService::getPlacementsForMessage($message['message_id']) as $placement ) {
                $placements[$placement['placement']] = true;
            }
        }
        $document = ToolRegistrationDocument::registrationDocument($row['registration_id']);
        $claims = is_array($document) ? ToolRegistrationDocument::requestedClaims($document) : array();
        $scopes = is_array($document) ? ToolRegistrationDocument::requestedScopes($document) : array();
        $privacy = array();
        if ( in_array('name', $claims, true) || in_array('given_name', $claims, true) || in_array('family_name', $claims, true) ) {
            $privacy[] = 'names';
        }
        if ( in_array('email', $claims, true) ) {
            $privacy[] = 'email';
        }
        $serviceFor = array(
            ToolRegistrationDocument::SCOPE_SCORE => 'score',
            ToolRegistrationDocument::SCOPE_LINEITEM => 'lineitem',
            ToolRegistrationDocument::SCOPE_RESULT => 'result',
            ToolRegistrationDocument::SCOPE_ROSTER => 'roster',
        );
        $services = array();
        foreach ( $scopes as $scope ) {
            if ( isset($serviceFor[$scope]) ) {
                $services[] = $serviceFor[$scope];
            }
        }
        return array(
            'registration_id' => $row['registration_id'],
            'title' => $row['title'],
            'lti11_url' => $row['lti11_url'],
            'lti11_key' => $row['lti11_key'],
            'lti11_secret' => $row['lti11_secret'],
            'messages' => $messages,
            'placements' => array_keys($placements),
            'privacy' => $privacy,
            'services' => $services,
            'other_launch_urls' => ToolRegistrationService::otherLaunchUrlCount(
                (int) $contextId,
                $row['lti11_url'],
                $row['registration_id']
            ),
        );
    }

    /**
     * @param int $count
     * @return string empty when no other tool uses the URL
     */
    public static function launchUrlNote($count) {
        $count = (int) $count;
        if ( $count < 1 ) {
            return '';
        }
        if ( $count === 1 ) {
            return __('There is 1 other tool using this launch URL.');
        }
        return sprintf(__('There are %d other tools using this launch URL.'), $count);
    }

    /**
     * @param int $contextId
     * @param string $url
     * @param int $exceptRegistrationId
     * @return int
     */
    public static function otherLaunchUrlCount($contextId, $url, $exceptRegistrationId = 0) {
        return ToolRegistrationService::otherLaunchUrlCount((int) $contextId, $url, (int) $exceptRegistrationId);
    }

    /**
     * Tools already visible in this course.
     *
     * course_owned is true when this course administers an LTI 1.1 registration.
     * A tool shared from an organization stays visible and is not course_owned.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function toolsOnCourse($contextId) {
        $contextId = (int) $contextId;
        $tools = ToolDeploymentService::getRegistrationsForContext($contextId);
        $counts = ToolRegistrationService::otherLaunchUrlCounts(array_column($tools, 'registration_id'));
        foreach ( $tools as $i => $tool ) {
            $registration = ToolRegistrationService::findRegistration((int) $tool['registration_id']);
            $tools[$i]['course_owned'] = $registration !== null
                && $registration['lti_version'] === '1.1'
                && $registration['owner_context_id'] === $contextId;
            $tools[$i]['can_test'] = $registration !== null && $registration['lti_version'] === '1.1';
            $version = $registration !== null ? (string) $registration['lti_version'] : '';
            $tools[$i]['lti_version'] = ($version === '1.1' || $version === '1.3') ? $version : '';
            $registrationId = (int) $tool['registration_id'];
            $tools[$i]['other_launch_urls'] = $counts[$registrationId] ?? 0;
        }
        return $tools;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function metaFromPost(array $post) {
        $title = self::postedText($post, 'title', 'Tool title', 512);
        $url = self::postedText($post, 'lti11_url', 'Launch URL', 2048);
        if ( ! preg_match('#^https?://#i', $url) ) {
            throw new \InvalidArgumentException('Launch URL must start with http:// or https://.');
        }
        $messages = self::postedChecks($post, 'messages');
        $privacy = self::postedChecks($post, 'privacy');
        $claims = array();
        if ( in_array('names', $privacy, true) ) {
            $claims[] = 'name';
            $claims[] = 'given_name';
            $claims[] = 'family_name';
        }
        if ( in_array('email', $privacy, true) ) {
            $claims[] = 'email';
        }
        $scopes = array();
        foreach ( self::postedChecks($post, 'services') as $service ) {
            $scopes[] = self::scopeUri($service);
        }
        return array(
            'title' => $title,
            'lti_version' => '1.1',
            'lti11_key' => self::postedText($post, 'lti11_key', 'Key', 255),
            'lti11_secret' => self::postedText($post, 'lti11_secret', 'Secret', 512),
            'lti11_url' => $url,
            'messages' => $messages,
            'placements' => self::postedChecks($post, 'placements'),
            'claims' => $claims,
            'scopes' => $scopes,
        );
    }

    /**
     * @param string $service
     * @return string
     */
    private static function scopeUri($service) {
        $uris = array(
            'score' => ToolRegistrationDocument::SCOPE_SCORE,
        );
        if ( ! isset($uris[$service]) ) {
            throw new \InvalidArgumentException('That choice is not part of this form.');
        }
        return $uris[$service];
    }

    /**
     * @param array<string, mixed> $post
     * @param string $name
     * @return array<int, string>
     */
    private static function postedChecks(array $post, $name) {
        $allowed = array();
        foreach ( self::formSections() as $section ) {
            if ( ($section['kind'] ?? '') !== 'checks' || ($section['name'] ?? '') !== $name ) {
                continue;
            }
            foreach ( $section['boxes'] as $box ) {
                $allowed[$box['value']] = true;
            }
        }
        if ( ! array_key_exists($name, $post) || $post[$name] === null || $post[$name] === '' ) {
            return array();
        }
        $values = $post[$name];
        if ( ! is_array($values) || ! array_is_list($values) ) {
            throw new \InvalidArgumentException('That choice is not part of this form.');
        }
        $out = array();
        foreach ( $values as $value ) {
            if ( ! is_string($value) || ! isset($allowed[$value]) || isset($out[$value]) ) {
                throw new \InvalidArgumentException('That choice is not part of this form.');
            }
            $out[$value] = true;
        }
        return array_keys($out);
    }

    /**
     * @param array<string, mixed> $post
     * @param string $name
     * @param string $label
     * @param int $max
     * @return string
     */
    private static function postedText(array $post, $name, $label, $max) {
        $value = $post[$name] ?? '';
        if ( ! is_string($value) ) {
            throw new \InvalidArgumentException($label.' is required.');
        }
        $value = trim($value);
        if ( $value === '' ) {
            throw new \InvalidArgumentException($label.' is required.');
        }
        if ( strlen($value) > $max ) {
            throw new \InvalidArgumentException($label.' is too long.');
        }
        return $value;
    }
}
