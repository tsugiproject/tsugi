<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_Topic extends \Tsugi\Util\TsugiDOM {

    /**
     * @var string
     */
    private $topicNs;

    /**
     * @param CC|null $cc Cartridge profile; default CC 1.2
     */
    function __construct($cc = null) {
        $this->topicNs = ($cc instanceof CC) ? $cc->topicNs() : CC::TOPIC_NS;
        $schema = ($cc instanceof CC) ? $cc->topicSchemaLocation() : CC::TOPIC_SCHEMA_LOCATION;
        parent::__construct('<topic xmlns="'.$this->topicNs.'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="'.$schema.'"> <title>The Psychology of Faces</title> <text texttype="text/html">Is recognition of human emotional states learned or innate?</text></topic>');
        $this->set_namespace($this->topicNs);
        $this->delete_tag('title');
        $this->delete_tag('text');
    }

    public function set_title($text) {
        $this->add_child_ns($this->topicNs, $this->firstChild, 'title', $text);
    }

    public function set_text($text) {
        $this->add_child_ns($this->topicNs, $this->firstChild, 'text', $text);
    }

}
