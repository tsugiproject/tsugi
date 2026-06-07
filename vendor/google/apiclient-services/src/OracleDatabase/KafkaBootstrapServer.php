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

class KafkaBootstrapServer extends \Google\Model
{
  /**
   * Required. The name or address of a host.
   *
   * @var string
   */
  public $host;
  /**
   * Optional. The port of an endpoint usually specified for a connection.
   *
   * @var int
   */
  public $port;
  /**
   * Optional. The private IP address of the connection's endpoint in the
   * customer's VCN, typically a database endpoint or a big data endpoint (e.g.
   * Kafka bootstrap server). In case the privateIp is provided, the subnetId
   * must also be provided. In case the privateIp (and the subnetId) is not
   * provided it is assumed the datasource is publicly accessible. In case the
   * connection is accessible only privately, the lack of privateIp will result
   * in not being able to access the connection.
   *
   * @var string
   */
  public $privateIpAddress;

  /**
   * Required. The name or address of a host.
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
   * Optional. The port of an endpoint usually specified for a connection.
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
   * Optional. The private IP address of the connection's endpoint in the
   * customer's VCN, typically a database endpoint or a big data endpoint (e.g.
   * Kafka bootstrap server). In case the privateIp is provided, the subnetId
   * must also be provided. In case the privateIp (and the subnetId) is not
   * provided it is assumed the datasource is publicly accessible. In case the
   * connection is accessible only privately, the lack of privateIp will result
   * in not being able to access the connection.
   *
   * @param string $privateIpAddress
   */
  public function setPrivateIpAddress($privateIpAddress)
  {
    $this->privateIpAddress = $privateIpAddress;
  }
  /**
   * @return string
   */
  public function getPrivateIpAddress()
  {
    return $this->privateIpAddress;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(KafkaBootstrapServer::class, 'Google_Service_OracleDatabase_KafkaBootstrapServer');
