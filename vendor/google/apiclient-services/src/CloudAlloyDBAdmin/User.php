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

namespace Google\Service\CloudAlloyDBAdmin;

class User extends \Google\Collection
{
  protected $collection_key = 'databaseRoles';
  /**
   * @var string[]
   */
  public $databaseRoles;
  /**
   * @var bool
   */
  public $keepExtraRoles;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $password;
  /**
   * @var string
   */
  public $userType;

  /**
   * @param string[]
   */
  public function setDatabaseRoles($databaseRoles)
  {
    $this->databaseRoles = $databaseRoles;
  }
  /**
   * @return string[]
   */
  public function getDatabaseRoles()
  {
    return $this->databaseRoles;
  }
  /**
   * @param bool
   */
  public function setKeepExtraRoles($keepExtraRoles)
  {
    $this->keepExtraRoles = $keepExtraRoles;
  }
  /**
   * @return bool
   */
  public function getKeepExtraRoles()
  {
    return $this->keepExtraRoles;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param string
   */
  public function setPassword($password)
  {
    $this->password = $password;
  }
  /**
   * @return string
   */
  public function getPassword()
  {
    return $this->password;
  }
  /**
   * @param string
   */
  public function setUserType($userType)
  {
    $this->userType = $userType;
  }
  /**
   * @return string
   */
  public function getUserType()
  {
    return $this->userType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(User::class, 'Google_Service_CloudAlloyDBAdmin_User');
