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
        $this->preserveWhiteSpace = false;
        $this->LoadXML($text);
        $this->formatOutput = true;
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
        if ( isset($entry->firstChild) ) {
            $entry->replaceChild($newText, $entry->firstChild); 
        } else {
            $entry->appendChild($newText);
        }
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

    public function get_element($ns, $tag) {
        if ( is_string($tag) ) {
            $list = $this->getElementsByTagNameNS($ns,$tag);
            $entry = $list->item(0);
        } else if ( $tag instanceof \DOMNodeList ) {
            $entry = $tag->item(0);
        } else if ( $tag instanceof \DOMNode ) {
            $entry = $tag;
        } else {
            die('Expecting string, DOMNodeList, or DOMNode');
        }
        return $entry;
    }

    public function delete_children_ns($ns, $tag) {
        $entry = $this->get_element($ns, $tag); 
        while ($entry->hasChildNodes()) {
            $entry->removeChild($entry->firstChild);
        }
        return $entry;
    }

    public function get_tag($tag, $key=null, $value=null) {
        return $this->get_tag_ns($this->currentNamespace, $tag, $key, $value);
    }

    public function get_tag_ns($ns, $tag, $key=null, $value=null) {
        $list = $this->getElementsByTagNameNS($ns,$tag);
        if ( $key === null ) {
            $entry = $list->item(0);
            return $entry;
        }
        for($i=0;$i<$list->length;$i++) {
            $attr = $list->item($i)->getAttribute($key);
            if ( $attr == $value ) {
                return $list->item($i);
            }
        }
        return null;
    }

    public function add_child($entry, $tag, $text=null, $attr=null) {
        return $this->add_child_ns($this->currentNamespace, $entry, $tag, $text, $attr);
    }

    public function add_child_ns($ns, $entry, $tag, $text=null, $attr=null) {
        if ( is_string($entry) ) {
            $entry = $this->get_tag($entry);
        }
        if ( $text != null && strlen($text) > 0 ) {
            $element = $this->createElementNS($ns, $tag, $text);
        } else {
            $element = $this->createElementNS($ns, $tag);
        }
        if ( $attr !== null ) foreach($attr as $key => $value ) {
            $element->setAttribute($key, $value);
        }
        $entry->appendChild($element);
        return $element;
    }

    public function dump_dom_list($node_list) {
        $retval = '';
        for($i=0; $i<$node_list->length;$i++) {
            $retval .= 'node('.$i . ")\n";
            $retval .= $this->saveXML($node_list->item($i));
        }
        return $retval;
    }

    public function dump_dom_node($tag) {
        return $this->saveXML($tag);
    }

    // http://stackoverflow.com/questions/6475394/php-xpath-query-on-xml-with-default-namespace-binding
    function dump_dom_levels($node, $level = 0) 
    {
        $class = get_class($node);
        if ($class == "DOMNodeList") {
            echo "Level $level ($class): $node->length items\n";
                foreach ($node as $child_node) {
                echo $level.':'.$child_node->getNodePath() . "\n";
                dump_dom_levels($child_node, $level+1);
            }
        } else {
            $nChildren = 0;
            foreach ($node->childNodes as $child_node) {
                if ($child_node->hasChildNodes()) {
                    $nChildren++;
                }
            }
            if ($nChildren) {
                echo "Level $level ($class): $nChildren children\n";
            }
            foreach ($node->childNodes as $child_node) {
                echo $level.':'.$child_node->getNodePath() . "\n";
                if ($child_node->hasChildNodes()) {
                    dump_dom_levels($child_node, $level+1);
                }
            }
        }
    }
}
