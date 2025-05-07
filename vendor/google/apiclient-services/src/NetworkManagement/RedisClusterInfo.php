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

namespace Google\Service\NetworkManagement;

class RedisClusterInfo extends \Google\Model
{
  /**
   * @var string
   */
  public $discoveryEndpointIpAddress;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $location;
  /**
   * @var string
   */
  public $networkUri;
  /**
   * @var string
   */
  public $secondaryEndpointIpAddress;
  /**
   * @var string
   */
  public $uri;

  /**
   * @param string
   */
  public function setDiscoveryEndpointIpAddress($discoveryEndpointIpAddress)
  {
    $this->discoveryEndpointIpAddress = $discoveryEndpointIpAddress;
  }
  /**
   * @return string
   */
  public function getDiscoveryEndpointIpAddress()
  {
    return $this->discoveryEndpointIpAddress;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param string
   */
  public function setLocation($location)
  {
    $this->location = $location;
  }
  /**
   * @return string
   */
  public function getLocation()
  {
    return $this->location;
  }
  /**
   * @param string
   */
  public function setNetworkUri($networkUri)
  {
    $this->networkUri = $networkUri;
  }
  /**
   * @return string
   */
  public function getNetworkUri()
  {
    return $this->networkUri;
  }
  /**
   * @param string
   */
  public function setSecondaryEndpointIpAddress($secondaryEndpointIpAddress)
  {
    $this->secondaryEndpointIpAddress = $secondaryEndpointIpAddress;
  }
  /**
   * @return string
   */
  public function getSecondaryEndpointIpAddress()
  {
    return $this->secondaryEndpointIpAddress;
  }
  /**
   * @param string
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RedisClusterInfo::class, 'Google_Service_NetworkManagement_RedisClusterInfo');
