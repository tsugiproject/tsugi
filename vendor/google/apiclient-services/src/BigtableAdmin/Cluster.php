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

namespace Google\Service\BigtableAdmin;

class Cluster extends \Google\Model
{
  protected $clusterConfigType = ClusterConfig::class;
  protected $clusterConfigDataType = '';
  /**
   * @var string
   */
  public $defaultStorageType;
  protected $encryptionConfigType = EncryptionConfig::class;
  protected $encryptionConfigDataType = '';
  /**
   * @var string
   */
  public $location;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $nodeScalingFactor;
  /**
   * @var int
   */
  public $serveNodes;
  /**
   * @var string
   */
  public $state;

  /**
   * @param ClusterConfig
   */
  public function setClusterConfig(ClusterConfig $clusterConfig)
  {
    $this->clusterConfig = $clusterConfig;
  }
  /**
   * @return ClusterConfig
   */
  public function getClusterConfig()
  {
    return $this->clusterConfig;
  }
  /**
   * @param string
   */
  public function setDefaultStorageType($defaultStorageType)
  {
    $this->defaultStorageType = $defaultStorageType;
  }
  /**
   * @return string
   */
  public function getDefaultStorageType()
  {
    return $this->defaultStorageType;
  }
  /**
   * @param EncryptionConfig
   */
  public function setEncryptionConfig(EncryptionConfig $encryptionConfig)
  {
    $this->encryptionConfig = $encryptionConfig;
  }
  /**
   * @return EncryptionConfig
   */
  public function getEncryptionConfig()
  {
    return $this->encryptionConfig;
  }
  /**
   * @param string
   */
  public function setLocation($location)
  {
    $this->location = $location;
  }
  /**
   * @return string
   */
  public function getLocation()
  {
    return $this->location;
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
   * @param string
   */
  public function setNodeScalingFactor($nodeScalingFactor)
  {
    $this->nodeScalingFactor = $nodeScalingFactor;
  }
  /**
   * @return string
   */
  public function getNodeScalingFactor()
  {
    return $this->nodeScalingFactor;
  }
  /**
   * @param int
   */
  public function setServeNodes($serveNodes)
  {
    $this->serveNodes = $serveNodes;
  }
  /**
   * @return int
   */
  public function getServeNodes()
  {
    return $this->serveNodes;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Cluster::class, 'Google_Service_BigtableAdmin_Cluster');
