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

namespace Google\Service\Backupdr;

class AliasIpRange extends \Google\Model
{
  /**
   * @var string
   */
  public $ipCidrRange;
  /**
   * @var string
   */
  public $subnetworkRangeName;

  /**
   * @param string
   */
  public function setIpCidrRange($ipCidrRange)
  {
    $this->ipCidrRange = $ipCidrRange;
  }
  /**
   * @return string
   */
  public function getIpCidrRange()
  {
    return $this->ipCidrRange;
  }
  /**
   * @param string
   */
  public function setSubnetworkRangeName($subnetworkRangeName)
  {
    $this->subnetworkRangeName = $subnetworkRangeName;
  }
  /**
   * @return string
   */
  public function getSubnetworkRangeName()
  {
    return $this->subnetworkRangeName;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AliasIpRange::class, 'Google_Service_Backupdr_AliasIpRange');
