<?php

namespace Tsugi\Services\ExternalTools;

/**
 * Builds an outbound launch request for an external tool.
 *
 * Additional outbound launch strategies can implement this interface as Tsugi
 * evolves into a fuller mediation layer.
 */
interface ExternalLaunchProvider
{
    /**
     * Build an outbound launch request from launch/session data.
     *
     * @param array $launch_data Current Tsugi launch/session data
     * @param string $launch_url Remote launch target
     * @param string $callback_endpoint Callback endpoint exposed by Tsugi
     * @param string $callback_token Callback token for the remote tool
     * @return ExternalLaunchRequest
     */
    public function build(
        array $launch_data,
        string $launch_url,
        string $callback_endpoint,
        string $callback_token
    ): ExternalLaunchRequest;
}
