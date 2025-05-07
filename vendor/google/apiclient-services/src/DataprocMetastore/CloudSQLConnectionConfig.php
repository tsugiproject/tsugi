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

namespace Google\Service\DataprocMetastore;

class CloudSQLConnectionConfig extends \Google\Model
{
  /**
   * @var string
   */
  public $hiveDatabaseName;
  /**
   * @var string
   */
  public $instanceConnectionName;
  /**
   * @var string
   */
  public $ipAddress;
  /**
   * @var string
   */
  public $natSubnet;
  /**
   * @var string
   */
  public $password;
  /**
   * @var int
   */
  public $port;
  /**
   * @var string
   */
  public $proxySubnet;
  /**
   * @var string
   */
  public $username;

  /**
   * @param string
   */
  public function setHiveDatabaseName($hiveDatabaseName)
  {
    $this->hiveDatabaseName = $hiveDatabaseName;
  }
  /**
   * @return string
   */
  public function getHiveDatabaseName()
  {
    return $this->hiveDatabaseName;
  }
  /**
   * @param string
   */
  public function setInstanceConnectionName($instanceConnectionName)
  {
    $this->instanceConnectionName = $instanceConnectionName;
  }
  /**
   * @return string
   */
  public function getInstanceConnectionName()
  {
    return $this->instanceConnectionName;
  }
  /**
   * @param string
   */
  public function setIpAddress($ipAddress)
  {
    $this->ipAddress = $ipAddress;
  }
  /**
   * @return string
   */
  public function getIpAddress()
  {
    return $this->ipAddress;
  }
  /**
   * @param string
   */
  public function setNatSubnet($natSubnet)
  {
    $this->natSubnet = $natSubnet;
  }
  /**
   * @return string
   */
  public function getNatSubnet()
  {
    return $this->natSubnet;
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
   * @param int
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
   * @param string
   */
  public function setProxySubnet($proxySubnet)
  {
    $this->proxySubnet = $proxySubnet;
  }
  /**
   * @return string
   */
  public function getProxySubnet()
  {
    return $this->proxySubnet;
  }
  /**
   * @param string
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
class_alias(CloudSQLConnectionConfig::class, 'Google_Service_DataprocMetastore_CloudSQLConnectionConfig');
