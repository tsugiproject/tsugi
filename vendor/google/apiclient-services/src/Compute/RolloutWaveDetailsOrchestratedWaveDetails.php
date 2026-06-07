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

class RolloutWaveDetailsOrchestratedWaveDetails extends \Google\Collection
{
  protected $collection_key = 'failedLocations';
  /**
   * Output only. Resource completed so far.
   *
   * @var string
   */
  public $completedResourcesCount;
  /**
   * Output only. Estimated timestamp at which the wave will complete.
   * Extrapolated from current progress.
   *
   * @var string
   */
  public $estimatedCompletionTime;
  /**
   * Output only. Estimated total count of resources.
   *
   * @var string
   */
  public $estimatedTotalResourcesCount;
  /**
   * Output only. Locations that failed during orchestration, and
   * ProgressiveRollout stopped retrying. There may be some successful resources
   * rolled out in the wave as the location may have failed later in the
   * Rollout.
   *
   * @var string[]
   */
  public $failedLocations;
  /**
   * Output only. Resources failed.
   *
   * @var string
   */
  public $failedResourcesCount;
  protected $locationStatusType = RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus::class;
  protected $locationStatusDataType = 'map';

  /**
   * Output only. Resource completed so far.
   *
   * @param string $completedResourcesCount
   */
  public function setCompletedResourcesCount($completedResourcesCount)
  {
    $this->completedResourcesCount = $completedResourcesCount;
  }
  /**
   * @return string
   */
  public function getCompletedResourcesCount()
  {
    return $this->completedResourcesCount;
  }
  /**
   * Output only. Estimated timestamp at which the wave will complete.
   * Extrapolated from current progress.
   *
   * @param string $estimatedCompletionTime
   */
  public function setEstimatedCompletionTime($estimatedCompletionTime)
  {
    $this->estimatedCompletionTime = $estimatedCompletionTime;
  }
  /**
   * @return string
   */
  public function getEstimatedCompletionTime()
  {
    return $this->estimatedCompletionTime;
  }
  /**
   * Output only. Estimated total count of resources.
   *
   * @param string $estimatedTotalResourcesCount
   */
  public function setEstimatedTotalResourcesCount($estimatedTotalResourcesCount)
  {
    $this->estimatedTotalResourcesCount = $estimatedTotalResourcesCount;
  }
  /**
   * @return string
   */
  public function getEstimatedTotalResourcesCount()
  {
    return $this->estimatedTotalResourcesCount;
  }
  /**
   * Output only. Locations that failed during orchestration, and
   * ProgressiveRollout stopped retrying. There may be some successful resources
   * rolled out in the wave as the location may have failed later in the
   * Rollout.
   *
   * @param string[] $failedLocations
   */
  public function setFailedLocations($failedLocations)
  {
    $this->failedLocations = $failedLocations;
  }
  /**
   * @return string[]
   */
  public function getFailedLocations()
  {
    return $this->failedLocations;
  }
  /**
   * Output only. Resources failed.
   *
   * @param string $failedResourcesCount
   */
  public function setFailedResourcesCount($failedResourcesCount)
  {
    $this->failedResourcesCount = $failedResourcesCount;
  }
  /**
   * @return string
   */
  public function getFailedResourcesCount()
  {
    return $this->failedResourcesCount;
  }
  /**
   * Output only. Status of each location in the wave. Map keys (locations) must
   * be specified like "us-east1" or "asia-west1-a".
   *
   * @param RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus[] $locationStatus
   */
  public function setLocationStatus($locationStatus)
  {
    $this->locationStatus = $locationStatus;
  }
  /**
   * @return RolloutWaveDetailsOrchestratedWaveDetailsLocationStatus[]
   */
  public function getLocationStatus()
  {
    return $this->locationStatus;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutWaveDetailsOrchestratedWaveDetails::class, 'Google_Service_Compute_RolloutWaveDetailsOrchestratedWaveDetails');
