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

namespace Google\Service\CloudDeploy;

class Target extends \Google\Collection
{
  protected $collection_key = 'executionConfigs';
  /**
   * @var string[]
   */
  public $annotations;
  protected $anthosClusterType = AnthosCluster::class;
  protected $anthosClusterDataType = '';
  protected $associatedEntitiesType = AssociatedEntities::class;
  protected $associatedEntitiesDataType = 'map';
  /**
   * @var string
   */
  public $createTime;
  protected $customTargetType = CustomTarget::class;
  protected $customTargetDataType = '';
  /**
   * @var string[]
   */
  public $deployParameters;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $etag;
  protected $executionConfigsType = ExecutionConfig::class;
  protected $executionConfigsDataType = 'array';
  protected $gkeType = GkeCluster::class;
  protected $gkeDataType = '';
  /**
   * @var string[]
   */
  public $labels;
  protected $multiTargetType = MultiTarget::class;
  protected $multiTargetDataType = '';
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $requireApproval;
  protected $runType = CloudRunLocation::class;
  protected $runDataType = '';
  /**
   * @var string
   */
  public $targetId;
  /**
   * @var string
   */
  public $uid;
  /**
   * @var string
   */
  public $updateTime;

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
   * @param AnthosCluster
   */
  public function setAnthosCluster(AnthosCluster $anthosCluster)
  {
    $this->anthosCluster = $anthosCluster;
  }
  /**
   * @return AnthosCluster
   */
  public function getAnthosCluster()
  {
    return $this->anthosCluster;
  }
  /**
   * @param AssociatedEntities[]
   */
  public function setAssociatedEntities($associatedEntities)
  {
    $this->associatedEntities = $associatedEntities;
  }
  /**
   * @return AssociatedEntities[]
   */
  public function getAssociatedEntities()
  {
    return $this->associatedEntities;
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
   * @param CustomTarget
   */
  public function setCustomTarget(CustomTarget $customTarget)
  {
    $this->customTarget = $customTarget;
  }
  /**
   * @return CustomTarget
   */
  public function getCustomTarget()
  {
    return $this->customTarget;
  }
  /**
   * @param string[]
   */
  public function setDeployParameters($deployParameters)
  {
    $this->deployParameters = $deployParameters;
  }
  /**
   * @return string[]
   */
  public function getDeployParameters()
  {
    return $this->deployParameters;
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
   * @param ExecutionConfig[]
   */
  public function setExecutionConfigs($executionConfigs)
  {
    $this->executionConfigs = $executionConfigs;
  }
  /**
   * @return ExecutionConfig[]
   */
  public function getExecutionConfigs()
  {
    return $this->executionConfigs;
  }
  /**
   * @param GkeCluster
   */
  public function setGke(GkeCluster $gke)
  {
    $this->gke = $gke;
  }
  /**
   * @return GkeCluster
   */
  public function getGke()
  {
    return $this->gke;
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
   * @param MultiTarget
   */
  public function setMultiTarget(MultiTarget $multiTarget)
  {
    $this->multiTarget = $multiTarget;
  }
  /**
   * @return MultiTarget
   */
  public function getMultiTarget()
  {
    return $this->multiTarget;
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
   * @param bool
   */
  public function setRequireApproval($requireApproval)
  {
    $this->requireApproval = $requireApproval;
  }
  /**
   * @return bool
   */
  public function getRequireApproval()
  {
    return $this->requireApproval;
  }
  /**
   * @param CloudRunLocation
   */
  public function setRun(CloudRunLocation $run)
  {
    $this->run = $run;
  }
  /**
   * @return CloudRunLocation
   */
  public function getRun()
  {
    return $this->run;
  }
  /**
   * @param string
   */
  public function setTargetId($targetId)
  {
    $this->targetId = $targetId;
  }
  /**
   * @return string
   */
  public function getTargetId()
  {
    return $this->targetId;
  }
  /**
   * @param string
   */
  public function setUid($uid)
  {
    $this->uid = $uid;
  }
  /**
   * @return string
   */
  public function getUid()
  {
    return $this->uid;
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
class_alias(Target::class, 'Google_Service_CloudDeploy_Target');
