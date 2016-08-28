<?php

namespace Tsugi\Util;


class CC extends \Tsugi\Util\TsugiDOM {

    const CC_1_2_CP =   'http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1';
    const WL_NS =       'http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2';
    const BLTI_NS =     'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
    const LTICM_NS =    'http://www.imsglobal.org/xsd/imslticm_v1p0';
    const LTICP_NS =    'http://www.imsglobal.org/xsd/imslticp_v1p0';
    const LOM_NS =      'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource';
    const LOMIMSCC_NS = 'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest';
    
    const metadata_xpath = '/*/*[1]';
    const item_xpath = '/*/*[2]/*/*';
    const resource_xpath = '/*/*[3]';
    const lom_general_xpath = '/*/*[1]/lomimscc:lom/lomimscc:general';

    public $resource_count = 0;

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="cctd0015"
  xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1"
  xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource"
  xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imscp_v1p2_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lomresource_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lommanifest_v1p0.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.2.0</schemaversion>
    <lomimscc:lom>
      <lomimscc:general>
        <lomimscc:title>
          <lomimscc:string language="en-US">Common Cartridge Test Data Set - Validation Cartridge 1</lomimscc:string>
        </lomimscc:title>
        <lomimscc:description>
          <lomimscc:string language="en-US">Sample Common Cartridge with a Basic Learning Tools Interoperability Link</lomimscc:string>
        </lomimscc:description>
      </lomimscc:general>
    </lomimscc:lom>
  </metadata>
  <organizations>
    <organization identifier="T_1000" structure="rooted-hierarchy">
      <item identifier="T_00000">
        <item identifier="T_00001" identifierref="T_00001_R">
          <title>BLTI Test</title>
        </item>
        <item identifier="T_00005" identifierref="T_00005_R">
          <title>Web Link Test</title>
        </item>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="T_00001_R" type="imsbasiclti_xmlv1p0">
      <file href="LTI.xml"/>
      <dependency identifierref="BLTI001_Icon"/>
    </resource>
    <resource identifier="T_00005_R" type="imswl_xmlv1p2">
      <file href="WebLink.xml"/>
    </resource>
  </resources>
</manifest>');

        $xpath = new \DOMXpath($this);
        $res = $xpath->query(self::resource_xpath)->item(0);
        $this->delete_children_ns(self::CC_1_2_CP, $res);
        $items = $xpath->query(self::item_xpath)->item(0);
        $this->delete_children_ns(self::CC_1_2_CP, $items);
        $lom = $xpath->query(self::lom_general_xpath)->item(0);
        $this->delete_children_ns(self::LOMIMSCC_NS, $lom);
    }

    public function set_title($title) {
        $xpath = new \DOMXpath($this);
        $general = $xpath->query(CC::lom_general_xpath)->item(0);
        $new_title = $this->add_child_ns(CC::LOMIMSCC_NS, $general, 'title');
        $new_string = $this->add_child_ns(CC::LOMIMSCC_NS, $new_title, 'string', $title, array("language" => "en-US"));
    }

    public function set_description($title) {
        $xpath = new \DOMXpath($this);
        $general = $xpath->query(CC::lom_general_xpath)->item(0);
        $new_description = $this->add_child_ns(CC::LOMIMSCC_NS, $general, 'description');
        $new_string = $this->add_child_ns(CC::LOMIMSCC_NS, $new_description, 'string', $title, array("language" => "en-US"));
    }

    public function add_module($title) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $identifier = 'T_'.$resource_str;

        $xpath = new \DOMXpath($this);

        $items = $xpath->query(CC::item_xpath)->item(0);
        $module = $this->add_child_ns(CC::CC_1_2_CP, $items, 'item', null, array('identifier' => $identifier));
        $new_title = $this->add_child_ns(CC::CC_1_2_CP, $module, 'title', $title);
        return $module;
    }

    public function add_sub_module($module, $title) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $identifier = 'T_'.$resource_str;

        $sub_module = $this->add_child_ns(CC::CC_1_2_CP, $module, 'item', null, array('identifier' => $identifier));
        $new_title = $this->add_child_ns(CC::CC_1_2_CP, $sub_module, 'title',$title);
        return $sub_module;
    }

    public function add_web_link($module, $title=null) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $file = 'xml/WL_'.$resource_str.'.xml';
        $identifier = 'T_'.$resource_str;
        $type = 'imswl_xmlv1p2';
        $this-> add_resource_item($module, $title, $type, $identifier, $file);
        return $file;
    }

    public function add_lti_link($module, $title=null) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $file = 'xml/LT_'.$resource_str.'.xml';
        $identifier = 'T_'.$resource_str;
        $type = 'imsbasiclti_xmlv1p0';
        $this-> add_resource_item($module, $title, $type, $identifier, $file);
        return $file;
    }

    public function add_resource_item($module, $title=null, $type, $identifier, $file) {
        $identifier_ref = $identifier."_R";

        $xpath = new \DOMXpath($this);

        $new_item = $this->add_child_ns(CC::CC_1_2_CP, $module, 'item', null, array('identifier' => $identifier, "identifierref" => $identifier_ref));
        if ( $title != null ) {
            $new_title = $this->add_child_ns(CC::CC_1_2_CP, $new_item, 'title', $title);
        }

        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $identifier_ref = $identifier."_R";
        $new_resource = $this->add_child_ns(CC::CC_1_2_CP, $resources, 'resource', null, array('identifier' => $identifier_ref, "type" => $type));
        $new_file = $this->add_child_ns(CC::CC_1_2_CP, $new_resource, 'file', null, array("href" => $file));
        return $file;
    }

}
