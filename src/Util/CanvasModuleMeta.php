<?php

namespace Tsugi\Util;

/**
 * This class allows us to produce the Canvas course_settings/module_meta.xml extension in a CC
 */

class CanvasModuleMeta extends \Tsugi\Util\TsugiDOM {

    const CANVAS_CC_1_0 = 'http://canvas.instructure.com/xsd/cccv1p0';

    const content_type = 'content_type';
    const content_type_ExternalUrl = 'ExternalUrl';
    const content_type_WikiPage = 'WikiPage';
    const content_type_ContextExternalTool = 'ContextExternalTool';
    const content_type_ContextModuleSubHeader = 'ContextModuleSubHeader';
    const content_type_Assignment = 'Assignment';
    const content_type_DiscussionTopic = 'DiscussionTopic';

    const module = 'module';
    const item = 'item';
    const items = 'items';
    const title = 'title';
    const url = 'url';
    const new_tab = 'new_tab';
    const new_tab_true = 'true';
    const new_tab_false = 'false';
    const indent = 'indent';
    const link_settings_json = 'link_settings_json';
    const identifierref = 'identifierref';
    const identifier = 'identifier';
    const workflow_state = 'workflow_state';
    const workflow_state_active = 'active';
    const workflow_state_unpublished = 'unpublished';

    const position = 'position';
    const require_sequential_progress = 'require_sequential_progress';
    const locked = 'locked';

    const cc_to_canvas_map = array(
        'imswl_xmlv1p1' => self::content_type_ExternalUrl,
        'imsdt_v1p1' => self::content_type_DiscussionTopic,
        'imsbasiclti_xmlv1p0' => self::content_type_ContextExternalTool,
    );

    public $module_position = 0;
    public $item_position = 0;

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<modules xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 https://canvas.instructure.com/xsd/cccv1p0.xsd">
</modules>');
        $this->set_namespace(self::CANVAS_CC_1_0);
        $this->module_position = 0;
        $this->item_position = 0;
    }

    /**
     * Adds a module to the manifest
     *
     * @param $title The title of the module
     * @param $identifier The identifier of the module (from the imsmanifest)
     *
     * In the imsmanifest there are two levels of items - the top level is like
     * "Week 1", etc and the second level is "Reading 1" etc.   The identifier
     * on the module tag needs to be the same as the "Week 1" identifier.
     *
     * @return the DOMNode of the items tag within the newly added module
     */

    public function add_module($title, $identifier) {
        $root = $this->documentElement;
        $this->module_position++;
        $this->item_position = 0;

        $module = $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $root, self::module, null,
            array(self::identifier => $identifier));
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::title, $title);
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::workflow_state, self::workflow_state_unpublished);
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::position, $this->module_position);
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::require_sequential_progress, 'false');
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::locked, 'false');
        return $module;
    }

    /**
     * Add the items collection below the module.
     *
     * The items collections has no attributes and only <item> children
     */
    public function add_items($module) {
        $items = $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $module, self::items);
        return $items;
    }

    /**
     * Adds an item to the items in a module
     *
     * @param $items DOMNode The items collection
     * @param $title The title of the sub module
     * @param $children An aray of child text nodes under <item>
     *
     * In the imsmanifest there are two levels of items - the top level is like
     * "Week 1", etc and the second level is "Reading 1" etc.   The identifier
     * and identifierref need to be the same as the "Reading 1" identifier.
     *
     * @return the DOMNode of the newly added item
     */
    public function add_item($items, $identifier, $children=null) {
        $this->item_position++;
        $item = $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $items, self::item, null,
            array(self::identifier => $identifier));
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $item, self::position, $this->item_position);
        if ( is_array($children) ) {
            foreach($children as $key => $value) {
                $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $item, $key, $value);
            }
        }
        return $item;
    }

    /*
     * Return an array of the common child tags across item types in Canvas
     */
    public function child_tags($content_type) {
        return array(
            self::content_type => $content_type,
            self::workflow_state => self::workflow_state_unpublished,
            self::position => '1',
            self::new_tab => self::new_tab_true,
            self::indent => '0',
            self::link_settings_json => 'null'
        );
    }

    /*
     * Augment a array of child tags with the base tags expected
     *
     * @param $children An aray of child text nodes under <item>
     *
     * @return The augmented array
     */
    public function web_link_tags($children) {
        return array_merge(
            array(
                // "title" => 'New TAB',
                // "url" => 'https://www.dr-chuck.com/',
                // "identifierref" => 'g700b708061f61c7751d0b29228c7344c',
                "content_type" => self::Content_Type_ExternalUrl,
                "workflow_state" => 'active',
                "position" => '1',
                "new_tab" => 'true',
                "indent" => '0',
                "link_settings_json" => 'null'
            ), $children
        );
    }


    public function web_link_item($items, $identifier, $tags) {
        $item = $this->add_item($items, $identifier);
        $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, 'content_type', 'ExternalUrl');
        if ( is_array($tags) ) {
            foreach($tags as $key => $value) {
                $this->add_child_ns(CanvasModuleMeta::CANVAS_CC_1_0, $item, $key, $value);
            }
        }
    }

}

/*
 * export/course_settings/module_meta.xml

<?xml version="1.0" encoding="UTF-8"?>
<modules xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 https://canvas.instructure.com/xsd/cccv1p0.xsd">
  <module identifier="gface69d46377ea091ed34e422b29fd82">
    <title>Yada</title>
    <workflow_state>active</workflow_state>
    <position>1</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="g700b708061f61c7751d0b29228c7344c">
        <content_type>ExternalUrl</content_type>
        <workflow_state>active</workflow_state>
        <title>New TAB</title>
        <identifierref>g700b708061f61c7751d0b29228c7344c</identifierref>
        <url>https://www.dr-chuck.com/</url>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g5c7799d081148669774dd3ed928cc989">
        <content_type>WikiPage</content_type>
        <workflow_state>active</workflow_state>
        <title>Super Cool title</title>
        <identifierref>g88e7be25c0a192dc9d027b3c2a6d7820</identifierref>
        <position>2</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="geef4910ffff6de363f38fbb06e3c3d6a">
        <content_type>ExternalUrl</content_type>
        <workflow_state>active</workflow_state>
        <title>INLINE</title>
        <identifierref>geef4910ffff6de363f38fbb06e3c3d6a</identifierref>
        <url>https://www.dr-chuck.com/</url>
        <position>3</position>
        <new_tab>false</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g77b72d04e6b052a5db44ee12bd36b625">
        <content_type>ContextExternalTool</content_type>
        <workflow_state>active</workflow_state>
        <title>YouTube</title>
        <identifierref>g09e7de4991b935c537a6172ed0e286ff</identifierref>
        <url>https://www.dj4e.com/mod/youtube/</url>
        <position>4</position>
        <new_tab>false</new_tab>
        <indent>0</indent>
        <link_settings_json>{"selection_width":"","selection_height":""}</link_settings_json>
      </item>
    </items>
  </module>
  <module identifier="g14a300ff9b6c71132cd6c2a6fbb77cdc">
    <title>Week 2</title>
    <workflow_state>active</workflow_state>
    <position>2</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="g9350a40a2cb8125fffe2fcce95b790be">
        <content_type>ExternalUrl</content_type>
        <workflow_state>active</workflow_state>
        <title>PY4E</title>
        <identifierref>g9350a40a2cb8125fffe2fcce95b790be</identifierref>
        <url>http://www.py4e.com/</url>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="gf1b7a15076bb3d24ca40fd4b39a56a4d">
        <content_type>ContextModuleSubHeader</content_type>
        <workflow_state>active</workflow_state>
        <title>rtfrterterter</title>
        <position>2</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g9e4710332d6f4441b906c95e8160d7a6">
        <content_type>ExternalUrl</content_type>
        <workflow_state>active</workflow_state>
        <title>Chuck</title>
        <identifierref>g9e4710332d6f4441b906c95e8160d7a6</identifierref>
        <url>http://www.dr-chuck.com</url>
        <position>3</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
    </items>
  </module>
  <module identifier="g51c80f3cf670dd5b8e575aa22b40ab8f">
    <title>Installing Python</title>
    <workflow_state>active</workflow_state>
    <position>3</position>
    <locked>false</locked>
    <items>
      <item identifier="ga42525b8ed7ebdc30769de561ec8c129">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Assignment: Installing Python</title>
        <identifierref>ga42525b8ed7ebdc30769de561ec8c129</identifierref>
        <url>https://umsi.py4e.com/install.php</url>
        <position>1</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g6cc7ef687c45a3e2e7af3d1471742a0a">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Reference: Using Replit to write Python Programs</title>
        <identifierref>g6cc7ef687c45a3e2e7af3d1471742a0a</identifierref>
        <url>https://umsi.py4e.com/software-replit.php</url>
        <position>2</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="gff750c363069289c72dc0a1a97d99f58">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Reference: Setting up a Python Environment in Microsoft Windows</title>
        <identifierref>gff750c363069289c72dc0a1a97d99f58</identifierref>
        <url>https://umsi.py4e.com/software-win.php</url>
        <position>3</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g15c1f11c646fd0fd467bda3b9f2532aa">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Reference: Setting up a Python Environment in Macintosh</title>
        <identifierref>g15c1f11c646fd0fd467bda3b9f2532aa</identifierref>
        <url>https://umsi.py4e.com/software-mac.php</url>
        <position>4</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g93898e3d071d4b6934c3cbc6c459ea7d">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Reference: Recommended Programming Text Editors</title>
        <identifierref>g93898e3d071d4b6934c3cbc6c459ea7d</identifierref>
        <url>https://umsi.py4e.com/editors.php</url>
        <position>5</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="gc4b7a4d61aa669b102107679fc990957">
        <content_type>Assignment</content_type>
        <workflow_state>active</workflow_state>
        <title>Tool: Peer Graded: Installation Screen Shots</title>
        <identifierref>g759659702e2eebc72d494ab4e69489fc</identifierref>
        <position>6</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
    </items>
  </module>
  <module identifier="g03e899c38a63e4008fe4c839ef8c16cc">
    <title>Functions</title>
    <workflow_state>active</workflow_state>
    <position>4</position>
    <locked>false</locked>
    <items>
      <item identifier="g09d26a70679df97c9f49689a6aa6a9b1">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Video: Functions - Part 1</title>
        <identifierref>g09d26a70679df97c9f49689a6aa6a9b1</identifierref>
        <url>https://www.youtube.com/watch?v=5Kzw-0-DQAk</url>
        <position>1</position>
        <new_tab>true</new_tab>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="gfabb91063714fc26d8ac7e1b2a6c4da7">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Video: Functions - Part 2</title>
        <identifierref>gfabb91063714fc26d8ac7e1b2a6c4da7</identifierref>
        <url>https://www.youtube.com/watch?v=AJVNYRqn8kM</url>
        <position>2</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="gc294f0b4e303ea9f4a9905ed4bd037ff">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Video: Worked Exercise 4.6</title>
        <identifierref>gc294f0b4e303ea9f4a9905ed4bd037ff</identifierref>
        <url>https://www.youtube.com/watch?v=l93PhBUJ_S0</url>
        <position>3</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g33d477d73184d2bb119f5d4ba79e516b">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Slides: Pythonlearn-04-Functions.pptx</title>
        <identifierref>g33d477d73184d2bb119f5d4ba79e516b</identifierref>
        <url>https://umsi.py4e.com/lectures3/Pythonlearn-04-Functions.pptx</url>
        <position>4</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g8eb4475a47881eedc6eb36fe85350343">
        <content_type>ExternalUrl</content_type>
        <workflow_state>unpublished</workflow_state>
        <title>Reference: Chapter 4: Functions</title>
        <identifierref>g8eb4475a47881eedc6eb36fe85350343</identifierref>
        <url>https://umsi.py4e.com/html3/04-functions</url>
        <position>5</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g68ce875bb7e88973ff914657e9f49afd">
        <content_type>Assignment</content_type>
        <workflow_state>active</workflow_state>
        <title>Autograder: Exercise 4.6</title>
        <identifierref>g8d04fd2c384e1f233cfbeea541c0a3c5</identifierref>
        <position>6</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
      <item identifier="g485e808da320fdcd97070425d14ec0e7">
        <content_type>Assignment</content_type>
        <workflow_state>active</workflow_state>
        <title>Quiz: Functions</title>
        <identifierref>gc181961a3089961764d7def472922b41</identifierref>
        <position>7</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
    </items>
  </module>
  <module identifier="g562ac8fae166f85ab5989c5a427ee20d">
    <title>Discussion Module</title>
    <workflow_state>active</workflow_state>
    <position>5</position>
    <require_sequential_progress>false</require_sequential_progress>
    <locked>false</locked>
    <items>
      <item identifier="g98bc0cfbb38b715ea6f539f207c6faf4">
        <content_type>DiscussionTopic</content_type>
        <workflow_state>active</workflow_state>
        <title>Topic Title Was Here</title>
        <identifierref>gcc7b6878925aef57191069f1575e5b2a</identifierref>
        <position>1</position>
        <new_tab/>
        <indent>0</indent>
        <link_settings_json>null</link_settings_json>
      </item>
    </items>
  </module>
</modules>
 */
