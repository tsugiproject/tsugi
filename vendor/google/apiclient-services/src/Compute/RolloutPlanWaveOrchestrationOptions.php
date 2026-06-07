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

class RolloutPlanWaveOrchestrationOptions extends \Google\Collection
{
  protected $collection_key = 'delays';
  protected $delaysType = RolloutPlanWaveOrchestrationOptionsDelay::class;
  protected $delaysDataType = 'array';
  /**
   * Optional. Maximum number of locations to be orchestrated in parallel.
   *
   * @var string
   */
  public $maxConcurrentLocations;
  /**
   * Optional. Maximum number of resources to be orchestrated per location in
   * parallel.
   *
   * @var string
   */
  public $maxConcurrentResourcesPerLocation;

  /**
   * Optional. Delays, if any, to be added between batches of projects. We allow
   * multiple Delays to be specified, letting users set separate delays between
   * batches of projects corresponding to different locations and batches of
   * projects corresponding to the same location.
   *
   * @param RolloutPlanWaveOrchestrationOptionsDelay[] $delays
   */
  public function setDelays($delays)
  {
    $this->delays = $delays;
  }
  /**
   * @return RolloutPlanWaveOrchestrationOptionsDelay[]
   */
  public function getDelays()
  {
    return $this->delays;
  }
  /**
   * Optional. Maximum number of locations to be orchestrated in parallel.
   *
   * @param string $maxConcurrentLocations
   */
  public function setMaxConcurrentLocations($maxConcurrentLocations)
  {
    $this->maxConcurrentLocations = $maxConcurrentLocations;
  }
  /**
   * @return string
   */
  public function getMaxConcurrentLocations()
  {
    return $this->maxConcurrentLocations;
  }
  /**
   * Optional. Maximum number of resources to be orchestrated per location in
   * parallel.
   *
   * @param string $maxConcurrentResourcesPerLocation
   */
  public function setMaxConcurrentResourcesPerLocation($maxConcurrentResourcesPerLocation)
  {
    $this->maxConcurrentResourcesPerLocation = $maxConcurrentResourcesPerLocation;
  }
  /**
   * @return string
   */
  public function getMaxConcurrentResourcesPerLocation()
  {
    return $this->maxConcurrentResourcesPerLocation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWaveOrchestrationOptions::class, 'Google_Service_Compute_RolloutPlanWaveOrchestrationOptions');
