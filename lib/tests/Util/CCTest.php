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
        $moduleId = $cc_dom->last_identifier;
        $this->assertStringStartsWith('M_', $moduleId, 'Module identifier should start with M_');

        $sub_module = $cc_dom->add_sub_module($module, 'Part 1');
        $subModuleId = $cc_dom->last_identifier;
        $this->assertStringStartsWith('S_', $subModuleId, 'Submodule identifier should start with S_');

        $file1 = $cc_dom->add_web_link($sub_module, 'Video: Introducting SQL');
        $webLinkId = $cc_dom->last_identifier;
        $webLinkIdRef = $cc_dom->last_identifierref;
        $this->assertStringStartsWith('WL_', $webLinkId, 'Weblink identifier should start with WL_');
        $this->assertEquals($webLinkId . '_R', $webLinkIdRef, 'identifierref should be identifier + _R');
        $this->assertEquals($file1, $cc_dom->last_file);

        $file2= $cc_dom->add_lti_link($sub_module, 'Autograder: Single Table SQL');
        $ltiLinkId = $cc_dom->last_identifier;
        $ltiLinkIdRef = $cc_dom->last_identifierref;
        $this->assertStringStartsWith('LT_', $ltiLinkId, 'LTI identifier should start with LT_');
        $this->assertEquals($ltiLinkId . '_R', $ltiLinkIdRef, 'identifierref should be identifier + _R');
        $this->assertEquals($file2, $cc_dom->last_file);

        $save = $cc_dom->saveXML();
        
        // Verify XML structure and content using assertions rather than exact string match
        // This allows for hash-based identifiers while still validating structure
        $this->assertStringContainsString('<lomimscc:string language="en-US">Web Applications for Everybody</lomimscc:string>', $save);
        $this->assertStringContainsString('<lomimscc:string language="en-US">Awesome MOOC to learn PHP, MySQL, and JavaScript.</lomimscc:string>', $save);
        $this->assertStringContainsString('<item identifier="' . $moduleId . '">', $save);
        $this->assertStringContainsString('<title>Week 1</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $subModuleId . '">', $save);
        $this->assertStringContainsString('<title>Part 1</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $webLinkId . '" identifierref="' . $webLinkIdRef . '">', $save);
        $this->assertStringContainsString('<title>Video: Introducting SQL</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $ltiLinkId . '" identifierref="' . $ltiLinkIdRef . '">', $save);
        $this->assertStringContainsString('<title>Autograder: Single Table SQL</title>', $save);
        $this->assertStringContainsString('<resource identifier="' . $webLinkIdRef . '" type="imswl_xmlv1p1">', $save);
        $this->assertStringContainsString('<resource identifier="' . $ltiLinkIdRef . '" type="imsbasiclti_xmlv1p0">', $save);
        $this->assertStringContainsString('<file href="' . $file1 . '"/>', $save);
        $this->assertStringContainsString('<file href="' . $file2 . '"/>', $save);

    }

    public function testZip() {
        global $CFG;
        
        // Enable Canvas assignment extension for this test
        // This is required for zip_add_lti_outcome_to_module to create assignment wrappers
        $originalCFG = isset($CFG) ? $CFG : null;
        
        // Create a mock CFG object with getExtension method
        $CFG = new class {
            public $extensions = array();
            
            public function getExtension($key, $default = null) {
                return $this->extensions[$key] ?? $default;
            }
        };
        $CFG->extensions['canvas_assignment_extension'] = true;

        $filename = tempnam(sys_get_temp_dir(), 'cc.zip');
        unlink($filename);
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            die("Cannot open $filename\n");
        }

        $cc_dom = new CC();
        $cc_dom->set_title('Web Applications for Everybody');
        $cc_dom->set_description('Awesome MOOC to learn PHP, MySQL, and JavaScript.');

        $module1 = $cc_dom->add_module('Week 1');
        $module1Id = $cc_dom->last_identifier;
        $this->assertStringStartsWith('M_', $module1Id, 'Module identifier should start with M_');

        $cc_dom->zip_add_url_to_module($zip, $module1,  'Video: Introducting SQL', 'https://www.youtube.com/12345');
        $webLinkId = $cc_dom->last_identifier;
        $webLinkIdRef = $cc_dom->last_identifierref;
        $this->assertStringStartsWith('WL_', $webLinkId, 'Weblink identifier should start with WL_');
        $this->assertEquals($webLinkId . '_R', $webLinkIdRef, 'identifierref should be identifier + _R');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/youtube/';
        $cc_dom->zip_add_lti_to_module($zip, $module1, 'Autograder: Single Table SQL', $endpoint, $custom_arr, $extensions);

        $ltiLinkId = $cc_dom->last_identifier;
        $ltiLinkIdRef = $cc_dom->last_identifierref;
        $this->assertStringStartsWith('LT_', $ltiLinkId, 'LTI identifier should start with LT_');
        $this->assertEquals($ltiLinkId . '_R', $ltiLinkIdRef, 'identifierref should be identifier + _R');

        $module2 = $cc_dom->add_module('Week 2');
        $module2Id = $cc_dom->last_identifier;
        $this->assertStringStartsWith('M_', $module2Id, 'Module identifier should start with M_');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/gift/';
        $cc_dom->zip_add_lti_outcome_to_module($zip, $module2, 'Quiz: Single Table SQL', $endpoint, $custom_arr, $extensions);

        // zip_add_lti_outcome_to_module creates both an LTI resource and an assignment resource.
        // The assignment is what gets added to the module, so last_identifier is the assignment identifier.
        $assignmentId = $cc_dom->last_identifier;
        $assignmentIdRef = $cc_dom->last_identifierref;
        $assignmentFile = $cc_dom->last_file; // File path like 'assignments/ASSIGNMENT_hash.xml'
        $this->assertStringStartsWith('ID_', $assignmentId, 'Assignment identifier should start with ID_ (assignment type uses default prefix)');
        $this->assertEquals($assignmentId . '_R', $assignmentIdRef, 'identifierref should be identifier + _R');

        $custom_arr = array();
        $extensions = array('apphome' => 'Apphome-value');
        $endpoint = 'https://www.py4e.com/mod/gift/';
        $cc_dom->zip_add_topic_to_module($zip, $module2, 'Discuss: Single Table SQL', 'Have a nice day.');

        $topicId = $cc_dom->last_identifier;
        $topicIdRef = $cc_dom->last_identifierref;
        $this->assertStringStartsWith('TO_', $topicId, 'Topic identifier should start with TO_');
        $this->assertEquals($topicId . '_R', $topicIdRef, 'identifierref should be identifier + _R');


        $save = $cc_dom->saveXML();
        
        // Verify XML structure and content using assertions rather than exact string match
        // This allows for hash-based identifiers while still validating structure
        $this->assertStringContainsString('<lomimscc:string language="en-US">Web Applications for Everybody</lomimscc:string>', $save);
        $this->assertStringContainsString('<lomimscc:string language="en-US">Awesome MOOC to learn PHP, MySQL, and JavaScript.</lomimscc:string>', $save);
        $this->assertStringContainsString('<item identifier="' . $module1Id . '">', $save);
        $this->assertStringContainsString('<title>Week 1</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $webLinkId . '" identifierref="' . $webLinkIdRef . '">', $save);
        $this->assertStringContainsString('<title>Video: Introducting SQL</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $ltiLinkId . '" identifierref="' . $ltiLinkIdRef . '">', $save);
        $this->assertStringContainsString('<title>Autograder: Single Table SQL</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $module2Id . '">', $save);
        $this->assertStringContainsString('<title>Week 2</title>', $save);
        $this->assertStringContainsString('<item identifier="' . $assignmentId . '" identifierref="' . $assignmentIdRef . '">', $save);
        $this->assertStringContainsString('<title>Quiz: Single Table SQL</title>', $save);
        
        // Canvas requires LTI resources to be referenced in the organizations tree
        // Verify that a nested item referencing the LTI resource exists under the assignment item
        // The nested item should have identifierref matching an LTI resource (LT_*_R pattern)
        // and title "(hidden)"
        $this->assertStringContainsString('(hidden)', $save, 'Should contain hidden LTI item title');
        
        // Verify the LTI resource exists in the manifest (created by zip_add_lti_outcome_to_module)
        // The LTI resource should have type "imsbasiclti_xmlv1p0" and identifier matching LT_*_R pattern
        $this->assertMatchesRegularExpression(
            '/<resource identifier="LT_[^"]+_R" type="imsbasiclti_xmlv1p0">/',
            $save,
            'Should contain LTI resource for the assignment'
        );
        
        // Verify nested item structure: assignment item should contain a nested item
        // The nested item should reference the LTI resource via identifierref
        // Pattern: <item identifier="ASSIGNMENT_ID" identifierref="ASSIGNMENT_ID_R">...<item identifier="LT_..." identifierref="LT_..._R">
        $assignmentItemPattern = '<item identifier="' . preg_quote($assignmentId, '/') . '"';
        $nestedLtiItemPattern = '<item[^>]*identifierref="LT_[^"]+_R"';
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($assignmentItemPattern, '/') . '[^>]*>.*?' . $nestedLtiItemPattern . '/s',
            $save,
            'Assignment item should contain nested LTI item with identifierref pointing to LTI resource'
        );
        
        $this->assertStringContainsString('<item identifier="' . $topicId . '" identifierref="' . $topicIdRef . '">', $save);
        $this->assertStringContainsString('<title>Discuss: Single Table SQL</title>', $save);
        $this->assertStringContainsString('<resource identifier="' . $webLinkIdRef . '" type="imswl_xmlv1p1">', $save);
        $this->assertStringContainsString('<resource identifier="' . $ltiLinkIdRef . '" type="imsbasiclti_xmlv1p0">', $save);
        // zip_add_lti_outcome_to_module creates an assignment resource (not directly an LTI resource)
        // The assignment resource type is 'associatedcontent/imscc_xmlv1p1/learning-application-resource'
        $this->assertStringContainsString('<resource identifier="' . $assignmentIdRef . '" type="associatedcontent/imscc_xmlv1p1/learning-application-resource">', $save);
        $this->assertStringContainsString('<resource identifier="' . $topicIdRef . '" type="imsdt_v1p1">', $save);

        // Test canvas compatibility
        $meta = $cc_dom->canvas_module_meta->prettyXML();
        
        // Verify Canvas XML structure using assertions rather than exact string match
        $this->assertStringContainsString('<module identifier="' . $module1Id . '">', $meta);
        $this->assertStringContainsString('<title>Week 1</title>', $meta);
        $this->assertStringContainsString('<item identifier="' . $webLinkId . '">', $meta);
        $this->assertStringContainsString('<content_type>ExternalUrl</content_type>', $meta);
        $this->assertStringContainsString('<title>Video: Introducting SQL</title>', $meta);
        $this->assertStringContainsString('<identifierref>' . $webLinkIdRef . '</identifierref>', $meta);
        $this->assertStringContainsString('<item identifier="' . $ltiLinkId . '">', $meta);
        $this->assertStringContainsString('<content_type>ContextExternalTool</content_type>', $meta);
        $this->assertStringContainsString('<title>Autograder: Single Table SQL</title>', $meta);
        $this->assertStringContainsString('<identifierref>' . $ltiLinkIdRef . '</identifierref>', $meta);
        $this->assertStringContainsString('<module identifier="' . $module2Id . '">', $meta);
        $this->assertStringContainsString('<title>Week 2</title>', $meta);
        $this->assertStringContainsString('<item identifier="' . $assignmentId . '">', $meta);
        $this->assertStringContainsString('<content_type>Assignment</content_type>', $meta);
        $this->assertStringContainsString('<title>Quiz: Single Table SQL</title>', $meta);
        // Canvas assignments use the assignment resource identifier (_R format)
        $this->assertStringContainsString('<identifierref>' . $assignmentIdRef . '</identifierref>', $meta);
        $this->assertStringContainsString('<item identifier="' . $topicId . '">', $meta);
        $this->assertStringContainsString('<content_type>DiscussionTopic</content_type>', $meta);
        $this->assertStringContainsString('<title>Discuss: Single Table SQL</title>', $meta);
        $this->assertStringContainsString('<identifierref>' . $topicIdRef . '</identifierref>', $meta);

        $file = 'course_settings/module_meta.xml';

        $zip->addFromString($file,$meta);

        $zip->addFromString('imsmanifest.xml',$cc_dom->saveXML());

        $zip->close();
        
        // Restore original CFG
        if ($originalCFG === null) {
            unset($CFG);
        } else {
            $CFG = $originalCFG;
        }

    }
}
