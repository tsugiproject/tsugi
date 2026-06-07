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

class SnapshotSettingsAccessLocation extends \Google\Model
{
  /**
   * Any regions will be able to access the source location.
   */
  public const POLICY_ALL_REGIONS = 'ALL_REGIONS';
  public const POLICY_POLICY_UNSPECIFIED = 'POLICY_UNSPECIFIED';
  /**
   * Only allowlisted regions will be able to restore region scoped snapshots
   */
  public const POLICY_SPECIFIC_REGIONS = 'SPECIFIC_REGIONS';
  protected $locationsType = SnapshotSettingsAccessLocationAccessLocationPreference::class;
  protected $locationsDataType = 'map';
  /**
   * Policy of which location is allowed to access snapshot.
   *
   * @var string
   */
  public $policy;

  /**
   * List of regions that can restore a regional  snapshot from the current
   * region
   *
   * @param SnapshotSettingsAccessLocationAccessLocationPreference[] $locations
   */
  public function setLocations($locations)
  {
    $this->locations = $locations;
  }
  /**
   * @return SnapshotSettingsAccessLocationAccessLocationPreference[]
   */
  public function getLocations()
  {
    return $this->locations;
  }
  /**
   * Policy of which location is allowed to access snapshot.
   *
   * Accepted values: ALL_REGIONS, POLICY_UNSPECIFIED, SPECIFIC_REGIONS
   *
   * @param self::POLICY_* $policy
   */
  public function setPolicy($policy)
  {
    $this->policy = $policy;
  }
  /**
   * @return self::POLICY_*
   */
  public function getPolicy()
  {
    return $this->policy;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SnapshotSettingsAccessLocation::class, 'Google_Service_Compute_SnapshotSettingsAccessLocation');
