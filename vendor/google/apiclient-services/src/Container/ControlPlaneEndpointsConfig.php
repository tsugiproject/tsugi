<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Container;

class ControlPlaneEndpointsConfig extends \Google\Model
{
  protected $dnsEndpointConfigType = DNSEndpointConfig::class;
  protected $dnsEndpointConfigDataType = '';
  protected $ipEndpointsConfigType = IPEndpointsConfig::class;
  protected $ipEndpointsConfigDataType = '';

  /**
   * @param DNSEndpointConfig
   */
  public function setDnsEndpointConfig(DNSEndpointConfig $dnsEndpointConfig)
  {
    $this->dnsEndpointConfig = $dnsEndpointConfig;
  }
  /**
   * @return DNSEndpointConfig
   */
  public function getDnsEndpointConfig()
  {
    return $this->dnsEndpointConfig;
  }
  /**
   * @param IPEndpointsConfig
   */
  public function setIpEndpointsConfig(IPEndpointsConfig $ipEndpointsConfig)
  {
    $this->ipEndpointsConfig = $ipEndpointsConfig;
  }
  /**
   * @return IPEndpointsConfig
   */
  public function getIpEndpointsConfig()
  {
    return $this->ipEndpointsConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ControlPlaneEndpointsConfig::class, 'Google_Service_Container_ControlPlaneEndpointsConfig');
