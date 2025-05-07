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

namespace Google\Service\CloudDeploy;

class GkeCluster extends \Google\Model
{
  /**
   * @var string
   */
  public $cluster;
  /**
   * @var bool
   */
  public $dnsEndpoint;
  /**
   * @var bool
   */
  public $internalIp;
  /**
   * @var string
   */
  public $proxyUrl;

  /**
   * @param string
   */
  public function setCluster($cluster)
  {
    $this->cluster = $cluster;
  }
  /**
   * @return string
   */
  public function getCluster()
  {
    return $this->cluster;
  }
  /**
   * @param bool
   */
  public function setDnsEndpoint($dnsEndpoint)
  {
    $this->dnsEndpoint = $dnsEndpoint;
  }
  /**
   * @return bool
   */
  public function getDnsEndpoint()
  {
    return $this->dnsEndpoint;
  }
  /**
   * @param bool
   */
  public function setInternalIp($internalIp)
  {
    $this->internalIp = $internalIp;
  }
  /**
   * @return bool
   */
  public function getInternalIp()
  {
    return $this->internalIp;
  }
  /**
   * @param string
   */
  public function setProxyUrl($proxyUrl)
  {
    $this->proxyUrl = $proxyUrl;
  }
  /**
   * @return string
   */
  public function getProxyUrl()
  {
    return $this->proxyUrl;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GkeCluster::class, 'Google_Service_CloudDeploy_GkeCluster');
