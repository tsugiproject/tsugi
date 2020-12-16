<?php

namespace Tsugi\Util;

/**
 * This is a general purpose ContentItem class with no Tsugi-specific dependencies.
 *
 * Deep Linking 1.0 / Content Item Spec
 *
 * https://www.imsglobal.org/specs/lticiv1p0/specification
 *
 */
class ContentItem {

    public $json;

    function __construct() {
        $text = '{
            "@context" : [
                "http://purl.imsglobal.org/ctx/lti/v1/ContentItem",
                {
                    "lineItem" : "http://purl.imsglobal.org/ctx/lis/v2/LineItem",
                    "res" : "http://purl.imsglobal.org/ctx/lis/v2p1/Result#"
                }
            ],
            "@graph" : [ ]
        }';

        // Because of D2L
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
     * allowMultiple - Returns true if we can return LTI Link Items
     */
    public static function allowMultiple($postdata) {
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        if ( isset($postdata['accept_multiple']) ) {
            if ( $postdata['accept_multiple'] && $postdata['accept_multiple'] != "false" ) return true;
        }
        return false;
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
     * allowLink - Returns true if we can return HTML Items
     *
     * Upwards compatibility with DeepLink 2.0
     */
    public static function allowLink($postdata) {
        return self::allowContentItem($postdata);
    }

    /**
     * allowContentItem - Returns true if we can return HTML Items
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
     * allowImportItem - Returns true if we can return IMS Common Cartridges
     */
    public static function allowImportItem($postdata) {
        $cc_types = array('application/vnd.ims.imsccv1p1',
            'application/vnd.ims.imsccv1p2', 'application/vnd.ims.imsccv1p3');
        if ( ! isset($postdata['content_item_return_url']) ) return false;
        if ( isset($postdata['accept_media_types']) ) {
            $accept = $postdata['accept_media_types'];

            foreach($cc_types as $cc_mimetype ){
                $m = new Mimeparse;
                $cc_allowed = $m->best_match(array($cc_mimetype), $accept);
                if ( $cc_mimetype == $cc_allowed ) return true;
            }
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
     * @param $url The launch URL of the tool that is about to be placed
     * @param $title A plain text title of the content-item.
     * @param $text A plain text description of the content-item.
     * @param $icon An image URL of an icon
     * @param $fa_icon The class name of a FontAwesome icon
     * @param $custom An optional array of custom key / value pairs
     * @param $points The number of points if this is an assignment
     * @param $activityId The activity for the item
     *
     */
    public function addLtiLinkItem($url, $title=false, $text=false,
        $icon=false, $fa_icon=false, $custom=false,
        $points=false, $activityId=false, $additionalParams = array())
    {
        global $CFG;
        $params = array(
            'url' => $url,
            'title' => $title,
            'text' => $text,
            'icon' => $icon,
            'fa_icon' => $fa_icon,
            'custom' => $custom,
            'points' => $points,
            'activityId' => $activityId,
        );

        if ( is_array($additionalParams) ) {
            $params = array_merge($additionalParams, $params);
        }

        $this->addLtiLinkItemExtended($params);
    }

    /**
     * addLtiLinkItemExtended - Add an LTI Link Content Item
     *
     * @param $params Key/Value pair of configurable options for content item (see addLtiLinkItem)
     *
     */
    public function addLtiLinkItemExtended($params = array())
    {
        global $CFG;

        // populate the default parameters
        if (empty($params['title']))
            $params['title'] = false;
        if (empty($params['text']))
            $params['text'] = false;
        if (empty($params['icon']))
            $params['icon'] = false;
        if (empty($params['fa_icon']))
            $params['fa_icon'] = false;
        if (empty($params['custom']))
            $params['custom'] = false;
        if (empty($params['points']))
            $params['points'] = false;
        if (empty($params['activityId']))
            $params['activityId'] = false;
        if (empty($params['placementTarget']))
            $params['placementTarget'] = 'iframe';
        if (empty($params['placementWindowTarget']))
            $params['placementWindowTarget'] = '';
        if (empty($params['placementWidth']))
            $params['placementWidth'] = '';
        if (empty($params['placementHeight']))
            $params['placementHeight'] = '';

        // https://www.imsglobal.org/lti/model/uml/purl.imsglobal.org/vocab/lti/v1/ci/index.html
        $item = '{ "@type" : "LtiLinkItem",
                    "@id" : ":item2",
                    "title" : "A cool tool hosted in the Tsugi environment.",
                    "mediaType" : "application/vnd.ims.lti.v1.ltilink",
                    "text" : "For more information on how to build and host powerful LTI-based Tools quickly, see www.tsugi.org",
                    "url" : "http://www.tsugi.org/",
                    "placementAdvice" : {
                        "presentationDocumentTarget" : "iframe"
                    },
                    "icon" : {
                        "@id" : "https://static.tsugi.org/img/default-icon.png",
                        "fa_icon" : "fa-magic",
                        "width" : 64,
                        "height" : 64
                    },
                    "lineItem" : {
                        "@type" : "LineItem",
                        "label" : "Gradable External Tool",
                        "reportingMethod" : "res:totalScore",
                        "assignedActivity" : {
                            "@id" : "http://toolprovider.example.com/assessment/66400",
                            "activityId" : "a-9334df-33"
                        },
                        "scoreConstraints" : {
                            "@type" : "NumericLimits",
                            "normalMaximum" : 10
                        }
                    }
                }';

        $json = json_decode($item);
        $json->url = $params['url'];
        if ( $params['title'] ) $json->{'title'} = $params['title'];
        if ( $params['text'] ) $json->{'text'} = $params['text'];
        if ( $params['icon'] ) $json->{'icon'}->{'@id'} = $params['icon'];
        if ( $params['fa_icon'] ) $json->icon->fa_icon = $params['fa_icon'];
        if ( $params['custom'] ) $json->custom = $params['custom'];
        if ( $params['points'] || $params['activityId'] ) {
            $json->lineItem->label = $params['title'];
            if ( $params['activityId'] ) {
                $json->lineItem->assignedActivity->{'@id'} = $CFG->wwwroot . '/lti/activity/' . $params['activityId'];
                $json->lineItem->assignedActivity->activityId = $params['activityId'];
            }
            if ( $params['points'] ) {
                $json->lineItem->scoreConstraints->normalMaximum = $params['points'];
            }
        } else {
            unset($json->lineItem);
        }

        if ($params['placementTarget'])
            $json->placementAdvice->presentationDocumentTarget = $params['placementTarget'];
        if ($params['placementWindowTarget'])
            $json->placementAdvice->windowTarget = $params['placementWindowTarget'];
        if (! empty($params['placementWidth']))
            $json->placementAdvice->displayWidth = $params['placementWidth'];
        if (! empty($params['placementHeight']))
            $json->placementAdvice->displayHeight = $params['placementHeight'];

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
        $icon=false, $fa_icon=false, $additionalParams = array())
    {
        $params = array(
            'url' => $url,
            'title' => $title,
            'text' => $text,
            'icon' => $icon,
            'fa_icon' => $fa_icon,
        );
        // package the parameter list into an array for the helper function
        if (! empty($additionalParams['placementTarget']))
            $params['placementTarget'] = $additionalParams['placementTarget'];
        if (! empty($additionalParams['placementWidth']))
            $params['placementWidth'] = $additionalParams['placementWidth'];
        if (! empty($additionalParams['placementHeight']))
            $params['placementHeight'] = $additionalParams['placementHeight'];

        $this->addContentItemExtended($params);
    }


    /**
     * addContentItemExtended - Add an Content Item
     *
         * @param $params Key/Value pair of configurable options for content item (see addContentItem)
     *
     */
    public function addContentItemExtended($params = array())
    {
        if (empty($params['title']))
            $params['title'] = false;
        if (empty($params['text']))
            $params['text'] = false;
        if (empty($params['icon']))
            $params['icon'] = false;
        if (empty($params['fa_icon']))
            $params['fa_icon'] = false;
        if (empty($params['placementTarget']))
            $params['placementTarget'] = 'iframe';
        if (empty($params['placementWindowTarget']))
            $params['placementWindowTarget'] = '';
        if (empty($params['placementWidth']))
            $params['placementWidth'] = '';
        if (empty($params['placementHeight']))
            $params['placementHeight'] = '';

        // https://www.imsglobal.org/specs/lticiv1p0/specification-3
        $item = '{ "@type" : "ContentItem",
                "@id" : ":item2",
                "title" : "A cool tool hosted in the Tsugi environment.",
                "mediaType" : "text/html",
                "text" : "For more information on how to build and host powerful LTI-based Tools quickly, see www.tsugi.org",
                "url" : "http://www.tsugi.org/",
                "placementAdvice" : {
                        "presentationDocumentTarget" : "iframe",
                        "displayWidth" : "640",
                        "displayHeight" : "480"
                },
                "icon" : {
                        "@id" : "https://static.tsugi.org/img/default-icon.png",
                        "fa_icon" : "fa-magic",
                        "width" : 64,
                        "height" : 64
                }
        }';

        $json = json_decode($item);
        $json->url = $params['url'];
        if ( $params['title'] ) $json->{'title'} = $params['title'];
        if ( $params['text'] ) $json->{'text'} = $params['text'];
        if ( $params['icon'] ) $json->{'icon'}->{'@id'} = $params['icon'];
        if ( $params['fa_icon'] ) $json->icon->fa_icon = $params['fa_icon'];

        if ($params['placementTarget'])
            $json->placementAdvice->presentationDocumentTarget = $params['placementTarget'];
        if ($params['placementWindowTarget'])
            $json->placementAdvice->windowTarget = $params['placementWindowTarget'];
        if (! empty($params['placementWidth']))
            $json->placementAdvice->displayWidth = $params['placementWidth'];
        if (! empty($params['placementHeight']))
            $json->placementAdvice->displayHeight = $params['placementHeight'];

        $json->{'@id'} = ':item'.(count($this->json->{'@graph'})+1);

        $this->json->{'@graph'}[] = $json;
    }

    /**
     * addFileItem - Add an File Item
     *
     * @param url The launch URL of the tool that is about to be placed
     * @param title A plain text title of the content-item.
     * @params additionalParams Array of configurable parameters for LTI placement (options: placementTarget, placementWidth, placementHeight)
     *
     */
    public function addFileItem($url, $title=false, $additionalParams = array())
    {
        $item = '{
  "@type" : "FileItem",
  "url" : "http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1.xsd",
  "copyAdvice" : "true",
  "expiresAt" : "2014-03-05T00:00:00Z",
  "mediaType" : "application/xml",
  "title" : "Imported from Tsugi",
  "placementAdvice" : {
    "windowTarget" : "_blank"
  }
}';

        if (empty($additionalParams['placementTarget']))
            $additionalParams['placementTarget'] = 'window';
        if (empty($additionalParams['placementWindowTarget']))
            $additionalParams['placementWindowTarget'] = '_blank';
        if (empty($additionalParams['placementWidth']))
            $additionalParams['placementWidth'] = '';
        if (empty($additionalParams['placementHeight']))
            $additionalParams['placementHeight'] = '';

        $json = json_decode($item);
        $json->url = $url;
        if ( isset($additionalParams['title']) ) $json->{'title'} = $additionalParams['title'];

        $datetime = (new \DateTime('+1 day'))->format(\DateTime::ATOM);
        $datetime = substr($datetime,0,19) . 'Z';
        $json->expiresAt = $datetime;

        if ($additionalParams['placementTarget'])
            $json->placementAdvice->presentationDocumentTarget = $additionalParams['placementTarget'];
        if ($additionalParams['placementWindowTarget'])
            $json->placementAdvice->windowTarget = $additionalParams['placementWindowTarget'];
        if (! empty($additionalParams['placementWidth']))
            $json->placementAdvice->displayWidth = $additionalParams['placementWidth'];
        if (! empty($additionalParams['placementHeight']))
            $json->placementAdvice->displayHeight = $additionalParams['placementHeight'];

        $json->{'@id'} = ':item'.(count($this->json->{'@graph'})+1);

        $this->json->{'@graph'}[] = $json;
    }

}
