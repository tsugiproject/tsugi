<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;

/**
 * This is a general purpose DeepLink class with no Tsugi-specific dependencies.
 *
 * Deep Linking 2.0 spec:
 * https://www.imsglobal.org/spec/lti-dl/v2p0
 */
class DeepLinkResponse extends DeepLinkRequest {

    const DATA_CLAIM = "https://purl.imsglobal.org/spec/lti-dl/claim/data";
    const CONTENT_ITEMS = "https://purl.imsglobal.org/spec/lti-dl/claim/content_items";

    // From: https://www.imsglobal.org/spec/lti-dl/v2p0#errorlog
    const MESSAGE_MSG = "https://purl.imsglobal.org/spec/lti-dl/claim/msg";
    const MESSAGE_LOG = "https://purl.imsglobal.org/spec/lti-dl/claim/log";
    const MESSAGE_ERRORMSG = "https://purl.imsglobal.org/spec/lti-dl/claim/errormsg";
    const MESSAGE_ERRORLOG = "https://purl.imsglobal.org/spec/lti-dl/claim/errorlog";

    public $json;
    public $items = array();

    function __construct($request) {
        $this->claim = $request->claim;
        // TODO: deployment_id
$text='{
  "https://purl.imsglobal.org/spec/lti/claim/deployment_id":
    "07940580-b309-415e-a37c-914d387c1150",
  "https://purl.imsglobal.org/spec/lti/claim/message_type": "LtiDeepLinkingResponse",
  "https://purl.imsglobal.org/spec/lti/claim/version": "1.3.0",
  "https://purl.imsglobal.org/spec/lti-dl/claim/data": "csrftoken:c7fbba78-7b75-46e3-9201-11e6d5f36f53"
}';
        $this->json = json_decode($text);
        if ( is_object($this->json) ) return;
        error_log("Unable to parse JSON");
        error_log($text);
        die();
    }

    /**
     * Return the claims array to send back to the LMS
     */
    function getContentItemSelection()
    {
        $this->json->{self::CONTENT_ITEMS} = $this->items;
        unset($this->json->{self::DATA_CLAIM});
        if ( isset($this->claim->data) ) {
            $this->json->{self::DATA_CLAIM} = $this->claim->data;
        }
        return $this->json;
    }

    /**
     * addLtiLinkItem - Add an LTI Link Deep Link
     *
     * @param $url The launch URL of the tool that is about to be placed
     * @param $title A plain text title of the content-item.
     * @param $text A plain text description of the content-item.
     * @param $icon An image URL of an icon
     * @param $fa_icon The class name of a FontAwesome icon
     * @param $custom An optional array of custom key / value pairs
     * @param $scoreMaximum The scoreMaximum if this is an assignment
     * @param $resourceId The resourceId for the item if it is an assignment
     * @param array $additionalParms A key/value array of additional values to send
     *
     */
    public function addLtiLinkItem($url, $title=false, $text=false,
        $icon=false, $fa_icon=false, $custom=false,
        $scoreMaximum=false, $resourceId=false, $additionalParams = array())
    {
        global $CFG;
        $params = array(
            'type' => 'link',
            'url' => $url,
            'title' => $title,
            'text' => $text,
            'icon' => $icon,
            'fa_icon' => $fa_icon,
            'custom' => $custom,
            'scoreMaximum' => $scoreMaximum,
            'resourceId' => $resourceId,
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

        // https://www.imsglobal.org/spec/lti-dl/v2p0
        $item = '{
            "type": "ltiResourceLink",
            "title": "A title",
            "text": "Extended description",
            "url": "https://lti.example.com/launchMe",
            "icon": {
                "url": "https://lti.example.com/image.jpg",
                "fa_icon" : "fa-magic",
                "width": 100,
                "height": 100
            },
            "thumbnail": {
                "url": "https://lti.example.com/thumb.jpg",
                "width": 90,
                "height": 90
            },
            "lineItem": {
                "scoreMaximum": 87,
                "label": "Chapter 12 quiz",
                "resourceId": "xyzpdq1234",
                "tag": "originality"
            },
            "available": {
                "startDateTime": "2020-07-23T20:05:02Z",
                "endDateTime": "2020-08-25T20:05:02Z"
            },
            "submission": {
                "startDateTime": "2020-07-24T20:05:02Z",
                "endDateTime": "2020-08-24T20:05:02Z"
            },
            "custom": {
                "quiz_id": "az-123",
                "duedate": "$Resource.submission.endDateTime"
            },
            "window": {
                "targetName": "examplePublisherContent"
            },
            "iframe": {
                "height": 800,
                "height": 600
            }
        }';

        $json = json_decode($item);
        $json->url = $params['url'];
        if ( U::get($params, 'title') ) $json->{'title'} = U::get($params, 'title');
        if ( U::get($params, 'text') ) $json->{'text'} = U::get($params, 'text');
        if ( U::get($params, 'icon') ) $json->{'icon_url'} = U::get($params, 'icon');
        if ( U::get($params, 'fa_icon') ) $json->{'fa_icon'} = U::get($params, 'fa_icon');

        unset($json->custom);
        if ( U::get($params, 'custom') ) $json->{'custom'} = U::get($params, 'custom');

        if ( U::get($params, 'scoreMaximum') ) {
            $json->lineItem->label = $params['title'];
            unset($json->lineItem->scoreMaximum);
            if ( U::get($params, 'scoreMaximum') ) $json->lineItem->scoreMaximum = U::get($params, 'scoreMaximum');
            unset($json->lineItem->resourceId);
            if ( U::get($params, 'resourceId') ) $json->lineItem->resourceId = U::get($params, 'resourceId');
            unset($json->lineItem->tag);
            if ( U::get($params, 'tag') ) $json->lineItem->tag = U::get($params, 'tag');
        } else {
            unset($json->lineItem);
        }

        unset($json->available);
        unset($json->submission);

        if ( U::get($params, "availableStart") || U::get($params, "availableEnd") ) {
            $available = new \stdClass();
            if ( U::get($params, "availableStart") ) $available->startDateTime = U::get($params, "availableStart");
            if ( U::get($params, "availableEnd"  ) ) $available->endDateTime   = U::get($params, "availableEnd");
            $json->available = $available;
        }

        if ( U::get($params, "submissionStart") || U::get($params, "submissionEnd") ) {
            $submission = new \stdClass();
            if ( U::get($params, "submissionStart") ) $submission->startDateTime = U::get($params, "submissionStart");
            if ( U::get($params, "submissionEnd"  ) ) $submission->endDateTime = U::get($params, "submissionEnd");
            $json->submission = $submission;
        }
        unset($json->window);
        unset($json->iframe);

        if (U::get($params, 'placementTarget') == 'iframe' &&
            is_numeric(U::get($params,'placementHeight')) && is_numeric(U::get($params,'placementWidth')) ) {

            $iframe = new \stdClass();
            $iframe->height = $params['placementHeight'];
            $iframe->width = $params['placementWidth'];
            $json->iframe = $iframe;
        }

        if (is_array(U::get($params, 'submissionReview')) ) {
            $json->submissionReview = U::get($params, 'submissionReview');
        }

        $this->items[] = $json;
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

        $item = '{ "type" : "ContentItem",
                "@id" : ":item2",
                "title" : "A cool tool hosted in the Tsugi environment.",
                "mediaType" : "text/html",
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

        $json->{'@id'} = ':item'.(count($this->items)+1);
        $this->items[] = $json;
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
  "type" : "file",
  "url" : "http://www.imsglobal.org/xsd/qti/qtiv2p1/imsqti_v2p1.xsd",
  "expiresAt" : "2014-03-05T00:00:00Z",
  "mediaType" : "application/xml",
  "title" : "Imported from Tsugi"
}';

        $json = json_decode($item);
        $json->url = $url;
        if ( $title ) $json->{'title'} = $title;
        $datetime = (new \DateTime('+1 day'))->format(\DateTime::ATOM);
        $datetime = substr($datetime,0,19) . 'Z';
        $json->expiresAt = $datetime;

        if ( U::get($additionalParams,'mediaType') ) $json->{'mediaType'} = $additionalParams['mediaType'];
        if ( U::get($additionalParams,'expiresAt') ) $json->{'expiresAt'} = $additionalParams['expiresAt'];

        $json->{'@id'} = ':item'.(count($this->items)+1);

        $this->items[] = $json;
    }

}

/*
{
  "iss": "962fa4d8-bcbf-49a0-94b2-2de05ad274af",
  "aud": "https://platform.example.org",
  "exp": 1510185728,
  "iat": 1510185228,
  "nonce": "fc5fdc6d-5dd6-47f4-b2c9-5d1216e9b771",
  "azp": "962fa4d8-bcbf-49a0-94b2-2de05ad274af",
  "https://purl.imsglobal.org/spec/lti/claim/deployment_id":
    "07940580-b309-415e-a37c-914d387c1150",
  "https://purl.imsglobal.org/spec/lti/claim/message_type": "LtiDeepLinkingResponse",
  "https://purl.imsglobal.org/spec/lti/claim/version": "1.3.0",
  "https://purl.imsglobal.org/spec/lti-dl/claim/content_items": [
    {
      "type": "link",
      "title": "My Home Page",
      "url": "https://something.example.com/page.html",
      "icon": {
        "url": "https://lti.example.com/image.jpg",
        "width": 100,
        "height": 100
      },
      "thumbnail": {
        "url": "https://lti.example.com/thumb.jpg",
        "width": 90,
        "height": 90
      }
    },
    {
      "type": "html",
      "html": " A Custom Title "
    },
    {
      "type": "link",
      "url": "https://www.youtube.com/watch?v=corV3-WsIro",
      "embed": {
        "html":
          "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/corV3-WsIro\" frameborder=\"0\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>"
      },
      "window": {
        "targetName": "youtube-corV3-WsIro",
        "windowFeatures": "height=560,width=315,menubar=no"
      },
      "iframe": {
        "width": 560,
        "height": 315,
        "src": "https://www.youtube.com/embed/corV3-WsIro"
      }
    },
    {
      "type": "image",
      "url": "https://www.example.com/image.png",
      "https://www.example.com/resourceMetadata": {
        "license": "CCBY4.0"
      }
    },
    {
      "type": "ltiResourceLink",
      "title": "A title",
      "url": "https://lti.example.com/launchMe",
      "presentation": {
        "documentTarget": "iframe",
        "width": 500,
        "height": 600
      },
      "icon": {
        "url": "https://lti.example.com/image.jpg",
        "width": 100,
        "height": 100
      },
      "thumbnail": {
        "url": "https://lti.example.com/thumb.jpg",
        "width": 90,
        "height": 90
      },
      "lineItem": {
        "scoreMaximum": 87,
        "label": "Chapter 12 quiz",
        "resourceId": "xyzpdq1234",
        "tag": "originality"
      },
      "custom": {
        "quiz_id": "az-123",
        "duedate": "$Resource.submission.endDateTime"
      },
      "window": {
        "targetName": "examplePublisherContent"
      },
      "iframe": {
        "height": 890
      }
    },
    {
      "type": "file",
      "title": "A file like a PDF that is my assignment submissions",
      "url": "https://my.example.com/assignment1.pdf",
      "mediaType": "application/pdf",
      "expiresAt": "2018-03-06T20:05:02Z"
    },
    {
      "type": "https://www.example.com/custom_type",
      "data": "somedata"
    }
  ],
  "https://purl.imsglobal.org/spec/lti-dl/data": "csrftoken:c7fbba78-7b75-46e3-9201-11e6d5f36f53"
}
*/
