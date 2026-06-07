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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementV1ProfileAppInstallInstance extends \Google\Model
{
  /**
   * Output only. The email of the profile.
   *
   * @var string
   */
  public $email;
  /**
   * Output only. The Chrome client side profile ID.
   *
   * @var string
   */
  public $profileId;
  /**
   * Output only. The organizational unit id of the profile.
   *
   * @var string
   */
  public $profileOrgUnitId;
  /**
   * Output only. Profile permanent ID is the unique identifier of a profile
   * within one customer.
   *
   * @var string
   */
  public $profilePermanentId;

  /**
   * Output only. The email of the profile.
   *
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }
  /**
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }
  /**
   * Output only. The Chrome client side profile ID.
   *
   * @param string $profileId
   */
  public function setProfileId($profileId)
  {
    $this->profileId = $profileId;
  }
  /**
   * @return string
   */
  public function getProfileId()
  {
    return $this->profileId;
  }
  /**
   * Output only. The organizational unit id of the profile.
   *
   * @param string $profileOrgUnitId
   */
  public function setProfileOrgUnitId($profileOrgUnitId)
  {
    $this->profileOrgUnitId = $profileOrgUnitId;
  }
  /**
   * @return string
   */
  public function getProfileOrgUnitId()
  {
    return $this->profileOrgUnitId;
  }
  /**
   * Output only. Profile permanent ID is the unique identifier of a profile
   * within one customer.
   *
   * @param string $profilePermanentId
   */
  public function setProfilePermanentId($profilePermanentId)
  {
    $this->profilePermanentId = $profilePermanentId;
  }
  /**
   * @return string
   */
  public function getProfilePermanentId()
  {
    return $this->profilePermanentId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementV1ProfileAppInstallInstance::class, 'Google_Service_ChromeManagement_GoogleChromeManagementV1ProfileAppInstallInstance');
