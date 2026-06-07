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

class InstantSnapshotGroupParameters extends \Google\Model
{
  /**
   * The source instant snapshot group used to create disks. You can provide
   * this as a partial or full URL to the resource. For example, the following
   * are valid values:              - https://www.googleapis.com/compute/v1/proj
   * ects/project/zones/zone/instantSnapshotGroups/instantSnapshotGroup      -
   * projects/project/zones/zone/instantSnapshotGroups/instantSnapshotGroup
   * - zones/zone/instantSnapshotGroups/instantSnapshotGroup
   *
   * @var string
   */
  public $sourceInstantSnapshotGroup;

  /**
   * The source instant snapshot group used to create disks. You can provide
   * this as a partial or full URL to the resource. For example, the following
   * are valid values:              - https://www.googleapis.com/compute/v1/proj
   * ects/project/zones/zone/instantSnapshotGroups/instantSnapshotGroup      -
   * projects/project/zones/zone/instantSnapshotGroups/instantSnapshotGroup
   * - zones/zone/instantSnapshotGroups/instantSnapshotGroup
   *
   * @param string $sourceInstantSnapshotGroup
   */
  public function setSourceInstantSnapshotGroup($sourceInstantSnapshotGroup)
  {
    $this->sourceInstantSnapshotGroup = $sourceInstantSnapshotGroup;
  }
  /**
   * @return string
   */
  public function getSourceInstantSnapshotGroup()
  {
    return $this->sourceInstantSnapshotGroup;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(InstantSnapshotGroupParameters::class, 'Google_Service_Compute_InstantSnapshotGroupParameters');
