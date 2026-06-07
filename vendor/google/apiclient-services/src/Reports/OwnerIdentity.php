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

namespace Google\Service\Reports;

class OwnerIdentity extends \Google\Model
{
  protected $customerIdentityType = CustomerIdentity::class;
  protected $customerIdentityDataType = '';
  protected $groupIdentityType = GroupIdentity::class;
  protected $groupIdentityDataType = '';
  protected $userIdentityType = UserIdentity::class;
  protected $userIdentityDataType = '';

  /**
   * Identity of the Google Workspace customer who owns the resource.
   *
   * @param CustomerIdentity $customerIdentity
   */
  public function setCustomerIdentity(CustomerIdentity $customerIdentity)
  {
    $this->customerIdentity = $customerIdentity;
  }
  /**
   * @return CustomerIdentity
   */
  public function getCustomerIdentity()
  {
    return $this->customerIdentity;
  }
  /**
   * Identity of the group who owns the resource.
   *
   * @param GroupIdentity $groupIdentity
   */
  public function setGroupIdentity(GroupIdentity $groupIdentity)
  {
    $this->groupIdentity = $groupIdentity;
  }
  /**
   * @return GroupIdentity
   */
  public function getGroupIdentity()
  {
    return $this->groupIdentity;
  }
  /**
   * Identity of the user who owns the resource.
   *
   * @param UserIdentity $userIdentity
   */
  public function setUserIdentity(UserIdentity $userIdentity)
  {
    $this->userIdentity = $userIdentity;
  }
  /**
   * @return UserIdentity
   */
  public function getUserIdentity()
  {
    return $this->userIdentity;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(OwnerIdentity::class, 'Google_Service_Reports_OwnerIdentity');
