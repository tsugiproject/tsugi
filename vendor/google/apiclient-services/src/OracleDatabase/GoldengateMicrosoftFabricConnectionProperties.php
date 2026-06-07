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

class GoldengateMicrosoftFabricConnectionProperties extends \Google\Model
{
  /**
   * Optional. Azure client ID of the application.
   *
   * @var string
   */
  public $clientId;
  /**
   * Optional. Client secret associated with the client id.
   *
   * @var string
   */
  public $clientSecret;
  /**
   * Optional. Optional Microsoft Fabric service endpoint. Default value:
   * https://onelake.dfs.fabric.microsoft.com
   *
   * @var string
   */
  public $endpoint;
  /**
   * Optional. The technology type of MicrosoftFabricConnection.
   *
   * @var string
   */
  public $technologyType;
  /**
   * Optional. Azure tenant ID of the application.
   *
   * @var string
   */
  public $tenantId;

  /**
   * Optional. Azure client ID of the application.
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
   * Optional. Client secret associated with the client id.
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
   * Optional. Optional Microsoft Fabric service endpoint. Default value:
   * https://onelake.dfs.fabric.microsoft.com
   *
   * @param string $endpoint
   */
  public function setEndpoint($endpoint)
  {
    $this->endpoint = $endpoint;
  }
  /**
   * @return string
   */
  public function getEndpoint()
  {
    return $this->endpoint;
  }
  /**
   * Optional. The technology type of MicrosoftFabricConnection.
   *
   * @param string $technologyType
   */
  public function setTechnologyType($technologyType)
  {
    $this->technologyType = $technologyType;
  }
  /**
   * @return string
   */
  public function getTechnologyType()
  {
    return $this->technologyType;
  }
  /**
   * Optional. Azure tenant ID of the application.
   *
   * @param string $tenantId
   */
  public function setTenantId($tenantId)
  {
    $this->tenantId = $tenantId;
  }
  /**
   * @return string
   */
  public function getTenantId()
  {
    return $this->tenantId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateMicrosoftFabricConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateMicrosoftFabricConnectionProperties');
