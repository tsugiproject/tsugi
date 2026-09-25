<?php

namespace Tsugi\Core;

/**
 * LTI launch_presentation parameters for this request.
 *
 * Same fields as an LTI 1.1 launch presentation. Internal tools set
 * return_url when the request names where to go back, the way a platform
 * sets launch_presentation_return_url.
 */
class LaunchPresentation {

    /** @var string|null */
    public $return_url = null;

    /** @var string|null */
    public $document_target = null;

    /** @var string|null */
    public $locale = null;

    /** @var string|null */
    public $height = null;

    /** @var string|null */
    public $width = null;

    /** @var string|null */
    public $css_url = null;
}
