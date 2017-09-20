<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_LTI extends \Tsugi\Util\TsugiDOM {

    function __construct($xml=false) {
        if ( ! $xml ) $xml = '<?xml version="1.0" encoding="UTF-8"?>
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
</cartridge_basiclti_link>';

        parent::__construct($xml);

        $this->set_namespace(CC::BLTI_NS);
        $this->delete_children('extensions');
        $this->delete_children('icon');
        $this->delete_children('secure_icon');
        $this->delete_children('launch_url');
        $this->delete_children('secure_launch_url');
    }

    public function set_title($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'title', $text);
        $this->replace_text_ns(CC::BLTI_NS, 'description', $text);
    }

    public function set_description($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'description', $text);
    }

    public function set_launch_url($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'launch_url', $text);
    }

    public function set_secure_launch_url($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'launch_url', $text);
        $this->replace_text_ns(CC::BLTI_NS, 'secure_launch_url', $text);
    }

    public function set_icon($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'icon', $text);
    }

    public function set_secure_icon($text) {
        $this->replace_text_ns(CC::BLTI_NS, 'secure_icon', $text);
    }

    public function set_custom($key,$value) {
        $tag = $this->get_tag_ns(CC::BLTI_NS, 'custom');
        $this->add_child_ns(CC::LTICM_NS, $tag, 'property', $value, array("name"=>$key));
    }

    public function set_extension($key,$value) {
        $tag = $this->get_tag_ns(CC::BLTI_NS, 'extensions');
        $this->add_child_ns(CC::LTICM_NS, $tag, 'property', $value, array("name"=>$key));
    }

    public function saveXML(\DOMNode $node = NULL, $options = NULL) {

        // Clear out empty nodes
        // http://stackoverflow.com/questions/8603237/remove-empty-tags-from-a-xml-with-php
        $xpath = new \DOMXPath($this);

        foreach( $xpath->query('//*[not(node())]') as $entry ) {
            $entry->parentNode->removeChild($entry);
        }

        return parent::saveXML($node, $options);
    }

}
