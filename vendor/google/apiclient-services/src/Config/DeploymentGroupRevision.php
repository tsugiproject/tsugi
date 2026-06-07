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

namespace Google\Service\Config;

class DeploymentGroupRevision extends \Google\Collection
{
  protected $collection_key = 'alternativeIds';
  /**
   * Output only. The alternative IDs of the deployment group revision.
   *
   * @var string[]
   */
  public $alternativeIds;
  /**
   * Output only. Time when the deployment group revision was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Identifier. The name of the deployment group revision. Format: 'projects/{p
   * roject_id}/locations/{location}/deploymentGroups/{deployment_group}/revisio
   * ns/{revision}'.
   *
   * @var string
   */
  public $name;
  protected $snapshotType = DeploymentGroup::class;
  protected $snapshotDataType = '';

  /**
   * Output only. The alternative IDs of the deployment group revision.
   *
   * @param string[] $alternativeIds
   */
  public function setAlternativeIds($alternativeIds)
  {
    $this->alternativeIds = $alternativeIds;
  }
  /**
   * @return string[]
   */
  public function getAlternativeIds()
  {
    return $this->alternativeIds;
  }
  /**
   * Output only. Time when the deployment group revision was created.
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
   * Identifier. The name of the deployment group revision. Format: 'projects/{p
   * roject_id}/locations/{location}/deploymentGroups/{deployment_group}/revisio
   * ns/{revision}'.
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
   * Output only. The snapshot of the deployment group at this revision.
   *
   * @param DeploymentGroup $snapshot
   */
  public function setSnapshot(DeploymentGroup $snapshot)
  {
    $this->snapshot = $snapshot;
  }
  /**
   * @return DeploymentGroup
   */
  public function getSnapshot()
  {
    return $this->snapshot;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeploymentGroupRevision::class, 'Google_Service_Config_DeploymentGroupRevision');
