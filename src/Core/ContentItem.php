<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;

/**
 * This is a ContentItem class with Tsugi-specific convienences.
 *
 */
class ContentItem extends \Tsugi\Util\ContentItem {

    /**
     * returnUrl - Returns the content_item_return_url
     *
     * @return string The content_item_return_url or false
     */
    public static function returnUrl($postdata=false) {
        if ( ! $postdata ) $postdata = LTIX::ltiRawPostArray();
        return parent::returnUrl($postdata);
    }

    /**
     * allowLtiLinkItem - Returns true if we can return LTI Link Items
     */
    public static function allowLtiLinkItem($postdata=false) {
        if ( ! $postdata ) $postdata = LTIX::ltiRawPostArray();
        return parent::allowLtiLinkItem($postdata);
    }

    /**
     * allowContentItem - Returns true if we can return HTML Items
     */
    public static function allowContentItem($postdata=false) {
        if ( ! $postdata ) $postdata = LTIX::ltiRawPostArray();
        return parent::allowContentItem($postdata);
    }

    /**
     * allowImportItem - Returns true if we can return Common Cartridges
     */
    public static function allowImportItem($postdata=false) {
        if ( ! $postdata ) $postdata = LTIX::ltiRawPostArray();
        return parent::allowImportItem($postdata);
    }

    /**
     * Return the parameters to send back to the LMS
     */
    function getContentItemSelection($data=false)
    {
        if ( ! $data ) $data = LTIX::ltiRawParameter('data');
        return parent::getContentItemSelection($data);
    }

    /**
     * Make up a response
     *
     * @param $endform Some HTML to be included before the form closing tag
     *
     *     $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
     *
     */
    function prepareResponse($endform=false) {
        $return_url = $this->returnUrl();
        $parms = $this->getContentItemSelection();
        $parms = LTIX::signParameters($parms, $return_url, "POST", "Install Content");
        $endform = '<a href="index.php" class="btn btn-warning">Back to Store</a>';
        $content = LTI::postLaunchHTML($parms, $return_url, true, false, $endform);
        return $content;
    }

}
