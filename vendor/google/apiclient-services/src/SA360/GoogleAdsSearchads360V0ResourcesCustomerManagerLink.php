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

namespace Google\Service\SA360;

class GoogleAdsSearchads360V0ResourcesCustomerManagerLink extends \Google\Model
{
  /**
   * @var string
   */
  public $managerCustomer;
  /**
   * @var string
   */
  public $managerLinkId;
  /**
   * @var string
   */
  public $resourceName;
  /**
   * @var string
   */
  public $startTime;
  /**
   * @var string
   */
  public $status;

  /**
   * @param string
   */
  public function setManagerCustomer($managerCustomer)
  {
    $this->managerCustomer = $managerCustomer;
  }
  /**
   * @return string
   */
  public function getManagerCustomer()
  {
    return $this->managerCustomer;
  }
  /**
   * @param string
   */
  public function setManagerLinkId($managerLinkId)
  {
    $this->managerLinkId = $managerLinkId;
  }
  /**
   * @return string
   */
  public function getManagerLinkId()
  {
    return $this->managerLinkId;
  }
  /**
   * @param string
   */
  public function setResourceName($resourceName)
  {
    $this->resourceName = $resourceName;
  }
  /**
   * @return string
   */
  public function getResourceName()
  {
    return $this->resourceName;
  }
  /**
   * @param string
   */
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  /**
   * @return string
   */
  public function getStartTime()
  {
    return $this->startTime;
  }
  /**
   * @param string
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleAdsSearchads360V0ResourcesCustomerManagerLink::class, 'Google_Service_SA360_GoogleAdsSearchads360V0ResourcesCustomerManagerLink');
