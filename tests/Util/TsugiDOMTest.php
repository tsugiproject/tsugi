<?php

require_once "src/Util/TsugiDOM.php";


class TsugiDomTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral() {

        $xmlin = 
'<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link
  xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
  xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
  xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0"
  xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <blti:title>BLTI Test</blti:title>
  <blti:description>Test a BLTI Link</blti:description>
  <blti:custom>
    <lticm:property name="keyname">value</lticm:property>
  </blti:custom>
  <blti:extensions platform="www.tsugi.org">
    <lticm:property name="apphome">value</lticm:property>
  </blti:extensions>
  <blti:extensions platform="canvas.instructure.com">
    <lticm:property name="outcome">10.0</lticm:property>
  </blti:extensions>
  <blti:launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:launch_url>
  <blti:secure_launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:secure_launch_url>
  <blti:icon>url to an icon for this tool (optional)</blti:icon>
  <blti:secure_icon>secure url to an icon for this tool (optional)&gt;</blti:secure_icon>
  <blti:vendor>
    <lticp:code>ims.org</lticp:code>
    <lticp:name>IMS Global Learning Consortium</lticp:name>
    <lticp:description/>
    <lticp:url>http://www.imsglobal.org</lticp:url>
    <lticp:contact>
      <lticp:email>blti@imsglobal.org</lticp:email>
    </lticp:contact>
  </blti:vendor>
</cartridge_basiclti_link>';

        $xmlout = 
'<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0p1.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <blti:title>XML SUCKS!</blti:title>
  <blti:custom>
    <blti:property e="mc-squared">SWEET!</blti:property>
  </blti:custom>
  <blti:extensions platform="www.tsugi.org">
    <lticm:property name="apphome">value</lticm:property>
  </blti:extensions>
  <blti:extensions platform="canvas.instructure.com">
    <lticm:property name="outcome">10.0</lticm:property>
  </blti:extensions>
  <blti:launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:launch_url>
  <blti:secure_launch_url>http://www.imsglobal.org/developers/BLTI/tool.php</blti:secure_launch_url>
  <blti:icon>url to an icon for this tool (optional)</blti:icon>
  <blti:secure_icon>secure url to an icon for this tool (optional)&gt;</blti:secure_icon>
  <blti:vendor>
    <lticp:code>ims.org</lticp:code>
    <lticp:name>IMS Global Learning Consortium</lticp:name>
    <lticp:description/>
    <lticp:url>http://www.imsglobal.org</lticp:url>
    <lticp:contact>
      <lticp:email>blti@imsglobal.org</lticp:email>
    </lticp:contact>
  </blti:vendor>
</cartridge_basiclti_link>
';

        $blti_ns = 'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
    
        $lti_dom = new \Tsugi\Util\TsugiDOM($xmlin);
        $lti_dom->set_namespace($blti_ns);

        $lti_dom->replace_text('title', 'XML SUCKS!');
        $lti_dom->delete_tag('description');
        $lti_dom->delete_children('custom');
        $tag = $lti_dom->get_tag('custom');
        $lti_dom->add_child($tag, 'property', 'SWEET!', array("e"=>"mc-squared"));
        $save = $lti_dom->saveXML();
        $this->assertEquals($xmlout,$save);

        $lti_dom->delete_children('custom');
        $tag = $lti_dom->get_tag('custom');
        $lti_dom->add_child('custom', 'property', 'DUDE!', array("e"=>"mc-squared"));
        $save = $lti_dom->saveXML();
        $this->assertEquals(str_replace('SWEET', 'DUDE', $xmlout),$save);

        $tag = $lti_dom->get_tag('extensions', 'platform', 'canvas.instructure.com');
        $this->assertNotNull($tag);
        $this->assertEquals($tag->firstChild->nodeName, "lticm:property");
        $this->assertEquals($tag->firstChild->getAttribute('name'), 'outcome');

    }
}
