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

namespace Google\Service\Compute;

class NetworkEndpoint extends \Google\Model
{
  /**
   * @var string[]
   */
  public $annotations;
  /**
   * @var int
   */
  public $clientDestinationPort;
  /**
   * @var string
   */
  public $fqdn;
  /**
   * @var string
   */
  public $instance;
  /**
   * @var string
   */
  public $ipAddress;
  /**
   * @var string
   */
  public $ipv6Address;
  /**
   * @var int
   */
  public $port;

  /**
   * @param string[]
   */
  public function setAnnotations($annotations)
  {
    $this->annotations = $annotations;
  }
  /**
   * @return string[]
   */
  public function getAnnotations()
  {
    return $this->annotations;
  }
  /**
   * @param int
   */
  public function setClientDestinationPort($clientDestinationPort)
  {
    $this->clientDestinationPort = $clientDestinationPort;
  }
  /**
   * @return int
   */
  public function getClientDestinationPort()
  {
    return $this->clientDestinationPort;
  }
  /**
   * @param string
   */
  public function setFqdn($fqdn)
  {
    $this->fqdn = $fqdn;
  }
  /**
   * @return string
   */
  public function getFqdn()
  {
    return $this->fqdn;
  }
  /**
   * @param string
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
  public function setIpv6Address($ipv6Address)
  {
    $this->ipv6Address = $ipv6Address;
  }
  /**
   * @return string
   */
  public function getIpv6Address()
  {
    return $this->ipv6Address;
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NetworkEndpoint::class, 'Google_Service_Compute_NetworkEndpoint');
