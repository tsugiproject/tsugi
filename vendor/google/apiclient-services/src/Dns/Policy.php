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

namespace Google\Service\Dns;

class Policy extends \Google\Collection
{
  protected $collection_key = 'networks';
  protected $alternativeNameServerConfigType = PolicyAlternativeNameServerConfig::class;
  protected $alternativeNameServerConfigDataType = '';
  /**
   * @var string
   */
  public $description;
  protected $dns64ConfigType = PolicyDns64Config::class;
  protected $dns64ConfigDataType = '';
  /**
   * @var bool
   */
  public $enableInboundForwarding;
  /**
   * @var bool
   */
  public $enableLogging;
  /**
   * @var string
   */
  public $id;
  /**
   * @var string
   */
  public $kind;
  /**
   * @var string
   */
  public $name;
  protected $networksType = PolicyNetwork::class;
  protected $networksDataType = 'array';

  /**
   * @param PolicyAlternativeNameServerConfig
   */
  public function setAlternativeNameServerConfig(PolicyAlternativeNameServerConfig $alternativeNameServerConfig)
  {
    $this->alternativeNameServerConfig = $alternativeNameServerConfig;
  }
  /**
   * @return PolicyAlternativeNameServerConfig
   */
  public function getAlternativeNameServerConfig()
  {
    return $this->alternativeNameServerConfig;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param PolicyDns64Config
   */
  public function setDns64Config(PolicyDns64Config $dns64Config)
  {
    $this->dns64Config = $dns64Config;
  }
  /**
   * @return PolicyDns64Config
   */
  public function getDns64Config()
  {
    return $this->dns64Config;
  }
  /**
   * @param bool
   */
  public function setEnableInboundForwarding($enableInboundForwarding)
  {
    $this->enableInboundForwarding = $enableInboundForwarding;
  }
  /**
   * @return bool
   */
  public function getEnableInboundForwarding()
  {
    return $this->enableInboundForwarding;
  }
  /**
   * @param bool
   */
  public function setEnableLogging($enableLogging)
  {
    $this->enableLogging = $enableLogging;
  }
  /**
   * @return bool
   */
  public function getEnableLogging()
  {
    return $this->enableLogging;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param string
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param PolicyNetwork[]
   */
  public function setNetworks($networks)
  {
    $this->networks = $networks;
  }
  /**
   * @return PolicyNetwork[]
   */
  public function getNetworks()
  {
    return $this->networks;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Policy::class, 'Google_Service_Dns_Policy');
