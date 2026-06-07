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

class GlobalVmExtensionPolicyRolloutOperationRolloutStatus extends \Google\Collection
{
  protected $collection_key = 'currentRollouts';
  protected $currentRolloutsType = GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata::class;
  protected $currentRolloutsDataType = 'array';
  protected $previousRolloutType = GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata::class;
  protected $previousRolloutDataType = '';

  /**
   * Output only. [Output Only] The current rollouts for the latest version of
   * the resource. There should be only one current rollout, but for
   * scalability, we make it repeated.
   *
   * @param GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata[] $currentRollouts
   */
  public function setCurrentRollouts($currentRollouts)
  {
    $this->currentRollouts = $currentRollouts;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata[]
   */
  public function getCurrentRollouts()
  {
    return $this->currentRollouts;
  }
  /**
   * Output only. [Output Only] The last completed rollout resource. This field
   * will not be populated until the first rollout is completed.
   *
   * @param GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata $previousRollout
   */
  public function setPreviousRollout(GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata $previousRollout)
  {
    $this->previousRollout = $previousRollout;
  }
  /**
   * @return GlobalVmExtensionPolicyRolloutOperationRolloutStatusRolloutMetadata
   */
  public function getPreviousRollout()
  {
    return $this->previousRollout;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GlobalVmExtensionPolicyRolloutOperationRolloutStatus::class, 'Google_Service_Compute_GlobalVmExtensionPolicyRolloutOperationRolloutStatus');
