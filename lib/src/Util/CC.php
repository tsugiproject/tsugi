<?php

namespace Tsugi\Util;

/**
 * This class allows us to produce an IMS Common Cartridge (1.2 by default, 1.1 for Moodle).
 *
 * Version strings, namespaces, and resource types are centralized here so a
 * cartridge cannot advertise one CC version in the manifest while emitting
 * another version's QTI or web-link identifiers. Pass PROFILE_11 for Moodle.
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
    const VERSION_11 = '1.1.0';
    const SCHEMA_NAME = 'IMS Common Cartridge';
    const PROFILE_11 = '1.1';
    const PROFILE_12 = '1.2';

    const CC_NS =       'http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1';
    const CC_11_NS =    'http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1';
    /** @deprecated Use CC_NS. Same URI; name kept for older callers. */
    const CC_1_1_CP =   self::CC_NS;
    const WL_NS =       'http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2';
    const WL_11_NS =    'http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1';
    const BLTI_NS =     'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
    const TOPIC_NS =    'http://www.imsglobal.org/xsd/imsccv1p2/imsdt_v1p2';
    const TOPIC_11_NS = 'http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1';
    const LTICM_NS =    'http://www.imsglobal.org/xsd/imslticm_v1p0';
    const LTICP_NS =    'http://www.imsglobal.org/xsd/imslticp_v1p0';
    const LOM_NS =      'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/resource';
    const LOM_11_NS =   'http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource';
    const LOMIMSCC_NS = 'http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest';
    const LOMIMSCC_11_NS = 'http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest';

    const WEB_LINK_TYPE = 'imswl_xmlv1p2';
    const WEB_LINK_TYPE_11 = 'imswl_xmlv1p1';
    const WEBCONTENT_TYPE = 'webcontent';
    const TOPIC_TYPE = 'imsdt_xmlv1p2';
    const TOPIC_TYPE_11 = 'imsdt_xmlv1p1';
    const LTI_TYPE = 'imsbasiclti_xmlv1p0';
    const ASSOCIATED_CONTENT_TYPE = 'associatedcontent/imscc_xmlv1p2/learning-application-resource';
    const ASSOCIATED_CONTENT_TYPE_11 = 'associatedcontent/imscc_xmlv1p1/learning-application-resource';

    /** IMS CC 1.2 QTI 1.2.1 assessment resource type. */
    const QTI_ASSESSMENT_TYPE = 'imsqti_xmlv1p2/imscc_xmlv1p2/assessment';
    const QTI_ASSESSMENT_TYPE_11 = 'imsqti_xmlv1p2/imscc_xmlv1p1/assessment';
    const QTI_NS = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2';
    const QTI_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_qtiasiv1p2p1_v1p0.xsd';
    const QTI_SCHEMA_LOCATION_11 = 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_qtiasiv1p2p1_v1p0.xsd';
    const WL_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imswl_v1p2.xsd';
    const WL_SCHEMA_LOCATION_11 = 'http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imswl_v1p1.xsd';
    const TOPIC_SCHEMA_LOCATION = 'http://www.imsglobal.org/xsd/imsccv1p2/imsdt_v1p2 http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imsdt_v1p2.xsd';
    const TOPIC_SCHEMA_LOCATION_11 = 'http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imsdt_v1p1.xsd';

    const metadata_xpath = '/*/*[1]';
    const item_xpath = '/*/*[2]/*/*';
    const resource_xpath = '/*/*[3]';
    const lom_general_xpath = '/*/*[1]/lomimscc:lom/lomimscc:general';

    /** LOM general.identifier catalog for an explicit Font Awesome icon class. */
    const LOM_CATALOG_ICON = 'tsugi.icon';
    /** LOM general.identifier catalog for web-link URL vs course-content. */
    const LOM_CATALOG_HREF_SOURCE = 'tsugi.href_source';
    /** LOM general.identifier catalog; LTI launch_presentation_document_target values. */
    const LOM_CATALOG_DOCUMENT_TARGET = 'tsugi.launch_presentation_document_target';

    public $resource_count = 0;

    public $last_type = false;
    public $last_file = false;
    public $last_identifier = false;
    public $last_identifierref = false;

    /**
     * Zip paths already used under web_resources/ in this cartridge.
     *
     * @var array<string, true>
     */
    private $webResourceNames = array();

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

    /**
     * CC profile for this document: PROFILE_11 or PROFILE_12.
     *
     * @var string
     */
    public $cc_profile = self::PROFILE_12;

    function __construct($profile = self::PROFILE_12) {
        $this->cc_profile = ($profile === self::PROFILE_11) ? self::PROFILE_11 : self::PROFILE_12;
        parent::__construct($this->manifestSkeleton());
        $xpath = new \DOMXpath($this);
        $res = $xpath->query(self::resource_xpath)->item(0);
        $this->delete_children_ns($this->ccNs(), $res);
        $items = $xpath->query(self::item_xpath)->item(0);
        $this->delete_children_ns($this->ccNs(), $items);
        $lom = $xpath->query(self::lom_general_xpath)->item(0);
        $this->delete_children_ns($this->lomImsccNs(), $lom);

        // Optionally create a DOM that can be used for the
        // course_settings/module_meta.xml
        // Canvas extension to CC. Setup "generic" / Moodle turns this off.
        $this->canvas_module_meta = new CanvasModuleMeta();

        // Initialize identifier generator for deterministic IDs
        $this->idGenerator = new CCIdentifier();
    }

    /**
     * CC 1.1 for Moodle and Generic 1.1 export; everyone else is CC 1.2.
     *
     * @param mixed $tsugi_lms
     * @return string
     */
    public static function profileForFlavor($tsugi_lms) {
        $lms = is_string($tsugi_lms) ? strtolower(trim($tsugi_lms)) : '';
        return ($lms === 'moodle' || $lms === 'generic11') ? self::PROFILE_11 : self::PROFILE_12;
    }

    public function isCc11() {
        return $this->cc_profile === self::PROFILE_11;
    }

    public function schemaVersion() {
        return $this->isCc11() ? self::VERSION_11 : self::VERSION;
    }

    public function ccNs() {
        return $this->isCc11() ? self::CC_11_NS : self::CC_NS;
    }

    public function lomImsccNs() {
        return $this->isCc11() ? self::LOMIMSCC_11_NS : self::LOMIMSCC_NS;
    }

    public function webLinkType() {
        return $this->isCc11() ? self::WEB_LINK_TYPE_11 : self::WEB_LINK_TYPE;
    }

    public function topicType() {
        return $this->isCc11() ? self::TOPIC_TYPE_11 : self::TOPIC_TYPE;
    }

    public function qtiAssessmentType() {
        return $this->isCc11() ? self::QTI_ASSESSMENT_TYPE_11 : self::QTI_ASSESSMENT_TYPE;
    }

    public function associatedContentType() {
        return $this->isCc11() ? self::ASSOCIATED_CONTENT_TYPE_11 : self::ASSOCIATED_CONTENT_TYPE;
    }

    public function webLinkNs() {
        return $this->isCc11() ? self::WL_11_NS : self::WL_NS;
    }

    public function webLinkSchemaLocation() {
        return $this->isCc11() ? self::WL_SCHEMA_LOCATION_11 : self::WL_SCHEMA_LOCATION;
    }

    public function topicNs() {
        return $this->isCc11() ? self::TOPIC_11_NS : self::TOPIC_NS;
    }

    public function topicSchemaLocation() {
        return $this->isCc11() ? self::TOPIC_SCHEMA_LOCATION_11 : self::TOPIC_SCHEMA_LOCATION;
    }

    public function qtiSchemaLocation() {
        return $this->isCc11() ? self::QTI_SCHEMA_LOCATION_11 : self::QTI_SCHEMA_LOCATION;
    }

    /**
     * Empty-ish manifest matching this profile (sample items are stripped in the constructor).
     *
     * @return string
     */
    private function manifestSkeleton() {
        $ccNs = $this->ccNs();
        $lomNs = $this->isCc11() ? self::LOM_11_NS : self::LOM_NS;
        $lomImsccNs = $this->lomImsccNs();
        $schemaVersion = $this->schemaVersion();
        $cpXsd = $this->isCc11()
            ? 'http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imscp_v1p2_v1p0.xsd'
            : 'http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imscp_v1p2_v1p0.xsd';
        $lomXsd = $this->isCc11()
            ? 'http://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lommanifest_v1p0.xsd'
            : 'http://www.imsglobal.org/profile/cc/ccv1p2/LOM/ccv1p2_lommanifest_v1p0.xsd';
        $wlType = $this->webLinkType();
        $schemaLocation = $ccNs.' '.$cpXsd.' '.$lomImsccNs.' '.$lomXsd
            .' http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd'
            .' http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd'
            .' http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd'
            .' http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0p1.xsd';
        return '<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="cctd0015" xmlns="'.$ccNs.'" xmlns:lom="'.$lomNs.'" xmlns:lomimscc="'.$lomImsccNs.'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="'.$schemaLocation.'">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>'.$schemaVersion.'</schemaversion>
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
    <resource identifier="T_00005_R" type="'.$wlType.'">
      <file href="WebLink.xml"/>
    </resource>
  </resources>
</manifest>';
    }

    /**
     * Drop Canvas-only extensions so the cartridge is spec CC only.
     *
     * Used by Setup Generic and Moodle export. Do not call from legacy cc/export.php.
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
        $new_title = $this->add_child_ns($this->lomImsccNs(), $general, 'title');
        $new_string = $this->add_child_ns($this->lomImsccNs(), $new_title, 'string', $title, array("language" => "en-US"));
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
        $new_description = $this->add_child_ns($this->lomImsccNs(), $general, 'description');
        $new_string = $this->add_child_ns($this->lomImsccNs(), $new_description, 'string', $desc, array("language" => "en-US"));
    }

    /**
     * Non-empty lesson/module description for organization-item LOM metadata.
     *
     * Accepts a string, or a lesson/module object/array with a description
     * property. Empty string and non-strings become null (skip metadata).
     *
     * @param mixed $source
     * @return string|null
     */
    public static function organizationDescription($source) {
        if ( is_object($source) && isset($source->description) ) {
            $source = $source->description;
        } else if ( is_array($source) && isset($source['description']) ) {
            $source = $source['description'];
        }
        if ( ! is_string($source) || $source === '' ) {
            return null;
        }
        return $source;
    }

    /**
     * Explicit Font Awesome icon class from a lesson/module, or null.
     *
     * @param mixed $source
     * @return string|null
     */
    public static function organizationIcon($source) {
        if ( is_object($source) && isset($source->icon) ) {
            $source = $source->icon;
        } else if ( is_array($source) && isset($source['icon']) ) {
            $source = $source['icon'];
        }
        if ( ! is_string($source) || $source === '' ) {
            return null;
        }
        if ( ! preg_match('/^fa-[a-z0-9-]+$/', $source) ) {
            return null;
        }
        return $source;
    }

    /**
     * Web-link href source: url or course, or null if unset/invalid.
     *
     * @param mixed $source
     * @return string|null
     */
    public static function organizationHrefSource($source) {
        if ( is_object($source) && isset($source->href_source) ) {
            $source = $source->href_source;
        } else if ( is_array($source) && isset($source['href_source']) ) {
            $source = $source['href_source'];
        }
        if ( $source === 'url' || $source === 'course' ) {
            return $source;
        }
        return null;
    }

    /**
     * Extra LOM identifier catalog/entry pairs from a lesson/module.
     *
     * @param mixed $source
     * @return array<string, string>
     */
    public static function lomIdentifiersFromLesson($source) {
        $pairs = array();
        $icon = self::organizationIcon($source);
        if ( $icon !== null ) {
            $pairs[self::LOM_CATALOG_ICON] = $icon;
        }
        $hrefSource = self::organizationHrefSource($source);
        if ( $hrefSource !== null ) {
            $pairs[self::LOM_CATALOG_HREF_SOURCE] = $hrefSource;
        }
        $documentTarget = self::documentTargetFromLesson($source);
        if ( $documentTarget !== null ) {
            $pairs[self::LOM_CATALOG_DOCUMENT_TARGET] = $documentTarget;
        }
        return $pairs;
    }

    /**
     * catalog => entry from this organization item's own LOM identifiers.
     *
     * @return array<string, string>
     */
    public static function lomIdentifiersFromItem(\DOMElement $item) {
        $metadata = null;
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'metadata' ) {
                $metadata = $child;
                break;
            }
        }
        if ( ! $metadata instanceof \DOMElement ) {
            return array();
        }
        $pairs = array();
        foreach ( $metadata->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'identifier' ) {
                continue;
            }
            $catalog = '';
            $entry = '';
            foreach ( $el->childNodes as $child ) {
                if ( ! $child instanceof \DOMElement ) {
                    continue;
                }
                if ( $child->localName === 'catalog' ) {
                    $catalog = trim($child->textContent);
                } else if ( $child->localName === 'entry' ) {
                    $entry = trim($child->textContent);
                }
            }
            if ( $catalog !== '' && $entry !== '' ) {
                $pairs[$catalog] = $entry;
            }
        }
        return $pairs;
    }

    /**
     * IMS webLink url/@windowTarget from a lesson/item target, or null to omit.
     *
     * Maps Tsugi/LTI values onto the CC 1.2 attribute: _blank, _self, modal.
     *
     * @param mixed $source lesson object/array, or a target string
     * @return string|null
     */
    public static function windowTargetFromLesson($source) {
        $target = self::lessonTargetValue($source);
        if ( $target === null ) {
            return null;
        }
        $t = strtolower($target);
        if ( $t === '_blank' || $t === 'window' || $t === 'blank' ) {
            return '_blank';
        }
        if ( $t === '_self' || $t === 'iframe' || $t === 'frame' || $t === 'self' ) {
            return '_self';
        }
        if ( $t === 'modal' ) {
            return 'modal';
        }
        if ( $t === '_parent' || $t === '_top' ) {
            return $t;
        }
        return null;
    }

    /**
     * LTI launch_presentation_document_target from a lesson/item, or null to omit.
     *
     * window / iframe / frame / modal (Tsugi).
     *
     * @param mixed $source lesson object/array, or a target string
     * @return string|null
     */
    public static function documentTargetFromLesson($source) {
        $target = self::lessonTargetValue($source);
        if ( $target === null ) {
            return null;
        }
        $t = strtolower($target);
        if ( $t === '_blank' || $t === 'window' || $t === 'blank' ) {
            return 'window';
        }
        if ( $t === '_self' || $t === 'iframe' || $t === 'self' ) {
            return 'iframe';
        }
        if ( $t === 'frame' ) {
            return 'frame';
        }
        if ( $t === 'modal' ) {
            return 'modal';
        }
        return null;
    }

    /**
     * Tsugi lesson target from an LTI document_target or IMS windowTarget, or null.
     *
     * @param mixed $documentTarget
     * @return string|null
     */
    public static function lessonTargetFromDocumentTarget($documentTarget) {
        return self::windowTargetFromLesson($documentTarget);
    }

    /**
     * Tsugi lesson target from an IMS webLink windowTarget, or null.
     *
     * @param mixed $windowTarget
     * @return string|null
     */
    public static function lessonTargetFromWindowTarget($windowTarget) {
        return self::windowTargetFromLesson($windowTarget);
    }

    /**
     * Canvas module_meta new_tab from windowTarget, else $default.
     *
     * @param string|null $windowTarget
     * @param bool $default
     * @return bool
     */
    public static function canvasNewTabForWindowTarget($windowTarget, $default = true) {
        if ( $windowTarget === '_blank' ) {
            return true;
        }
        if ( $windowTarget === '_self' || $windowTarget === 'modal' ) {
            return false;
        }
        return (bool) $default;
    }

    /**
     * @param mixed $source
     * @return string|null
     */
    private static function lessonTargetValue($source) {
        if ( is_object($source) && isset($source->target) ) {
            $source = $source->target;
        } else if ( is_array($source) && isset($source['target']) ) {
            $source = $source['target'];
        }
        if ( ! is_string($source) || $source === '' ) {
            return null;
        }
        return $source;
    }

    /**
     * LOM general.description text from an organization item, or null.
     *
     * Reads only this item's own metadata (not nested items). Does not trim
     * or rewrite the recovered string, so HTML descriptions round-trip.
     *
     * @return string|null
     */
    public static function lomDescriptionFromItem(\DOMElement $item) {
        $metadata = null;
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'metadata' ) {
                $metadata = $child;
                break;
            }
        }
        if ( ! $metadata instanceof \DOMElement ) {
            return null;
        }
        foreach ( $metadata->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'description' ) {
                continue;
            }
            foreach ( $el->childNodes as $child ) {
                if ( $child instanceof \DOMElement && $child->localName === 'string' ) {
                    return $child->textContent;
                }
            }
            return $el->textContent;
        }
        return null;
    }

    /**
     * Attach CC 1.2 LOM to an organization item (description plus Tsugi identifiers).
     *
     * Identifiers use standard lomimscc catalog/entry (repeatable). No-op for
     * CC 1.1, a missing item, or when there is nothing to emit. Inserts
     * metadata after title and before nested items.
     *
     * @param \DOMElement|null $item
     * @param mixed $description string, or lesson/module object/array
     * @return bool true when metadata was emitted
     */
    public function add_item_lom_description($item, $description) {
        if ( $this->isCc11() ) {
            return false;
        }
        if ( ! $item instanceof \DOMElement ) {
            return false;
        }
        $text = self::organizationDescription($description);
        $pairs = self::lomIdentifiersFromLesson($description);
        if ( $text === null && count($pairs) < 1 ) {
            return false;
        }

        $lomNs = $this->lomImsccNs();
        $metadata = $this->createElementNS($this->ccNs(), 'metadata');
        $lom = $this->createElementNS($lomNs, 'lomimscc:lom');
        $metadata->appendChild($lom);
        $general = $this->createElementNS($lomNs, 'lomimscc:general');
        $lom->appendChild($general);
        foreach ( $pairs as $catalog => $entry ) {
            $idEl = $this->createElementNS($lomNs, 'lomimscc:identifier');
            $general->appendChild($idEl);
            $catEl = $this->createElementNS($lomNs, 'lomimscc:catalog');
            $catEl->appendChild($this->createTextNode($catalog));
            $idEl->appendChild($catEl);
            $entryEl = $this->createElementNS($lomNs, 'lomimscc:entry');
            $entryEl->appendChild($this->createTextNode($entry));
            $idEl->appendChild($entryEl);
        }
        if ( $text !== null ) {
            $descEl = $this->createElementNS($lomNs, 'lomimscc:description');
            $general->appendChild($descEl);
            $string = $this->createElementNS($lomNs, 'lomimscc:string');
            $string->setAttribute('language', 'en-US');
            $string->appendChild($this->createTextNode($text));
            $descEl->appendChild($string);
        }

        $afterTitle = null;
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'title' ) {
                $afterTitle = $child->nextSibling;
                break;
            }
        }
        if ( $afterTitle ) {
            $item->insertBefore($metadata, $afterTitle);
        } else {
            $item->appendChild($metadata);
        }
        return true;
    }

    /**
     * Attach LOM to the most recently added organization item.
     *
     * @param mixed $source
     * @return bool
     */
    public function add_last_item_lom($source) {
        return $this->add_item_lom_description(
            $this->organizationItemByIdentifier($this->last_identifier),
            $source
        );
    }

    /**
     * Adds a module to the manifest
     *
     * @param $title The title of the module
     * @param $parentPath Optional parent path for deterministic ID generation (e.g., "")
     * @param mixed $description Optional lesson description (CC 1.2 LOM on the item)
     *
     * @return the DOMNode of the newly added module
     */
    public function add_module($title, $parentPath = '', $description = null) {
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('module', $title, $parentPath);
        
        // Store module path for later lookups
        $modulePath = $parentPath ? $parentPath . '|' . $title : $title;

        $xpath = new \DOMXpath($this);

        $items = $xpath->query(CC::item_xpath)->item(0);
        $module = $this->add_child_ns($this->ccNs(), $items, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns($this->ccNs(), $module, 'title', $title);
        $this->add_item_lom_description($module, $description);
        
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
     * @param mixed $description Optional lesson description (CC 1.2 LOM on the item)
     *
     * @return the DOMNode of the newly added sub module
     */
    public function add_sub_module($module, $title, $parentPath = null, $description = null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('submodule', $title, $parentPath);
        
        // Store module path for later lookups
        $modulePath = $parentPath ? $parentPath . '|' . $title : $title;
        $sub_module = $this->add_child_ns($this->ccNs(), $module, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns($this->ccNs(), $sub_module, 'title',$title);
        $this->add_item_lom_description($sub_module, $description);
        
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
        
        $type = $this->webLinkType();
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
        
        $type = $this->topicType();
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

        $this->add_resource_item($module, $title, $this->qtiAssessmentType(), $this->last_identifier, $file);
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
     * @param \Tsugi\Services\Quiz1\Quiz1|null $quiz For Canvas assessment_meta points/description
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
     * Canvas-only: assessment_meta.xml, a same-bytes non_cc_assessments copy,
     * and the manifest dependency. Canvas selective import discovers the quiz
     * from the non_cc file on the metadata resource.
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
        if ( $quiz instanceof \Tsugi\Services\Quiz1\Quiz1 ) {
            $points = $quiz->pointsPossible();
            $description = (string) $quiz->instructions;
        }
        $zip->addFromString($meta_path, CanvasAssessmentMeta::xml($id, $title, $description, $points, 1, $id));

        $resource = $this->resource_by_identifier($idref);
        if ( $resource ) {
            $this->add_child_ns($this->ccNs(), $resource, 'dependency', null, array('identifierref' => $meta_id));
        }

        $xpath = new \DOMXpath($this);
        $resources = $xpath->query(self::resource_xpath)->item(0);
        $lor = $this->add_child_ns($this->ccNs(), $resources, 'resource', null, array(
            'identifier' => $meta_id,
            'type' => $this->associatedContentType(),
            'href' => $meta_path,
        ));
        $this->add_child_ns($this->ccNs(), $lor, 'file', null, array('href' => $id.'/assessment_qti.xml'));
        $this->add_child_ns($this->ccNs(), $lor, 'file', null, array('href' => $meta_path));
        $this->add_child_ns($this->ccNs(), $lor, 'file', null, array('href' => $non_cc));
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
     * @param string|null $resourceHref Optional href on the resource element (webcontent).
     * @return \DOMNode The created item node
     */
    public function add_resource_item($module, $title, $type, $identifier, $file, $resourceHref=null) {
        $this->last_file = $file;
        $this->last_type = $type;
        $this->last_identifier = $identifier;
        $this->last_identifierref = $identifier."_R";

        $xpath = new \DOMXpath($this);

        $new_item = $this->add_child_ns($this->ccNs(), $module, 'item', null,
            array('identifier' => $this->last_identifier, "identifierref" => $this->last_identifierref));
        if ( $title != null ) {
            $new_title = $this->add_child_ns($this->ccNs(), $new_item, 'title', $title);
        }

        $resources = $xpath->query(CC::resource_xpath)->item(0);
        $res_attrs = array('identifier' => $this->last_identifierref, "type" => $type);
        if ( is_string($resourceHref) && $resourceHref !== '' ) {
            $res_attrs['href'] = $resourceHref;
        }
        $new_resource = $this->add_child_ns($this->ccNs(), $resources, 'resource', null, $res_attrs);
        $new_file = $this->add_child_ns($this->ccNs(), $new_resource, 'file', null, array("href" => $file));

        return $new_item;
    }

    /**
     * Extra module item that points at an existing resource (same identifierref).
     * Used when Lessons lists the same file or page more than once.
     *
     * @param \DOMNode $module
     * @param string $title
     * @param string $itemIdentifier Unique item identifier (not the resource id)
     * @param string $identifierref Existing resource identifier
     * @param string|null $canvasContentType CanvasModuleMeta content_type_* or null
     * @return \DOMNode
     */
    public function add_identifierref_item($module, $title, $itemIdentifier, $identifierref, $canvasContentType=null) {
        $this->last_identifier = $itemIdentifier;
        $this->last_identifierref = $identifierref;
        $new_item = $this->add_child_ns($this->ccNs(), $module, 'item', null,
            array('identifier' => $itemIdentifier, 'identifierref' => $identifierref));
        if ( $title != null ) {
            $this->add_child_ns($this->ccNs(), $new_item, 'title', $title);
        }
        if ( $this->canvas_items && is_string($canvasContentType) && $canvasContentType !== '' ) {
            $w = $this->canvas_module_meta->child_tags($canvasContentType);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::identifierref] = $identifierref;
            $this->canvas_module_meta->add_item($this->canvas_items, $itemIdentifier, $w);
        }
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
        $new_resource = $this->add_child_ns($this->ccNs(), $resources, 'resource', null,
            array('identifier' => $identifierref, "type" => $type));
        $new_file = $this->add_child_ns($this->ccNs(), $new_resource, 'file', null, array("href" => $file));
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
     * @param bool $new_tab Canvas ExternalUrl new_tab when lesson has no target
     * @param mixed $lesson Optional lesson/item: windowTarget plus LOM description
     *
     * @return The name of a file to contain the web link XML in the ZIP.
     */
    function zip_add_url_to_module($zip, $module, $title, $url, $parentPath=null, $new_tab=true, $lesson=null) {
        $file = $this->add_web_link($module, $title, $url, $parentPath);
        $web_dom = new CC_WebLink($this);
        $web_dom->set_title($title);
        $windowTarget = self::windowTargetFromLesson($lesson);
        $attrs = array();
        if ( $windowTarget !== null ) {
            $attrs['windowTarget'] = $windowTarget;
            $new_tab = self::canvasNewTabForWindowTarget($windowTarget, $new_tab);
        }
        $web_dom->set_url($url, $attrs);
        $zip->addFromString($file,$web_dom->saveXML());

        $itemEl = $this->organizationItemByIdentifier($this->last_identifier);
        $this->add_item_lom_description($itemEl, $lesson);

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

    /**
     * Organization item with this identifier, or null.
     *
     * @param string|false $identifier
     * @return \DOMElement|null
     */
    private function organizationItemByIdentifier($identifier) {
        if ( ! is_string($identifier) || $identifier === '' ) {
            return null;
        }
        foreach ( $this->getElementsByTagName('*') as $el ) {
            if ( $el instanceof \DOMElement && $el->localName === 'item'
                && $el->getAttribute('identifier') === $identifier ) {
                return $el;
            }
        }
        return null;
    }

    /**
     * Add a webcontent file to the module and store the bytes in the ZIP.
     *
     * @param \ZipArchive $zip
     * @param \DOMNode $module
     * @param string $title
     * @param string $filename Display / zip basename
     * @param string $bytes File contents
     * @param string|null $parentPath
     * @param string|null $stableKey SHA-256 or other stable identity
     * @return string Path of the file inside the ZIP
     */
    function zip_add_file_to_module($zip, $module, $title, $filename, $bytes, $parentPath=null, $stableKey=null) {
        return $this->zip_add_file_at_path_to_module(
            $zip,
            $module,
            $title,
            $this->uniqueWebResourcePath($filename),
            $bytes,
            $stableKey
        );
    }

    /**
     * Reserve a unique web_resources/ path before writing bytes (page-to-page FILEBASE).
     *
     * @param string $filename
     * @return string
     */
    public function reserveWebResourcePath($filename) {
        return $this->uniqueWebResourcePath($filename);
    }

    /**
     * Add webcontent at an already-reserved zip path.
     *
     * @param \ZipArchive $zip
     * @param \DOMNode $module
     * @param string $title
     * @param string $zipPath
     * @param string $bytes
     * @param string|null $stableKey
     * @return string
     */
    function zip_add_file_at_path_to_module($zip, $module, $title, $zipPath, $bytes, $stableKey=null) {
        $this->webResourceNames[$zipPath] = true;
        $identity = is_string($stableKey) && $stableKey !== ''
            ? strtolower($stableKey)
            : (string) $zipPath;
        $additionalProps = array();
        if ( is_string($stableKey) && $stableKey !== '' ) {
            $additionalProps['sha256'] = strtolower($stableKey);
        }
        $this->last_identifier = $this->idGenerator->makeIdentifier('file', $identity, '', $additionalProps);
        $this->add_resource_item($module, $title, self::WEBCONTENT_TYPE, $this->last_identifier, $zipPath, $zipPath);
        $zip->addFromString($zipPath, $bytes);

        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_Attachment);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::identifierref] = $this->last_identifierref;
            $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
        return $zipPath;
    }

    /**
     * Another Lessons listing of a file already in the cartridge.
     * New module item, same resource identifierref — Canvas Files stays one blob.
     *
     * @param \DOMNode $module
     * @param string $title
     * @param string $stableKey SHA-256
     * @param int $listingIndex 2 for the second listing, 3 for the third, ...
     * @param string $identifierref Resource identifier from the first listing
     * @return \DOMNode
     */
    function zip_add_file_listing_to_module($module, $title, $stableKey, $listingIndex, $identifierref) {
        $identity = is_string($stableKey) && $stableKey !== ''
            ? strtolower($stableKey)
            : (string) $title;
        $itemId = $this->idGenerator->makeIdentifier('file', $identity, '', array(
            'listing' => (string) ((int) $listingIndex),
        ));
        return $this->add_identifierref_item(
            $module,
            $title,
            $itemId,
            $identifierref,
            CanvasModuleMeta::content_type_Attachment
        );
    }

    /**
     * Add a Canvas-style wiki page (wiki_content/*.html) as IMS CC webcontent.
     * Canvas reads the page name from the HTML <title>.
     *
     * @param \ZipArchive $zip
     * @param \DOMNode $module
     * @param string $title
     * @param string $logicalKey Zip basename without .html
     * @param string $html Full HTML document
     * @param string|null $parentPath
     * @return string Path of the file inside the ZIP
     */
    function zip_add_wiki_page_to_module($zip, $module, $title, $logicalKey, $html, $parentPath=null) {
        $key = is_string($logicalKey) ? trim($logicalKey) : '';
        $filename = $key !== '' ? $key.'.html' : 'page.html';
        $zipPath = $this->uniqueWikiContentPath($filename);
        $identity = $key !== '' ? $key : 'page';
        $this->last_identifier = CCIdentifier::wikiIdentifier($identity);
        $this->add_resource_item($module, $title, self::WEBCONTENT_TYPE, $this->last_identifier, $zipPath, $zipPath);
        $html = self::injectCanvasWikiMetas($html, $this->last_identifierref);
        $zip->addFromString($zipPath, $html);

        if ( $this->canvas_items ) {
            $w = $this->canvas_module_meta->child_tags(CanvasModuleMeta::content_type_WikiPage);
            $w[CanvasModuleMeta::title] = $title;
            $w[CanvasModuleMeta::identifierref] = $this->last_identifierref;
            $this->canvas_module_meta->add_item($this->canvas_items, $this->last_identifier, $w);
        }
        return $zipPath;
    }

    /**
     * Another Lessons listing of a wiki page already in the cartridge.
     *
     * @param \DOMNode $module
     * @param string $title
     * @param string $logicalKey
     * @param int $listingIndex
     * @param string $identifierref
     * @return \DOMNode
     */
    function zip_add_wiki_listing_to_module($module, $title, $logicalKey, $listingIndex, $identifierref) {
        $identity = is_string($logicalKey) && trim($logicalKey) !== '' ? trim($logicalKey) : 'page';
        $itemId = $this->idGenerator->makeIdentifier('wiki', $identity, '', array(
            'listing' => (string) ((int) $listingIndex),
        ));
        return $this->add_identifierref_item(
            $module,
            $title,
            $itemId,
            $identifierref,
            CanvasModuleMeta::content_type_WikiPage
        );
    }

    /**
     * Canvas binds a wiki page to its manifest resource through
     * <meta name="identifier">, which must equal the resource identifier.
     *
     * @param string $html
     * @param string $identifier Resource identifier (identifierref)
     * @return string
     */
    public static function injectCanvasWikiMetas($html, $identifier) {
        if ( ! is_string($html) || $html === '' || ! is_string($identifier) || $identifier === '' ) {
            return is_string($html) ? $html : '';
        }
        $html = preg_replace('/<meta\s+name=["\']identifier["\'][^>]*>\s*/i', '', $html);
        $html = preg_replace('/<meta\s+name=["\']editing_roles["\'][^>]*>\s*/i', '', $html);
        $html = preg_replace('/<meta\s+name=["\']workflow_state["\'][^>]*>\s*/i', '', $html);
        $id = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');
        $metas = '<meta name="identifier" content="'.$id.'"/>'
            .'<meta name="editing_roles" content="teachers"/>'
            .'<meta name="workflow_state" content="active"/>';
        if ( preg_match('/<\/title>/i', $html) ) {
            return (string) preg_replace('/<\/title>/i', '</title>'.$metas, $html, 1);
        }
        if ( preg_match('/<head\b[^>]*>/i', $html) ) {
            return (string) preg_replace('/<head\b[^>]*>/i', '$0'.$metas, $html, 1);
        }
        return $html;
    }

    /**
     * Safe unique path under web_resources/ for an in-cartridge file.
     *
     * @param string $filename
     */
    private function uniqueWebResourcePath($filename) {
        return $this->uniqueNamedPath('web_resources/', $filename, 'file.bin');
    }

    /**
     * Safe unique path under wiki_content/ for a Canvas wiki page.
     *
     * @param string $filename
     */
    private function uniqueWikiContentPath($filename) {
        return $this->uniqueNamedPath('wiki_content/', $filename, 'page.html');
    }

    /**
     * @param string $dir
     * @param string $filename
     * @param string $fallback
     */
    private function uniqueNamedPath($dir, $filename, $fallback) {
        $rel = str_replace('\\', '/', (string) $filename);
        $rel = ltrim($rel, '/');
        $parts = array();
        foreach ( explode('/', $rel) as $seg ) {
            $seg = trim($seg);
            if ( $seg === '' || $seg === '.' || $seg === '..' ) {
                continue;
            }
            $seg = preg_replace('/[<>:"|?*\x00-\x1F]+/', '_', $seg);
            $seg = trim($seg, ' .');
            if ( $seg === '' ) {
                continue;
            }
            $parts[] = $seg;
        }
        if ( ! $parts ) {
            $parts = array($fallback);
        }
        $base = implode('/', $parts);
        $dir = rtrim((string) $dir, '/').'/';
        $name = $base;
        $n = 1;
        while ( isset($this->webResourceNames[$dir.$name]) ) {
            $n++;
            $leaf = $parts[count($parts) - 1];
            $dot = strrpos($leaf, '.');
            if ( $dot === false || $dot === 0 ) {
                $parts[count($parts) - 1] = $leaf.'_'.$n;
            } else {
                $parts[count($parts) - 1] = substr($leaf, 0, $dot).'_'.$n.substr($leaf, $dot);
            }
            $name = implode('/', $parts);
        }
        $path = $dir.$name;
        $this->webResourceNames[$path] = true;
        return $path;
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
            $assignment_item = $this->add_resource_item($module, $title, $this->associatedContentType(), $assignment_identifier, $assignment_file);
            $assignment_resource_id = $assignment_identifier . "_R";
            
            // Canvas requires LTI resources to be referenced in the organizations tree or they get discarded
            // Add a nested item under the assignment item that references the LTI resource
            // This ensures Canvas keeps the LTI resource when importing
            $lti_item = $this->add_child_ns($this->ccNs(), $assignment_item, 'item', null,
                array('identifier' => $lti_identifier, "identifierref" => $lti_resource_id));
            $hidden_title = $this->add_child_ns($this->ccNs(), $lti_item, 'title', '(hidden)');
            
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
            $lti_item = $this->add_child_ns($this->ccNs(), $module, 'item', null,
                array('identifier' => $lti_identifier, "identifierref" => $lti_resource_id));
            $lti_title = $this->add_child_ns($this->ccNs(), $lti_item, 'title', $title);
            
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
     * @param mixed $description Optional heading description (CC 1.2 LOM)
     *
     * @return The DOMNode of the newly added header item
     */
    public function add_header_item($module, $title, $parentPath=null, $description=null) {
        // Get parent path if not provided
        if ($parentPath === null) {
            $moduleHash = spl_object_hash($module);
            $parentPath = isset($this->modulePaths[$moduleHash]) ? $this->modulePaths[$moduleHash] : '';
        }
        
        // Generate deterministic identifier
        $this->last_identifier = $this->idGenerator->makeIdentifier('header', $title, $parentPath);

        // Add item to manifest without identifierref (Canvas sub-header)
        $header_item = $this->add_child_ns($this->ccNs(), $module, 'item', null, array('identifier' => $this->last_identifier));
        $new_title = $this->add_child_ns($this->ccNs(), $header_item, 'title', $title);
        $this->add_item_lom_description($header_item, $description);

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
        $web_dom = new CC_Topic($this);
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
        $new_resource = $this->add_child_ns($this->ccNs(), $resources, 'resource', null,
            array(
                'identifier' => "g5d51089383699fa7bcf3f5c9b81c857d",
                "type" => $this->associatedContentType(),
                "href" => "course_settings/canvas_export.txt"
            )
        );

        $new_file = $this->add_child_ns($this->ccNs(), $new_resource, 'file', null, array("href" => "course_settings/canvas_export.txt"));
        $new_file = $this->add_child_ns($this->ccNs(), $new_resource, 'file', null, array("href" => "course_settings/module_meta.xml"));

        $meta = $this->canvas_module_meta->prettyXML();
        $file = 'course_settings/module_meta.xml';
        $zip->addFromString($file,$meta);
    }
}
