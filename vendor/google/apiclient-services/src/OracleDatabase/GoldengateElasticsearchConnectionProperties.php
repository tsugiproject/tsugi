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

class GoldengateElasticsearchConnectionProperties extends \Google\Model
{
  /**
   * Authentication type not specified.
   */
  public const AUTHENTICATION_TYPE_ELASTICSEARCH_AUTHENTICATION_TYPE_UNSPECIFIED = 'ELASTICSEARCH_AUTHENTICATION_TYPE_UNSPECIFIED';
  /**
   * No authentication.
   */
  public const AUTHENTICATION_TYPE_NONE = 'NONE';
  /**
   * Basic authentication.
   */
  public const AUTHENTICATION_TYPE_BASIC = 'BASIC';
  /**
   * Security protocol not specified.
   */
  public const SECURITY_PROTOCOL_ELASTICSEARCH_SECURITY_PROTOCOL_UNSPECIFIED = 'ELASTICSEARCH_SECURITY_PROTOCOL_UNSPECIFIED';
  /**
   * Plain text communication.
   */
  public const SECURITY_PROTOCOL_PLAIN = 'PLAIN';
  /**
   * Transport Layer Security.
   */
  public const SECURITY_PROTOCOL_TLS = 'TLS';
  /**
   * Optional. Authentication type for Elasticsearch.
   *
   * @var string
   */
  public $authenticationType;
  /**
   * Optional. Fingerprint required by TLS security protocol. Eg.:
   * '6152b2dfbff200f973c5074a5b91d06ab3b472c07c09a1ea57bb7fd406cdce9c'
   *
   * @var string
   */
  public $fingerprint;
  /**
   * Optional. Input only. The password Oracle Goldengate uses for Elastic
   * Search connection in plain text.
   *
   * @var string
   */
  public $password;
  /**
   * Optional. Input only. The resource name of a secret version in Secret
   * Manager which contains the password Oracle Goldengate uses for Elastic
   * Search connection. Format:
   * projects/{project}/secrets/{secret}/versions/{version}.
   *
   * @var string
   */
  public $passwordSecretVersion;
  /**
   * Optional. Security protocol for Elasticsearch.
   *
   * @var string
   */
  public $securityProtocol;
  /**
   * Optional. Comma separated list of Elasticsearch server addresses, specified
   * as host:port entries, where :port is optional. If port is not specified, it
   * defaults to 9200. Example:
   * "server1.example.com:4000,server2.example.com:4000"
   *
   * @var string
   */
  public $servers;
  /**
   * Optional. The technology type of ElasticsearchConnection.
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
   * Optional. Authentication type for Elasticsearch.
   *
   * Accepted values: ELASTICSEARCH_AUTHENTICATION_TYPE_UNSPECIFIED, NONE, BASIC
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
   * Optional. Fingerprint required by TLS security protocol. Eg.:
   * '6152b2dfbff200f973c5074a5b91d06ab3b472c07c09a1ea57bb7fd406cdce9c'
   *
   * @param string $fingerprint
   */
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  /**
   * @return string
   */
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  /**
   * Optional. Input only. The password Oracle Goldengate uses for Elastic
   * Search connection in plain text.
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
   * Manager which contains the password Oracle Goldengate uses for Elastic
   * Search connection. Format:
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
   * Optional. Security protocol for Elasticsearch.
   *
   * Accepted values: ELASTICSEARCH_SECURITY_PROTOCOL_UNSPECIFIED, PLAIN, TLS
   *
   * @param self::SECURITY_PROTOCOL_* $securityProtocol
   */
  public function setSecurityProtocol($securityProtocol)
  {
    $this->securityProtocol = $securityProtocol;
  }
  /**
   * @return self::SECURITY_PROTOCOL_*
   */
  public function getSecurityProtocol()
  {
    return $this->securityProtocol;
  }
  /**
   * Optional. Comma separated list of Elasticsearch server addresses, specified
   * as host:port entries, where :port is optional. If port is not specified, it
   * defaults to 9200. Example:
   * "server1.example.com:4000,server2.example.com:4000"
   *
   * @param string $servers
   */
  public function setServers($servers)
  {
    $this->servers = $servers;
  }
  /**
   * @return string
   */
  public function getServers()
  {
    return $this->servers;
  }
  /**
   * Optional. The technology type of ElasticsearchConnection.
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
class_alias(GoldengateElasticsearchConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateElasticsearchConnectionProperties');
