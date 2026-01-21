<?php

namespace Tsugi\Util;

use \Tsugi\Util\CC;

class CC_Topic extends \Tsugi\Util\TsugiDOM {

    function __construct() {
        parent::__construct('<topic xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imsdt_v1p1.xsd"> <title>The Psychology of Faces</title> <text texttype="text/html">Is recognition of human emotional states learned or innate?</text></topic>');
        $this->set_namespace(CC::TOPIC_NS);
        $this->delete_tag('title');
        $this->delete_tag('text');
    }

    public function set_title($text) {
        $this->add_child_ns(CC::TOPIC_NS, $this->firstChild, 'title', $text);
    }

    public function set_text($text) {
        $this->add_child_ns(CC::TOPIC_NS, $this->firstChild, 'text', $text);
    }

}
