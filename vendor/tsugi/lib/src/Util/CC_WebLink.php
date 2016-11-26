<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_WebLink extends \Tsugi\Util\TsugiDOM {

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2 
  http://www.imsglobal.org/profile/cc/ccv1p2/ccv1p2_imswl_v1p2.xsd">
  <title>Wikipedia - Psychology</title>
  <url href="http://en.wikipedia.org/wiki/Psychology" target="_iframe"/>
</webLink>');

        $this->set_namespace(CC::WL_NS);
        $this->delete_tag('title');
        $this->delete_tag('url');
    }

    public function set_title($text) {
        $this->add_child_ns(CC::WL_NS, $this->firstChild, 'title', $text);
    }

    public function set_url($href, $attr=false) {
        if ( $attr == null ) $attr = array();
        $attr = array_merge(array('href' => $href), $attr);
        $this->add_child_ns(CC::WL_NS, $this->firstChild, 'url', '', $attr);
    }

    public function set_launch_url($text) {
    }

    public function set_secure_launch_url($text) {
        $this->add_child_ns(CC::BLTI_NS, $this->firstChild, 'secure_launch_url', $text);
    }

    public function set_icon($text) {
        $this->add_child_ns(CC::BLTI_NS, $this->firstChild, 'icon', $text);
    }

    public function set_secure_icon($text) {
        $this->add_child_ns(CC::BLTI_NS, $this->firstChild, 'secure_icon', $text);
    }

    public function set_custom($key,$value) {
        $tag = $this->get_tag_ns(CC::BLTI_NS, 'custom');
        $this->add_child_ns(CC::LTICM_NS, $tag, 'property', $value, array("name"=>$key));
    }

    public function set_extension($key,$value) {
        $tag = $this->get_tag_ns(CC::BLTI_NS, 'extensions');
        $this->add_child_ns(CC::LTICM_NS, $tag, 'property', $value, array("name"=>$key));
    }

}
