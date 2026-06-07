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

class GlobalVmExtensionPolicyRolloutOperationRolloutInput extends \Google\Model
{
  public const PREDEFINED_ROLLOUT_PLAN_FAST_ROLLOUT = 'FAST_ROLLOUT';
  public const PREDEFINED_ROLLOUT_PLAN_ROLLOUT_PLAN_UNSPECIFIED = 'ROLLOUT_PLAN_UNSPECIFIED';
  public const PREDEFINED_ROLLOUT_PLAN_SLOW_ROLLOUT = 'SLOW_ROLLOUT';
  /**
   * Optional. Specifies the behavior of the rollout if a conflict is detected
   * in a project during a rollout. This only applies to `insert` and `update`
   * methods.
   *
   * A conflict occurs in the following cases:
   *
   * * `insert` method: If the zonal policy already exists when the insert
   * happens. * `update` method: If the zonal policy was modified by a zonal API
   * call   outside of this rollout.
   *
   * Possible values are the following:
   *
   * * `""` (empty string): If a conflict occurs, the local value is not
   * overwritten. This is the default behavior. * `"overwrite"`: If a conflict
   * occurs, the local value is overwritten   with the rollout value.
   *
   * @var string
   */
  public $conflictBehavior;
  /**
   * Optional. The name of the rollout plan. Ex.
   * projects//locations/global/rolloutPlans/.
   *
   * @var string
   */
  public $name;
  /**
   * Optional. Specifies the predefined rollout plan for the policy. Valid
   * values are `SLOW_ROLLOUT` and `FAST_ROLLOUT`. The recommended value is
   * `SLOW_ROLLOUT` for progressive rollout. For more information, see Rollout
   * plans for global policies.
   *
   * @var string
   */
  public $predefinedRolloutPlan;
  /**
   * Optional. The UUID that identifies a policy rollout retry attempt for
   * update and delete operations. Set this field only when retrying a rollout
   * for an existing extension policy.
   *
   * * `update` method: Lets you retry policy rollout without changes. An error
   * occurs if you set retry_uuid but the policy is modified. * `delete` method:
   * Lets you retry policy deletion rollout if the previous deletion rollout is
   * not finished and the policy is in the DELETING state. If you set this field
   * when the policy is not in the DELETING state, an error occurs.
   *
   * @var string
   */
  public $retryUuid;

  /**
   * Optional. Specifies the behavior of the rollout if a conflict is detected
   * in a project during a rollout. This only applies to `insert` and `update`
   * methods.
   *
   * A conflict occurs in the following cases:
   *
   * * `insert` method: If the zonal policy already exists when the insert
   * happens. * `update` method: If the zonal policy was modified by a zonal API
   * call   outside of this rollout.
   *
   * Possible values are the following:
   *
   * * `""` (empty string): If a conflict occurs, the local value is not
   * overwritten. This is the default behavior. * `"overwrite"`: If a conflict
   * occurs, the local value is overwritten   with the rollout value.
   *
   * @param string $conflictBehavior
   */
  public function setConflictBehavior($conflictBehavior)
  {
    $this->conflictBehavior = $conflictBehavior;
  }
  /**
   * @return string
   */
  public function getConflictBehavior()
  {
    return $this->conflictBehavior;
  }
  /**
   * Optional. The name of the rollout plan. Ex.
   * projects//locations/global/rolloutPlans/.
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
   * Optional. Specifies the predefined rollout plan for the policy. Valid
   * values are `SLOW_ROLLOUT` and `FAST_ROLLOUT`. The recommended value is
   * `SLOW_ROLLOUT` for progressive rollout. For more information, see Rollout
   * plans for global policies.
   *
   * Accepted values: FAST_ROLLOUT, ROLLOUT_PLAN_UNSPECIFIED, SLOW_ROLLOUT
   *
   * @param self::PREDEFINED_ROLLOUT_PLAN_* $predefinedRolloutPlan
   */
  public function setPredefinedRolloutPlan($predefinedRolloutPlan)
  {
    $this->predefinedRolloutPlan = $predefinedRolloutPlan;
  }
  /**
   * @return self::PREDEFINED_ROLLOUT_PLAN_*
   */
  public function getPredefinedRolloutPlan()
  {
    return $this->predefinedRolloutPlan;
  }
  /**
   * Optional. The UUID that identifies a policy rollout retry attempt for
   * update and delete operations. Set this field only when retrying a rollout
   * for an existing extension policy.
   *
   * * `update` method: Lets you retry policy rollout without changes. An error
   * occurs if you set retry_uuid but the policy is modified. * `delete` method:
   * Lets you retry policy deletion rollout if the previous deletion rollout is
   * not finished and the policy is in the DELETING state. If you set this field
   * when the policy is not in the DELETING state, an error occurs.
   *
   * @param string $retryUuid
   */
  public function setRetryUuid($retryUuid)
  {
    $this->retryUuid = $retryUuid;
  }
  /**
   * @return string
   */
  public function getRetryUuid()
  {
    return $this->retryUuid;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GlobalVmExtensionPolicyRolloutOperationRolloutInput::class, 'Google_Service_Compute_GlobalVmExtensionPolicyRolloutOperationRolloutInput');
