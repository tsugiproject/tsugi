<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_WebLink extends \Tsugi\Util\TsugiDOM {

    /**
     * @var string
     */
    private $wlNs;

    /**
     * @param CC|null $cc Cartridge profile; default CC 1.2
     */
    function __construct($cc = null) {
        $this->wlNs = ($cc instanceof CC) ? $cc->webLinkNs() : CC::WL_NS;
        $schema = ($cc instanceof CC) ? $cc->webLinkSchemaLocation() : CC::WL_SCHEMA_LOCATION;
        parent::__construct('<?xml version="1.0" encoding="UTF-8"?>
<webLink xmlns="'.$this->wlNs.'"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="'.$schema.'">
  <title>Wikipedia - Psychology</title>
  <url href="http://en.wikipedia.org/wiki/Psychology"/>
</webLink>');

        $this->set_namespace($this->wlNs);
        $this->delete_tag('title');
        $this->delete_tag('url');
    }

    public function set_title($text) {
        $this->add_child_ns($this->wlNs, $this->firstChild, 'title', $text);
    }

    public function set_url($href, $attr=false) {
        if ( $attr == null ) $attr = array();
        $attr = array_merge(array('href' => $href), $attr);
        $this->add_child_ns($this->wlNs, $this->firstChild, 'url', '', $attr);
    }

}
