<?php
require_once "../../config.php";

// See the end of the file for some documentation references 

$pieces = parse_url($CFG->wwwroot);
$domain = isset($pieces['host']) ? $pieces['host'] : false;

header("Content-type: application/xml");
echo('<?xml version="1.0" encoding="UTF-8"?>'."\n");
?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
  <blti:title><?= htmlent_utf8(strip_tags($CFG->servicename)) ?></blti:title>
  <blti:description><?= htmlent_utf8(strip_tags($CFG->servicedesc)) ?></blti:description>
  <blti:launch_url><?= $CFG->wwwroot ?>/about.php</blti:launch_url>
  <blti:custom>
    <lticm:property name="sub_canvas_account_id">$Canvas.account.id</lticm:property>
    <lticm:property name="sub_canvas_account_name">$Canvas.account.name</lticm:property>
    <lticm:property name="sub_canvas_account_sis_sourceId">$Canvas.account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_api_domain">$Canvas.api.domain</lticm:property>
    <lticm:property name="sub_canvas_assignment_id">$Canvas.assignment.id</lticm:property>
    <lticm:property name="sub_canvas_assignment_points_possible">$Canvas.assignment.pointsPossible</lticm:property>
    <lticm:property name="sub_canvas_assignment_title">$Canvas.assignment.title</lticm:property>
    <lticm:property name="sub_canvas_course_id">$Canvas.course.id</lticm:property>
    <lticm:property name="sub_canvas_course_sis_source_id">$Canvas.course.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_enrollment_enrollment_state">$Canvas.enrollment.enrollmentState</lticm:property>
    <lticm:property name="sub_canvas_membership_concluded_roles">$Canvas.membership.concludedRoles</lticm:property>
    <lticm:property name="sub_canvas_membership_roles">$Canvas.membership.roles</lticm:property>
    <lticm:property name="sub_canvas_root_account.id">$Canvas.root_account.id</lticm:property>
    <lticm:property name="sub_canvas_root_account_sis_source_id">$Canvas.root_account.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_user_id">$Canvas.user.id</lticm:property>
    <lticm:property name="sub_canvas_user_login_id">$Canvas.user.loginId</lticm:property>
    <lticm:property name="sub_canvas_user_sis_source_id">$Canvas.user.sisSourceId</lticm:property>
    <lticm:property name="sub_canvas_caliper_url">$Caliper.url</lticm:property>
    <lticm:property name="person_address_timezone">$Person.address.timezone</lticm:property>
    <lticm:property name="person_email_primary">$Person.email.primary</lticm:property>
    <lticm:property name="person_name_family">$Person.name.family</lticm:property>
    <lticm:property name="person_name_full">$Person.name.full</lticm:property>
    <lticm:property name="person_name_given">$Person.name.given</lticm:property>
    <lticm:property name="user_image">$User.image</lticm:property>
  </blti:custom>
  <blti:extensions platform="canvas.instructure.com">
     <lticm:property name="privacy_level">public</lticm:property>
<?php
     if ( $domain ) echo ('<lticm:property name="domain">'.$domain."</lticm:property>\n");
?>
    <lticm:property name="icon_url"><?= $CFG->staticroot ?>/img/default-icon-16x16.png</lticm:property>
    <lticm:options name="link_selection">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url"><?= $CFG->wwwroot ?>/lti/store/index.php?type=link_selection</lticm:property>
    </lticm:options>
    <lticm:options name="assignment_selection">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url"><?= $CFG->wwwroot ?>/lti/store/index.php?type=assignment_selection</lticm:property>
    </lticm:options>
    <lticm:options name="editor_button">
      <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
      <lticm:property name="url"><?= $CFG->wwwroot ?>/lti/store/index.php?type=editor_button</lticm:property>
    </lticm:options>
<?php if ( isset($CFG->lessons) ) { ?>
    <lticm:options name="migration_selection">
        <lticm:property name="enabled">true</lticm:property>
        <lticm:property name="url"><?= $CFG->wwwroot ?>/cc/export</lticm:property>
    </lticm:options>
<?php } ?>
    <lticm:property name="text"><?= htmlent_utf8(strip_tags($CFG->servicename)) ?></lticm:property>
  </blti:extensions>
</cartridge_basiclti_link><?php

/*
Best documentation on how it works is here:

https://www.eduappcenter.com/docs/extensions/content

I believe you already have a canvas account and you can install 
a tool in any course you have or create. If you want to be able 
to see it in action, you can install this tool:

http://lti-tool-provider.herokuapp.com/

it links to its xml config on that page, it is here:

http://lti-tool-provider.herokuapp.com/tool_config.xml

The trick in that xml is the block: <lticm:options name="editor_button">

That is what makes a button appear in the RCE. The other option of 
interest to you there is selection for module items which is 
'resource_selection'. We don't have an UI components to enable those placements. 

These 2 pages show how to configure it in Canvas:

https://www.eduappcenter.com/docs/extensions/canvas_wysiwyg
https://www.eduappcenter.com/docs/extensions/canvas_link_selection

If you need more details let me know.

- Bracken

Note from: John Jonston - https://www.edu-apps.org/build_xml.html
*/

