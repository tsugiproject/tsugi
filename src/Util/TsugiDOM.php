<?php

namespace Tsugi\Util;

/**
 * Wrap the DOMDocument class with some convienence methods
 *
 * Sample use:
 *
 *     $blti_ns = 'http://www.imsglobal.org/xsd/imsbasiclti_v1p0';
 *     $lti_dom = new TsugiDOM(file_get_contents ('cc/LTI.xml'));
 *     $lti_dom->set_namespace($blti_ns);
 *     $lti_dom->replace_text('title', 'New Title');
 *     $lti_dom->delete_tag('description');
 *     $lti_dom->delete_children('custom');
 *     $tag = $lti_dom->get_tag('custom');
 *     // $lti_dom->add_child($tag, 'property', 'SWEET!', array("e"=>"mc-squared"));
 *     $lti_dom->add_child('custom', 'property', 'SWEET!', array("e"=>"mc-squared"));
 *     echo $lti_dom->saveXML();
 */

class TsugiDOM extends \DOMDocument{

    public $currentNamespace = false;

    public function __construct($text) {
        parent::__construct();
        $this->LoadXML($text);
    } 

    public function set_namespace($new) {
        $this->currentNamespace = $new;
    }

    public function replace_text($tag, $text) {
        $this->replace_text_ns($this->currentNamespace, $tag, $text);
    }

    public function replace_text_ns($ns, $tag, $text) {
        $list = $this->getElementsByTagNameNS($ns,$tag);
        $entry = $list->item(0);
        $newText = new \DOMText($text);
        $entry->replaceChild($newText, $entry->firstChild); 
    }

    public function delete_tag($tag) {
        $this->delete_tag_ns($this->currentNamespace, $tag);
    }

    public function delete_tag_ns($ns, $tag) {
        $list = $this->getElementsByTagNameNS($ns,$tag);
        $entry = $list->item(0);
        $entry->parentNode->removeChild($entry); 
    }

    public function delete_children($tag) {
        return $this->delete_children_ns($this->currentNamespace,$tag);
    }

    public function delete_children_ns($ns, $tag) {
        $list = $this->getElementsByTagNameNS($ns,$tag);
        $entry = $list->item(0);
        while ($entry->hasChildNodes()) {
            $entry->removeChild($entry->firstChild);
        }
        return $entry;
    }

    public function get_tag($tag) {
        return $this->get_tag_ns($this->currentNamespace, $tag);
    }

    public function get_tag_ns($ns, $tag) {
        $list = $this->getElementsByTagNameNS($ns,$tag);
        $entry = $list->item(0);
        return $entry;
    }

    public function add_child($entry, $tag, $text, $attr=null) {
        $this->add_child_ns($this->currentNamespace, $entry, $tag, $text, $attr);
    }

    public function add_child_ns($ns, $entry, $tag, $text, $attr=null) {
        if ( is_string($entry) ) {
            $entry = $this->get_tag($entry);
        }
        $element = $this->createElementNS($ns, $tag, $text);
        if ( $attr !== false ) foreach($attr as $key => $value ) {
            $element->setAttribute($key, $value);
        }
        $entry->appendChild($element);
    }
}
