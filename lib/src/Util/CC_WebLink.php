<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_WebLink extends \Tsugi\Util\TsugiDOM {

    function __construct() {
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imswl_v1p1.xsd">
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

}
