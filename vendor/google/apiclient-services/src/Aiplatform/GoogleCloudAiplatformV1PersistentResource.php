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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1PersistentResource extends \Google\Collection
{
  protected $collection_key = 'resourcePools';
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $displayName;
  protected $encryptionSpecType = GoogleCloudAiplatformV1EncryptionSpec::class;
  protected $encryptionSpecDataType = '';
  protected $errorType = GoogleRpcStatus::class;
  protected $errorDataType = '';
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $network;
  /**
   * @var string[]
   */
  public $reservedIpRanges;
  protected $resourcePoolsType = GoogleCloudAiplatformV1ResourcePool::class;
  protected $resourcePoolsDataType = 'array';
  protected $resourceRuntimeType = GoogleCloudAiplatformV1ResourceRuntime::class;
  protected $resourceRuntimeDataType = '';
  protected $resourceRuntimeSpecType = GoogleCloudAiplatformV1ResourceRuntimeSpec::class;
  protected $resourceRuntimeSpecDataType = '';
  /**
   * @var bool
   */
  public $satisfiesPzi;
  /**
   * @var bool
   */
  public $satisfiesPzs;
  /**
   * @var string
   */
  public $startTime;
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $updateTime;

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
   * @param GoogleCloudAiplatformV1EncryptionSpec
   */
  public function setEncryptionSpec(GoogleCloudAiplatformV1EncryptionSpec $encryptionSpec)
  {
    $this->encryptionSpec = $encryptionSpec;
  }
  /**
   * @return GoogleCloudAiplatformV1EncryptionSpec
   */
  public function getEncryptionSpec()
  {
    return $this->encryptionSpec;
  }
  /**
   * @param GoogleRpcStatus
   */
  public function setError(GoogleRpcStatus $error)
  {
    $this->error = $error;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getError()
  {
    return $this->error;
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
   * @param string
   */
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  /**
   * @return string
   */
  public function getNetwork()
  {
    return $this->network;
  }
  /**
   * @param string[]
   */
  public function setReservedIpRanges($reservedIpRanges)
  {
    $this->reservedIpRanges = $reservedIpRanges;
  }
  /**
   * @return string[]
   */
  public function getReservedIpRanges()
  {
    return $this->reservedIpRanges;
  }
  /**
   * @param GoogleCloudAiplatformV1ResourcePool[]
   */
  public function setResourcePools($resourcePools)
  {
    $this->resourcePools = $resourcePools;
  }
  /**
   * @return GoogleCloudAiplatformV1ResourcePool[]
   */
  public function getResourcePools()
  {
    return $this->resourcePools;
  }
  /**
   * @param GoogleCloudAiplatformV1ResourceRuntime
   */
  public function setResourceRuntime(GoogleCloudAiplatformV1ResourceRuntime $resourceRuntime)
  {
    $this->resourceRuntime = $resourceRuntime;
  }
  /**
   * @return GoogleCloudAiplatformV1ResourceRuntime
   */
  public function getResourceRuntime()
  {
    return $this->resourceRuntime;
  }
  /**
   * @param GoogleCloudAiplatformV1ResourceRuntimeSpec
   */
  public function setResourceRuntimeSpec(GoogleCloudAiplatformV1ResourceRuntimeSpec $resourceRuntimeSpec)
  {
    $this->resourceRuntimeSpec = $resourceRuntimeSpec;
  }
  /**
   * @return GoogleCloudAiplatformV1ResourceRuntimeSpec
   */
  public function getResourceRuntimeSpec()
  {
    return $this->resourceRuntimeSpec;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzi($satisfiesPzi)
  {
    $this->satisfiesPzi = $satisfiesPzi;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzi()
  {
    return $this->satisfiesPzi;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzs($satisfiesPzs)
  {
    $this->satisfiesPzs = $satisfiesPzs;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzs()
  {
    return $this->satisfiesPzs;
  }
  /**
   * @param string
   */
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  /**
   * @return string
   */
  public function getStartTime()
  {
    return $this->startTime;
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
class_alias(GoogleCloudAiplatformV1PersistentResource::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1PersistentResource');
