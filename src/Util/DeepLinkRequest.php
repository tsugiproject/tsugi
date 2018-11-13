<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;

/**
 * This is a general purpose DeepLinkRequest class
 *
 */
class DeepLinkRequest {

    public $claim;
/*
  "https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings": {
    "deep_link_return_url": "https://platform.example/deep_links",
    "accept_types": ["link", "file", "html", "ltiResourceLink", "image"],
    "accept_media_types": "image/:::asterisk:::,text/html",
    "accept_presentation_document_targets": ["iframe", "window", "embed"],
    "accept_multiple": true,
    "auto_create": true,
    "title": "This is the default title",
    "text": "This is the default text",
    "data": "csrftoken:c7fbba78-7b75-46e3-9201-11e6d5f36f53"
  }
 */

    function __construct($claim) {
        if ( is_object($claim) ) $this->claim = $claim;
        if ( is_string($claim) ) $this->claim = json_decode($claim);
    }

    /**
     * returnUrl - Returns the deep_link_return_url
     *
     * @return string The deep_link_return_url or false
     */
    public function returnUrl() {
        if ( ! isset($this->claim) ) return false;
        return isset($this->claim->deep_link_return_url) ? $this->claim->deep_link_return_url : false;
    }

    /**
     * allowMimetype - Returns true if we can return LTI Link Items
     */
    public function allowMimetype($Mimetype) {
        if ( ! $this->returnUrl() ) return false;
        if ( isset($this->claim->accept_media_types) ) {
            $ma = $Mimetype;
            if ( ! is_array($ma) ) $ma = array($Mimetype);
            $m = new Mimeparse;
            $allowed = $m->best_match($ma, $this->claim->accept_media_types);
            if ( $Mimetype != $allowed ) return false;
            return true;
        }
        return false;
    }

    /**
     * allowLtiLinkItem - Returns true if we can return LTI Link Items
     */
    public function allowLtiLinkItem() {
        if ( ! $this->returnUrl() ) return false;
        return $this->allowMimetype('application/vnd.ims.lti.v1.ltiResourceLink');
    }

    /**
     * allowContentItem - Returns true if we can return HTML Items
     */
    public function allowContentItem() {
        if ( ! $this->returnUrl() ) return false;
        return $this->allowMimetype('text/html');
    }

    /**
     * allowImportItem - Returns true if we can return IMS Common Cartridges
     */
    public function allowImportItem() {
        $cc_types = array('application/vnd.ims.imsccv1p1',
            'application/vnd.ims.imsccv1p2', 'application/vnd.ims.imsccv1p3');

        foreach($cc_types as $cc_mimetype ){
            $m = new Mimeparse;
            $cc_allowed = $this->allowMimetype($cc_mimetype);
            if ( $cc_allowed ) return true;
        }
        return false;
    }

}
