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

class MasterAuthorizedNetworksConfig extends \Google\Collection
{
  protected $collection_key = 'cidrBlocks';
  protected $cidrBlocksType = CidrBlock::class;
  protected $cidrBlocksDataType = 'array';
  /**
   * @var bool
   */
  public $enabled;
  /**
   * @var bool
   */
  public $gcpPublicCidrsAccessEnabled;
  /**
   * @var bool
   */
  public $privateEndpointEnforcementEnabled;

  /**
   * @param CidrBlock[]
   */
  public function setCidrBlocks($cidrBlocks)
  {
    $this->cidrBlocks = $cidrBlocks;
  }
  /**
   * @return CidrBlock[]
   */
  public function getCidrBlocks()
  {
    return $this->cidrBlocks;
  }
  /**
   * @param bool
   */
  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  /**
   * @return bool
   */
  public function getEnabled()
  {
    return $this->enabled;
  }
  /**
   * @param bool
   */
  public function setGcpPublicCidrsAccessEnabled($gcpPublicCidrsAccessEnabled)
  {
    $this->gcpPublicCidrsAccessEnabled = $gcpPublicCidrsAccessEnabled;
  }
  /**
   * @return bool
   */
  public function getGcpPublicCidrsAccessEnabled()
  {
    return $this->gcpPublicCidrsAccessEnabled;
  }
  /**
   * @param bool
   */
  public function setPrivateEndpointEnforcementEnabled($privateEndpointEnforcementEnabled)
  {
    $this->privateEndpointEnforcementEnabled = $privateEndpointEnforcementEnabled;
  }
  /**
   * @return bool
   */
  public function getPrivateEndpointEnforcementEnabled()
  {
    return $this->privateEndpointEnforcementEnabled;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MasterAuthorizedNetworksConfig::class, 'Google_Service_Container_MasterAuthorizedNetworksConfig');
