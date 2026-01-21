<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;
use \Tsugi\Util\CC_LTI;

class CC_LTI_Outcome extends \Tsugi\Util\CC_LTI {

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link
  xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
  xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
  xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
  xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <blti:title>BLTI Test</blti:title>
  <blti:description>Test a BLTI Link</blti:description>
  <blti:custom>
    <lticm:property name="caliper_url">$Caliper.url</lticm:property>
  </blti:custom>
  <blti:extensions platform="www.tsugi.org">
    <lticm:property name="caliper_url">$Caliper.url</lticm:property>
    <lticm:property name="apphome">value</lticm:property>
  </blti:extensions>
  <blti:extensions platform="canvas.instructure.com">
    <lticm:property name="outcome">10.0</lticm:property>
    <lticm:property name="canvas_caliper_url">$Caliper.url</lticm:property>
  </blti:extensions>
  <blti:launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:launch_url>
  <blti:secure_launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:secure_launch_url>
  <blti:icon>url to an icon for this tool (optional)</blti:icon>
  <blti:secure_icon>secure url to an icon for this tool (optional)&gt;</blti:secure_icon>
  <blti:vendor>
    <lticp:code>tsugi.org</lticp:code>
    <lticp:name>Tsugi Learning Platform</lticp:name>
    <lticp:description>
        Tsugi is a learning platform and set of APIs that make it easy to build
        highly interoperable learning tools for use in standards compliant
        Learning Management Systems like Sakai, Moodle, Blackboard, Brightspace, and Canvas.
    </lticp:description>
    <lticp:url>http://www.tsugi.org</lticp:url>
    <lticp:contact>
      <lticp:email>tsugi@apereo.org</lticp:email>
    </lticp:contact>
  </blti:vendor>
</cartridge_basiclti_link>');

        $this->set_namespace(CC::BLTI_NS);
        $this->delete_children('extensions');
        $this->delete_children('icon');
        $this->delete_children('secure_icon');
        $this->delete_children('launch_url');
        $this->delete_children('secure_launch_url');
    }

}
