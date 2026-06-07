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

class GoldengateSnowflakeConnectionProperties extends \Google\Model
{
  /**
   * Authentication type not specified.
   */
  public const AUTHENTICATION_TYPE_AUTHENTICATION_TYPE_UNSPECIFIED = 'AUTHENTICATION_TYPE_UNSPECIFIED';
  /**
   * Basic authentication.
   */
  public const AUTHENTICATION_TYPE_BASIC = 'BASIC';
  /**
   * Key pair authentication.
   */
  public const AUTHENTICATION_TYPE_KEY_PAIR = 'KEY_PAIR';
  /**
   * Optional. Used authentication mechanism to access Snowflake.
   *
   * @var string
   */
  public $authenticationType;
  /**
   * @var string
   */
  public $connectionUrl;
  /**
   * Optional. Input only. The password Oracle Goldengate uses to connect to
   * Snowflake platform in plain text.
   *
   * @var string
   */
  public $password;
  /**
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password Oracle Goldengate uses to connect to
   * Snowflake platform. Format:
   * projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @var string
   */
  public $passwordSecretVersion;
  /**
   * Optional. The content of private key file in PEM format.
   *
   * @var string
   */
  public $privateKeyFile;
  /**
   * Optional. Password if the private key file is encrypted.
   *
   * @var string
   */
  public $privateKeyPassphraseSecret;
  /**
   * Optional. The technology type of SnowflakeConnection.
   *
   * @var string
   */
  public $technologyType;
  /**
   * Optional. The username Oracle Goldengate uses to connect to Snowflake.
   *
   * @var string
   */
  public $username;

  /**
   * Optional. Used authentication mechanism to access Snowflake.
   *
   * Accepted values: AUTHENTICATION_TYPE_UNSPECIFIED, BASIC, KEY_PAIR
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
   * Optional. Input only. The password Oracle Goldengate uses to connect to
   * Snowflake platform in plain text.
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
   * Manager which contains the password Oracle Goldengate uses to connect to
   * Snowflake platform. Format:
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
   * Optional. The content of private key file in PEM format.
   *
   * @param string $privateKeyFile
   */
  public function setPrivateKeyFile($privateKeyFile)
  {
    $this->privateKeyFile = $privateKeyFile;
  }
  /**
   * @return string
   */
  public function getPrivateKeyFile()
  {
    return $this->privateKeyFile;
  }
  /**
   * Optional. Password if the private key file is encrypted.
   *
   * @param string $privateKeyPassphraseSecret
   */
  public function setPrivateKeyPassphraseSecret($privateKeyPassphraseSecret)
  {
    $this->privateKeyPassphraseSecret = $privateKeyPassphraseSecret;
  }
  /**
   * @return string
   */
  public function getPrivateKeyPassphraseSecret()
  {
    return $this->privateKeyPassphraseSecret;
  }
  /**
   * Optional. The technology type of SnowflakeConnection.
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
   * Optional. The username Oracle Goldengate uses to connect to Snowflake.
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
class_alias(GoldengateSnowflakeConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateSnowflakeConnectionProperties');
