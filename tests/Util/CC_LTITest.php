<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CC_LTI.php";

use \Tsugi\Util\CC_LTI;

class CC_LTITest extends \PHPUnit\Framework\TestCase
{
    public function testGeneral() {

        $lti_dom = new CC_LTI();
        $lti_dom->set_title('Autograder: Single-table SQL');
        $lti_dom->set_description('Create a single SQL table and insert some records.');
        $lti_dom->set_secure_launch_url('https://www.php-intro.com/tools/sql/index.php');
        $lti_dom->set_custom('exercise','single_mysql.php');
        $lti_dom->set_extension('apphome','http://www.php-intro.com');
        $save = $lti_dom->saveXML();
        $xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp="http://www.imsglobal.org/xsd/imslticp_v1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <blti:title>Autograder: Single-table SQL</blti:title>
  <blti:description>Create a single SQL table and insert some records.</blti:description>
  <blti:custom>
    <lticm:property name="caliper_url">$Caliper.url</lticm:property>
    <lticm:property name="exercise">single_mysql.php</lticm:property>
  </blti:custom>
  <blti:extensions platform="www.tsugi.org">
    <lticm:property name="apphome">http://www.php-intro.com</lticm:property>
  </blti:extensions>
  <blti:extensions platform="canvas.instructure.com">
    <lticm:property name="canvas_caliper_url">$Caliper.url</lticm:property>
  </blti:extensions>
  <blti:launch_url>https://www.php-intro.com/tools/sql/index.php</blti:launch_url>
  <blti:secure_launch_url>https://www.php-intro.com/tools/sql/index.php</blti:secure_launch_url>
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
</cartridge_basiclti_link>
';
        $this->assertEquals($xmlout,$save);

    }

    /**
     * Test set_launch_url() method
     */
    public function testSetLaunchUrl() {
        $lti_dom = new CC_LTI();
        $lti_dom->set_title('Test Tool');
        $lti_dom->set_launch_url('http://example.com/launch');
        $save = $lti_dom->saveXML();
        
        $this->assertStringContainsString('<blti:launch_url>http://example.com/launch</blti:launch_url>', $save,
            'set_launch_url should add launch_url element');
    }

    /**
     * Test set_icon() method
     */
    public function testSetIcon() {
        $lti_dom = new CC_LTI();
        $lti_dom->set_title('Test Tool');
        $lti_dom->set_icon('http://example.com/icon.png');
        $save = $lti_dom->saveXML();
        
        $this->assertStringContainsString('<blti:icon>http://example.com/icon.png</blti:icon>', $save,
            'set_icon should add icon element');
    }

    /**
     * Test set_secure_icon() method
     */
    public function testSetSecureIcon() {
        $lti_dom = new CC_LTI();
        $lti_dom->set_title('Test Tool');
        $lti_dom->set_secure_icon('https://example.com/secure-icon.png');
        $save = $lti_dom->saveXML();
        
        $this->assertStringContainsString('<blti:secure_icon>https://example.com/secure-icon.png</blti:secure_icon>', $save,
            'set_secure_icon should add secure_icon element');
    }

    /**
     * Test all icon and launch URL methods together
     * 
     * Note: set_secure_launch_url() also sets launch_url to the same value,
     * so if both are called, the last one wins for launch_url.
     */
    public function testAllLaunchAndIconMethods() {
        $lti_dom = new CC_LTI();
        $lti_dom->set_title('Complete Tool');
        $lti_dom->set_description('A tool with all launch and icon options');
        // Call set_secure_launch_url last since it also sets launch_url
        $lti_dom->set_secure_launch_url('https://example.com/secure-launch');
        $lti_dom->set_icon('http://example.com/icon.png');
        $lti_dom->set_secure_icon('https://example.com/secure-icon.png');
        $save = $lti_dom->saveXML();
        
        // set_secure_launch_url sets both launch_url and secure_launch_url to the same value
        $this->assertStringContainsString('<blti:launch_url>https://example.com/secure-launch</blti:launch_url>', $save,
            'Should contain launch_url (set by set_secure_launch_url)');
        $this->assertStringContainsString('<blti:secure_launch_url>https://example.com/secure-launch</blti:secure_launch_url>', $save,
            'Should contain secure_launch_url');
        $this->assertStringContainsString('<blti:icon>http://example.com/icon.png</blti:icon>', $save,
            'Should contain icon');
        $this->assertStringContainsString('<blti:secure_icon>https://example.com/secure-icon.png</blti:secure_icon>', $save,
            'Should contain secure_icon');
    }
}
