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

class GlobalVmExtensionPolicyRolloutOperation extends \Google\Model
{
  protected $rolloutInputType = GlobalVmExtensionPolicyRolloutOperationRolloutInput::class;
  protected $rolloutInputDataType = '';
  protected $rolloutStatusType = GlobalVmExtensionPolicyRolloutOperationRolloutStatus::class;
  protected $rolloutStatusDataType = '';

  /**
   * Required. The rollout input which defines the rollout plan.
   *
   * @param GlobalVmExtensionPolicyRolloutOperationRolloutInput $rolloutInput
   */
  public function setRolloutInput(GlobalVmExtensionPolicyRolloutOperationRolloutInput $rolloutInput)
  {
    $this->rolloutInput = $rolloutInput;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperationRolloutInput
   */
  public function getRolloutInput()
  {
    return $this->rolloutInput;
  }
  /**
   * Output only. [Output Only] The rollout status of the policy.
   *
   * @param GlobalVmExtensionPolicyRolloutOperationRolloutStatus $rolloutStatus
   */
  public function setRolloutStatus(GlobalVmExtensionPolicyRolloutOperationRolloutStatus $rolloutStatus)
  {
    $this->rolloutStatus = $rolloutStatus;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperationRolloutStatus
   */
  public function getRolloutStatus()
  {
    return $this->rolloutStatus;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GlobalVmExtensionPolicyRolloutOperation::class, 'Google_Service_Compute_GlobalVmExtensionPolicyRolloutOperation');
