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

namespace Google\Service\CloudDomains;

class ManagementSettings extends \Google\Model
{
  /**
   * @var string
   */
  public $effectiveTransferLockState;
  /**
   * @var string
   */
  public $preferredRenewalMethod;
  /**
   * @var string
   */
  public $renewalMethod;
  /**
   * @var string
   */
  public $transferLockState;

  /**
   * @param string
   */
  public function setEffectiveTransferLockState($effectiveTransferLockState)
  {
    $this->effectiveTransferLockState = $effectiveTransferLockState;
  }
  /**
   * @return string
   */
  public function getEffectiveTransferLockState()
  {
    return $this->effectiveTransferLockState;
  }
  /**
   * @param string
   */
  public function setPreferredRenewalMethod($preferredRenewalMethod)
  {
    $this->preferredRenewalMethod = $preferredRenewalMethod;
  }
  /**
   * @return string
   */
  public function getPreferredRenewalMethod()
  {
    return $this->preferredRenewalMethod;
  }
  /**
   * @param string
   */
  public function setRenewalMethod($renewalMethod)
  {
    $this->renewalMethod = $renewalMethod;
  }
  /**
   * @return string
   */
  public function getRenewalMethod()
  {
    return $this->renewalMethod;
  }
  /**
   * @param string
   */
  public function setTransferLockState($transferLockState)
  {
    $this->transferLockState = $transferLockState;
  }
  /**
   * @return string
   */
  public function getTransferLockState()
  {
    return $this->transferLockState;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ManagementSettings::class, 'Google_Service_CloudDomains_ManagementSettings');
