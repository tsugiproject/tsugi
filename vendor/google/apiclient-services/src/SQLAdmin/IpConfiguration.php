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

namespace Google\Service\SQLAdmin;

class IpConfiguration extends \Google\Collection
{
  protected $collection_key = 'customSubjectAlternativeNames';
  /**
   * @var string
   */
  public $allocatedIpRange;
  protected $authorizedNetworksType = AclEntry::class;
  protected $authorizedNetworksDataType = 'array';
  /**
   * @var string[]
   */
  public $customSubjectAlternativeNames;
  /**
   * @var bool
   */
  public $enablePrivatePathForGoogleCloudServices;
  /**
   * @var bool
   */
  public $ipv4Enabled;
  /**
   * @var string
   */
  public $privateNetwork;
  protected $pscConfigType = PscConfig::class;
  protected $pscConfigDataType = '';
  /**
   * @var bool
   */
  public $requireSsl;
  /**
   * @var string
   */
  public $serverCaMode;
  /**
   * @var string
   */
  public $serverCaPool;
  /**
   * @var string
   */
  public $sslMode;

  /**
   * @param string
   */
  public function setAllocatedIpRange($allocatedIpRange)
  {
    $this->allocatedIpRange = $allocatedIpRange;
  }
  /**
   * @return string
   */
  public function getAllocatedIpRange()
  {
    return $this->allocatedIpRange;
  }
  /**
   * @param AclEntry[]
   */
  public function setAuthorizedNetworks($authorizedNetworks)
  {
    $this->authorizedNetworks = $authorizedNetworks;
  }
  /**
   * @return AclEntry[]
   */
  public function getAuthorizedNetworks()
  {
    return $this->authorizedNetworks;
  }
  /**
   * @param string[]
   */
  public function setCustomSubjectAlternativeNames($customSubjectAlternativeNames)
  {
    $this->customSubjectAlternativeNames = $customSubjectAlternativeNames;
  }
  /**
   * @return string[]
   */
  public function getCustomSubjectAlternativeNames()
  {
    return $this->customSubjectAlternativeNames;
  }
  /**
   * @param bool
   */
  public function setEnablePrivatePathForGoogleCloudServices($enablePrivatePathForGoogleCloudServices)
  {
    $this->enablePrivatePathForGoogleCloudServices = $enablePrivatePathForGoogleCloudServices;
  }
  /**
   * @return bool
   */
  public function getEnablePrivatePathForGoogleCloudServices()
  {
    return $this->enablePrivatePathForGoogleCloudServices;
  }
  /**
   * @param bool
   */
  public function setIpv4Enabled($ipv4Enabled)
  {
    $this->ipv4Enabled = $ipv4Enabled;
  }
  /**
   * @return bool
   */
  public function getIpv4Enabled()
  {
    return $this->ipv4Enabled;
  }
  /**
   * @param string
   */
  public function setPrivateNetwork($privateNetwork)
  {
    $this->privateNetwork = $privateNetwork;
  }
  /**
   * @return string
   */
  public function getPrivateNetwork()
  {
    return $this->privateNetwork;
  }
  /**
   * @param PscConfig
   */
  public function setPscConfig(PscConfig $pscConfig)
  {
    $this->pscConfig = $pscConfig;
  }
  /**
   * @return PscConfig
   */
  public function getPscConfig()
  {
    return $this->pscConfig;
  }
  /**
   * @param bool
   */
  public function setRequireSsl($requireSsl)
  {
    $this->requireSsl = $requireSsl;
  }
  /**
   * @return bool
   */
  public function getRequireSsl()
  {
    return $this->requireSsl;
  }
  /**
   * @param string
   */
  public function setServerCaMode($serverCaMode)
  {
    $this->serverCaMode = $serverCaMode;
  }
  /**
   * @return string
   */
  public function getServerCaMode()
  {
    return $this->serverCaMode;
  }
  /**
   * @param string
   */
  public function setServerCaPool($serverCaPool)
  {
    $this->serverCaPool = $serverCaPool;
  }
  /**
   * @return string
   */
  public function getServerCaPool()
  {
    return $this->serverCaPool;
  }
  /**
   * @param string
   */
  public function setSslMode($sslMode)
  {
    $this->sslMode = $sslMode;
  }
  /**
   * @return string
   */
  public function getSslMode()
  {
    return $this->sslMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IpConfiguration::class, 'Google_Service_SQLAdmin_IpConfiguration');
