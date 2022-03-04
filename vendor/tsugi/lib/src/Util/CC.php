<?php

namespace Tsugi\Util;

/**
 * This class allows us to produce an IMS Common Cartridge Version 1.2
 *
 * Usage to Create a ZIP file:
 *
 *     $zip = new ZipArchive();
 *     if ($zip->open('cc.zip', ZipArchive::CREATE)!==TRUE) {
 *     $cc_dom = new CC();
 *     $cc_dom->set_title('Web Applications for Everybody');
 *     $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');
 *     $module = $cc_dom->add_module('Week 1');
 *     $sub_module = $cc_dom->add_sub_module($module, 'Part 1');
 *     $cc_dom->zip_add_url_to_module($zip, $sub_module, 'WA4E', 'https://www.wa4e.com');
 *     $custom = array('exercise' => 'http_headers.php');
 *     $cc_dom->zip_add_lti_to_module($zip, $sub_module, 'RRC',
 *         'https://www.wa4e.com/tools/autograder/index.php', $custom);
 *     $zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());
 *     $zip->close();
 */

class CC extends \Tsugi\Util\TsugiDOM {

    const CC_1_1_CP =   'http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1';
    const WL_NS =       'http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1';
    const BLTI_NS =     'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
    const TOPIC_NS =    'http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1';
    const LTICM_NS =    'http://www.imsglobal.org/xsd/imslticm_v1p0';
    const LTICP_NS =    'http://www.imsglobal.org/xsd/imslticp_v1p0';
    const LOM_NS =      'http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource';
    const LOMIMSCC_NS = 'http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest';

    const metadata_xpath = '/*/*[1]';
    const item_xpath = '/*/*[2]/*/*';
    const resource_xpath = '/*/*[3]';
    const lom_general_xpath = '/*/*[1]/lomimscc:lom/lomimscc:general';

    public $resource_count = 0;

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="cctd0015" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
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
    <resource identifier="T_00005_R" type="imswl_xmlv1p1">
      <file href="WebLink.xml"/>
    </resource>
  </resources>
</manifest>');

        $xpath = new \DOMXpath($this);
        $res = $xpath->query(self::resource_xpath)->item(0);
        $this->delete_children_ns(self::CC_1_1_CP, $res);
        $items = $xpath->query(self::item_xpath)->item(0);
        $this->delete_children_ns(self::CC_1_1_CP, $items);
        $lom = $xpath->query(self::lom_general_xpath)->item(0);
        $this->delete_children_ns(self::LOMIMSCC_NS, $lom);
    }

    /*
     * Set the title
     *
     * This function must be called or the resulting CC will not be compliant.
     * This function must only be called once.
     *
     * @param $title The title
     */
    public function set_title($title) {
        $xpath = new \DOMXpath($this);
        $general = $xpath->query(CC::lom_general_xpath)->item(0);
        $new_title = $this->add_child_ns(CC::LOMIMSCC_NS, $general, 'title');
        $new_string = $this->add_child_ns(CC::LOMIMSCC_NS, $new_title, 'string', $title, array("language" => "en-US"));
    }

    /*
     * Set the description
     *
     * @param $desc The new description
     *
     * This function must be called or the resulting CC will not be compliant
     * This function must only be called once.
     */
    public function set_description($desc) {
        $xpath = new \DOMXpath($this);
        $general = $xpath->query(CC::lom_general_xpath)->item(0);
        $new_description = $this->add_child_ns(CC::LOMIMSCC_NS, $general, 'description');
        $new_string = $this->add_child_ns(CC::LOMIMSCC_NS, $new_description, 'string', $desc, array("language" => "en-US"));
    }

    /**
     * Adds a module to the manifest
     *
     * @param $title The title of the module
     *
     * @return the DOMNode of the newly added module
     */
    public function add_module($title) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $identifier = 'T_'.$resource_str;

        $xpath = new \DOMXpath($this);

        $items = $xpath->query(CC::item_xpath)->item(0);
        $module = $this->add_child_ns(CC::CC_1_1_CP, $items, 'item', null, array('identifier' => $identifier));
        $new_title = $this->add_child_ns(CC::CC_1_1_CP, $module, 'title', $title);
        return $module;
    }

    /**
     * Adds a sub module to a module
     *
     * As a note, while some LMS's are happpy with deeply nested
     * sub-module trees, other LMS's prefre a strict two-layer
     * module / submodule structure.
     *
     * @param $sub_module DOMNode The module where we are adding the submodule
     * @param $title The title of the sub module
     *
     * @return the DOMNode of the newly added sub module
     */
    public function add_sub_module($module, $title) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $identifier = 'T_'.$resource_str;

        $sub_module = $this->add_child_ns(CC::CC_1_1_CP, $module, 'item', null, array('identifier' => $identifier));
        $new_title = $this->add_child_ns(CC::CC_1_1_CP, $sub_module, 'title',$title);
        return $sub_module;
    }

    /*
     * Add a web link resource item
     *
     * This adds the web link to the manifest,  to complete this when making a
     * zip file, you must generate and place the web link XML in the returned file
     * name within the ZIP.  The `zip_add_url_to_module()` combines these two steps.
     *
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    public function add_web_link($module, $title=null) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $file = 'xml/WL_'.$resource_str.'.xml';
        $identifier = 'T_'.$resource_str;
        $type = 'imswl_xmlv1p1';
        $this-> add_resource_item($module, $title, $type, $identifier, $file);
        return $file;
    }

    /*
     * Add a topic resource item
     *
     * This adds the topic to the manifest,  to complete this when making a
     * zip file, you must generate and place the web link XML in the returned file
     * name within the ZIP.  The `zip_add_topic_to_module()` combines these two steps.
     *
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    public function add_topic($module, $title=null) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $file = 'xml/TO_'.$resource_str.'.xml';
        $identifier = 'T_'.$resource_str;
        $type = 'imsdt_v1p1';
        $this-> add_resource_item($module, $title, $type, $identifier, $file);
        return $file;
    }

    /**
     * Add an LTI link resource item
     *
     * This adds an LTI link to the manifest, to complete this when making a
     * zip file, you must generate and place the LTI XML in the returned file
     * name within the ZIP.  The `zip_add_lti_to_module()` combines these two steps.
     *
     * @param $module DOMNode The module or sub module where we are adding the lti link
     * @param $title The title of the LTI link
     *
     * @return The name of a file to contain the lti link XML in the ZIP.
     */
    public function add_lti_link($module, $title=null) {
        $this->resource_count++;
        $resource_str = str_pad($this->resource_count.'',6,'0',STR_PAD_LEFT);
        $file = 'xml/LT_'.$resource_str.'.xml';
        $identifier = 'T_'.$resource_str;
        $type = 'imsbasiclti_xmlv1p0';
        $this-> add_resource_item($module, $title, $type, $identifier, $file);
        return $file;
    }

    /**
     * Add a resource to the manifest.
     */
    public function add_resource_item($module, $title, $type, $identifier, $file) {
        $identifier_ref = $identifier."_R";

        $xpath = new \DOMXpath($this);

        $new_item = $this->add_child_ns(CC::CC_1_1_CP, $module, 'item', null, array('identifier' => $identifier, "identifierref" => $identifier_ref));
        if ( $title != null ) {
            $new_title = $this->add_child_ns(CC::CC_1_1_CP, $new_item, 'title', $title);
        }

        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $identifier_ref = $identifier."_R";
        $new_resource = $this->add_child_ns(CC::CC_1_1_CP, $resources, 'resource', null, array('identifier' => $identifier_ref, "type" => $type));
        $new_file = $this->add_child_ns(CC::CC_1_1_CP, $new_resource, 'file', null, array("href" => $file));
        return $file;
    }

    /*
     * Add a web link resource item and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     * @param $url The url for the link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_url_to_module($zip, $module, $title, $url) {
        $file = $this->add_web_link($module, $title);
        $web_dom = new CC_WebLink();
        $web_dom->set_title($title);
        $web_dom->set_url($url, array("target" => "_iframe"));
        $zip->addFromString($file,$web_dom->saveXML());
    }

    /*
     * Add a LTI link resource item and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the LTI link
     * @param $title The title of the link
     * @param $url The url/endpoint for the link
     * @param $custom An optional array of custom parameters for this link
     * @param $extenions An optional array of tsugi extensions for this link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_lti_to_module($zip, $module, $title, $url, $custom=null, $extensions=null) {
        $file = $this->add_lti_link($module, $title);
        $lti_dom = new CC_LTI();
        $lti_dom->set_title($title);
        // $lti_dom->set_description('Create a single SQL table and insert some records.');
        $lti_dom->set_secure_launch_url($url);
        if ( $custom != null ) foreach($custom as $key => $value) {
            $lti_dom->set_custom($key,$value);
        }
        if ( $extensions != null ) foreach($extensions as $key => $value) {
            $lti_dom->set_extension($key,$value);
        }
        $zip->addFromString($file,$lti_dom->saveXML());
    }

    /*
     * Add a LTI link Outcome and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the LTI link
     * @param $title The title of the link
     * @param $url The url/endpoint for the link
     * @param $custom An optional array of custom parameters for this link
     * @param $extenions An optional array of tsugi extensions for this link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_lti_outcome_to_module($zip, $module, $title, $url, $custom=null, $extensions=null) {
        $file = $this->add_lti_link($module, $title);
        $lti_dom = new CC_LTI_Outcome();
        $lti_dom->set_title($title);
        // $lti_dom->set_description('Create a single SQL table and insert some records.');
        $lti_dom->set_secure_launch_url($url);
        if ( $custom != null ) foreach($custom as $key => $value) {
            $lti_dom->set_custom($key,$value);
        }
        if ( $extensions != null ) foreach($extensions as $key => $value) {
            $lti_dom->set_extension($key,$value);
        }
        $zip->addFromString($file,$lti_dom->saveXML());
    }

    /*
     * Add a topic item and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     * @param $text The url for the link
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_topic_to_module($zip, $module, $title, $text) {
        $file = $this->add_topic($module, $title);
        $web_dom = new CC_Topic();
        $web_dom->set_title($title);
        $web_dom->set_text($text);
        $zip->addFromString($file,$web_dom->saveXML());
    }


}
