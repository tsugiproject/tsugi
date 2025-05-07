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

namespace Google\Service\Networkconnectivity;

class Hub extends \Google\Collection
{
  protected $collection_key = 'routingVpcs';
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $description;
  /**
   * @var bool
   */
  public $exportPsc;
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
  public $policyMode;
  /**
   * @var string
   */
  public $presetTopology;
  /**
   * @var string[]
   */
  public $routeTables;
  protected $routingVpcsType = RoutingVPC::class;
  protected $routingVpcsDataType = 'array';
  protected $spokeSummaryType = SpokeSummary::class;
  protected $spokeSummaryDataType = '';
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $uniqueId;
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
   * @param bool
   */
  public function setExportPsc($exportPsc)
  {
    $this->exportPsc = $exportPsc;
  }
  /**
   * @return bool
   */
  public function getExportPsc()
  {
    return $this->exportPsc;
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
  public function setPolicyMode($policyMode)
  {
    $this->policyMode = $policyMode;
  }
  /**
   * @return string
   */
  public function getPolicyMode()
  {
    return $this->policyMode;
  }
  /**
   * @param string
   */
  public function setPresetTopology($presetTopology)
  {
    $this->presetTopology = $presetTopology;
  }
  /**
   * @return string
   */
  public function getPresetTopology()
  {
    return $this->presetTopology;
  }
  /**
   * @param string[]
   */
  public function setRouteTables($routeTables)
  {
    $this->routeTables = $routeTables;
  }
  /**
   * @return string[]
   */
  public function getRouteTables()
  {
    return $this->routeTables;
  }
  /**
   * @param RoutingVPC[]
   */
  public function setRoutingVpcs($routingVpcs)
  {
    $this->routingVpcs = $routingVpcs;
  }
  /**
   * @return RoutingVPC[]
   */
  public function getRoutingVpcs()
  {
    return $this->routingVpcs;
  }
  /**
   * @param SpokeSummary
   */
  public function setSpokeSummary(SpokeSummary $spokeSummary)
  {
    $this->spokeSummary = $spokeSummary;
  }
  /**
   * @return SpokeSummary
   */
  public function getSpokeSummary()
  {
    return $this->spokeSummary;
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
  public function setUniqueId($uniqueId)
  {
    $this->uniqueId = $uniqueId;
  }
  /**
   * @return string
   */
  public function getUniqueId()
  {
    return $this->uniqueId;
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
class_alias(Hub::class, 'Google_Service_Networkconnectivity_Hub');
