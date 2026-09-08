<?php

namespace Tsugi\Util;

/**
 * This class allows us to produce an IMS Common Cartridge Version 1.2
 *
 * Version strings, namespaces, and resource types are centralized here so a
 * cartridge cannot advertise CC 1.2 in the manifest while emitting CC 1.1
 * QTI or web-link identifiers.
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

    const VERSION = '1.2.0';
    const SCHEMA_NAME = 'IMS Common Cartridge';

    const CC_NS =       'http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1';
    /** @deprecated Use CC_NS. Same URI; name kept for older callers. */
    const CC_1_1_CP =   self::CC_NS;
    const WL_NS =       'http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2';
    const BLTI_NS =     'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
    const TOPIC_NS =    'http://www.imsglobal.org/xsd/imsccv1p2/imsdt_v1p2';
    const LTICM_NS =    'http://www.imsglobal.org/xsd/imslticm_v1p0';
    const LTICP_NS =    'http://www.imsglobal.org/xsd/imslticp_v1p0';
    const LOM_NS =      'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource';
    const LOMIMSCC_NS = 'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest';

    const WEB_LINK_TYPE = 'imswl_xmlv1p2';
    const TOPIC_TYPE = 'imsdt_xmlv1p2';
    const LTI_TYPE = 'imsbasiclti_xmlv1p0';
    const ASSOCIATED_CONTENT_TYPE = 'associatedcontent/imscc_xmlv1p2/learning-application-resource';

    /** IMS CC 1.2 QTI 1.2.1 assessment resource type. */
    const QTI_ASSESSMENT_TYPE = 'imsqti_xmlv1p2/imscc_xmlv1p2/assessment';
    const QTI_NS = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2';
    const QTI_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_qtiasiv1p2p1_v1p0.xsd';
    const WL_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imswl_v1p2.xsd';
    const TOPIC_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/imsccv1p2/imsdt_v1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imsdt_v1p2.xsd';

    const metadata_xpath = '/*/*[1]';
    const item_xpath = '/*/*[2]/*/*';
    const resource_xpath = '/*/*[3]';
    const lom_general_xpath = '/*/*[1]/lomimscc:lom/lomimscc:general';

    public $resource_count = 0;

    public $last_type = false;
    public $last_file = false;
    public $last_identifier = false;
    public $last_identifierref = false;

    public $canvas_module_meta = null;
    public $canvas_modules = null;
    public $canvas_items = null;

    /**
     * When false, do not emit Canvas course_settings or assignment wrappers.
     * Default true so legacy /cc/export is unchanged.
     */
    public $canvas_extensions = true;

    /**
     * Setup Canvas export only: assessment_meta.xml, non_cc_assessments, dependency.
     * Off for Generic and Sakai. Legacy /cc/export does not set this.
     */
    public $canvas_quiz_wrapper = false;

    /**
     * Identifier generator for deterministic, hash-based identifiers
     * @var CCIdentifier
     */
    private $idGenerator = null;

    /**
     * Map of DOMNode to module path for tracking parent paths
     * @var array
     */
    private $modulePaths = array();

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="cctd0015" xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imscp_v1p2_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lommanifest_v1p0.xsd http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd">
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
        $this->delete_children_ns(self::CC_NS, $res);
        $items = $xpath->query(self::item_xpath)->item(0);
        $this->delete_children_ns(self::CC_NS, $items);
        $lom = $xpath->query(self::lom_general_xpath)->item(0);
        $this->delete_children_ns(self::LOMIMSCC_NS, $lom);

        // Optionally create a DOM that can be used for the
        // course_settings/module_meta.xml
        // Canvas extension to CC. Setup "generic" turns this off.
        $this->canvas_module_meta = new CanvasModuleMeta();

        // Initialize identifier generator for deterministic IDs
        $this->idGenerator = new CCIdentifier();
    }

    /**
     * Drop Canvas-only extensions so the cartridge is spec CC only.
     *
     * Used by Setup Generic export. Do not call from legacy cc/export.php.
     */
    public function disable_canvas_extensions() {
        $this->canvas_extensions = false;
        $this->canvas_quiz_wrapper = false;
        $this->canvas_module_meta = null;
        $this->canvas_modules = null;
        $this->canvas_items = null;
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
     * @param $parentPath Optional parent path for deterministic ID generation (e.g., "")
     *
     * @return the DOMNode of the newly added module
     */
    public function add_module($title, $parentPath = '') {
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('module', $title, $parentPath);
        
        // Store module path for later lookups
        $modulePath = $parentPath ? $parentPath . '|' . $title : $title;

        $xpath = new \DOMXpath($this);

        $items = $xpath->query(CC::item_xpath)->item(0);
        $module = $this->add_child_ns(CC::CC_NS, $items, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns(CC::CC_NS, $module, 'title', $title);
        
        // Store path for this module node
        $this->modulePaths[spl_object_hash($module)] = $modulePath;

        if ( $this->canvas_module_meta ) {
            $this->canvas_modules = $this->canvas_module_meta->add_module($title, $this->last_identifier);
            $this->canvas_items = $this->canvas_module_meta->add_items($this->canvas_modules);
        }

        return $module;
    }

    /**
     * Adds a sub module to a module
     *
     * As a note, while some LMS's are happpy with deeply nested
     * sub-module trees, other LMS's prefer a strict two-layer
     * module / submodule structure.
     *
     * @param $module DOMNode The module where we are adding the submodule
     * @param $title The title of the sub module
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return the DOMNode of the newly added sub module
     */
    public function add_sub_module($module, $title, $parentPath = null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('submodule', $title, $parentPath);
        
        // Store module path for later lookups
        $modulePath = $parentPath ? $parentPath . '|' . $title : $title;
        $sub_module = $this->add_child_ns(CC::CC_NS, $module, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns(CC::CC_NS, $sub_module, 'title',$title);
        
        // Store path for this submodule node
        $this->modulePaths[spl_object_hash($sub_module)] = $modulePath;
        
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
     * @param $url Optional URL for deterministic ID generation
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    public function add_web_link($module, $title=null, $url=null, $parentPath=null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $additionalProps = array();
        if ($url !== null) {
            $additionalProps['url'] = $url;
        }
        $this->last_identifier = $this->idGenerator->makeIdentifier('weblink', $title ?: '', $parentPath, $additionalProps);
        
        // Generate file name based on identifier (use last part of hash)
        $fileHash = substr($this->last_identifier, strpos($this->last_identifier, '_') + 1);
        $file = 'xml/WL_'.$fileHash.'.xml';
        
        $type = self::WEB_LINK_TYPE;
        $this-> add_resource_item($module, $title, $type, $this->last_identifier, $file);
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
     * @param $text Optional text content for deterministic ID generation
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    public function add_topic($module, $title=null, $text=null, $parentPath=null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $additionalProps = array();
        if ($text !== null) {
            $additionalProps['text'] = $text;
        }
        $this->last_identifier = $this->idGenerator->makeIdentifier('topic', $title ?: '', $parentPath, $additionalProps);
        
        // Generate file name based on identifier (use last part of hash)
        $fileHash = substr($this->last_identifier, strpos($this->last_identifier, '_') + 1);
        $file = 'xml/TO_'.$fileHash.'.xml';
        
        $type = self::TOPIC_TYPE;
        $this-> add_resource_item($module, $title, $type, $this->last_identifier, $file);
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
     * @param $url Optional URL/endpoint for deterministic ID generation
     * @param $resourceLinkId Optional resource_link_id for deterministic ID generation
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the lti link XML in the ZIP.
     */
    public function add_lti_link($module, $title=null, $url=null, $resourceLinkId=null, $parentPath=null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $additionalProps = array();
        if ($url !== null) {
            $additionalProps['url'] = $url;
        }
        if ($resourceLinkId !== null) {
            $additionalProps['resource_link_id'] = $resourceLinkId;
        }
        $this->last_identifier = $this->idGenerator->makeIdentifier('lti', $title ?: '', $parentPath, $additionalProps);
        
        // Generate file name based on identifier (use last part of hash)
        $fileHash = substr($this->last_identifier, strpos($this->last_identifier, '_') + 1);
        $file = 'xml/LT_'.$fileHash.'.xml';
        
        $type = self::LTI_TYPE;
        $this-> add_resource_item($module, $title, $type, $this->last_identifier, $file);
        return $file;
    }

    /**
     * Add a QTI 1.2.1 assessment resource item to the manifest.
     *
     * Place the assessment XML at the returned path in the ZIP.
     * {@see zip_add_qti_assessment_to_module()} combines both steps.
     *
     * @param \DOMNode $module
     * @param string|null $title
     * @param int|string|null $quizId Stable Quiz1 id for deterministic identifiers
     * @param string|null $parentPath
     * @return string Path of the QTI XML file inside the ZIP
     */
    public function add_qti_assessment($module, $title=null, $quizId=null, $parentPath=null) {
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }

        $additionalProps = array();
        if ($quizId !== null) {
            $additionalProps['quiz_id'] = (string) $quizId;
        }
        $this->last_identifier = $this->idGenerator->makeIdentifier('qti', $title ?: '', $parentPath, $additionalProps);

        $fileHash = substr($this->last_identifier, strpos($this->last_identifier, '_') + 1);
        $file = $this->canvas_quiz_wrapper
            ? $this->last_identifier.'/assessment_qti.xml'
            : 'xml/Q1_'.$fileHash.'.xml';

        $this->add_resource_item($module, $title, self::QTI_ASSESSMENT_TYPE, $this->last_identifier, $file);
        return $file;
    }

    /**
     * Add a QTI assessment to the module and write the XML into the ZIP.
     *
     * @param \ZipArchive $zip
     * @param \DOMNode $module
     * @param string $title
     * @param string $qtiXml UTF-8 QTI 1.2.1 assessment document
     * @param int|string|null $quizId
     * @param string|null $parentPath
     * @param \Tsugi\Services\Quiz1\Quiz|null $quiz For Canvas assessment_meta points/description
     * @return string
     */
    public function zip_add_qti_assessment_to_module($zip, $module, $title, $qtiXml, $quizId=null, $parentPath=null, $quiz=null) {
        $file = $this->add_qti_assessment($module, $title, $quizId, $parentPath);
        $this->zip_finish_qti_assessment($zip, $file, $title, $qtiXml, $quiz);
        return $file;
    }

    /**
     * Write QTI bytes, optional Canvas quiz wrapper, and module_meta item.
     */
    public function zip_finish_qti_assessment($zip, $file, $title, $qtiXml, $quiz=null) {
        $zip->addFromString($file, $qtiXml);

        if ( $this->canvas_quiz_wrapper ) {
            $this->zip_add_canvas_quiz_wrapper($zip, $title, $qtiXml, $quiz);
        }

        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_QuizzesQuiz);
            $w[CanvasModuleMeta::title] = $title;
            // Canvas ignores the CC organization when module_meta is present.
            // identifierref must be the quiz migration_id (folder / QTI assessment
            // ident), not the CC resource identifier (_R).
            $w[CanvasModuleMeta::identifierref] = $this->last_identifier;
            $w[CanvasModuleMeta::new_tab] = CanvasModuleMeta::new_tab_false;
            $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
        return $file;
    }

    /**
     * Canvas-only: non_cc_assessments + assessment_meta.xml + manifest dependency.
     */
    public function zip_add_canvas_quiz_wrapper($zip, $title, $qtiXml, $quiz=null) {
        $id = $this->last_identifier;
        $idref = $this->last_identifierref;
        $meta_id = $id.'_meta';
        $meta_path = $id.'/assessment_meta.xml';
        $non_cc = 'non_cc_assessments/'.$id.'.xml.qti';

        $zip->addFromString($non_cc, $qtiXml);
        $points = 0;
        $description = '';
        if ( $quiz instanceof \Tsugi\Services\Quiz1\Quiz ) {
            $points = $quiz->pointsPossible();
            $description = (string) $quiz->instructions;
        }
        $zip->addFromString($meta_path, CanvasAssessmentMeta::xml($id, $title, $description, $points, 1, $id));

        $resource = $this->resource_by_identifier($idref);
        if ( $resource ) {
            $this->add_child_ns(self::CC_NS, $resource, 'dependency', null, array('identifierref' => $meta_id));
        }

        $xpath = new \DOMXpath($this);
        $resources = $xpath->query(self::resource_xpath)->item(0);
        $lor = $this->add_child_ns(self::CC_NS, $resources, 'resource', null, array(
            'identifier' => $meta_id,
            'type' => self::ASSOCIATED_CONTENT_TYPE,
            'href' => $meta_path,
        ));
        $this->add_child_ns(self::CC_NS, $lor, 'file', null, array('href' => $id.'/assessment_qti.xml'));
        $this->add_child_ns(self::CC_NS, $lor, 'file', null, array('href' => $meta_path));
    }

    /**
     * @return \DOMElement|null
     */
    private function resource_by_identifier($identifier) {
        $xpath = new \DOMXpath($this);
        $nodes = $xpath->query('//*[local-name()="resource" and @identifier="'.$identifier.'"]');
        return $nodes && $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Add a resource to the manifest.
     * 
     * @return \DOMNode The created item node
     */
    public function add_resource_item($module, $title, $type, $identifier, $file) {
        $this->last_file = $file;
        $this->last_type = $type;
        $this->last_identifier = $identifier;
        $this->last_identifierref = $identifier."_R";

        $xpath = new \DOMXpath($this);

        $new_item = $this->add_child_ns(CC::CC_NS, $module, 'item', null,
            array('identifier' => $this->last_identifier, "identifierref" => $this->last_identifierref));
        if ( $title != null ) {
            $new_title = $this->add_child_ns(CC::CC_NS, $new_item, 'title', $title);
        }

        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $new_resource = $this->add_child_ns(CC::CC_NS, $resources, 'resource', null,
            array('identifier' => $this->last_identifierref, "type" => $type));
        $new_file = $this->add_child_ns(CC::CC_NS, $new_resource, 'file', null, array("href" => $file));

        return $new_item;
    }

    /**
     * Add a resource to the manifest without creating a module item.
     * Used for creating LTI resources that will be referenced by Canvas assignments.
     *
     * @param string $type The resource type
     * @param string $identifier The resource identifier
     * @param string $file The file path
     * @return string The identifierref (identifier + "_R")
     */
    public function add_resource_only($type, $identifier, $file) {
        $identifierref = $identifier."_R";
        $xpath = new \DOMXpath($this);
        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $new_resource = $this->add_child_ns(CC::CC_NS, $resources, 'resource', null,
            array('identifier' => $identifierref, "type" => $type));
        $new_file = $this->add_child_ns(CC::CC_NS, $new_resource, 'file', null, array("href" => $file));
        return $identifierref;
    }

    /*
     * Add a web link resource item and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     * @param $url The url for the link
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_url_to_module($zip, $module, $title, $url, $parentPath=null, $new_tab=true) {
        $file = $this->add_web_link($module, $title, $url, $parentPath);
        $web_dom = new CC_WebLink();
        $web_dom->set_title($title);
        $web_dom->set_url($url);
        $zip->addFromString($file,$web_dom->saveXML());

        // Add to the ever-growing canvas_module_meta
        // new_tab=false => Canvas opens ExternalUrl inline (iframe), useful for embed players
        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_ExternalUrl);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::url] = $url;
            $w[CanvasModuleMeta::identifierref] = $this->last_identifierref;
            $w[CanvasModuleMeta::new_tab] = $new_tab
                ? CanvasModuleMeta::new_tab_true
                : CanvasModuleMeta::new_tab_false;
            $item = $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
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
     * @param $resourceLinkId Optional resource_link_id for deterministic ID generation
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_lti_to_module($zip, $module, $title, $url, $custom=null, $extensions=null, $resourceLinkId=null, $parentPath=null) {
        $file = $this->add_lti_link($module, $title, $url, $resourceLinkId, $parentPath);
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

        // Add to the ever-growing canvas_module_meta
        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_ContextExternalTool);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::url] = $url;
            $w[CanvasModuleMeta::identifierref] = $this->last_identifierref;
            $w[CanvasModuleMeta::new_tab] = CanvasModuleMeta::new_tab_true;
            $item = $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
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
     * @param $resourceLinkId Optional resource_link_id for deterministic ID generation
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    /**
     * Generate Canvas assignment XML
     *
     * @param string $title The assignment title
     * @param string $ltiResourceId The identifier of the LTI resource this assignment references
     * @param string $launchUrl The actual launch URL for the LTI tool
     * @param float $pointsPossible Points possible (default 10.0)
     * @return string XML content
     */
    private function generate_canvas_assignment_xml($title, $ltiResourceId, $launchUrl, $pointsPossible = 10.0) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<assignment xmlns="http://canvas.instructure.com/xsd/cccv1p0">' . "\n";
        $xml .= '  <title>' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</title>' . "\n";
        $xml .= '  <points_possible>' . htmlspecialchars((string)$pointsPossible, ENT_XML1, 'UTF-8') . '</points_possible>' . "\n";
        $xml .= '  <grading_type>points</grading_type>' . "\n";
        $xml .= '  <submission_types>external_tool</submission_types>' . "\n";
        $xml .= '  <external_tool_tag_attributes>' . "\n";
        $xml .= '    <url>' . htmlspecialchars($launchUrl, ENT_XML1, 'UTF-8') . '</url>' . "\n";
        $xml .= '    <new_tab>false</new_tab>' . "\n";
        $xml .= '    <resource_link_id>' . htmlspecialchars($ltiResourceId, ENT_XML1, 'UTF-8') . '</resource_link_id>' . "\n";
        $xml .= '  </external_tool_tag_attributes>' . "\n";
        $xml .= '</assignment>';
        return $xml;
    }

    function zip_add_lti_outcome_to_module($zip, $module, $title, $url, $custom=null, $extensions=null, $resourceLinkId=null, $parentPath=null) {
        global $CFG;
        
        // Check if Canvas assignment wrapper extension is enabled (OFF by default)
        $canvas_assignments = $this->canvas_extensions
            && (isset($CFG) ? ($CFG->getExtension('canvas_assignment_extension') ?? false) : false);
        
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier for LTI resource
        $additionalProps = array();
        if ($url !== null) {
            $additionalProps['url'] = $url;
        }
        if ($resourceLinkId !== null) {
            $additionalProps['resource_link_id'] = $resourceLinkId;
        }
        $lti_identifier = $this->idGenerator->makeIdentifier('lti', $title ?: '', $parentPath, $additionalProps);
        
        // Generate file name for LTI resource
        $fileHash = substr($lti_identifier, strpos($lti_identifier, '_') + 1);
        $lti_file = 'xml/LT_'.$fileHash.'.xml';
        
        // Create LTI resource in manifest (without module item initially)
        $lti_resource_id = $this->add_resource_only(self::LTI_TYPE, $lti_identifier, $lti_file);
        
        // Generate and save LTI XML
        $lti_dom = new CC_LTI_Outcome();
        $lti_dom->set_title($title);
        $lti_dom->set_secure_launch_url($url);
        if ( $custom != null ) foreach($custom as $key => $value) {
            $lti_dom->set_custom($key,$value);
        }
        if ( $extensions != null ) foreach($extensions as $key => $value) {
            $lti_dom->set_extension($key,$value);
        }
        // Ensure Canvas outcome extension is set for assignments
        $lti_dom->set_canvas_extension('outcome', '10.0');
        $zip->addFromString($lti_file, $lti_dom->saveXML());

        if ( $canvas_assignments ) {
            // EXPERIMENTAL MODE: Generate Canvas assignment wrapper
            // Create Canvas assignment resource
            $assignment_identifier = $this->idGenerator->makeIdentifier('assignment', $title ?: '', $parentPath, array('lti_id' => $lti_identifier));
            $assignment_fileHash = substr($assignment_identifier, strpos($assignment_identifier, '_') + 1);
            $assignment_file = 'assignments/ASSIGNMENT_'.$assignment_fileHash.'.xml';
            
            // Create assignment resource and module item
            $assignment_item = $this->add_resource_item($module, $title, self::ASSOCIATED_CONTENT_TYPE, $assignment_identifier, $assignment_file);
            $assignment_resource_id = $assignment_identifier . "_R";
            
            // Canvas requires LTI resources to be referenced in the organizations tree or they get discarded
            // Add a nested item under the assignment item that references the LTI resource
            // This ensures Canvas keeps the LTI resource when importing
            $lti_item = $this->add_child_ns(CC::CC_NS, $assignment_item, 'item', null,
                array('identifier' => $lti_identifier, "identifierref" => $lti_resource_id));
            $hidden_title = $this->add_child_ns(CC::CC_NS, $lti_item, 'title', '(hidden)');
            
            // Generate and save Canvas assignment XML (references the LTI resource identifier)
            // Canvas expects resource_link_id to match the LTI resource identifier in the manifest
            // Use the actual launch URL instead of placeholder
            $assignment_xml = $this->generate_canvas_assignment_xml($title, $lti_resource_id, $url, 10.0);
            $zip->addFromString($assignment_file, $assignment_xml);

            // Add to the ever-growing canvas_module_meta
            if ( $this->canvas_items ) {
                $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_Assignment);
                $w[CanvasModuleMeta::title] = $title;
                $w[CanvasModuleMeta::url] = $url;
                $w[CanvasModuleMeta::identifierref] = $assignment_resource_id; // Reference assignment, not LTI
                $w[CanvasModuleMeta::new_tab] = CanvasModuleMeta::new_tab_true;
                $item = $this->canvas_module_meta->add_item($this->canvas_items, $assignment_identifier, $w);
            }
        } else {
            // DEFAULT MODE: Just create LTI resource with module item (no assignment wrapper)
            // Create module item pointing directly to the LTI resource
            $lti_item = $this->add_child_ns(CC::CC_NS, $module, 'item', null,
                array('identifier' => $lti_identifier, "identifierref" => $lti_resource_id));
            $lti_title = $this->add_child_ns(CC::CC_NS, $lti_item, 'title', $title);
            
            // Add to canvas_module_meta as LTI tool (not assignment)
            if ( $this->canvas_items ) {
                $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_ContextExternalTool);
                $w[CanvasModuleMeta::title] = $title;
                $w[CanvasModuleMeta::url] = $url;
                $w[CanvasModuleMeta::identifierref] = $lti_resource_id;
                $w[CanvasModuleMeta::new_tab] = CanvasModuleMeta::new_tab_true;
                $item = $this->canvas_module_meta->add_item($this->canvas_items, $lti_identifier, $w);
            }
        }
    }

    /**
     * Add a header item as a Canvas sub-header (no resource, just metadata)
     *
     * @param $module DOMNode The module or sub module where we are adding the header
     * @param $title The title/text of the header
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The DOMNode of the newly added header item
     */
    public function add_header_item($module, $title, $parentPath=null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('header', $title, $parentPath);

        // Add item to manifest without identifierref (Canvas sub-header)
        $header_item = $this->add_child_ns(CC::CC_NS, $module, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns(CC::CC_NS, $header_item, 'title', $title);

        // Add to Canvas module metadata as ContextModuleSubHeader
        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_ContextModuleSubHeader);
            $w[CanvasModuleMeta::title] = $title;
            // ContextModuleSubHeader does NOT have identifierref
            unset($w[CanvasModuleMeta::identifierref]);
            $item = $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }

        return $header_item;
    }

    /*
     * Add a topic item and create the file within the ZIP
     *
     * @param $zip The zip file handle that we are creating
     * @param $module DOMNode The module or sub module where we are adding the web link
     * @param $title The title of the link
     * @param $text The url for the link
     * @param $parentPath Optional parent path for deterministic ID generation (auto-detected if not provided)
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_topic_to_module($zip, $module, $title, $text, $parentPath=null) {
        $file = $this->add_topic($module, $title, $text, $parentPath);
        $web_dom = new CC_Topic();
        $web_dom->set_title($title);
        $web_dom->set_text($text);
        $zip->addFromString($file,$web_dom->saveXML());

        // Add to the ever-growing canvas_module_meta
        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_DiscussionTopic);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::identifierref] = $this->last_identifierref;
            $w[CanvasModuleMeta::new_tab] = CanvasModuleMeta::new_tab_false;
            $item = $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
    }

    /** Add the course_settings/module_meta.xml to the manifest and ZIP
     *
     * <resource identifier="g5d51089383699fa7bcf3f5c9b81c857d"
     *     type="associatedcontent/imscc_xmlv1p2/learning-application-resource"
     *      href="course_settings/canvas_export.txt">
     */
    function zip_add_canvas_module_meta($zip) {
        if ( ! $this->canvas_extensions || ! $this->canvas_module_meta ) {
            return;
        }

        $zip->addFromString('course_settings/canvas_export.txt',"Q: What did the panda say when he was forced out of his natural habitat?\nA: This is un-BEAR-able\n");

        $xpath = new \DOMXpath($this);

        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $new_resource = $this->add_child_ns(CC::CC_NS, $resources, 'resource', null,
            array(
                'identifier' => "g5d51089383699fa7bcf3f5c9b81c857d",
                "type" => self::ASSOCIATED_CONTENT_TYPE,
                "href" => "course_settings/canvas_export.txt"
            )
        );

        $new_file = $this->add_child_ns(CC::CC_NS, $new_resource, 'file', null, array("href" => "course_settings/canvas_export.txt"));
        $new_file = $this->add_child_ns(CC::CC_NS, $new_resource, 'file', null, array("href" => "course_settings/module_meta.xml"));

        $meta = $this->canvas_module_meta->prettyXML();
        $file = 'course_settings/module_meta.xml';
        $zip->addFromString($file,$meta);
    }
}
