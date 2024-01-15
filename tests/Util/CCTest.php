<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CanvasModuleMeta.php";

use \Tsugi\Util\CC;

class CCTest extends \PHPUnit\Framework\TestCase
{
    public function testManifest() {

        $cc_dom = new CC();
        $cc_dom->set_title('Web Applications for Everybody');
        $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

        $module = $cc_dom->add_module('Week 1');
        $this->assertEquals($cc_dom->last_identifier,'T_000001');

        $sub_module = $cc_dom->add_sub_module($module, 'Part 1');
        $this->assertEquals($cc_dom->last_identifier,'T_000002');

        $file1 = $cc_dom->add_web_link($sub_module, 'Video: Introducting SQL');
        $this->assertEquals($cc_dom->last_identifier,'T_000003');
        $this->assertEquals($cc_dom->last_identifierref,'T_000003_R');
        $this->assertEquals($file1, $cc_dom->last_file);

        $file2= $cc_dom->add_lti_link($sub_module, 'Autograder: Single Table SQL');
        $this->assertEquals($cc_dom->last_identifier,'T_000004');
        $this->assertEquals($cc_dom->last_identifierref,'T_000004_R');
        $this->assertEquals($file2, $cc_dom->last_file);

$xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="cctd0015" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
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
    <resource identifier="T_000003_R" type="imswl_xmlv1p1">
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

    public function testZip() {

        $filename = tempnam(sys_get_temp_dir(), 'cc.zip');
        unlink($filename);
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            die("Cannot open $filename\n");
        }

        $cc_dom = new CC();
        $cc_dom->set_title('Web Applications for Everybody');
        $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

        $module = $cc_dom->add_module('Week 1');
        $this->assertEquals($cc_dom->last_identifier,'T_000001');

        $cc_dom->zip_add_url_to_module($zip, $module,  'Video: Introducting SQL', 'https://www.youtube.com/12345');
        $this->assertEquals($cc_dom->last_identifier,'T_000002');
        $this->assertEquals($cc_dom->last_identifierref,'T_000002_R');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/youtube/';
        $cc_dom->zip_add_lti_to_module($zip, $module, 'Autograder: Single Table SQL', $endpoint, $custom_arr, $extensions);

        $this->assertEquals($cc_dom->last_identifier,'T_000003');
        $this->assertEquals($cc_dom->last_identifierref,'T_000003_R');

        $module = $cc_dom->add_module('Week 2');
        $this->assertEquals($cc_dom->last_identifier,'T_000004');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/gift/';
        $cc_dom->zip_add_lti_outcome_to_module($zip, $module, 'Quiz: Single Table SQL', $endpoint, $custom_arr, $extensions);

        $this->assertEquals($cc_dom->last_identifier,'T_000005');
        $this->assertEquals($cc_dom->last_identifierref,'T_000005_R');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/gift/';
        $cc_dom->zip_add_topic_to_module($zip, $module, 'Discuss: Single Table SQL', 'Have a nice day.');

        $this->assertEquals($cc_dom->last_identifier,'T_000006');
        $this->assertEquals($cc_dom->last_identifierref,'T_000006_R');


$xmlout = '<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="cctd0015" xsi:schemaLocation="http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
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
          <item identifier="T_000002" identifierref="T_000002_R">
            <title>Video: Introducting SQL</title>
          </item>
          <item identifier="T_000003" identifierref="T_000003_R">
            <title>Autograder: Single Table SQL</title>
          </item>
        </item>
        <item identifier="T_000004">
          <title>Week 2</title>
          <item identifier="T_000005" identifierref="T_000005_R">
            <title>Quiz: Single Table SQL</title>
          </item>
          <item identifier="T_000006" identifierref="T_000006_R">
            <title>Discuss: Single Table SQL</title>
          </item>
        </item>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="T_000002_R" type="imswl_xmlv1p1">
      <file href="xml/WL_000002.xml"/>
    </resource>
    <resource identifier="T_000003_R" type="imsbasiclti_xmlv1p0">
      <file href="xml/LT_000003.xml"/>
    </resource>
    <resource identifier="T_000005_R" type="imsbasiclti_xmlv1p0">
      <file href="xml/LT_000005.xml"/>
    </resource>
    <resource identifier="T_000006_R" type="imsdt_v1p1">
      <file href="xml/TO_000006.xml"/>
    </resource>
  </resources>
</manifest>
';
        $save = $cc_dom->saveXML();
        // echo($save);
        $this->assertEquals($xmlout,$save);

$canvasOut = '<?xml version="1.0" encoding="UTF-8"?>
<modules xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 https://canvas.instructure.com/xsd/cccv1p0.xsd">
  <module identifier="T_000001">
    <title>Week 1</title>
    <workflow_state>unpublished</workflow_state>
    <position>1</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="T_000002">
        <position>1</position>
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
        <title>Video: Introducting SQL</title>
        <url>https://www.youtube.com/12345</url>
        <identifierref>T_000002_R</identifierref>
      </item>
      <item identifier="T_000003">
        <position>2</position>
        <content_type>ContextExternalTool</content_type>
        <workflow_state>unpublished</workflow_state>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
        <title>Autograder: Single Table SQL</title>
        <url>https://www.py4e.com/mod/youtube/</url>
        <identifierref>T_000003_R</identifierref>
      </item>
    </items>
  </module>
  <module identifier="T_000004">
    <title>Week 2</title>
    <workflow_state>unpublished</workflow_state>
    <position>2</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="T_000005">
        <position>1</position>
        <content_type>Assignment</content_type>
        <workflow_state>unpublished</workflow_state>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
        <title>Quiz: Single Table SQL</title>
        <url>https://www.py4e.com/mod/gift/</url>
        <identifierref>T_000005_R</identifierref>
      </item>
      <item identifier="T_000006">
        <position>2</position>
        <content_type>DiscussionTopic</content_type>
        <workflow_state>unpublished</workflow_state>
        <position>1</position>
        <new_tab>false</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
        <title>Discuss: Single Table SQL</title>
        <identifierref>T_000006_R</identifierref>
      </item>
    </items>
  </module>
</modules>
';
        // Test canvas compatibility
        $meta = $cc_dom->canvas_module_meta->prettyXML();
        // echo($meta);
        $this->assertEquals($meta,$canvasOut);

        $file = 'course_settings/module_meta.xml';

        $zip->addFromString($file,$meta);

        $zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

        $zip->close();


    }
}
