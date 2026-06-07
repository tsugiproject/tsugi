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

namespace Google\Service\Compute;

class SnapshotGroupParameters extends \Google\Collection
{
  protected $collection_key = 'replicaZones';
  /**
   * URLs of the zones where disks should be replicated to. Only applicable for
   * regional resources.
   *
   * @var string[]
   */
  public $replicaZones;
  /**
   * The source snapshot group used to create disks. You can provide this as a
   * partial or full URL to the resource. For example, the following are valid
   * values:              - https://www.googleapis.com/compute/v1/projects/proje
   * ct/global/snapshotGroups/snapshotGroup     -
   * projects/project/global/snapshotGroups/snapshotGroup      -
   * global/snapshotGroups/snapshotGroup
   *
   * @var string
   */
  public $sourceSnapshotGroup;
  /**
   * URL of the disk type resource describing which disk type to use to create
   * disks. Provide this when creating the disk. For
   * example:projects/project/zones/zone/diskTypes/pd-ssd. See Persistent disk
   * types.
   *
   * @var string
   */
  public $type;

  /**
   * URLs of the zones where disks should be replicated to. Only applicable for
   * regional resources.
   *
   * @param string[] $replicaZones
   */
  public function setReplicaZones($replicaZones)
  {
    $this->replicaZones = $replicaZones;
  }
  /**
   * @return string[]
   */
  public function getReplicaZones()
  {
    return $this->replicaZones;
  }
  /**
   * The source snapshot group used to create disks. You can provide this as a
   * partial or full URL to the resource. For example, the following are valid
   * values:              - https://www.googleapis.com/compute/v1/projects/proje
   * ct/global/snapshotGroups/snapshotGroup     -
   * projects/project/global/snapshotGroups/snapshotGroup      -
   * global/snapshotGroups/snapshotGroup
   *
   * @param string $sourceSnapshotGroup
   */
  public function setSourceSnapshotGroup($sourceSnapshotGroup)
  {
    $this->sourceSnapshotGroup = $sourceSnapshotGroup;
  }
  /**
   * @return string
   */
  public function getSourceSnapshotGroup()
  {
    return $this->sourceSnapshotGroup;
  }
  /**
   * URL of the disk type resource describing which disk type to use to create
   * disks. Provide this when creating the disk. For
   * example:projects/project/zones/zone/diskTypes/pd-ssd. See Persistent disk
   * types.
   *
   * @param string $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SnapshotGroupParameters::class, 'Google_Service_Compute_SnapshotGroupParameters');
