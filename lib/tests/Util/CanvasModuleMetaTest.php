<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CanvasModuleMeta.php";

use \Tsugi\Util\CanvasModuleMeta;

class CanvasModuleMetaTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneral() {

        $canvas_modules = new CanvasModuleMeta();

        $identifier = 'identifier_12345';
        $modules = $canvas_modules->add_module('Week 1', $identifier);
        $items = $canvas_modules->add_items($modules);

        $identifier = '12345';
        $w = $canvas_modules->child_tags(CanvasModuleMeta::content_type_ExternalUrl);
        $w[CanvasModuleMeta::title] = 'Dr. Chuck Home';
        $w[CanvasModuleMeta::url] = 'https://www.dr-chuck.com/';
        $w[CanvasModuleMeta::identifierref] = 'g700b708061f61c7751d0b29228c7344c';
        $w[CanvasModuleMeta::new_tab] = null;

        $item = $canvas_modules->add_item($items, $identifier, $w);

        // $file1 = $canvas_modules->add_web_link($sub_module, 'Video: Introducting SQL');
        // $file2= $canvas_modules->add_lti_link($sub_module, 'Autograder: Single Table SQL');

$xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<modules xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 https://canvas.instructure.com/xsd/cccv1p0.xsd">
  <module identifier="identifier_12345">
    <title>Week 1</title>
    <workflow_state>unpublished</workflow_state>
    <position>1</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="12345">
        <position>1</position>
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <position>1</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
        <title>Dr. Chuck Home</title>
        <url>https://www.dr-chuck.com/</url>
        <identifierref>g700b708061f61c7751d0b29228c7344c</identifierref>
      </item>
    </items>
  </module>
</modules>
';
        $canvas_modules->formatOutput = true;
        $canvas_modules->preserveWhiteSpace = true;
        $pretty = $canvas_modules->prettyXML();
        // echo($outXML);
        $this->assertEquals($xmlout,$pretty);

    }
}
