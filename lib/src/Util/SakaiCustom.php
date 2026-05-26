<?php

namespace Tsugi\Util;

/**
 * Sakai LTI custom substitution parameters for deep linking and tool registration.
 *
 * Custom parameter names for Sakai-specific substitutions use a `sak_` prefix;
 * values are still standard IMS/Sakai substitution variables (e.g. `$Sakai.assignment.openDate`).
 *
 * Keep in sync with org.sakaiproject.lti.util.SakaiLTIUtil assignment substitution constants.
 */
class SakaiCustom {

    /**
     * IMS ResourceLink date substitution parameters (unprefixed custom names).
     */
    public static function imsDateCustom() {
        return array(
            'availableStart' => '$ResourceLink.available.startDateTime',
            'availableEnd' => '$ResourceLink.available.endDateTime',
            'submissionStart' => '$ResourceLink.submission.startDateTime',
            'submissionEnd' => '$ResourceLink.submission.endDateTime',
        );
    }

    /**
     * Sakai.assignment.* date substitution parameters (`sak_` custom names).
     */
    public static function sakaiAssignmentDateCustom() {
        return array(
            // Sakai stores visible date but does not show it in Assignments UI unless assignment.visible.date.enabled
            'sak_visibleDate' => '$Sakai.assignment.visibleDate',
            'sak_openDate' => '$Sakai.assignment.openDate',
            'sak_dueDate' => '$Sakai.assignment.dueDate',
            'sak_closeDate' => '$Sakai.assignment.closeDate',
            'sak_resubmissionAcceptUntil' => '$Sakai.assignment.resubmissionAcceptUntil',
            'sak_availableStartDateTime' => '$Sakai.assignment.availableStartDateTime',
        );
    }

    /**
     * IMS and Sakai assignment date substitution parameters.
     */
    public static function assignmentDateCustom() {
        return array_merge(self::imsDateCustom(), self::sakaiAssignmentDateCustom());
    }

    /**
     * Common context/history and Sakai API substitution parameters.
     */
    public static function commonCustom() {
        return array(
            'resourcelink_id_history' => '$ResourceLink.id.history',
            'context_id_history' => '$Context.id.history',
            'sak_api_url' => '$Sakai.api.url',
            'sak_direct_url' => '$Sakai.direct.url',
            'sak_scopes' => '$Sakai.scopes.available',
        );
    }

    /**
     * Full custom map for Sakai deep link content items and tool registration.
     *
     * @param bool $includeCaliper Include $Caliper.url (legacy custom name canvas_caliper_url)
     * @param bool $includeCourseGroup Include $CourseGroup.id
     */
    public static function deepLinkCustom($includeCaliper = true, $includeCourseGroup = false) {
        $custom = array_merge(self::assignmentDateCustom(), self::commonCustom());
        if ( $includeCaliper ) {
            $custom['canvas_caliper_url'] = '$Caliper.url';
        }
        if ( $includeCourseGroup ) {
            $custom['coursegroup_id'] = '$CourseGroup.id';
        }
        return $custom;
    }
}
