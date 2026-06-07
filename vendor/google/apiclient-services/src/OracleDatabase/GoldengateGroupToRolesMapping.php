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

namespace Google\Service\OracleDatabase;

class GoldengateGroupToRolesMapping extends \Google\Model
{
  /**
   * Output only. The administrator group id.
   *
   * @var string
   */
  public $administratorGroupId;
  /**
   * Output only. The operator group id.
   *
   * @var string
   */
  public $operatorGroupId;
  /**
   * Output only. The security group id.
   *
   * @var string
   */
  public $securityGroupId;
  /**
   * Output only. The user group id.
   *
   * @var string
   */
  public $userGroupId;

  /**
   * Output only. The administrator group id.
   *
   * @param string $administratorGroupId
   */
  public function setAdministratorGroupId($administratorGroupId)
  {
    $this->administratorGroupId = $administratorGroupId;
  }
  /**
   * @return string
   */
  public function getAdministratorGroupId()
  {
    return $this->administratorGroupId;
  }
  /**
   * Output only. The operator group id.
   *
   * @param string $operatorGroupId
   */
  public function setOperatorGroupId($operatorGroupId)
  {
    $this->operatorGroupId = $operatorGroupId;
  }
  /**
   * @return string
   */
  public function getOperatorGroupId()
  {
    return $this->operatorGroupId;
  }
  /**
   * Output only. The security group id.
   *
   * @param string $securityGroupId
   */
  public function setSecurityGroupId($securityGroupId)
  {
    $this->securityGroupId = $securityGroupId;
  }
  /**
   * @return string
   */
  public function getSecurityGroupId()
  {
    return $this->securityGroupId;
  }
  /**
   * Output only. The user group id.
   *
   * @param string $userGroupId
   */
  public function setUserGroupId($userGroupId)
  {
    $this->userGroupId = $userGroupId;
  }
  /**
   * @return string
   */
  public function getUserGroupId()
  {
    return $this->userGroupId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateGroupToRolesMapping::class, 'Google_Service_OracleDatabase_GoldengateGroupToRolesMapping');
