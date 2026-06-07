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

class GoldengateGoldengateConnectionProperties extends \Google\Model
{
  /**
   * Optional. The name of the GoldengateDeployment associated with the
   * GoldengateConnection. Format: projects/{project}/locations/{location}/golde
   * ngateDeployments/{goldengate_deployment}
   *
   * @var string
   */
  public $goldengateDeploymentId;
  /**
   * Optional. The host of the GoldengateConnection.
   *
   * @var string
   */
  public $host;
  /**
   * Optional. Input only. The password used to connect to the Oracle Goldengate
   * in plain text.
   *
   * @var string
   */
  public $password;
  /**
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password used to connect to the Oracle
   * Goldengate. Format: projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @var string
   */
  public $passwordSecretVersion;
  /**
   * Optional. The port of the GoldengateConnection.
   *
   * @var int
   */
  public $port;
  /**
   * Optional. The technology type.
   *
   * @var string
   */
  public $technologyType;
  /**
   * Optional. The username credential.
   *
   * @var string
   */
  public $username;

  /**
   * Optional. The name of the GoldengateDeployment associated with the
   * GoldengateConnection. Format: projects/{project}/locations/{location}/golde
   * ngateDeployments/{goldengate_deployment}
   *
   * @param string $goldengateDeploymentId
   */
  public function setGoldengateDeploymentId($goldengateDeploymentId)
  {
    $this->goldengateDeploymentId = $goldengateDeploymentId;
  }
  /**
   * @return string
   */
  public function getGoldengateDeploymentId()
  {
    return $this->goldengateDeploymentId;
  }
  /**
   * Optional. The host of the GoldengateConnection.
   *
   * @param string $host
   */
  public function setHost($host)
  {
    $this->host = $host;
  }
  /**
   * @return string
   */
  public function getHost()
  {
    return $this->host;
  }
  /**
   * Optional. Input only. The password used to connect to the Oracle Goldengate
   * in plain text.
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
   * Manager which contains the password used to connect to the Oracle
   * Goldengate. Format: projects/{project}/secrets/{secret}/versions/{version}.
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
   * Optional. The port of the GoldengateConnection.
   *
   * @param int $port
   */
  public function setPort($port)
  {
    $this->port = $port;
  }
  /**
   * @return int
   */
  public function getPort()
  {
    return $this->port;
  }
  /**
   * Optional. The technology type.
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
   * Optional. The username credential.
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
class_alias(GoldengateGoldengateConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateGoldengateConnectionProperties');
