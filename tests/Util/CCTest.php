<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";

use \Tsugi\Util\CC;

class CCTest extends PHPUnit_Framework_TestCase
{
    public function testGeneral() {

        $cc_dom = new CC();
        $cc_dom->set_title('Web Applications for Everybody');
        $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');
        $module = $cc_dom->add_module('Week 1');
        $sub_module = $cc_dom->add_sub_module($module, 'Part 1');
        $file1 = $cc_dom->add_web_link($sub_module, 'Video: Introducting SQL');
        $file2= $cc_dom->add_lti_link($sub_module, 'Autograder: Single Table SQL');

$xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="cctd0015" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imscp_v1p2_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lomresource_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lommanifest_v1p0.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.2.0</schemaversion>
    <lomimscc:lom>
      <lomimscc:general>
        <lomimscc:title>
          <lomimscc:string language="en-US">Web Applications for Everybody</lomimscc:string>
        </lomimscc:title>
        <lomimscc:description>
          <lomimscc:string language="en-US">Awesome MOOC to learn PHP, MySQL, and JavaScript.</lomimscc:string>
        </lomimscc:description>
      </lomimscc:general>
    </lomimscc:lom>
  </metadata>
  <organizations>
    <organization identifier="T_1000" structure="rooted-hierarchy">
      <item identifier="T_00000">
        <item identifier="T_000001">
          <title>Week 1</title>
          <item identifier="T_000002">
            <title>Part 1</title>
            <item identifier="T_000003" identifierref="T_000003_R">
              <title>Video: Introducting SQL</title>
            </item>
            <item identifier="T_000004" identifierref="T_000004_R">
              <title>Autograder: Single Table SQL</title>
            </item>
          </item>
        </item>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="T_000003_R" type="imswl_xmlv1p2">
      <file href="xml/WL_000003.xml"/>
    </resource>
    <resource identifier="T_000004_R" type="imsbasiclti_xmlv1p0">
      <file href="xml/LT_000004.xml"/>
    </resource>
  </resources>
</manifest>
';
        $save = $cc_dom->saveXML();
        // echo $save
        $this->assertEquals($xmlout,$save);

    }
}
