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
                        "text": "Canvas Tsugi",
			            "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "assignment_selection",
                        "message_type": "LtiDeepLinkingRequest",
            			"selection_width": 800,
            			"selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=assignment_selection"
                    },
                    {
                        "text": "Canvas Tsugi",
			            "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "link_selection",
                        "message_type": "LtiDeepLinkingRequest",
            			"selection_width": 800,
            			"selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=link_selection"
                    },
                    {
                        "text": "Canvas Tsugi",
			            "enabled": true,
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "migration_selection",
                        "message_type": "LtiDeepLinkingRequest",
            			"selection_width": 800,
            			"selection_height": 800,
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/cc/export/?placement=migration_selection"
                    },
                    {
                        "text": "Canvas Tsugi",
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
        },
        {
            "platform": "tsugi.org",
			"note": "Canvas finds a default redirect_uris value from target_link_uri.  So Tsugi puts its redirect_uri there and makes sure to override target_link_uri in each of the placements so the global target_link_uri is in effect ignored."
		}
    ],
	"public_jwk_url" : "https://www.tsugi.org/jwk_url_goes_here",
    "public_jwk": {
        "e": "AQAB",
        "n": "qO4FGwu73DwNXVFG6EJKNCnE5ceBAnxi5kOk3exYqx-mSCJNU7J3E88qbZa_jyhSOtSs1ZwtcBoBhROIcbfGznCLGoi3OjZzt223I7cT8WaR1gZlB0XJ6f1XPPo6-IleRZZ7BF1O6SlsIorN00i-K7hF-S9euzdvOHkGLWS6UU537wT19famfvjO-UDzXWTxCVOcdmCnW0oSBVXJeFia-yk9gYMyRuoozKyb6T-s9--OgSVhpvtxNF4fDFc_h26Syve1d7BJwa8Nd0LwKxIniXAtVJi-1Itm3pqwspCE0VJPdPpTx6HRW9wexDn6EtYdUcKjy93l7xLvgnObd3mxfQ",
        "alg": "RS256",
        "kid": "6rW2pCGQblYiEvW_OIDTRBOr6_Pt1NVQaGZ-Z_FF9Ys",
        "kty": "RSA",
        "use": "sig"
    },
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
    "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/42_wtf_this_is_silly_when_there_are_placements"
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

$json->oidc_initiation_url = $CFG->wwwroot . "/lti/oidc_login".(isset($row['issuer_guid']) ? "/".$row['issuer_guid'] : '');


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
    $json->extensions[0]->settings->placements[$i]->text = $CFG->servicename;
	if ( $placement == "migration_selection" ) {
    	$json->extensions[0]->settings->placements[$i]->target_link_uri = 
			$CFG->wwwroot . "/cc/export";
	} else {
    	$json->extensions[0]->settings->placements[$i]->target_link_uri = 
			$CFG->wwwroot . "/lti/store/?placement=" .  urlencode($placement);
	}
    $json->extensions[0]->settings->placements[$i]->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
}

// User the public url variant
// https://canvas.instructure.com/doc/api/file.lti_dev_key_config.html
$json->public_jwk_url = $CFG->wwwroot . "/lti/keyset";
unset($json->public_jwk);

// TODO: Remove all this
/*
$rows = Keyset::getCurrentKeys();
if ( ! $rows || ! is_array($rows) || count($rows) < 1 ) {
    die("Could not load key");
}

$pubkey = $rows[0]['pubkey'];
// Handle the keyset
$jwk = Keyset::build_jwk($pubkey);

// echo(json_encode($jwk));
// echo("\n");
$json->public_jwk = $jwk;
*/

echo(json_encode($json, JSON_PRETTY_PRINT));
