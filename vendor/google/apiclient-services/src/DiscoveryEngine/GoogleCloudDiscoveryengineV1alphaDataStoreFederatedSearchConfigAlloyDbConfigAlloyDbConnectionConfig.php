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

class GoogleCloudDiscoveryengineV1alphaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig extends \Google\Model
{
  public const AUTH_MODE_AUTH_MODE_UNSPECIFIED = 'AUTH_MODE_UNSPECIFIED';
  /**
   * Uses P4SA when VAIS talks to AlloyDB.
   */
  public const AUTH_MODE_AUTH_MODE_SERVICE_ACCOUNT = 'AUTH_MODE_SERVICE_ACCOUNT';
  /**
   * Uses EUC when VAIS talks to AlloyDB.
   */
  public const AUTH_MODE_AUTH_MODE_END_USER_ACCOUNT = 'AUTH_MODE_END_USER_ACCOUNT';
  /**
   * Optional. Auth mode.
   *
   * @var string
   */
  public $authMode;
  /**
   * Required. The AlloyDB database to connect to.
   *
   * @var string
   */
  public $database;
  /**
   * Optional. If true, enable PSVS for AlloyDB.
   *
   * @var bool
   */
  public $enablePsvs;
  /**
   * Required. The AlloyDB instance to connect to.
   *
   * @var string
   */
  public $instance;
  /**
   * Required. Database password. If auth_mode = END_USER_ACCOUNT, it can be
   * unset. In that case, the password will be inferred on the AlloyDB side,
   * based on the authenticated user.
   *
   * @var string
   */
  public $password;
  /**
   * Required. Database user. If auth_mode = END_USER_ACCOUNT, it can be unset.
   * In that case, the user will be inferred on the AlloyDB side, based on the
   * authenticated user.
   *
   * @var string
   */
  public $user;

  /**
   * Optional. Auth mode.
   *
   * Accepted values: AUTH_MODE_UNSPECIFIED, AUTH_MODE_SERVICE_ACCOUNT,
   * AUTH_MODE_END_USER_ACCOUNT
   *
   * @param self::AUTH_MODE_* $authMode
   */
  public function setAuthMode($authMode)
  {
    $this->authMode = $authMode;
  }
  /**
   * @return self::AUTH_MODE_*
   */
  public function getAuthMode()
  {
    return $this->authMode;
  }
  /**
   * Required. The AlloyDB database to connect to.
   *
   * @param string $database
   */
  public function setDatabase($database)
  {
    $this->database = $database;
  }
  /**
   * @return string
   */
  public function getDatabase()
  {
    return $this->database;
  }
  /**
   * Optional. If true, enable PSVS for AlloyDB.
   *
   * @param bool $enablePsvs
   */
  public function setEnablePsvs($enablePsvs)
  {
    $this->enablePsvs = $enablePsvs;
  }
  /**
   * @return bool
   */
  public function getEnablePsvs()
  {
    return $this->enablePsvs;
  }
  /**
   * Required. The AlloyDB instance to connect to.
   *
   * @param string $instance
   */
  public function setInstance($instance)
  {
    $this->instance = $instance;
  }
  /**
   * @return string
   */
  public function getInstance()
  {
    return $this->instance;
  }
  /**
   * Required. Database password. If auth_mode = END_USER_ACCOUNT, it can be
   * unset. In that case, the password will be inferred on the AlloyDB side,
   * based on the authenticated user.
   *
   * @param string $password
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
   * Required. Database user. If auth_mode = END_USER_ACCOUNT, it can be unset.
   * In that case, the user will be inferred on the AlloyDB side, based on the
   * authenticated user.
   *
   * @param string $user
   */
  public function setUser($user)
  {
    $this->user = $user;
  }
  /**
   * @return string
   */
  public function getUser()
  {
    return $this->user;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig');
