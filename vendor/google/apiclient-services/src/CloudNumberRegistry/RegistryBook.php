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

namespace Google\Service\CloudNumberRegistry;

class RegistryBook extends \Google\Collection
{
  protected $collection_key = 'claimedScopes';
  protected $aggregatedDataType = AggregatedData::class;
  protected $aggregatedDataDataType = '';
  /**
   * Optional. List of scopes claimed by the RegistryBook. In Preview, Only
   * project scope is supported. Each scope is in the format of
   * projects/{project}. Each scope can only be claimed once.
   *
   * @var string[]
   */
  public $claimedScopes;
  /**
   * Output only. [Output only] Create time stamp
   *
   * @var string
   */
  public $createTime;
  /**
   * Output only. Whether the RegistryBook is the default one.
   *
   * @var bool
   */
  public $isDefault;
  /**
   * Optional. Labels as key value pairs
   *
   * @var string[]
   */
  public $labels;
  /**
   * Required. Identifier. name of resource
   *
   * @var string
   */
  public $name;
  /**
   * Output only. [Output only] Update time stamp
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. Aggregated data for the RegistryBook. Populated only when the
   * view is AGGREGATE.
   *
   * @param AggregatedData $aggregatedData
   */
  public function setAggregatedData(AggregatedData $aggregatedData)
  {
    $this->aggregatedData = $aggregatedData;
  }
  /**
   * @return AggregatedData
   */
  public function getAggregatedData()
  {
    return $this->aggregatedData;
  }
  /**
   * Optional. List of scopes claimed by the RegistryBook. In Preview, Only
   * project scope is supported. Each scope is in the format of
   * projects/{project}. Each scope can only be claimed once.
   *
   * @param string[] $claimedScopes
   */
  public function setClaimedScopes($claimedScopes)
  {
    $this->claimedScopes = $claimedScopes;
  }
  /**
   * @return string[]
   */
  public function getClaimedScopes()
  {
    return $this->claimedScopes;
  }
  /**
   * Output only. [Output only] Create time stamp
   *
   * @param string $createTime
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
   * Output only. Whether the RegistryBook is the default one.
   *
   * @param bool $isDefault
   */
  public function setIsDefault($isDefault)
  {
    $this->isDefault = $isDefault;
  }
  /**
   * @return bool
   */
  public function getIsDefault()
  {
    return $this->isDefault;
  }
  /**
   * Optional. Labels as key value pairs
   *
   * @param string[] $labels
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
   * Required. Identifier. name of resource
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
   * Output only. [Output only] Update time stamp
   *
   * @param string $updateTime
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
class_alias(RegistryBook::class, 'Google_Service_CloudNumberRegistry_RegistryBook');
