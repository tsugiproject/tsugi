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

class PolarisIcebergCatalog extends \Google\Model
{
  /**
   * Required. The Polaris client ID.
   *
   * @var string
   */
  public $clientId;
  /**
   * Optional. The Polaris client secret.
   *
   * @var string
   */
  public $clientSecret;
  /**
   * Required. The catalog name within Polaris.
   *
   * @var string
   */
  public $polarisCatalog;
  /**
   * Required. The Polaris principal role.
   *
   * @var string
   */
  public $principalRole;
  /**
   * Required. The Polaris uri.
   *
   * @var string
   */
  public $uri;

  /**
   * Required. The Polaris client ID.
   *
   * @param string $clientId
   */
  public function setClientId($clientId)
  {
    $this->clientId = $clientId;
  }
  /**
   * @return string
   */
  public function getClientId()
  {
    return $this->clientId;
  }
  /**
   * Optional. The Polaris client secret.
   *
   * @param string $clientSecret
   */
  public function setClientSecret($clientSecret)
  {
    $this->clientSecret = $clientSecret;
  }
  /**
   * @return string
   */
  public function getClientSecret()
  {
    return $this->clientSecret;
  }
  /**
   * Required. The catalog name within Polaris.
   *
   * @param string $polarisCatalog
   */
  public function setPolarisCatalog($polarisCatalog)
  {
    $this->polarisCatalog = $polarisCatalog;
  }
  /**
   * @return string
   */
  public function getPolarisCatalog()
  {
    return $this->polarisCatalog;
  }
  /**
   * Required. The Polaris principal role.
   *
   * @param string $principalRole
   */
  public function setPrincipalRole($principalRole)
  {
    $this->principalRole = $principalRole;
  }
  /**
   * @return string
   */
  public function getPrincipalRole()
  {
    return $this->principalRole;
  }
  /**
   * Required. The Polaris uri.
   *
   * @param string $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PolarisIcebergCatalog::class, 'Google_Service_OracleDatabase_PolarisIcebergCatalog');
