<?php

namespace Tsugi\Services\ExternalTools;

use \Tsugi\Util\LTI13;

/**
 * Current outbound launch provider for "remote Tsugi tool" forwarding.
 *
 * This preserves the existing JWT + callback launch shape used by ext/route.php
 * while giving us a clean seam for future outbound providers.
 */
class RemoteTsugiLaunchProvider implements ExternalLaunchProvider
{
    /**
     * @inheritDoc
     */
    public function build(
        array $launch_data,
        string $launch_url,
        string $callback_endpoint,
        string $callback_token
    ): ExternalLaunchRequest {
        unset($launch_data['event_nonce']);

        $jwt_claim = LTI13::base_jwt("iss", "subj");
        $jwt_claim["lti"] = $launch_data;
        $jwt_claim["callback"] = array(
            'endpoint' => $callback_endpoint,
            'token' => $callback_token,
        );

        return new ExternalLaunchRequest(
            $launch_url,
            $jwt_claim,
            strpos($launch_url, "debug=true") > 0,
            array('button' => "Go")
        );
    }
}
