<?php

namespace Tsugi\Services\ExternalTools;

/**
 * Value object representing an outbound external-tool launch.
 *
 * This is a small seam for future launch providers. Today it carries the
 * current remote Tsugi JWT launch shape without changing behavior.
 */
class ExternalLaunchRequest
{
    /**
     * @param string $launch_url Launch target URL
     * @param array $jwt_claim JWT claims to sign and forward
     * @param bool $debug Whether to render debug launch HTML
     * @param array $extra Extra options for LTI13::build_jwt_html()
     */
    public function __construct(
        public string $launch_url,
        public array $jwt_claim,
        public bool $debug = false,
        public array $extra = array()
    ) {
    }
}
