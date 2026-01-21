<?php

require_once "src/Util/ContentItem.php";
require_once "src/Core/ContentItem.php";

// Need a different name from the Util test
class ContentItemCoreTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneral() {

        $ci = new \Tsugi\Core\ContentItem();
        $ci->addLtiLinkItem('path', 'title', 'title', 'icon', 'fa_icon');
        $ci->addContentItem('r->url', 'r->title', 'r->title', 'r->thumbnail', 'r->icon');
        $good = '{
    "@context": "http:\/\/purl.imsglobal.org\/ctx\/lti\/v1\/ContentItem",
    "@graph": [
        {
            "@type": "LtiLinkItem",
            "@id": ":item1",
            "title": "title",
            "mediaType": "application\/vnd.ims.lti.v1.ltilink",
            "text": "title",
            "url": "path",
            "placementAdvice": {
                "presentationDocumentTarget": "window"
            },
            "icon": {
                "@id": "icon",
                "fa_icon": "fa_icon",
                "width": 64,
                "height": 64
            }
        },
        {
            "@type": "ContentItem",
            "@id": ":item2",
            "title": "r->title",
            "mediaType": "text\/html",
            "text": "r->title",
            "url": "r->url",
            "placementAdvice": {
                "presentationDocumentTarget": "window",
                "displayWidth": "640",
                "displayHeight": "480"
            },
            "icon": {
                "@id": "r->thumbnail",
                "fa_icon": "r->icon",
                "width": 64,
                "height": 64
            }
        }
    ]
}';
        $out = json_encode($ci->json,JSON_PRETTY_PRINT);
        
        $this->assertEquals($good, $out);

    }

    public function testStatic() {

        // Make sure these compile
        $retval = \Tsugi\Core\ContentItem::allowMultiple(array());
        $retval = \Tsugi\Util\ContentItem::allowMultiple(array());
        $this->assertNotNull($retval);

    }
}
