<?php

require_once "../../config.php";

use Tsugi\Core\LTIX;
use Tsugi\Core\Keyset;

LTIX::getConnection();

use Tsugi\Util\U;

// https://canvas.instructure.com/doc/api/file.lti_dev_key_config.html
$json_str = <<<JSON
{
    "title": "Tsugi Cloud for Canvas",
    "scopes": [
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/score",
        "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
    ],
    "extensions": [
        {
            "platform": "canvas.instructure.com",
            "privacy_level":"public",
            "settings": {
                "selection_width": 800,
                "selection_height": 800,
                "placements": [
                    {
                        "text": "Tsugi Assignments",
                        "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "assignment_selection",
                        "message_type": "LtiDeepLinkingRequest",
                        "selection_width": 800,
                        "selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=assignment_selection"
                    },
                    {
                        "text": "Tsugi",
                        "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "link_selection",
                        "message_type": "LtiDeepLinkingRequest",
                        "selection_width": 800,
                        "selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=link_selection"
                    },
                    {
                        "text": "Import from Tsugi",
                        "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "migration_selection",
                        "message_type": "LtiDeepLinkingRequest",
                        "selection_width": 800,
                        "selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=migration_selection"
                    },
                    {
                        "text": "Tsugi",
                        "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "editor_button",
                        "message_type": "LtiDeepLinkingRequest",
                        "selection_width": 800,
                        "selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=editor_button"
                    }
                ]
            }
        }
    ],
    "public_jwk_url" : "https://www.tsugi.org/jwk_url_goes_here",
    "description": "Tsugi Cloud for Canvas",
    "custom_fields": {
        "availableStart": "\$ResourceLink.available.startDateTime",
        "availableEnd": "\$ResourceLink.available.endDateTime",
        "submissionStart": "\$ResourceLink.submission.startDateTime",
        "submissionEnd": "\$ResourceLink.submission.endDateTime",
        "resourcelink_id_history": "\$ResourceLink.id.history",
        "context_id_history": "\$Context.id.history",
        "canvas_caliper_url": "\$Caliper.url",
        "timezone": "\$Person.address.timezone",
        "pointsPossible": "\$Canvas.assignment.pointsPossible",
        "userPronouns": "\$com.instructure.Person.pronouns",
        "localAssignmentId": "\$Canvas.assignment.id",
        "prevCourses": "\$Canvas.course.previousCourseIds",
        "termName": "\$Canvas.term.name",
        "assignmentUnlockAt": "\$Canvas.assignment.unlockAt.iso8601",
        "courseId": "\$Canvas.course.id",
        "canvas_api_domain": "\$Canvas.api.domain",
        "canvas_module_id": "\$Canvas.module.id",
        "toolContextLinkUrl": "\$Canvas.externalTool.url",
        "canvas_assignment_id": "\$Canvas.assignment.id",
        "prevContexts": "\$Canvas.course.previousContextIds",
        "userDisplayName": "\$Person.name.display",
        "assignmentLockAt": "\$Canvas.assignment.lockAt.iso8601",
        "anonymousGrading": "\$com.instructure.Assignment.anonymous_grading",
        "canvas_module_item_id": "\$Canvas.moduleItem.id",
        "ltiGroupContextIds": "\$Membership.course.groupIds",
        "termStart": "\$Canvas.term.startAt",
        "canvas_course_id": "\$Canvas.course.id",
        "courseName": "\$Canvas.course.name",
        "sectionIds": "\$Canvas.course.sectionIds",
        "sourceUserId": "\$Person.sourcedId"
    },
    "oidc_initiation_url": "https://canvas.tsugicloud.org/tsugi/lti/oidc_login",
    "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/42_wtf_this_is_silly_when_there_are_placements",
    "note_from_tsugi": "Canvas finds a default redirect_uris value from target_link_uri.  So Tsugi puts its redirect_uri there and makes sure to override target_link_uri in each of the placements so the global target_link_uri is in effect ignored."
}
JSON
;

// Possible additional custom values - if we can understand.
/*
    "supportsPrivateGroups": "true",
    "supportsExternalGrading": "true",
    "supportsUserGroups": "true",
*/

$json = json_decode(trim($json_str));

if ( ! $json ) {
    echo("<pre>\n");
    echo($json_str);
    echo("</pre>\n");
    die('Unable to parse JSON '.json_last_error_msg());
}

header("Content-type: application/json");

$json->title = $CFG->servicename;
$json->description = $CFG->servicename;
if ( $CFG->servicedesc ) {
    $json->description = $CFG->servicedesc;
}

$json->oidc_initiation_url = $CFG->wwwroot . "/lti/oidc_login".(isset($_GET['issuer_guid']) ? "/".$_GET['issuer_guid'] : '');

// TODO: Submit PR to Canvas :)
// Canvas sems not to have any way to specify the redirect_uris
// https://github.com/instructure/canvas-lms/blob/master/app/models/lti/tool_configuration.rb#L67
$json->redirect_uris = $CFG->wwwroot . "/lti/oidc_launch";  // What it *should be* if it were fixed properly
$json->oidc_redirect_uris = $json->redirect_uris;   // Maybe it should be this once fixed,

$json->target_link_uri = $json->redirect_uris;  // Work around bug in Canvas

$pieces = parse_url($CFG->wwwroot);
$domain = isset($pieces['host']) ? $pieces['host'] : false;

$json->extensions[0]->domain = $domain;
$json->extensions[0]->tool_id = md5($CFG->wwwroot);
$json->extensions[0]->settings->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
for($i=0; $i < count($json->extensions[0]->settings->placements); $i++) {
    $placement =$json->extensions[0]->settings->placements[$i]->placement;
    $json->extensions[0]->settings->placements[$i]->text =
        str_replace('Tsugi', $CFG->servicename, $json->extensions[0]->settings->placements[$i]->text);
    $json->extensions[0]->settings->placements[$i]->target_link_uri =
        $CFG->wwwroot . "/lti/store/?placement=" .  urlencode($placement);
    $json->extensions[0]->settings->placements[$i]->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
}

// User the public url variant
// https://canvas.instructure.com/doc/api/file.lti_dev_key_config.html
$json->public_jwk_url = $CFG->wwwroot . "/lti/keyset";

echo(json_encode($json, JSON_PRETTY_PRINT));

// vim: ts=8 et sw=4 sts=4
