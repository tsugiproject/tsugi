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

namespace Google\Service\Spanner;

class Instance extends \Google\Collection
{
  protected $collection_key = 'replicaComputeCapacity';
  protected $autoscalingConfigType = AutoscalingConfig::class;
  protected $autoscalingConfigDataType = '';
  /**
   * @var string
   */
  public $config;
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $defaultBackupScheduleType;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $edition;
  /**
   * @var string[]
   */
  public $endpointUris;
  protected $freeInstanceMetadataType = FreeInstanceMetadata::class;
  protected $freeInstanceMetadataDataType = '';
  /**
   * @var string
   */
  public $instanceType;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  /**
   * @var int
   */
  public $nodeCount;
  /**
   * @var int
   */
  public $processingUnits;
  protected $replicaComputeCapacityType = ReplicaComputeCapacity::class;
  protected $replicaComputeCapacityDataType = 'array';
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param AutoscalingConfig
   */
  public function setAutoscalingConfig(AutoscalingConfig $autoscalingConfig)
  {
    $this->autoscalingConfig = $autoscalingConfig;
  }
  /**
   * @return AutoscalingConfig
   */
  public function getAutoscalingConfig()
  {
    return $this->autoscalingConfig;
  }
  /**
   * @param string
   */
  public function setConfig($config)
  {
    $this->config = $config;
  }
  /**
   * @return string
   */
  public function getConfig()
  {
    return $this->config;
  }
  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param string
   */
  public function setDefaultBackupScheduleType($defaultBackupScheduleType)
  {
    $this->defaultBackupScheduleType = $defaultBackupScheduleType;
  }
  /**
   * @return string
   */
  public function getDefaultBackupScheduleType()
  {
    return $this->defaultBackupScheduleType;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param string
   */
  public function setEdition($edition)
  {
    $this->edition = $edition;
  }
  /**
   * @return string
   */
  public function getEdition()
  {
    return $this->edition;
  }
  /**
   * @param string[]
   */
  public function setEndpointUris($endpointUris)
  {
    $this->endpointUris = $endpointUris;
  }
  /**
   * @return string[]
   */
  public function getEndpointUris()
  {
    return $this->endpointUris;
  }
  /**
   * @param FreeInstanceMetadata
   */
  public function setFreeInstanceMetadata(FreeInstanceMetadata $freeInstanceMetadata)
  {
    $this->freeInstanceMetadata = $freeInstanceMetadata;
  }
  /**
   * @return FreeInstanceMetadata
   */
  public function getFreeInstanceMetadata()
  {
    return $this->freeInstanceMetadata;
  }
  /**
   * @param string
   */
  public function setInstanceType($instanceType)
  {
    $this->instanceType = $instanceType;
  }
  /**
   * @return string
   */
  public function getInstanceType()
  {
    return $this->instanceType;
  }
  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
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
   * @param int
   */
  public function setNodeCount($nodeCount)
  {
    $this->nodeCount = $nodeCount;
  }
  /**
   * @return int
   */
  public function getNodeCount()
  {
    return $this->nodeCount;
  }
  /**
   * @param int
   */
  public function setProcessingUnits($processingUnits)
  {
    $this->processingUnits = $processingUnits;
  }
  /**
   * @return int
   */
  public function getProcessingUnits()
  {
    return $this->processingUnits;
  }
  /**
   * @param ReplicaComputeCapacity[]
   */
  public function setReplicaComputeCapacity($replicaComputeCapacity)
  {
    $this->replicaComputeCapacity = $replicaComputeCapacity;
  }
  /**
   * @return ReplicaComputeCapacity[]
   */
  public function getReplicaComputeCapacity()
  {
    return $this->replicaComputeCapacity;
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
  /**
   * @param string
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Instance::class, 'Google_Service_Spanner_Instance');
