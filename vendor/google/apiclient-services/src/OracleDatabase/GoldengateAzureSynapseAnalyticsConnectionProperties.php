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

class GoldengateAzureSynapseAnalyticsConnectionProperties extends \Google\Model
{
  /**
   * Optional. JDBC connection string. e.g.: 'jdbc:sqlserver://.sql.azuresynapse
   * .net:1433;database=;encrypt=true;trustServerCertificate=false;hostNameInCer
   * tificate=*.sql.azuresynapse.net;loginTimeout=300;'
   *
   * @var string
   */
  public $connectionString;
  /**
   * Optional. Input only. The password Oracle Goldengate uses for Azure Synapse
   * Analytics connection in plain text.
   *
   * @var string
   */
  public $password;
  /**
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password Oracle Goldengate uses for Azure
   * Synapse Analytics connection. Format:
   * projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @var string
   */
  public $passwordSecretVersion;
  /**
   * Optional. The technology type of AzureSynapseAnalyticsConnection.
   *
   * @var string
   */
  public $technologyType;
  /**
   * Optional. The username Oracle Goldengate uses to connect the associated
   * system of the given technology.
   *
   * @var string
   */
  public $username;

  /**
   * Optional. JDBC connection string. e.g.: 'jdbc:sqlserver://.sql.azuresynapse
   * .net:1433;database=;encrypt=true;trustServerCertificate=false;hostNameInCer
   * tificate=*.sql.azuresynapse.net;loginTimeout=300;'
   *
   * @param string $connectionString
   */
  public function setConnectionString($connectionString)
  {
    $this->connectionString = $connectionString;
  }
  /**
   * @return string
   */
  public function getConnectionString()
  {
    return $this->connectionString;
  }
  /**
   * Optional. Input only. The password Oracle Goldengate uses for Azure Synapse
   * Analytics connection in plain text.
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
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password Oracle Goldengate uses for Azure
   * Synapse Analytics connection. Format:
   * projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @param string $passwordSecretVersion
   */
  public function setPasswordSecretVersion($passwordSecretVersion)
  {
    $this->passwordSecretVersion = $passwordSecretVersion;
  }
  /**
   * @return string
   */
  public function getPasswordSecretVersion()
  {
    return $this->passwordSecretVersion;
  }
  /**
   * Optional. The technology type of AzureSynapseAnalyticsConnection.
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
   * Optional. The username Oracle Goldengate uses to connect the associated
   * system of the given technology.
   *
   * @param string $username
   */
  public function setUsername($username)
  {
    $this->username = $username;
  }
  /**
   * @return string
   */
  public function getUsername()
  {
    return $this->username;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateAzureSynapseAnalyticsConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateAzureSynapseAnalyticsConnectionProperties');
