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

class GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata extends \Google\Model
{
  /**
   * Iteration was explicitly cancelled.
   */
  public const STATE_STATE_CANCELLED = 'STATE_CANCELLED';
  /**
   * Iteration completed, with all actions being successful.
   */
  public const STATE_STATE_COMPLETED = 'STATE_COMPLETED';
  /**
   * Iteration completed, with failures.
   */
  public const STATE_STATE_FAILED = 'STATE_FAILED';
  /**
   * The rollout is paused.
   */
  public const STATE_STATE_PAUSED = 'STATE_PAUSED';
  /**
   * Iteration is in progress.
   */
  public const STATE_STATE_PROCESSING = 'STATE_PROCESSING';
  /**
   * Impossible to determine current state of the iteration.
   */
  public const STATE_STATE_UNKNOWN = 'STATE_UNKNOWN';
  /**
   * Default value. This value is unused.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  protected $locationRolloutStatusType = GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus::class;
  protected $locationRolloutStatusDataType = 'map';
  /**
   * Output only. [Output Only] The name of the rollout. Ex.
   * projects//locations/global/rollouts/.
   *
   * @var string
   */
  public $rollout;
  /**
   * Output only. [Output Only] The name of the rollout plan. Ex.
   * projects//locations/global/rolloutPlans/.
   *
   * @var string
   */
  public $rolloutPlan;
  /**
   * Output only. [Output Only] The overall state of the rollout.
   *
   * @var string
   */
  public $state;

  /**
   * Output only. [Output Only] The rollout status for each location. The list
   * of the locations is the same as the list of locations in the rollout plan.
   *
   * @param GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus[] $locationRolloutStatus
   */
  public function setLocationRolloutStatus($locationRolloutStatus)
  {
    $this->locationRolloutStatus = $locationRolloutStatus;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus[]
   */
  public function getLocationRolloutStatus()
  {
    return $this->locationRolloutStatus;
  }
  /**
   * Output only. [Output Only] The name of the rollout. Ex.
   * projects//locations/global/rollouts/.
   *
   * @param string $rollout
   */
  public function setRollout($rollout)
  {
    $this->rollout = $rollout;
  }
  /**
   * @return string
   */
  public function getRollout()
  {
    return $this->rollout;
  }
  /**
   * Output only. [Output Only] The name of the rollout plan. Ex.
   * projects//locations/global/rolloutPlans/.
   *
   * @param string $rolloutPlan
   */
  public function setRolloutPlan($rolloutPlan)
  {
    $this->rolloutPlan = $rolloutPlan;
  }
  /**
   * @return string
   */
  public function getRolloutPlan()
  {
    return $this->rolloutPlan;
  }
  /**
   * Output only. [Output Only] The overall state of the rollout.
   *
   * Accepted values: STATE_CANCELLED, STATE_COMPLETED, STATE_FAILED,
   * STATE_PAUSED, STATE_PROCESSING, STATE_UNKNOWN, STATE_UNSPECIFIED
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata::class, 'Google_Service_Compute_GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata');
