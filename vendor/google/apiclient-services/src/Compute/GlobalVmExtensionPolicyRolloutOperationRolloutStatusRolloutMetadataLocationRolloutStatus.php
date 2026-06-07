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

class GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus extends \Google\Model
{
  /**
   * The location rollout is completed.
   */
  public const STATE_LOCATION_ROLLOUT_STATE_COMPLETED = 'LOCATION_ROLLOUT_STATE_COMPLETED';
  /**
   * The location rollout has failed.
   */
  public const STATE_LOCATION_ROLLOUT_STATE_FAILED = 'LOCATION_ROLLOUT_STATE_FAILED';
  /**
   * The location rollout has not started.
   */
  public const STATE_LOCATION_ROLLOUT_STATE_NOT_STARTED = 'LOCATION_ROLLOUT_STATE_NOT_STARTED';
  /**
   * The location rollout is skipped.
   */
  public const STATE_LOCATION_ROLLOUT_STATE_SKIPPED = 'LOCATION_ROLLOUT_STATE_SKIPPED';
  /**
   * Default value. This value is unused.
   */
  public const STATE_LOCATION_ROLLOUT_STATE_UNSPECIFIED = 'LOCATION_ROLLOUT_STATE_UNSPECIFIED';
  /**
   * Output only. [Output Only] The state of the location rollout.
   *
   * @var string
   */
  public $state;

  /**
   * Output only. [Output Only] The state of the location rollout.
   *
   * Accepted values: LOCATION_ROLLOUT_STATE_COMPLETED,
   * LOCATION_ROLLOUT_STATE_FAILED, LOCATION_ROLLOUT_STATE_NOT_STARTED,
   * LOCATION_ROLLOUT_STATE_SKIPPED, LOCATION_ROLLOUT_STATE_UNSPECIFIED
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
class_alias(GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus::class, 'Google_Service_Compute_GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadataLocationRolloutStatus');
