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

namespace Google\Service\Container;

class NodePool extends \Google\Collection
{
  protected $collection_key = 'locations';
  protected $autopilotConfigType = AutopilotConfig::class;
  protected $autopilotConfigDataType = '';
  protected $autoscalingType = NodePoolAutoscaling::class;
  protected $autoscalingDataType = '';
  protected $bestEffortProvisioningType = BestEffortProvisioning::class;
  protected $bestEffortProvisioningDataType = '';
  protected $conditionsType = StatusCondition::class;
  protected $conditionsDataType = 'array';
  protected $configType = NodeConfig::class;
  protected $configDataType = '';
  /**
   * @var string
   */
  public $etag;
  /**
   * @var int
   */
  public $initialNodeCount;
  /**
   * @var string[]
   */
  public $instanceGroupUrls;
  /**
   * @var string[]
   */
  public $locations;
  protected $managementType = NodeManagement::class;
  protected $managementDataType = '';
  protected $maxPodsConstraintType = MaxPodsConstraint::class;
  protected $maxPodsConstraintDataType = '';
  /**
   * @var string
   */
  public $name;
  protected $networkConfigType = NodeNetworkConfig::class;
  protected $networkConfigDataType = '';
  protected $placementPolicyType = PlacementPolicy::class;
  protected $placementPolicyDataType = '';
  /**
   * @var int
   */
  public $podIpv4CidrSize;
  protected $queuedProvisioningType = QueuedProvisioning::class;
  protected $queuedProvisioningDataType = '';
  /**
   * @var string
   */
  public $selfLink;
  /**
   * @var string
   */
  public $status;
  /**
   * @var string
   */
  public $statusMessage;
  protected $updateInfoType = UpdateInfo::class;
  protected $updateInfoDataType = '';
  protected $upgradeSettingsType = UpgradeSettings::class;
  protected $upgradeSettingsDataType = '';
  /**
   * @var string
   */
  public $version;

  /**
   * @param AutopilotConfig
   */
  public function setAutopilotConfig(AutopilotConfig $autopilotConfig)
  {
    $this->autopilotConfig = $autopilotConfig;
  }
  /**
   * @return AutopilotConfig
   */
  public function getAutopilotConfig()
  {
    return $this->autopilotConfig;
  }
  /**
   * @param NodePoolAutoscaling
   */
  public function setAutoscaling(NodePoolAutoscaling $autoscaling)
  {
    $this->autoscaling = $autoscaling;
  }
  /**
   * @return NodePoolAutoscaling
   */
  public function getAutoscaling()
  {
    return $this->autoscaling;
  }
  /**
   * @param BestEffortProvisioning
   */
  public function setBestEffortProvisioning(BestEffortProvisioning $bestEffortProvisioning)
  {
    $this->bestEffortProvisioning = $bestEffortProvisioning;
  }
  /**
   * @return BestEffortProvisioning
   */
  public function getBestEffortProvisioning()
  {
    return $this->bestEffortProvisioning;
  }
  /**
   * @param StatusCondition[]
   */
  public function setConditions($conditions)
  {
    $this->conditions = $conditions;
  }
  /**
   * @return StatusCondition[]
   */
  public function getConditions()
  {
    return $this->conditions;
  }
  /**
   * @param NodeConfig
   */
  public function setConfig(NodeConfig $config)
  {
    $this->config = $config;
  }
  /**
   * @return NodeConfig
   */
  public function getConfig()
  {
    return $this->config;
  }
  /**
   * @param string
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param int
   */
  public function setInitialNodeCount($initialNodeCount)
  {
    $this->initialNodeCount = $initialNodeCount;
  }
  /**
   * @return int
   */
  public function getInitialNodeCount()
  {
    return $this->initialNodeCount;
  }
  /**
   * @param string[]
   */
  public function setInstanceGroupUrls($instanceGroupUrls)
  {
    $this->instanceGroupUrls = $instanceGroupUrls;
  }
  /**
   * @return string[]
   */
  public function getInstanceGroupUrls()
  {
    return $this->instanceGroupUrls;
  }
  /**
   * @param string[]
   */
  public function setLocations($locations)
  {
    $this->locations = $locations;
  }
  /**
   * @return string[]
   */
  public function getLocations()
  {
    return $this->locations;
  }
  /**
   * @param NodeManagement
   */
  public function setManagement(NodeManagement $management)
  {
    $this->management = $management;
  }
  /**
   * @return NodeManagement
   */
  public function getManagement()
  {
    return $this->management;
  }
  /**
   * @param MaxPodsConstraint
   */
  public function setMaxPodsConstraint(MaxPodsConstraint $maxPodsConstraint)
  {
    $this->maxPodsConstraint = $maxPodsConstraint;
  }
  /**
   * @return MaxPodsConstraint
   */
  public function getMaxPodsConstraint()
  {
    return $this->maxPodsConstraint;
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
   * @param NodeNetworkConfig
   */
  public function setNetworkConfig(NodeNetworkConfig $networkConfig)
  {
    $this->networkConfig = $networkConfig;
  }
  /**
   * @return NodeNetworkConfig
   */
  public function getNetworkConfig()
  {
    return $this->networkConfig;
  }
  /**
   * @param PlacementPolicy
   */
  public function setPlacementPolicy(PlacementPolicy $placementPolicy)
  {
    $this->placementPolicy = $placementPolicy;
  }
  /**
   * @return PlacementPolicy
   */
  public function getPlacementPolicy()
  {
    return $this->placementPolicy;
  }
  /**
   * @param int
   */
  public function setPodIpv4CidrSize($podIpv4CidrSize)
  {
    $this->podIpv4CidrSize = $podIpv4CidrSize;
  }
  /**
   * @return int
   */
  public function getPodIpv4CidrSize()
  {
    return $this->podIpv4CidrSize;
  }
  /**
   * @param QueuedProvisioning
   */
  public function setQueuedProvisioning(QueuedProvisioning $queuedProvisioning)
  {
    $this->queuedProvisioning = $queuedProvisioning;
  }
  /**
   * @return QueuedProvisioning
   */
  public function getQueuedProvisioning()
  {
    return $this->queuedProvisioning;
  }
  /**
   * @param string
   */
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  /**
   * @return string
   */
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * @param string
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }
  /**
   * @param string
   */
  public function setStatusMessage($statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }
  /**
   * @return string
   */
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }
  /**
   * @param UpdateInfo
   */
  public function setUpdateInfo(UpdateInfo $updateInfo)
  {
    $this->updateInfo = $updateInfo;
  }
  /**
   * @return UpdateInfo
   */
  public function getUpdateInfo()
  {
    return $this->updateInfo;
  }
  /**
   * @param UpgradeSettings
   */
  public function setUpgradeSettings(UpgradeSettings $upgradeSettings)
  {
    $this->upgradeSettings = $upgradeSettings;
  }
  /**
   * @return UpgradeSettings
   */
  public function getUpgradeSettings()
  {
    return $this->upgradeSettings;
  }
  /**
   * @param string
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NodePool::class, 'Google_Service_Container_NodePool');
