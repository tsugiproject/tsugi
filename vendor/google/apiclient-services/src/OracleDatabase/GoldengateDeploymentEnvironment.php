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

class GoldengateDeploymentEnvironment extends \Google\Model
{
  /**
   * Default unspecified value.
   */
  public const CATEGORY_DEPLOYMENT_CATEGORY_UNSPECIFIED = 'DEPLOYMENT_CATEGORY_UNSPECIFIED';
  /**
   * Goldengate Deployment Environment category is DATA_REPLICATION_CATEGORY.
   */
  public const CATEGORY_DATA_REPLICATION_CATEGORY = 'DATA_REPLICATION_CATEGORY';
  /**
   * Goldengate Deployment Environment category is DATA_TRANSFORMS_CATEGORY.
   */
  public const CATEGORY_DATA_TRANSFORMS_CATEGORY = 'DATA_TRANSFORMS_CATEGORY';
  /**
   * Default unspecified value.
   */
  public const ENVIRONMENT_TYPE_DEPLOYMENT_ENVIRONMENT_TYPE_UNSPECIFIED = 'DEPLOYMENT_ENVIRONMENT_TYPE_UNSPECIFIED';
  /**
   * Goldengate Deployment Environment type is PRODUCTION.
   */
  public const ENVIRONMENT_TYPE_PRODUCTION = 'PRODUCTION';
  /**
   * Goldengate Deployment Environment type is DEVELOPMENT_OR_TESTING.
   */
  public const ENVIRONMENT_TYPE_DEVELOPMENT_OR_TESTING = 'DEVELOPMENT_OR_TESTING';
  /**
   * Output only. Whether auto scaling is enabled by default for the Goldengate
   * Deployment Environment resource.
   *
   * @var bool
   */
  public $autoScalingEnabled;
  /**
   * Output only. The category of the Goldengate Deployment Environment
   * resource.
   *
   * @var string
   */
  public $category;
  /**
   * Output only. The default CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @var int
   */
  public $defaultCpuCoreCount;
  /**
   * The display name of the Goldengate Deployment Environment resource.
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. The environment type of the Goldengate Deployment Environment
   * resource.
   *
   * @var string
   */
  public $environmentType;
  /**
   * Output only. The max CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @var int
   */
  public $maxCpuCoreCount;
  /**
   * Output only. The memory per CPU core in GBs of the Goldengate Deployment
   * Environment resource.
   *
   * @var int
   */
  public $memoryGbPerCpuCore;
  /**
   * Output only. The min CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @var int
   */
  public $minCpuCoreCount;
  /**
   * Identifier. The name of the Goldengate Deployment Environment resource with
   * the format: projects/{project}/locations/{location}/goldengateDeploymentEnv
   * ironments/{goldengate_deployment_environment}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The network bandwidth per CPU core in Gbps of the Goldengate
   * Deployment Environment resource.
   *
   * @var int
   */
  public $networkBandwidthGbpsPerCpuCore;
  /**
   * Output only. The storage usage limit per CPU core in GBs of the Goldengate
   * Deployment Environment resource.
   *
   * @var int
   */
  public $storageUsageLimitGbPerCpuCore;

  /**
   * Output only. Whether auto scaling is enabled by default for the Goldengate
   * Deployment Environment resource.
   *
   * @param bool $autoScalingEnabled
   */
  public function setAutoScalingEnabled($autoScalingEnabled)
  {
    $this->autoScalingEnabled = $autoScalingEnabled;
  }
  /**
   * @return bool
   */
  public function getAutoScalingEnabled()
  {
    return $this->autoScalingEnabled;
  }
  /**
   * Output only. The category of the Goldengate Deployment Environment
   * resource.
   *
   * Accepted values: DEPLOYMENT_CATEGORY_UNSPECIFIED,
   * DATA_REPLICATION_CATEGORY, DATA_TRANSFORMS_CATEGORY
   *
   * @param self::CATEGORY_* $category
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return self::CATEGORY_*
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * Output only. The default CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @param int $defaultCpuCoreCount
   */
  public function setDefaultCpuCoreCount($defaultCpuCoreCount)
  {
    $this->defaultCpuCoreCount = $defaultCpuCoreCount;
  }
  /**
   * @return int
   */
  public function getDefaultCpuCoreCount()
  {
    return $this->defaultCpuCoreCount;
  }
  /**
   * The display name of the Goldengate Deployment Environment resource.
   *
   * @param string $displayName
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
   * Output only. The environment type of the Goldengate Deployment Environment
   * resource.
   *
   * Accepted values: DEPLOYMENT_ENVIRONMENT_TYPE_UNSPECIFIED, PRODUCTION,
   * DEVELOPMENT_OR_TESTING
   *
   * @param self::ENVIRONMENT_TYPE_* $environmentType
   */
  public function setEnvironmentType($environmentType)
  {
    $this->environmentType = $environmentType;
  }
  /**
   * @return self::ENVIRONMENT_TYPE_*
   */
  public function getEnvironmentType()
  {
    return $this->environmentType;
  }
  /**
   * Output only. The max CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @param int $maxCpuCoreCount
   */
  public function setMaxCpuCoreCount($maxCpuCoreCount)
  {
    $this->maxCpuCoreCount = $maxCpuCoreCount;
  }
  /**
   * @return int
   */
  public function getMaxCpuCoreCount()
  {
    return $this->maxCpuCoreCount;
  }
  /**
   * Output only. The memory per CPU core in GBs of the Goldengate Deployment
   * Environment resource.
   *
   * @param int $memoryGbPerCpuCore
   */
  public function setMemoryGbPerCpuCore($memoryGbPerCpuCore)
  {
    $this->memoryGbPerCpuCore = $memoryGbPerCpuCore;
  }
  /**
   * @return int
   */
  public function getMemoryGbPerCpuCore()
  {
    return $this->memoryGbPerCpuCore;
  }
  /**
   * Output only. The min CPU core count of the Goldengate Deployment
   * Environment resource.
   *
   * @param int $minCpuCoreCount
   */
  public function setMinCpuCoreCount($minCpuCoreCount)
  {
    $this->minCpuCoreCount = $minCpuCoreCount;
  }
  /**
   * @return int
   */
  public function getMinCpuCoreCount()
  {
    return $this->minCpuCoreCount;
  }
  /**
   * Identifier. The name of the Goldengate Deployment Environment resource with
   * the format: projects/{project}/locations/{location}/goldengateDeploymentEnv
   * ironments/{goldengate_deployment_environment}
   *
   * @param string $name
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
   * Output only. The network bandwidth per CPU core in Gbps of the Goldengate
   * Deployment Environment resource.
   *
   * @param int $networkBandwidthGbpsPerCpuCore
   */
  public function setNetworkBandwidthGbpsPerCpuCore($networkBandwidthGbpsPerCpuCore)
  {
    $this->networkBandwidthGbpsPerCpuCore = $networkBandwidthGbpsPerCpuCore;
  }
  /**
   * @return int
   */
  public function getNetworkBandwidthGbpsPerCpuCore()
  {
    return $this->networkBandwidthGbpsPerCpuCore;
  }
  /**
   * Output only. The storage usage limit per CPU core in GBs of the Goldengate
   * Deployment Environment resource.
   *
   * @param int $storageUsageLimitGbPerCpuCore
   */
  public function setStorageUsageLimitGbPerCpuCore($storageUsageLimitGbPerCpuCore)
  {
    $this->storageUsageLimitGbPerCpuCore = $storageUsageLimitGbPerCpuCore;
  }
  /**
   * @return int
   */
  public function getStorageUsageLimitGbPerCpuCore()
  {
    return $this->storageUsageLimitGbPerCpuCore;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentEnvironment::class, 'Google_Service_OracleDatabase_GoldengateDeploymentEnvironment');
