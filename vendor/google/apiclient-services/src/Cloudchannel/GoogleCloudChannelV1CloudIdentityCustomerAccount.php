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

namespace Google\Service\Cloudchannel;

class GoogleCloudChannelV1CloudIdentityCustomerAccount extends \Google\Model
{
  /**
   * @var string
   */
  public $channelPartnerCloudIdentityId;
  /**
   * @var string
   */
  public $customerCloudIdentityId;
  /**
   * @var string
   */
  public $customerName;
  /**
   * @var string
   */
  public $customerType;
  /**
   * @var bool
   */
  public $existing;
  /**
   * @var bool
   */
  public $owned;

  /**
   * @param string
   */
  public function setChannelPartnerCloudIdentityId($channelPartnerCloudIdentityId)
  {
    $this->channelPartnerCloudIdentityId = $channelPartnerCloudIdentityId;
  }
  /**
   * @return string
   */
  public function getChannelPartnerCloudIdentityId()
  {
    return $this->channelPartnerCloudIdentityId;
  }
  /**
   * @param string
   */
  public function setCustomerCloudIdentityId($customerCloudIdentityId)
  {
    $this->customerCloudIdentityId = $customerCloudIdentityId;
  }
  /**
   * @return string
   */
  public function getCustomerCloudIdentityId()
  {
    return $this->customerCloudIdentityId;
  }
  /**
   * @param string
   */
  public function setCustomerName($customerName)
  {
    $this->customerName = $customerName;
  }
  /**
   * @return string
   */
  public function getCustomerName()
  {
    return $this->customerName;
  }
  /**
   * @param string
   */
  public function setCustomerType($customerType)
  {
    $this->customerType = $customerType;
  }
  /**
   * @return string
   */
  public function getCustomerType()
  {
    return $this->customerType;
  }
  /**
   * @param bool
   */
  public function setExisting($existing)
  {
    $this->existing = $existing;
  }
  /**
   * @return bool
   */
  public function getExisting()
  {
    return $this->existing;
  }
  /**
   * @param bool
   */
  public function setOwned($owned)
  {
    $this->owned = $owned;
  }
  /**
   * @return bool
   */
  public function getOwned()
  {
    return $this->owned;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudChannelV1CloudIdentityCustomerAccount::class, 'Google_Service_Cloudchannel_GoogleCloudChannelV1CloudIdentityCustomerAccount');
