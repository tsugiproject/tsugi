<?php

namespace Tsugi\Util;

/**
 * This is a general purpose ContentItem class with no Tsugi-specific dependencies.
 *
 */
class ContentItem {

    public $json;

    function __construct() {
        $text = '{
            "@context" : "http://purl.imsglobal.org/ctx/lti/v1/ContentItem", 
            "@graph" : [ ]
        }';

        $this->json = json_decode($text);
    }

    /**
     * returnUrl - Returns the content_item_return_url
     *
     * @return string The content_item_return_url or false
     */
    public static function returnUrl($postdata) {
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        return $postdata['content_item_return_url'];
    }

    /**
     * allowLtiLinkItem - Returns true if we can return LTI Link Items
     */
    public static function allowLtiLinkItem($postdata) {
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        if ( isset($postdata['accept_media_types']) ) {
            $ltilink_mimetype = 'application/vnd.ims.lti.v1.ltilink';
            $m = new Mimeparse;
            $ltilink_allowed = $m->best_match(array($ltilink_mimetype), $postdata['accept_media_types']);
            if ( $ltilink_mimetype != $ltilink_allowed ) return false;
            return true;
        }
        return false;
    }

    /**
     * allowContentItem - Returns true if we can return LTI Link Items
     */
    public static function allowContentItem($postdata) {
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        if ( isset($postdata['accept_media_types']) ) {
            $web_mimetype = 'text/html';
            $m = new Mimeparse;
            $web_allowed = $m->best_match(array($web_mimetype), $postdata['accept_media_types']);
            if ( $web_mimetype != $web_allowed ) return false;
            return true;
        }
        return false;
    }

    /**
     * Return the parameters to send back to the LMS
     */
    function getContentItemSelection($data=false)
    {
        $selection = json_encode($this->json);
        $parms = array();
        $parms["lti_message_type"] = "ContentItemSelection";
        $parms["lti_version"] = "LTI-1p0";
        $parms["content_items"] = $selection;
        if ( $data ) $parms['data'] = $data;
        return $parms;
    }



    /**
     * addLtiLinkItem - Add an LTI Link Content Item
     *
     * @param url The launch URL of the tool that is about to be placed
     * @param title A plain text title of the content-item.
     * @param text A plain text description of the content-item.
     * @param icon An image URL of an icon
     * @param fa_icon The class name of a FontAwesome icon
     * @param custom An optional array of custom key / value pairs
     *
     */
    public function addLtiLinkItem($url, $title=false, $text=false, 
        $icon=false, $fa_icon=false, $custom=false ) 
    {
        $item = '{ "@type" : "LtiLinkItem",
                    "@id" : ":item2",
                    "title" : "A cool tool hosted in the Tsugi environment.", 
                    "mediaType" : "application/vnd.ims.lti.v1.ltilink", 
                    "text" : "For more information on how to build and host powerful LTI-based Tools quickly, see www.tsugi.org",
                    "url" : "http://www.tsugi.org/",
                    "placementAdvice" : {
	                    "presentationDocumentTarget" : "window",
                        "windowTarget" : "_blank"
                    },
                    "icon" : {
                        "@id" : "https://www.dr-chuck.net/tsugi-static/img/default-icon.png",
                        "fa_icon" : "fa-magic",
                        "width" : 64,
                        "height" : 64
                    }
                }';

        $json = json_decode($item);
        $json->url = $url;
        if ( $title ) $json->{'title'} = $title;
        if ( $text ) $json->{'text'} = $text;
        if ( $icon ) $json->{'icon'}->{'@id'} = $icon;
        if ( $fa_icon ) $json->icon->fa_icon = $fa_icon;
        if ( $custom ) $json->custom = $custom;

        $json->{'@id'} = ':item'.(count($this->json->{'@graph'})+1);

        $this->json->{'@graph'}[] = $json;
    }

    /**
     * addContentItem - Add an Content Item
     *
     * @param url The launch URL of the tool that is about to be placed
     * @param title A plain text title of the content-item.
     * @param text A plain text description of the content-item.
     * @param icon An image URL of an icon
     * @param fa_icon The class name of a FontAwesome icon
     *
     */
    public function addContentItem($url, $title=false, $text=false, 
        $icon=false, $fa_icon=false ) 
    {
        $item = '{ "@type" : "ContentItem",
                    "@id" : ":item2",
                    "title" : "A cool tool hosted in the Tsugi environment.", 
                    "mediaType" : "text/html", 
                    "text" : "For more information on how to build and host powerful LTI-based Tools quickly, see www.tsugi.org",
                    "url" : "http://www.tsugi.org/",
                    "placementAdvice" : {
	                    "presentationDocumentTarget" : "window",
                        "windowTarget" : "_blank"
                    },
                    "icon" : {
                        "@id" : "https://www.dr-chuck.net/tsugi-static/img/default-icon.png",
                        "fa_icon" : "fa-magic",
                        "width" : 64,
                        "height" : 64
                    }
                }';

        $json = json_decode($item);
        $json->url = $url;
        if ( $title ) $json->{'title'} = $title;
        if ( $text ) $json->{'text'} = $text;
        if ( $icon ) $json->{'icon'}->{'@id'} = $icon;
        if ( $fa_icon ) $json->icon->fa_icon = $fa_icon;

        $json->{'@id'} = ':item'.(count($this->json->{'@graph'})+1);

        $this->json->{'@graph'}[] = $json;
    }

}
