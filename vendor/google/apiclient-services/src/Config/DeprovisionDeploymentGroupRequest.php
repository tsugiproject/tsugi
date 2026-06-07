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

namespace Google\Service\Config;

class DeprovisionDeploymentGroupRequest extends \Google\Model
{
  /**
   * Unspecified policy, resources will be deleted.
   */
  public const DELETE_POLICY_DELETE_POLICY_UNSPECIFIED = 'DELETE_POLICY_UNSPECIFIED';
  /**
   * Deletes resources actuated by the deployment.
   */
  public const DELETE_POLICY_DELETE = 'DELETE';
  /**
   * Abandons resources and only deletes the deployment and its metadata.
   */
  public const DELETE_POLICY_ABANDON = 'ABANDON';
  /**
   * Optional. Policy on how resources within each deployment should be handled
   * during deletion. This policy is applied globally to the deletion of all
   * deployments in this group. This corresponds to the 'delete_policy' field in
   * DeleteDeploymentRequest.
   *
   * @var string
   */
  public $deletePolicy;
  /**
   * Optional. If set to true, this option is propagated to the deletion of each
   * deployment in the group. This corresponds to the 'force' field in
   * DeleteDeploymentRequest.
   *
   * @var bool
   */
  public $force;

  /**
   * Optional. Policy on how resources within each deployment should be handled
   * during deletion. This policy is applied globally to the deletion of all
   * deployments in this group. This corresponds to the 'delete_policy' field in
   * DeleteDeploymentRequest.
   *
   * Accepted values: DELETE_POLICY_UNSPECIFIED, DELETE, ABANDON
   *
   * @param self::DELETE_POLICY_* $deletePolicy
   */
  public function setDeletePolicy($deletePolicy)
  {
    $this->deletePolicy = $deletePolicy;
  }
  /**
   * @return self::DELETE_POLICY_*
   */
  public function getDeletePolicy()
  {
    return $this->deletePolicy;
  }
  /**
   * Optional. If set to true, this option is propagated to the deletion of each
   * deployment in the group. This corresponds to the 'force' field in
   * DeleteDeploymentRequest.
   *
   * @param bool $force
   */
  public function setForce($force)
  {
    $this->force = $force;
  }
  /**
   * @return bool
   */
  public function getForce()
  {
    return $this->force;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeprovisionDeploymentGroupRequest::class, 'Google_Service_Config_DeprovisionDeploymentGroupRequest');
