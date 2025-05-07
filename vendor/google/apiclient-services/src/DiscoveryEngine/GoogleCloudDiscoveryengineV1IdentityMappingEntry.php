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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1IdentityMappingEntry extends \Google\Model
{
  /**
   * @var string
   */
  public $externalIdentity;
  /**
   * @var string
   */
  public $groupId;
  /**
   * @var string
   */
  public $userId;

  /**
   * @param string
   */
  public function setExternalIdentity($externalIdentity)
  {
    $this->externalIdentity = $externalIdentity;
  }
  /**
   * @return string
   */
  public function getExternalIdentity()
  {
    return $this->externalIdentity;
  }
  /**
   * @param string
   */
  public function setGroupId($groupId)
  {
    $this->groupId = $groupId;
  }
  /**
   * @return string
   */
  public function getGroupId()
  {
    return $this->groupId;
  }
  /**
   * @param string
   */
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  /**
   * @return string
   */
  public function getUserId()
  {
    return $this->userId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1IdentityMappingEntry::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1IdentityMappingEntry');
