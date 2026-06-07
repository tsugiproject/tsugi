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

class GoldengateDatabricksConnectionProperties extends \Google\Model
{
  /**
   * Authentication type not specified.
   */
  public const AUTHENTICATION_TYPE_DATABRICKS_AUTHENTICATION_TYPE_UNSPECIFIED = 'DATABRICKS_AUTHENTICATION_TYPE_UNSPECIFIED';
  /**
   * Personal access token authentication.
   */
  public const AUTHENTICATION_TYPE_PERSONAL_ACCESS_TOKEN = 'PERSONAL_ACCESS_TOKEN';
  /**
   * OAuth M2M authentication.
   */
  public const AUTHENTICATION_TYPE_OAUTH_M2M = 'OAUTH_M2M';
  /**
   * Optional. Authentication type for Databricks.
   *
   * @var string
   */
  public $authenticationType;
  /**
   * Optional. OAuth client id, only applicable for authentication_type ==
   * OAUTH_M2M
   *
   * @var string
   */
  public $clientId;
  /**
   * Optional. OAuth client secret, only applicable for authentication_type ==
   * OAUTH_M2M
   *
   * @var string
   */
  public $clientSecret;
  /**
   * Optional. Connection URL. e.g.: 'jdbc:databricks://adb-
   * 33934.4.azuredatabricks.net:443/default;transportMode=http;ssl=1;httpPath=s
   * ql/protocolv1/o/3393########44/0##3-7-hlrb'
   *
   * @var string
   */
  public $connectionUrl;
  /**
   * Optional. Input only. The password used to connect to Databricks in plain
   * text.
   *
   * @var string
   */
  public $password;
  /**
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password used to connect to Databricks. Format:
   * projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @var string
   */
  public $passwordSecretVersion;
  /**
   * Optional. External storage credential name to access files on object
   * storage such as ADLS Gen2, S3 or Cloud Storage.
   *
   * @var string
   */
  public $storageCredential;
  /**
   * Optional. The technology type of DatabricksConnection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Optional. Authentication type for Databricks.
   *
   * Accepted values: DATABRICKS_AUTHENTICATION_TYPE_UNSPECIFIED,
   * PERSONAL_ACCESS_TOKEN, OAUTH_M2M
   *
   * @param self::AUTHENTICATION_TYPE_* $authenticationType
   */
  public function setAuthenticationType($authenticationType)
  {
    $this->authenticationType = $authenticationType;
  }
  /**
   * @return self::AUTHENTICATION_TYPE_*
   */
  public function getAuthenticationType()
  {
    return $this->authenticationType;
  }
  /**
   * Optional. OAuth client id, only applicable for authentication_type ==
   * OAUTH_M2M
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
   * Optional. OAuth client secret, only applicable for authentication_type ==
   * OAUTH_M2M
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
   * Optional. Connection URL. e.g.: 'jdbc:databricks://adb-
   * 33934.4.azuredatabricks.net:443/default;transportMode=http;ssl=1;httpPath=s
   * ql/protocolv1/o/3393########44/0##3-7-hlrb'
   *
   * @param string $connectionUrl
   */
  public function setConnectionUrl($connectionUrl)
  {
    $this->connectionUrl = $connectionUrl;
  }
  /**
   * @return string
   */
  public function getConnectionUrl()
  {
    return $this->connectionUrl;
  }
  /**
   * Optional. Input only. The password used to connect to Databricks in plain
   * text.
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
   * Manager which contains the password used to connect to Databricks. Format:
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
   * Optional. External storage credential name to access files on object
   * storage such as ADLS Gen2, S3 or Cloud Storage.
   *
   * @param string $storageCredential
   */
  public function setStorageCredential($storageCredential)
  {
    $this->storageCredential = $storageCredential;
  }
  /**
   * @return string
   */
  public function getStorageCredential()
  {
    return $this->storageCredential;
  }
  /**
   * Optional. The technology type of DatabricksConnection.
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDatabricksConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateDatabricksConnectionProperties');
