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

namespace Google\Service\VMMigrationService;

class NetworkInterface extends \Google\Model
{
  /**
   * @var string
   */
  public $externalIp;
  /**
   * @var string
   */
  public $internalIp;
  /**
   * @var string
   */
  public $network;
  /**
   * @var string
   */
  public $networkTier;
  /**
   * @var string
   */
  public $subnetwork;

  /**
   * @param string
   */
  public function setExternalIp($externalIp)
  {
    $this->externalIp = $externalIp;
  }
  /**
   * @return string
   */
  public function getExternalIp()
  {
    return $this->externalIp;
  }
  /**
   * @param string
   */
  public function setInternalIp($internalIp)
  {
    $this->internalIp = $internalIp;
  }
  /**
   * @return string
   */
  public function getInternalIp()
  {
    return $this->internalIp;
  }
  /**
   * @param string
   */
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  /**
   * @return string
   */
  public function getNetwork()
  {
    return $this->network;
  }
  /**
   * @param string
   */
  public function setNetworkTier($networkTier)
  {
    $this->networkTier = $networkTier;
  }
  /**
   * @return string
   */
  public function getNetworkTier()
  {
    return $this->networkTier;
  }
  /**
   * @param string
   */
  public function setSubnetwork($subnetwork)
  {
    $this->subnetwork = $subnetwork;
  }
  /**
   * @return string
   */
  public function getSubnetwork()
  {
    return $this->subnetwork;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NetworkInterface::class, 'Google_Service_VMMigrationService_NetworkInterface');
