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

namespace Google\Service\BeyondCorp;

class GoogleCloudBeyondcorpSecuritygatewaysV1EndpointMatcher extends \Google\Collection
{
  protected $collection_key = 'ports';
  /**
   * @var string
   */
  public $hostname;
  /**
   * @var int[]
   */
  public $ports;

  /**
   * @param string
   */
  public function setHostname($hostname)
  {
    $this->hostname = $hostname;
  }
  /**
   * @return string
   */
  public function getHostname()
  {
    return $this->hostname;
  }
  /**
   * @param int[]
   */
  public function setPorts($ports)
  {
    $this->ports = $ports;
  }
  /**
   * @return int[]
   */
  public function getPorts()
  {
    return $this->ports;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudBeyondcorpSecuritygatewaysV1EndpointMatcher::class, 'Google_Service_BeyondCorp_GoogleCloudBeyondcorpSecuritygatewaysV1EndpointMatcher');
